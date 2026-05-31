<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectGraphService;
use Illuminate\Support\Facades\Auth;

class ProjectGraphController extends Controller
{
    private ProjectGraphService $graphService;

    public function __construct(ProjectGraphService $graphService)
    {
        $this->graphService = $graphService;
    }

    public function show(string $id)
    {
        $user = Auth::user();
        $project = Project::where('user_id', $user->id)->findOrFail($id);

        return view('projects.graph', [
            'project' => $project,
        ]);
    }

    public function data(string $id)
    {
        $user = Auth::user();
        $project = Project::where('user_id', $user->id)->findOrFail($id);

        $graph = $this->graphService->buildGraph($project);

        return response()->json([
            'success' => true,
            'data' => $graph,
        ]);
    }
}
