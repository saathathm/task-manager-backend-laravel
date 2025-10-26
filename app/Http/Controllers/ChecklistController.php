<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ChecklistController extends Controller
{

    public function toggleChecklistItem(Request $request, $taskId, $cid)
    {
        $task = Task::findOrFail($taskId);
        $checklistItem = $task->check_lists()->where('id', $cid)->firstOrFail();
        $checklistItem->is_completed = ! (bool) $checklistItem->is_completed;
        $checklistItem->save();

        $task->refreshStatus();

        return Response::json($task->load(['assignedUsers', 'check_lists', 'attachments']));
    }
}
