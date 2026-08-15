<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'project_id', 'title', 'description', 'icon', 'occurred_at'])]
class Activity extends Model
{
    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }
}
