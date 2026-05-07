<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RagDocumentController extends Controller
{
    private string $ragUrl;

    public function __construct()
    {
        $base = rtrim(config('services.ai_server.url', ''), '/');
        $this->ragUrl = $base . '/rag';
    }

    public function index()
    {
        try {
            $response = Http::timeout(10)->get("{$this->ragUrl}/documents");
            $documents = $response->ok() ? ($response->json()['documents'] ?? []) : [];
        } catch (\Exception $e) {
            $documents = [];
        }

        return view('admin-views.rag.index', compact('documents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'content'  => 'required|string',
            'category' => 'nullable|string|max:100',
            'lang'     => 'nullable|string|in:en,indic',
        ]);

        try {
            $response = Http::timeout(30)->post("{$this->ragUrl}/ingest", [
                'title'    => $request->title,
                'content'  => $request->content,
                'category' => $request->category ?: null,
                'lang'     => $request->lang ?: 'en',
            ]);

            if ($response->ok()) {
                return back()->with('success', 'Document added to knowledge base successfully.');
            }
        } catch (\Exception $e) {
        }

        return back()->with('error', 'Failed to add document. Please try again.')->withInput();
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'content'  => 'required|string',
            'category' => 'nullable|string|max:100',
            'lang'     => 'nullable|string|in:en,indic',
        ]);

        try {
            $response = Http::timeout(30)->put("{$this->ragUrl}/documents/{$id}", [
                'title'    => $request->title,
                'content'  => $request->content,
                'category' => $request->category ?: null,
                'lang'     => $request->lang ?: 'en',
            ]);

            if ($response->ok()) {
                return back()->with('success', 'Document updated successfully.');
            }
        } catch (\Exception $e) {
        }

        return back()->with('error', 'Failed to update document. Please try again.');
    }

    public function destroy(int $id)
    {
        try {
            Http::timeout(10)->delete("{$this->ragUrl}/documents/{$id}");
        } catch (\Exception $e) {
        }

        return back()->with('success', 'Document removed from knowledge base.');
    }
}
