<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MembersController extends Controller
{
    public function edit(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace();

        return Inertia::render('settings/Members', [
            'workspace' => $workspace,
            'members' => $workspace->members()->get(),
        ]);
    }
}
