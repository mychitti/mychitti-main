<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        $fetchError = null;
        try {
            $response = Http::timeout(15)->get("{$this->ragUrl}/documents");
            if ($response->ok()) {
                $body = $response->json();
                // Handle both { "documents": [...] } and bare array responses
                if (isset($body['documents'])) {
                    $documents = $body['documents'];
                } elseif (is_array($body) && array_is_list($body)) {
                    $documents = $body;
                } else {
                    $documents = [];
                    Log::warning('RAG /documents unexpected format', ['body' => $body]);
                    $fetchError = 'Unexpected response format from RAG service.';
                }
            } else {
                $documents = [];
                $fetchError = 'RAG service returned HTTP ' . $response->status() . ': ' . $response->body();
                Log::error('RAG /documents error', ['status' => $response->status(), 'body' => $response->body()]);
            }
        } catch (\Exception $e) {
            $documents = [];
            $fetchError = 'Could not reach RAG service: ' . $e->getMessage();
            Log::error('RAG /documents connection failed', ['error' => $e->getMessage()]);
        }

        return view('admin-views.rag.index', compact('documents', 'fetchError'));
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

            $errorDetail = $response->json('message') ?? $response->json('error') ?? $response->body();
            return back()->with('error', 'RAG service error (' . $response->status() . '): ' . $errorDetail)->withInput();
        } catch (\Exception $e) {
            return back()->with('error', 'Connection failed: ' . $e->getMessage())->withInput();
        }
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
