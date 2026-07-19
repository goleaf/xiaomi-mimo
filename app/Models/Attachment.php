<?php

namespace App\Models;

use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $id
 * @property string $todo_id
 * @property string $user_id
 * @property string $filename
 * @property string $path
 * @property string|null $mime_type
 * @property int $size
 * @property-read string $url
 */
class Attachment extends Model
{
    use HasUuid;

    protected $fillable = ['todo_id', 'user_id', 'filename', 'path', 'mime_type', 'size'];

    /** @return BelongsTo<Todo, $this> */
    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk((string) config('filesystems.attachment_disk'))->url($this->path);
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1).' '.$units[$i];
    }
}
