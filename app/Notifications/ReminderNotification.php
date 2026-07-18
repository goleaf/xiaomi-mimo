<?php

namespace App\Notifications;

use App\Models\Reminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Reminder $reminder) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Task Reminder: ' . $this->reminder->todo->title)
            ->line("This is a reminder for your task: {$this->reminder->todo->title}")
            ->action('View Task', url('/'))
            ->line('Thank you for using our application!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'reminder_id' => $this->reminder->id,
            'todo_id' => $this->reminder->todo_id,
            'todo_title' => $this->reminder->todo->title,
            'message' => "Reminder: {$this->reminder->todo->title}",
        ];
    }
}
