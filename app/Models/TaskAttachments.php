<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskAttachments extends Model
{
    use HasFactory;
    protected $fillable = ['task_id', 'attachment_url'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
