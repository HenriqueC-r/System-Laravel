<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'project_id', 'description', 'suggestions'])]
class AiRoadmap extends Model
{
    protected function casts(): array
    {
        return ['suggestions' => 'array'];
    }
}
