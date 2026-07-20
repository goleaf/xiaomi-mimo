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
    public const int MAX_PER_WORKSPACE = 200;

    /** @use HasFactory<TagFactory> */
    use HasFactory, HasUuid;

    protected $fillable = ['workspace_id', 'name'];

    public static function normalizeName(string $name): string
    {
        return mb_strtolower(trim($name), 'UTF-8');
    }

    protected static function booted(): void
    {
        static::saving(function (Tag $tag): void {
            if ($tag->isDirty('name')) {
                $tag->normalized_name = self::normalizeName($tag->name);
            }
        });
    }

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
