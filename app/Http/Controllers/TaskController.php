<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $tasks = Task::with(['assignedUsers', 'check_lists', 'attachments'])
            ->orderBy('updated_at', 'desc');
        if ($request->only('status')) {
            $tasks->where('status', $request->status);
            return Response::json($tasks->get());
        }
        return Response::json($tasks->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'in:Low,Medium,High',
            'due_date' => 'date',
            'assigned_user_ids' => 'required|array',
            'assigned_user_ids.*' => 'exists:users,id',
            'checklists' => 'required|array',
            'checklists.*.title' => 'string|max:255',
            'attachments' => 'nullable|array',
            'attachments.*.url' => [
                'string',
                'max:255',
                'regex:/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w\.-]*)*\/?$/'
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $task = Task::create($request->only(['title', 'description', 'priority', 'due_date']));

        if ($request->has('assigned_user_ids')) {
            $task->assignedUsers()->sync($request->assigned_user_ids);
        }

        if ($request->has('checklists')) {
            foreach ($request->checklists as $item) {
                $task->check_lists()->create(['title' => $item['title']]);
            }
        }

        if ($request->has('attachments')) {
            foreach ($request->attachments as $item) {
                $task->attachments()->create(['attachment_url' => $item['url']]);
            }
        }

        $task->refreshStatus();

        return Response::json([
            'message' => 'Task created successfully'
        ]);
    }

    public function show($id)
    {
        $task = Task::with(['assignedUsers', 'check_lists', 'attachments'])->findOrFail($id);
        return Response::json($task);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'in:Low,Medium,High',
            'due_date' => 'date',
            'assigned_user_ids' => 'required|array',
            'assigned_user_ids.*' => 'exists:users,id',
            'checklists' => 'required|array',
            'checklists.*.title' => 'string|max:255',
            'attachments' => 'nullable|array',
            'attachments.*.url' => [
                'string',
                'max:255',
                'regex:/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w\.-]*)*\/?$/'
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $task = Task::with(['assignedUsers', 'check_lists', 'attachments'])->whereId($id)->first();
        $task->update($request->only(['title', 'description', 'priority', 'due_date']));

        if ($request->has('assigned_user_ids')) {
            $task->assignedUsers()->sync($request->assigned_user_ids);
        }

        if ($request->has('checklists')) {
            $existingChecklists = $task->check_lists->keyBy('id');
            $idsToKeep = [];

            foreach ($request->checklists as $checklistData) {
                $title = $checklistData['title'];
                $cid = $checklistData['id'] ?? null;

                if ($cid && $existingChecklists->has($cid)) {
                    $checklist = $existingChecklists[$cid];
                    $checklist->title = $title;
                    $checklist->save();
                    $idsToKeep[] = $cid;
                } else {
                    $newChecklist = $task->check_lists()->create(['title' => $title]);
                    $idsToKeep[] = $newChecklist->id;
                }
            }

            $task->check_lists()->whereNotIn('id', $idsToKeep)->delete();
        }

        if ($request->has('attachments')) {
            $task->attachments()->delete();
            foreach ($request->attachments as $item) {
                $task->attachments()->create(['attachment_url' => $item['url']]);
            }
        }

        $task->refreshStatus();

        return Response::json([
            'message' => 'Task updated successfully'
        ]);
    }

    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();
        return response()->json(['message' => 'Task deleted successfully.']);
    }

    public function summary()
    {
        $summary = [
            "total" => Task::count(),
            "pending" => Task::where('status', 'Pending')->count(),
            "inprogress" => Task::where('status', 'In Progress')->count(),
            "completed" => Task::where('status', 'Completed')->count(),
            "high" => Task::where('priority', 'High')->count(),
            "medium" => Task::where('priority', 'Medium')->count(),
            "low" => Task::where('priority', 'Low')->count(),
        ];
        return Response::json($summary);
    }

    public function user_summary(Request $request)
    {
        if (!$user = $request->user) {
            return Response::json(['error' => 'User not found'], 401);
        }
        $tasks = User::findOrFail($user->id)->assignedTasks()->get();
        $summary = [
            "total" => $tasks->count(),
            "pending" => $tasks->where('status', 'Pending')->count(),
            "inprogress" => $tasks->where('status', 'In Progress')->count(),
            "completed" => $tasks->where('status', 'Completed')->count(),
            "high" => $tasks->where('priority', 'High')->count(),
            "medium" => $tasks->where('priority', 'Medium')->count(),
            "low" => $tasks->where('priority', 'Low')->count(),
        ];
        return Response::json($summary);
    }

    public function userTasks(Request $request)
    {
        if (!$user = $request->user) {
            return Response::json(['error' => 'User not found'], 401);
        }
        $tasks = User::findOrFail($user->id)->assignedTasks()->with(['assignedUsers', 'check_lists', 'attachments'])->orderBy('updated_at', 'desc');
        if ($request->only('status')) {
            $tasks->where('status', $request->status);
            return Response::json($tasks->get());
        }
        return Response::json($tasks->get());
    }
}
