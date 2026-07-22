<?php

namespace App\Services;

use App\Models\Todo;
use App\Models\UserPreference;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;
use LogicException;

class RecurrenceSchedule
{
    /** @var list<string> */
    public const array RULES = [
        'FREQ=DAILY',
        'FREQ=WEEKLY',
        'FREQ=MONTHLY',
        'FREQ=YEARLY',
        'FREQ=DAILY;INTERVAL=2',
        'FREQ=WEEKLY;INTERVAL=2',
    ];

    public function anchorFor(Todo $todo): CarbonImmutable
    {
        if ($todo->recurrence_anchor_date instanceof CarbonInterface) {
            return CarbonImmutable::instance($todo->recurrence_anchor_date)->startOfDay();
        }

        if ($todo->due_date instanceof CarbonInterface) {
            return CarbonImmutable::instance($todo->due_date)->startOfDay();
        }

        $todo->loadMissing(['assignee.preferences', 'workspace.owner.preferences']);
        $assigneePreferences = $todo->assignee?->getRelation('preferences');
        $ownerPreferences = $todo->workspace->owner->getRelation('preferences');
        $timezone = $assigneePreferences instanceof UserPreference
            ? $assigneePreferences->timezone
            : ($ownerPreferences instanceof UserPreference
                ? $ownerPreferences->timezone
                : 'UTC');
        $basis = $todo->completed_at ?? $todo->created_at ?? now();

        return CarbonImmutable::instance($basis)
            ->setTimezone($timezone)
            ->startOfDay();
    }

    public function occurrence(CarbonInterface $anchor, string $rule, int $sequence): CarbonImmutable
    {
        $rule = $this->canonicalRule($rule);

        if ($sequence < 0) {
            throw new InvalidArgumentException('Unsupported recurrence schedule.');
        }

        [$frequency, $interval] = $this->parse($rule);
        $steps = $interval * $sequence;
        $date = CarbonImmutable::instance($anchor)->startOfDay();

        return match ($frequency) {
            'DAILY' => $date->addDays($steps),
            'WEEKLY' => $date->addWeeks($steps),
            'MONTHLY' => $date->addMonthsNoOverflow($steps),
            'YEARLY' => $date->addYearsNoOverflow($steps),
            default => throw new LogicException('Unsupported canonical recurrence frequency.'),
        };
    }

    public function canonicalRule(string $rule): string
    {
        $canonical = str_ends_with($rule, ';INTERVAL=1')
            ? substr($rule, 0, -11)
            : $rule;

        if (! in_array($canonical, self::RULES, true)) {
            throw new InvalidArgumentException('Unsupported recurrence schedule.');
        }

        return $canonical;
    }

    /** @return array{string, int} */
    private function parse(string $rule): array
    {
        $parts = collect(explode(';', $rule))
            ->mapWithKeys(function (string $part): array {
                [$key, $value] = array_pad(explode('=', $part, 2), 2, '');

                return [$key => $value];
            });

        return [(string) $parts->get('FREQ'), (int) $parts->get('INTERVAL', 1)];
    }
}
