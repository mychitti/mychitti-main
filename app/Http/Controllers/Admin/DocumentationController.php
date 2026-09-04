<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Documentation;
use App\Models\DocumentationCategory;
use App\Models\DocumentationFile;
use App\Models\DocumentationVersion;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentationController extends Controller
{
    private const DIR = 'documentation/';

    private const IMAGE_DIR = 'documentation_images/';

    private const ALLOWED = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'ppt', 'pptx',
        'txt', 'md', 'json', 'zip', 'png', 'jpg', 'jpeg', 'gif', 'webp',
    ];

    public function index(Request $request)
    {
        $search   = $request->get('search');
        $category = $request->get('category');
        $type     = $request->get('type');

        $documents = Documentation::with('category')
            ->withCount(['files', 'versions'])
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%")
                  ->orWhere('tags', 'like', "%{$search}%");
            }))
            ->when($category, fn($q) => $q->where('category_id', $category))
            ->when($type, fn($q) => $q->where('doc_type', $type))
            ->latest('updated_at')
            ->paginate(15)
            ->appends($request->query());

        $categories = DocumentationCategory::withCount('documents')->orderBy('sort_order')->orderBy('name')->get();

        $counts = [
            'total' => Documentation::count(),
            'files' => DocumentationFile::count(),
        ];

        return view('admin-views.documentation.index', compact(
            'documents', 'categories', 'counts', 'search', 'category', 'type'
        ));
    }

    public function create()
    {
        $categories = DocumentationCategory::active()->orderBy('sort_order')->orderBy('name')->get();
        return view('admin-views.documentation.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'category_id' => 'nullable|string|max:120',
            'doc_type'    => 'required|in:editor,file',
            'version'     => 'nullable|string|max:20',
            'files.*'     => 'file|max:25600',
        ]);

        $doc = Documentation::create([
            'title'       => $request->title,
            'slug'        => $this->uniqueSlug($request->title),
            'category_id' => $this->resolveCategory($request->category_id),
            'doc_type'    => $request->doc_type,
            'summary'     => $request->summary,
            'content'     => $request->content,
            'version'     => $request->version ?: '1.0',
            'tags'        => $request->tags,
            'created_by'  => auth('admin')->id(),
            'updated_by'  => auth('admin')->id(),
        ]);

        if ($request->filled('content')) {
            $this->snapshotContent($doc, 'Initial version');
        }

        $stored = $this->storeUploads($request, $doc);

        _actionLog([
            'user_id'     => auth('admin')->id(),
            'user_type'   => 'admin',
            'action'      => 'created documentation',
            'model_type'  => 'Documentation',
            'model_id'    => $doc->id,
            'description' => 'Created document "' . $doc->title . '"',
        ]);

        Toastr::success('Document created' . ($stored ? " with {$stored} attachment(s)" : '') . '.');
        return redirect()->route('admin.documentation.show', $doc->id);
    }

    public function show($id)
    {
        $doc = Documentation::with(['category', 'author', 'editor', 'files', 'versions.author'])->findOrFail($id);

        return view('admin-views.documentation.show', compact('doc'));
    }

    public function edit($id)
    {
        $doc = Documentation::findOrFail($id);
        $categories = DocumentationCategory::active()->orderBy('sort_order')->orderBy('name')->get();
        return view('admin-views.documentation.edit', compact('doc', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $doc = Documentation::findOrFail($id);

        $request->validate([
            'title'       => 'required|string|max:255',
            'category_id' => 'nullable|string|max:120',
            'doc_type'    => 'required|in:editor,file',
            'version'     => 'nullable|string|max:20',
            'files.*'     => 'file|max:25600',
        ]);

        // Snapshot the outgoing body before it is overwritten, so every save is recoverable.
        $contentChanged = $request->content !== $doc->content;
        if ($contentChanged && filled($doc->content)) {
            $this->snapshotContent($doc, $request->version_note ?: 'Replaced by a newer edit');
        }

        $doc->update([
            'title'       => $request->title,
            'slug'        => $doc->title === $request->title ? $doc->slug : $this->uniqueSlug($request->title, $doc->id),
            'category_id' => $this->resolveCategory($request->category_id),
            'doc_type'    => $request->doc_type,
            'summary'     => $request->summary,
            'content'     => $request->content,
            'version'     => $request->version ?: $doc->version,
            'tags'        => $request->tags,
            'updated_by'  => auth('admin')->id(),
        ]);

        $this->storeUploads($request, $doc);

        Toastr::success('Document updated.');
        return redirect()->route('admin.documentation.show', $doc->id);
    }

    public function destroy($id)
    {
        $doc = Documentation::with(['files', 'versions'])->findOrFail($id);

        foreach ($doc->files as $file) {
            Helpers::delete_file(self::DIR, $file->stored_name);
        }
        foreach ($doc->versions as $version) {
            if ($version->stored_name) {
                Helpers::delete_file(self::DIR, $version->stored_name);
            }
        }

        DocumentationFile::where('documentation_id', $doc->id)->delete();
        DocumentationVersion::where('documentation_id', $doc->id)->delete();

        $title = $doc->title;
        $doc->delete();

        _actionLog([
            'user_id'     => auth('admin')->id(),
            'user_type'   => 'admin',
            'action'      => 'deleted documentation',
            'model_type'  => 'Documentation',
            'model_id'    => $id,
            'description' => 'Deleted document "' . $title . '"',
        ]);

        Toastr::success('Document deleted.');
        return redirect()->route('admin.documentation.index');
    }

    /* ------------------------------------------------------------------ files */

    public function upload_file(Request $request, $id)
    {
        $doc = Documentation::findOrFail($id);
        $request->validate(['files' => 'required', 'files.*' => 'file|max:25600']);

        $stored = $this->storeUploads($request, $doc);
        if (!$stored) {
            Toastr::error('Nothing uploaded — allowed types: ' . implode(', ', self::ALLOWED) . '.');
            return back();
        }

        Toastr::success($stored . ' file(s) uploaded.');
        return back();
    }

    public function download_file($fileId)
    {
        $file = DocumentationFile::findOrFail($fileId);
        if (!Storage::disk('public')->exists(self::DIR . $file->stored_name)) {
            Toastr::error('That file is no longer on disk.');
            return back();
        }
        return Storage::disk('public')->download(self::DIR . $file->stored_name, $file->file_name);
    }

    /**
     * Same bytes as download_file, but served inline so the browser renders it in place
     * instead of saving it. Used by the file preview pane on the document page.
     */
    public function view_file($fileId)
    {
        $file = DocumentationFile::findOrFail($fileId);
        $path = self::DIR . $file->stored_name;

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->response($path, $file->file_name, [
            'Content-Type'        => $file->mime ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . str_replace('"', '', $file->file_name) . '"',
        ]);
    }

    public function delete_file($fileId)
    {
        $file = DocumentationFile::findOrFail($fileId);
        $docId = $file->documentation_id;

        Helpers::delete_file(self::DIR, $file->stored_name);
        $file->delete();

        Toastr::success('Attachment removed.');
        return redirect()->route('admin.documentation.show', $docId);
    }

    /* --------------------------------------------------------------- versions */

    public function download_version($versionId)
    {
        $version = DocumentationVersion::findOrFail($versionId);
        if (!$version->stored_name || !Storage::disk('public')->exists(self::DIR . $version->stored_name)) {
            Toastr::error('That version has no downloadable file.');
            return back();
        }
        return Storage::disk('public')->download(self::DIR . $version->stored_name, $version->file_name);
    }

    public function restore_version($versionId)
    {
        $version = DocumentationVersion::findOrFail($versionId);
        $doc = Documentation::findOrFail($version->documentation_id);

        if ($version->source !== 'content') {
            Toastr::error('Only written versions can be restored. Download the file version instead.');
            return back();
        }

        // Restoring is itself an edit, so the body being replaced is snapshotted first.
        $this->snapshotContent($doc, 'Replaced by restore of v' . $version->version);

        $doc->update([
            'content'    => $version->content,
            'version'    => $doc->nextVersion(),
            'updated_by' => auth('admin')->id(),
        ]);

        Toastr::success('Restored v' . $version->version . ' as v' . $doc->version . '.');
        return redirect()->route('admin.documentation.show', $doc->id);
    }

    /**
     * Straight from the library screen: drop in a Word/PDF and it becomes a document, rather
     * than making someone open the editor form just to attach a file.
     */
    public function upload_document(Request $request)
    {
        $request->validate([
            'files'       => 'required',
            'files.*'     => 'file|max:25600',
            'category_id' => 'nullable|string|max:120',
        ]);

        $uploads = array_values(array_filter((array) $request->file('files')));
        if (!$uploads) {
            Toastr::error('No file received.');
            return back();
        }

        // An untitled upload takes its name from the first file, minus the extension.
        $title = trim((string) $request->title)
            ?: pathinfo($uploads[0]->getClientOriginalName(), PATHINFO_FILENAME);

        $doc = Documentation::create([
            'title'       => $title,
            'slug'        => $this->uniqueSlug($title),
            'category_id' => $this->resolveCategory($request->category_id),
            'doc_type'    => 'file',
            'summary'     => $request->summary,
            'version'     => $request->version ?: '1.0',
            'tags'        => $request->tags,
            'created_by'  => auth('admin')->id(),
            'updated_by'  => auth('admin')->id(),
        ]);

        $stored = $this->storeUploads($request, $doc);

        if (!$stored) {
            $doc->delete();
            Toastr::error('Unsupported file type — allowed: ' . implode(', ', self::ALLOWED) . '.');
            return back();
        }

        _actionLog([
            'user_id'     => auth('admin')->id(),
            'user_type'   => 'admin',
            'action'      => 'uploaded documentation',
            'model_type'  => 'Documentation',
            'model_id'    => $doc->id,
            'description' => 'Uploaded document "' . $doc->title . '" (' . $stored . ' file(s))',
        ]);

        Toastr::success('Uploaded "' . $doc->title . '" with ' . $stored . ' file(s).');
        return redirect()->route('admin.documentation.show', $doc->id);
    }

    /**
     * CKEditor's SimpleUploadAdapter endpoint for images dropped into a document body.
     */
    public function upload_image(Request $request)
    {
        $request->validate(['upload' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240']);

        $upload = $request->file('upload');
        $extension = strtolower($upload->getClientOriginalExtension()) ?: 'png';
        $storedName = Str::lower(Str::random(40)) . '.' . $extension;
        Helpers::upload(self::IMAGE_DIR, $extension, $upload, $storedName);

        return response()->json([
            'uploaded' => true,
            'url'      => asset('storage/app/public/' . self::IMAGE_DIR . $storedName),
        ]);
    }

    /* ---------------------------------------------------------------- shared */

    /**
     * Saves every accepted upload on the request and returns how many landed.
     */
    private function storeUploads(Request $request, Documentation $doc): int
    {
        if (!$request->hasFile('files')) {
            return 0;
        }

        $count = 0;
        foreach ((array) $request->file('files') as $upload) {
            if ($upload && $this->storeUpload($upload, $doc)) {
                $count++;
            }
        }
        return $count;
    }

    private function storeUpload($upload, Documentation $doc): ?DocumentationFile
    {
        $extension = strtolower($upload->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED, true)) {
            return null;
        }

        // Unguessable stored name: the public disk serves by path, so the filename is the only
        // thing standing between an internal SRS and anyone who tries the storage URL directly.
        $storedName = Str::lower(Str::random(40)) . '.' . $extension;
        Helpers::upload(self::DIR, $extension, $upload, $storedName);

        $file = DocumentationFile::create([
            'documentation_id' => $doc->id,
            'file_name'        => $upload->getClientOriginalName(),
            'stored_name'      => $storedName,
            'extension'        => $extension,
            'mime'             => $upload->getClientMimeType(),
            'size'             => $upload->getSize(),
            'uploaded_by'      => auth('admin')->id(),
        ]);

        DocumentationVersion::create([
            'documentation_id' => $doc->id,
            'version'          => $doc->version,
            'source'           => 'file',
            'file_name'        => $file->file_name,
            'stored_name'      => $storedName,
            'extension'        => $extension,
            'size'             => (int) $upload->getSize(),
            'note'             => 'Uploaded ' . $file->file_name,
            'created_by'       => auth('admin')->id(),
            'created_at'       => now(),
        ]);

        return $file;
    }

    private function snapshotContent(Documentation $doc, ?string $note): void
    {
        DocumentationVersion::create([
            'documentation_id' => $doc->id,
            'version'          => $doc->version,
            'source'           => 'content',
            'content'          => $doc->content,
            'note'             => $note,
            'created_by'       => auth('admin')->id(),
            'created_at'       => now(),
        ]);
    }

    /**
     * The category box is a Select2 tag input: it posts an existing category id, or the name the
     * admin just typed. A typed name reuses a category of that name before creating a new one, so
     * "SRS" typed twice does not end up as two categories.
     */
    private function resolveCategory($value): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (ctype_digit($value) && DocumentationCategory::whereKey($value)->exists()) {
            return (int) $value;
        }

        $existing = DocumentationCategory::whereRaw('LOWER(name) = ?', [mb_strtolower($value)])->first();
        if ($existing) {
            return $existing->id;
        }

        return DocumentationCategory::create([
            'name'       => $value,
            'slug'       => Str::slug($value) . '-' . Str::lower(Str::random(4)),
            'color'      => '#6c5ce7',
            'sort_order' => (int) DocumentationCategory::max('sort_order') + 1,
            'status'     => 1,
        ])->id;
    }

    private function uniqueSlug(string $title, $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'document';
        $slug = $base;
        $i = 2;

        while (Documentation::where('slug', $slug)->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
