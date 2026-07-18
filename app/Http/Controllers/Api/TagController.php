<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateTag;
use App\Actions\DeleteTag;
use App\Actions\UpdateTag;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TagController extends Controller
{
    public function index(Workspace $workspace): AnonymousResourceCollection
    {
        return TagResource::collection($workspace->tags()->withCount('todos')->get());
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
}
