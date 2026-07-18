<?php

namespace App\Enums;

enum ActivityEvent: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Completed = 'completed';
    case Uncompleted = 'uncompleted';
    case Deleted = 'deleted';
    case Restored = 'restored';
    case Archived = 'archived';
    case Unarchived = 'unarchived';
    case Pinned = 'pinned';
    case Unpinned = 'unpinned';
    case Favorited = 'favorited';
    case Unfavorited = 'unfavorited';
    case Attached = 'attached';
    case Detached = 'detached';
}
