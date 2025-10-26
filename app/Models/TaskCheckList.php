<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskCheckList extends Model
{
    use HasFactory;
    protected $fillable = ['task_id', 'title', 'is_completed'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
