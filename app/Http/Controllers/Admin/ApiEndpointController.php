<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Exports\ApiEndpointExport;
use App\Http\Controllers\Controller;
use App\Imports\ApiEndpointImport;
use App\Models\ApiEndpoint;
use App\Models\ApiProject;
use App\Services\PostmanCollectionParser;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ApiEndpointController extends Controller
{
    private const IMAGE_DIR = 'api_endpoints/';

    /* --------------------------------------------------------------- projects */

    public function index(Request $request)
    {
        $search = $request->get('search');

        $projects = ApiProject::withCount('endpoints')
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('base_url', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->get();

        $counts = [
            'projects'  => ApiProject::count(),
            'endpoints' => ApiEndpoint::count(),
        ];

        return view('admin-views.api-endpoints.index', compact('projects', 'counts', 'search'));
    }

    public function store_project(Request $request)
    {
        $request->validate(['name' => 'required|string|max:150']);

        $project = ApiProject::create([
            'name'        => $request->name,
            'slug'        => $this->uniqueSlug($request->name),
            'base_url'    => $request->base_url,
            'version'     => $request->version,
            'color'       => $request->color ?: '#1a73e8',
            'description' => $request->description,
            'status'      => 1,
            'created_by'  => auth('admin')->id(),
        ]);

        Toastr::success('Project created.');
        return redirect()->route('admin.api-endpoints.show', $project->id);
    }

    public function update_project(Request $request, $id)
    {
        $project = ApiProject::findOrFail($id);
        $request->validate(['name' => 'required|string|max:150']);

        $project->update([
            'name'        => $request->name,
            'slug'        => $project->name === $request->name ? $project->slug : $this->uniqueSlug($request->name, $project->id),
            'base_url'    => $request->base_url,
            'version'     => $request->version,
            'color'       => $request->color ?: $project->color,
            'description' => $request->description,
            'status'      => $request->boolean('status') ? 1 : 0,
        ]);

        Toastr::success('Project updated.');
        return back();
    }

    public function delete_project($id)
    {
        $project = ApiProject::with('endpoints')->findOrFail($id);

        foreach ($project->endpoints as $endpoint) {
            $this->deleteImages($endpoint);
        }
        ApiEndpoint::where('project_id', $project->id)->delete();

        $name = $project->name;
        $project->delete();

        _actionLog([
            'user_id'     => auth('admin')->id(),
            'user_type'   => 'admin',
            'action'      => 'deleted api project',
            'model_type'  => 'ApiProject',
            'model_id'    => $id,
            'description' => 'Deleted API project "' . $name . '" and its endpoints',
        ]);

        Toastr::success('Project deleted.');
        return redirect()->route('admin.api-endpoints.index');
    }

    public function show(Request $request, $id)
    {
        $project = ApiProject::findOrFail($id);

        $search = $request->get('search');
        $method = $request->get('method');

        $endpoints = $project->endpoints()
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('endpoint', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('usage_note', 'like', "%{$search}%")
                  ->orWhere('folder', 'like', "%{$search}%");
            }))
            ->when($method, fn($q, $v) => $q->where('method', $v))
            ->get();

        $projects = ApiProject::orderBy('name')->get();

        return view('admin-views.api-endpoints.show', compact('project', 'projects', 'endpoints', 'search', 'method'));
    }

    /**
     * Every endpoint across every project — the "where is this URL used?" screen.
     */
    public function all(Request $request)
    {
        $search  = $request->get('search');
        $method  = $request->get('method');
        $project = $request->get('project');

        $endpoints = ApiEndpoint::with('project')
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('endpoint', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('usage_note', 'like', "%{$search}%");
            }))
            ->when($method, fn($q, $v) => $q->where('method', $v))
            ->when($project, fn($q, $v) => $q->where('project_id', $v))
            ->orderBy('project_id')->orderBy('folder')->orderBy('sort_order')
            ->paginate(30)
            ->appends($request->query());

        $projects = ApiProject::orderBy('name')->get();

        return view('admin-views.api-endpoints.all', compact('endpoints', 'projects', 'search', 'method', 'project'));
    }

    /* -------------------------------------------------------------- endpoints */

    public function store_endpoint(Request $request, $projectId)
    {
        $project = ApiProject::findOrFail($projectId);
        $request->validate([
            'method'   => 'required|string|max:10',
            'endpoint' => 'required|string|max:500',
            'images.*' => 'image|max:10240',
        ]);

        $endpoint = ApiEndpoint::create($this->payload($request) + [
            'project_id' => $project->id,
            'sort_order' => (int) ApiEndpoint::where('project_id', $project->id)->max('sort_order') + 1,
        ]);

        $this->storeImages($request, $endpoint);

        Toastr::success('Endpoint added.');
        return back();
    }

    public function update_endpoint(Request $request, $id)
    {
        $endpoint = ApiEndpoint::findOrFail($id);
        $request->validate([
            'method'   => 'required|string|max:10',
            'endpoint' => 'required|string|max:500',
            'images.*' => 'image|max:10240',
        ]);

        $endpoint->update($this->payload($request));
        $this->storeImages($request, $endpoint);

        Toastr::success('Endpoint updated.');
        return back();
    }

    public function delete_endpoint($id)
    {
        $endpoint = ApiEndpoint::findOrFail($id);
        $projectId = $endpoint->project_id;

        $this->deleteImages($endpoint);
        $endpoint->delete();

        Toastr::success('Endpoint removed.');
        return redirect()->route('admin.api-endpoints.show', $projectId);
    }

    public function delete_image(Request $request, $id)
    {
        $endpoint = ApiEndpoint::findOrFail($id);
        $stored = $request->input('stored_name');

        $images = array_values(array_filter(
            $endpoint->image_list,
            fn($img) => ($img['stored_name'] ?? null) !== $stored
        ));

        if (count($images) !== count($endpoint->image_list)) {
            Helpers::delete_file(self::IMAGE_DIR, $stored);
            $endpoint->update(['images' => $images ? json_encode($images) : null]);
            Toastr::success('Image removed.');
        }

        return back();
    }

    /* ------------------------------------------------------------------ excel */

    public function export($projectId)
    {
        $project = ApiProject::findOrFail($projectId);
        $name = Str::slug($project->name) . '-endpoints-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new ApiEndpointExport($project->endpoints()->get()), $name);
    }

    public function import(Request $request, $projectId)
    {
        $project = ApiProject::findOrFail($projectId);
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv']);

        $import = new ApiEndpointImport($project->id, $request->boolean('replace'));
        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            Toastr::error('Import failed: ' . $e->getMessage());
            return back();
        }

        Toastr::success($import->imported . ' endpoint(s) imported.');
        return redirect()->route('admin.api-endpoints.show', $project->id);
    }

    /**
     * A Postman collection can either fill an existing project or create one named after itself.
     */
    public function import_postman(Request $request)
    {
        $request->validate(['file' => 'required|file|max:25600']);

        $upload = $request->file('file');
        if (strtolower($upload->getClientOriginalExtension()) !== 'json') {
            Toastr::error('Export the collection from Postman as a .json file and upload that.');
            return back();
        }

        try {
            $parsed = PostmanCollectionParser::parse(file_get_contents($upload->getRealPath()));
        } catch (\Throwable $e) {
            Toastr::error($e->getMessage());
            return back();
        }

        if (empty($parsed['endpoints'])) {
            Toastr::warning('That collection has no requests in it.');
            return back();
        }

        $project = $request->filled('project_id')
            ? ApiProject::findOrFail($request->project_id)
            : ApiProject::create([
                'name'       => $parsed['name'],
                'slug'       => $this->uniqueSlug($parsed['name']),
                'color'      => '#ff6c37',
                'status'     => 1,
                'created_by' => auth('admin')->id(),
            ]);

        if ($request->boolean('replace')) {
            foreach ($project->endpoints as $endpoint) {
                $this->deleteImages($endpoint);
            }
            ApiEndpoint::where('project_id', $project->id)->delete();
        }

        $sort = (int) ApiEndpoint::where('project_id', $project->id)->max('sort_order');
        foreach ($parsed['endpoints'] as $row) {
            ApiEndpoint::create($row + [
                'project_id' => $project->id,
                'sort_order' => ++$sort,
            ]);
        }

        _actionLog([
            'user_id'     => auth('admin')->id(),
            'user_type'   => 'admin',
            'action'      => 'imported postman collection',
            'model_type'  => 'ApiProject',
            'model_id'    => $project->id,
            'description' => 'Imported ' . count($parsed['endpoints']) . ' endpoints into "' . $project->name . '"',
        ]);

        Toastr::success(count($parsed['endpoints']) . ' endpoint(s) imported into "' . $project->name . '".');
        return redirect()->route('admin.api-endpoints.show', $project->id);
    }

    /* ----------------------------------------------------------------- shared */

    private function payload(Request $request): array
    {
        return [
            'folder'          => $request->folder,
            'name'            => $request->name,
            'method'          => Str::upper($request->method),
            'endpoint'        => $request->endpoint,
            'description'     => $request->description,
            'params'          => ApiEndpoint::encodeRows($request->param_key, $request->param_value, $request->param_note),
            'headers'         => ApiEndpoint::encodeRows($request->header_key, $request->header_value, $request->header_note),
            'request_body'    => $request->request_body,
            'response_sample' => $request->response_sample,
            'usage_note'      => $request->usage_note,
        ];
    }

    private function storeImages(Request $request, ApiEndpoint $endpoint): void
    {
        if (!$request->hasFile('images')) {
            return;
        }

        $images = $endpoint->image_list;
        foreach ((array) $request->file('images') as $upload) {
            if (!$upload) {
                continue;
            }
            $extension = strtolower($upload->getClientOriginalExtension()) ?: 'png';
            $storedName = Str::lower(Str::random(40)) . '.' . $extension;
            Helpers::upload(self::IMAGE_DIR, $extension, $upload, $storedName);

            $images[] = [
                'stored_name' => $storedName,
                'file_name'   => $upload->getClientOriginalName(),
            ];
        }

        $endpoint->update(['images' => $images ? json_encode($images) : null]);
    }

    private function deleteImages(ApiEndpoint $endpoint): void
    {
        foreach ($endpoint->image_list as $image) {
            if (!empty($image['stored_name'])) {
                Helpers::delete_file(self::IMAGE_DIR, $image['stored_name']);
            }
        }
    }

    private function uniqueSlug(string $name, $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'project';
        $slug = $base;
        $i = 2;

        while (ApiProject::where('slug', $slug)->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
