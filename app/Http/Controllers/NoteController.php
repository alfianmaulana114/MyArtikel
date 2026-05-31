<?php

namespace App\Http\Controllers;

use App\Http\Requests\NoteRequest;
use App\Models\Article;
use App\Models\Note;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NoteController extends Controller
{
    /**
     * Display a listing of the resource with advanced filtering.
     */
    public function index(Request $request)
    {
        return view('notes.index');
    }

    public function data(Request $request)
    {
        try {
            $query = Note::with(['article', 'user'])
                ->where('user_id', Auth::id());

            // Apply search filter
            if ($request->filled('search')) {
                $query->search($request->input('search'));
            }

            // Apply category filter
            if ($request->filled('category')) {
                $query->byCategory($request->input('category'));
            }

            // Apply type filter
            if ($request->filled('type')) {
                $query->byType($request->input('type'));
            }

            // Apply tags filter
            if ($request->filled('tags')) {
                $tags = is_array($request->input('tags')) ? $request->input('tags') : [$request->input('tags')];
                $query->byTags($tags);
            }

            // Apply article filter
            if ($request->filled('article_id')) {
                $query->where('article_id', $request->input('article_id'));
            }

            // Apply sync status filter
            if ($request->filled('sync_status')) {
                $query->bySyncStatus($request->input('sync_status'));
            }

            // Apply privacy filter
            if ($request->filled('is_private')) {
                $query->where('is_private', $request->boolean('is_private'));
            }

            // Apply anchoring filter
            if ($request->filled('anchored')) {
                if ($request->boolean('anchored')) {
                    $query->whereNotNull('paragraph_index');
                } else {
                    $query->whereNull('paragraph_index');
                }
            }

            // Apply sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $allowedSortFields = ['created_at', 'updated_at', 'title', 'category', 'type'];

            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->input('per_page', 20);
            $notes = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $notes->items(),
                'pagination' => [
                    'current_page' => $notes->currentPage(),
                    'last_page' => $notes->lastPage(),
                    'per_page' => $notes->perPage(),
                    'total' => $notes->total(),
                ],
                'filters' => [
                    'categories' => $this->getUserCategories(),
                    'types' => [Note::TYPE_PERSONAL, Note::TYPE_RESEARCH, Note::TYPE_DRAFT],
                    'tags' => $this->getUserTags(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notes',
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Return view for creating notes (if using server-side rendering)
        return view('notes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(NoteRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $noteData = $request->validated();
            $noteData['user_id'] = Auth::id();
            $noteData['device_id'] = $request->header('X-Device-ID', 'web');
            $noteData['sync_status'] = Note::SYNC_SYNCED;

            // Handle rich text content
            if ($request->boolean('is_rich_text') && $request->has('content_json')) {
                $noteData['content'] = $this->extractTextFromRichContent($request->input('content_json'));
            }

            $note = Note::create($noteData);

            // Set search vector for better search performance
            $note->setSearchVector();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Note created successfully',
                'data' => $note->load(['article', 'user']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create note',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $note = Note::with(['article', 'user'])
                ->where('user_id', Auth::id())
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $note,
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Note not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch note',
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Return view for editing notes (if using server-side rendering)
        $note = Note::where('user_id', Auth::id())->findOrFail($id);

        return view('notes.edit', compact('note'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(NoteRequest $request, string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $note = Note::where('user_id', Auth::id())->findOrFail($id);

            $noteData = $request->validated();
            $noteData['device_id'] = $request->header('X-Device-ID', 'web');
            $noteData['sync_status'] = Note::SYNC_PENDING; // Mark for sync

            // Handle rich text content
            if ($request->boolean('is_rich_text') && $request->has('content_json')) {
                $noteData['content'] = $this->extractTextFromRichContent($request->input('content_json'));
            }

            $note->update($noteData);

            // Update search vector
            $note->setSearchVector();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Note updated successfully',
                'data' => $note->load(['article', 'user']),
            ]);

        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Note not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update note',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $note = Note::where('user_id', Auth::id())->findOrFail($id);
            $note->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Note deleted successfully',
            ]);

        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Note not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete note',
            ], 500);
        }
    }

    /**
     * Sync notes across devices.
     */
    public function sync(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'notes' => ['required', 'array'],
                'notes.*.id' => ['required', 'string'],
                'notes.*.title' => ['nullable', 'string', 'max:255'],
                'notes.*.content' => ['required_without:notes.*.content_json', 'string', 'max:10000'],
                'notes.*.content_json' => ['required_without:notes.*.content', 'array'],
                'notes.*.article_id' => ['required', 'integer', 'exists:articles,id'],
                'notes.*.last_modified' => ['required', 'date'],
                'device_id' => ['required', 'string', 'max:255'],
            ]);

            $syncedNotes = [];
            $conflicts = [];
            $deviceId = $request->input('device_id');

            DB::beginTransaction();

            foreach ($request->input('notes') as $syncNote) {
                $existingNote = Note::where('user_id', Auth::id())
                    ->where('id', $syncNote['id'])
                    ->lockForUpdate()
                    ->first();

                if ($existingNote) {
                    // Check for conflicts
                    if ($existingNote->updated_at > $syncNote['last_modified']) {
                        $conflicts[] = [
                            'local' => $existingNote,
                            'remote' => $syncNote,
                        ];

                        continue;
                    }

                    // Update existing note
                    $existingNote->update([
                        'title' => $syncNote['title'] ?? $existingNote->title,
                        'content' => $syncNote['content'] ?? $this->extractTextFromRichContent($syncNote['content_json']),
                        'content_json' => $syncNote['content_json'] ?? null,
                        'device_id' => $deviceId,
                        'sync_status' => Note::SYNC_SYNCED,
                        'last_synced_at' => now(),
                    ]);

                    $syncedNotes[] = $existingNote;
                } else {
                    // Create new note
                    $newNote = Note::create([
                        'id' => $syncNote['id'],
                        'title' => $syncNote['title'] ?? null,
                        'content' => $syncNote['content'] ?? $this->extractTextFromRichContent($syncNote['content_json']),
                        'content_json' => $syncNote['content_json'] ?? null,
                        'user_id' => Auth::id(),
                        'article_id' => $syncNote['article_id'],
                        'device_id' => $deviceId,
                        'sync_status' => Note::SYNC_SYNCED,
                        'last_synced_at' => now(),
                    ]);

                    $syncedNotes[] = $newNote;
                }
            }

            // Get notes that need to be synced to client
            $pendingSyncNotes = Note::where('user_id', Auth::id())
                ->where('device_id', '!=', $deviceId)
                ->where('sync_status', Note::SYNC_PENDING)
                ->get();

            // Mark them as synced
            Note::where('user_id', Auth::id())
                ->where('device_id', '!=', $deviceId)
                ->where('sync_status', Note::SYNC_PENDING)
                ->update([
                    'sync_status' => Note::SYNC_SYNCED,
                    'last_synced_at' => now(),
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sync completed',
                'data' => [
                    'synced' => $syncedNotes,
                    'pending' => $pendingSyncNotes,
                    'conflicts' => $conflicts,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Sync failed',
            ], 500);
        }
    }

    /**
     * Get notes that need sync for a specific device.
     */
    public function getPendingSync(Request $request): JsonResponse
    {
        try {
            $deviceId = $request->header('X-Device-ID', 'web');

            $pendingNotes = Note::where('user_id', Auth::id())
                ->where('device_id', '!=', $deviceId)
                ->where('sync_status', Note::SYNC_PENDING)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $pendingNotes,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get pending sync notes',
            ], 500);
        }
    }

    /**
     * Search notes with advanced filtering.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => ['required', 'string', 'min:2', 'max:255'],
                'filters' => ['nullable', 'array'],
                'filters.category' => ['nullable', 'string', 'max:100'],
                'filters.type' => ['nullable', 'in:'.implode(',', [Note::TYPE_PERSONAL, Note::TYPE_RESEARCH, Note::TYPE_DRAFT])],
                'filters.tags' => ['nullable', 'array', 'max:10'],
                'filters.article_id' => ['nullable', 'integer', 'exists:articles,id'],
            ]);

            $query = $request->input('query');
            $filters = $request->input('filters', []);

            $notes = Note::with(['article', 'user'])
                ->where('user_id', Auth::id())
                ->search($query);

            // Apply additional filters
            if (isset($filters['category'])) {
                $notes->byCategory($filters['category']);
            }

            if (isset($filters['type'])) {
                $notes->byType($filters['type']);
            }

            if (isset($filters['tags'])) {
                $notes->byTags($filters['tags']);
            }

            if (isset($filters['article_id'])) {
                $notes->where('article_id', $filters['article_id']);
            }

            $results = $notes->orderBy('updated_at', 'desc')
                ->limit(50)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $results,
                'search_info' => [
                    'query' => $query,
                    'total_results' => $results->count(),
                    'filters_applied' => $filters,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
            ], 500);
        }
    }

    /**
     * Get user-specific categories.
     */
    private function getUserCategories(): array
    {
        return Note::where('user_id', Auth::id())
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->toArray();
    }

    /**
     * Get user-specific tags.
     */
    private function getUserTags(): array
    {
        $tags = Note::where('user_id', Auth::id())
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();

        return $tags;
    }

    /**
     * Extract plain text from rich content for search indexing.
     */
    private function extractTextFromRichContent($contentJson): string
    {
        if (! is_array($contentJson)) {
            return '';
        }

        $text = '';

        // Handle Quill.js format
        if (isset($contentJson['ops']) && is_array($contentJson['ops'])) {
            foreach ($contentJson['ops'] as $op) {
                if (isset($op['insert'])) {
                    if (is_string($op['insert'])) {
                        $text .= $op['insert'].' ';
                    }
                }
            }
        }

        return trim($text);
    }
}
