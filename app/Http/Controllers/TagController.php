<?php

namespace App\Http\Controllers;

use App\Actions\CreateTag;
use App\Actions\DeleteTag;
use App\Actions\UpdateTag;
use App\Http\Requests\StoreTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Workspace $workspace): JsonResponse
    {
        $tags = $workspace->tags()->withCount('todos')->get();

        return response()->json(['tags' => TagResource::collection($tags)]);
    }

    public function store(StoreTagRequest $request, Workspace $workspace, CreateTag $action): JsonResponse
    {
        $tag = $action->handle($workspace, $request->name);

        return response()->json(['tag' => new TagResource($tag)], 201);
    }

    public function update(StoreTagRequest $request, Tag $tag, UpdateTag $action): JsonResponse
    {
        $tag = $action->handle($tag, $request->name);

        return response()->json(['tag' => new TagResource($tag)]);
    }

    public function destroy(Tag $tag, DeleteTag $action): JsonResponse
    {
        $action->handle($tag);

        return response()->json(null, 204);
    }

    public function attach(Request $request, Todo $todo): JsonResponse
    {
        $request->validate(['tag_id' => 'required|uuid|exists:tags,id']);
        $todo->tags()->syncWithoutDetaching([$request->tag_id]);

        return response()->json(null, 204);
    }

    public function detach(Todo $todo, Tag $tag): JsonResponse
    {
        $todo->tags()->detach($tag->id);

        return response()->json(null, 204);
    }
}
