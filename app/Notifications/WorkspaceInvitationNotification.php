<?php

namespace App\Notifications;

use App\Models\WorkspaceInvitation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInvitationNotification extends Notification
{
    public function __construct(
        public WorkspaceInvitation $invitation,
        private string $token,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('workspace_management.invitation.email_subject', [
                'workspace' => $this->invitation->workspace->name,
            ]))
            ->line(__('workspace_management.invitation.email_intro', [
                'workspace' => $this->invitation->workspace->name,
            ]))
            ->action(
                __('workspace_management.invitation.email_action'),
                $this->acceptUrl(),
            )
            ->line(__('workspace_management.invitation.email_expiration'));
    }

    public function acceptUrl(): string
    {
        return $this->invitation->acceptUrl($this->token);
    }
}
