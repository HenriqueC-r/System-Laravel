<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'project_id', 'title', 'completed_at'])]
class Task extends Model
{
    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }
}
