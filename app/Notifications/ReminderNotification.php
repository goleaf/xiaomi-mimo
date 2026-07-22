<?php

namespace App\Notifications;

use App\Models\Reminder;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReminderNotification extends Notification
{
    public function __construct(public Reminder $reminder) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('workspace.notifications.reminder_subject', [
                'task' => $this->reminder->todo->title,
            ]))
            ->line(__('workspace.notifications.reminder_body', [
                'task' => $this->reminder->todo->title,
            ]))
            ->action(
                __('workspace.notifications.view_task'),
                route('todos.show', $this->reminder->todo),
            )
            ->line(__('workspace.notifications.reminder_footer'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return $this->databaseData();
    }

    /** @return array<string, mixed> */
    public function databaseData(): array
    {
        return [
            'kind' => 'reminder',
            'channel' => $this->reminder->type->value,
            'reminder_id' => $this->reminder->id,
            'todo_id' => $this->reminder->todo_id,
            'workspace_id' => $this->reminder->todo->workspace_id,
            'todo_title' => $this->reminder->todo->title,
        ];
    }
}
