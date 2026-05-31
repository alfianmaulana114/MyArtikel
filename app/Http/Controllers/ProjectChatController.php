<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProjectChatController extends Controller
{
    private ProjectChatService $chatService;

    public function __construct(ProjectChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Get chat history for a project.
     */
    public function history(string $projectId): JsonResponse
    {
        try {
            $user = Auth::user();
            $project = Project::where('user_id', $user->id)->findOrFail($projectId);

            $history = $this->chatService->getHistory($project->id, $user->id);

            return response()->json([
                'success' => true,
                'data' => $history,
            ]);
        } catch (\Exception $e) {
            Log::error('Project chat history failed', [
                'project_id' => $projectId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to load chat history.',
            ], 500);
        }
    }

    /**
     * Send a chat message and get AI response.
     */
    public function send(Request $request, string $projectId): JsonResponse
    {
        try {
            $user = Auth::user();
            $project = Project::where('user_id', $user->id)->findOrFail($projectId);

            $request->validate([
                'message' => 'required|string|max:2000',
            ]);

            $userMessage = $request->input('message');

            // Save user message
            $this->chatService->saveMessage($project->id, $user->id, 'user', $userMessage);

            // Retrieve relevant articles (token-free PHP LIKE search)
            $contextArticles = $this->chatService->retrieveRelevantArticles($project, $userMessage);

            // Get recent history for continuity
            $history = $this->chatService->getHistory($project->id, $user->id, 6);

            // Generate AI response
            $response = $this->chatService->generateResponse($project, $userMessage, $contextArticles, $history);

            // Save assistant message
            $this->chatService->saveMessage(
                $project->id,
                $user->id,
                'assistant',
                $response['content'],
                $response['context_articles']
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'content' => $response['content'],
                    'context_articles' => $response['context_articles'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Project chat send failed', [
                'project_id' => $projectId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate response. Please try again.',
            ], 500);
        }
    }
}
