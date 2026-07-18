<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use Inertia\Inertia;
use Inertia\Response;

class BackupController extends Controller
{
    public function edit(BackupService $service): Response
    {
        return Inertia::render('settings/Backup', [
            'backups' => $service->listBackups(),
        ]);
    }
}
