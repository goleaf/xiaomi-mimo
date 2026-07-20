<?php

namespace App\Data;

use App\Models\WorkspaceInvitation;

class WorkspaceInvitationIssue
{
    public function __construct(
        public WorkspaceInvitation $invitation,
        public string $token,
    ) {}
}
