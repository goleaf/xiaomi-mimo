<?php

namespace App\Models;

use App\Concerns\HasUuid;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory, HasUuid;

    protected $fillable = ['workspace_id', 'name'];

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsToMany<Todo, $this> */
    public function todos(): BelongsToMany
    {
        return $this->belongsToMany(Todo::class, 'todo_tag');
    }
}
