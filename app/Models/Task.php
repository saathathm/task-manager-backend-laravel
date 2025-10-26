<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory;
    protected $fillable = ['title', 'description', 'priority', 'due_date', 'status'];

    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'task_assignments');
    }

    public function check_lists()
    {
        return $this->hasMany(TaskCheckList::class);
    }

    public function attachments()
    {
        return $this->hasMany(TaskAttachments::class);
    }

    public function refreshStatus()
    {
        $total = $this->check_lists()->count();
        if ($total === 0) {
            $this->status = 'Pending';
            $this->save();
            return $this->status;
        }

        $completed = $this->check_lists()->where('is_completed', true)->count();

        if ($completed === 0) {
            $this->status = 'Pending';
        } elseif ($completed < $total) {
            $this->status = 'In Progress';
        } else {
            $this->status = 'Completed';
        }

        $this->save();

        return $this->status;
    }
}
