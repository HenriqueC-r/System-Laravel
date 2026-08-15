<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'project_id', 'content', 'read_at'])]
class Comment extends Model
{
    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
