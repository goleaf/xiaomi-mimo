<?php

namespace App\Models;

use App\Concerns\HasUuid;
use Database\Factories\LabelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Label extends Model
{
    /** @use HasFactory<LabelFactory> */
    use HasFactory, HasUuid;

    protected $fillable = ['workspace_id', 'name', 'color'];

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsToMany<Todo, $this> */
    public function todos(): BelongsToMany
    {
        return $this->belongsToMany(Todo::class, 'todo_label');
    }
}
