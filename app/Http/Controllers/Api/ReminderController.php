<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateReminder;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReminderRequest;
use App\Http\Resources\ReminderResource;
use App\Models\Reminder;
use App\Models\Todo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReminderController extends Controller
{
    public function index(Todo $todo): AnonymousResourceCollection
    {
        $this->authorize('view', $todo);

        return ReminderResource::collection($todo->reminders()->get());
    }

    public function store(StoreReminderRequest $request, Todo $todo, CreateReminder $action): JsonResponse
    {
        $reminder = $action->handle(
            $todo,
            $request->user(),
            $request->remindedAt(),
            $request->type(),
        );

        return response()->json(['reminder' => new ReminderResource($reminder)], 201);
    }

    public function destroy(Reminder $reminder): JsonResponse
    {
        $this->authorize('delete', $reminder);
        $reminder->delete();

        return response()->json(null, 204);
    }

    public function destroyScoped(Todo $todo, Reminder $reminder): JsonResponse
    {
        return $this->destroy($reminder);
    }
}
