<?php

namespace App\Http\Controllers;

use App\Actions\CreateTag;
use App\Actions\DeleteTag;
use App\Actions\UpdateTag;
use App\Http\Requests\AttachTagRequest;
use App\Http\Requests\DeleteTagRequest;
use App\Http\Requests\DetachTagRequest;
use App\Http\Requests\StoreTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);
        $tags = $workspace->tags()
            ->with('workspace')
            ->withCount('todos')
            ->orderBy('name')
            ->limit(Tag::MAX_PER_WORKSPACE)
            ->get();

        return response()->json(['tags' => TagResource::collection($tags)]);
    }

    public function store(StoreTagRequest $request, Workspace $workspace, CreateTag $action): JsonResponse
    {
        $tag = $action->handle($workspace, $request->name());

        return response()->json(['tag' => new TagResource($tag)], 201);
    }

    public function update(
        StoreTagRequest $request,
        Workspace $workspace,
        Tag $tag,
        UpdateTag $action,
    ): JsonResponse {
        $tag = $action->handle($tag, $request->name());

        return response()->json(['tag' => new TagResource($tag)]);
    }

    public function destroy(
        DeleteTagRequest $request,
        Workspace $workspace,
        Tag $tag,
        DeleteTag $action,
    ): JsonResponse {
        $action->handle($tag);

        return response()->json(null, 204);
    }

    public function attach(
        AttachTagRequest $request,
        Workspace $workspace,
        Todo $todo,
    ): JsonResponse {
        $todo->tags()->syncWithoutDetachingOrFail([$request->tagId()]);

        return response()->json(null, 204);
    }

    public function detach(
        DetachTagRequest $request,
        Workspace $workspace,
        Todo $todo,
        Tag $tag,
    ): JsonResponse {
        $todo->tags()->detachOrFail($tag->id);

        return response()->json(null, 204);
    }
}
