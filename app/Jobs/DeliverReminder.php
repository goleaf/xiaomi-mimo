<?php

namespace App\Jobs;

use App\Actions\DeliverClaimedReminder;
use App\Actions\FailReminderDelivery;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class DeliverReminder implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    public int $uniqueFor = 600;

    public function __construct(
        public string $reminderId,
        public string $claimToken,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(
        DeliverClaimedReminder $deliverReminder,
        FailReminderDelivery $failReminderDelivery,
    ): void {
        try {
            $deliverReminder->handle($this->reminderId, $this->claimToken);
        } catch (Throwable $exception) {
            $failReminderDelivery->handle($this->reminderId, $this->claimToken, $exception);

            throw $exception;
        }
    }

    public function uniqueId(): string
    {
        return $this->reminderId;
    }

    /** @return list<string> */
    public function tags(): array
    {
        return ['reminder:'.$this->reminderId];
    }
}
