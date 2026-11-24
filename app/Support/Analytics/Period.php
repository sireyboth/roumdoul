<?php

namespace App\Support\Analytics;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

/**
 * The date range the admin dashboard looks at (its «Period» filter) and the range it is compared with
 * («Compare with»: the period just before, the same dates last year, or nothing). Also decides how the
 * charts bucket time: by day up to ~2 months, by week up to ~6 months, by month after that.
 */
final class Period
{
    public const PRESETS = [
        'today' => 'Today',
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        '90d' => 'Last 90 days',
        'this_month' => 'This month',
        'last_month' => 'Last month',
        'this_year' => 'This year',
        '12m' => 'Last 12 months',
        'custom' => 'Custom dates',
    ];

    public const COMPARE = [
        'previous' => 'Previous period',
        'last_year' => 'Same period last year',
        'none' => 'No comparison',
    ];

    public static function timezone(): string
    {
        return (string) config('roumdoul.event_timezone', 'Asia/Phnom_Penh');
    }

    public function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
        public readonly string $compare = 'previous',
        public readonly string $preset = '30d',
    ) {}

    /** @param  array<string, mixed>|null  $filters  the dashboard's page filters */
    public static function fromFilters(?array $filters): self
    {
        $preset = $filters['period'] ?? '30d';
        $compare = $filters['compare'] ?? 'previous';
        $compare = isset(self::COMPARE[$compare]) ? $compare : 'previous';
        $now = CarbonImmutable::now(self::timezone());

        [$start, $end] = match ($preset) {
            'today' => [$now->startOfDay(), $now->endOfDay()],
            '7d' => [$now->subDays(6)->startOfDay(), $now->endOfDay()],
            '90d' => [$now->subDays(89)->startOfDay(), $now->endOfDay()],
            'this_month' => [$now->startOfMonth(), $now->endOfDay()],
            'last_month' => [$now->subMonthNoOverflow()->startOfMonth(), $now->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [$now->startOfYear(), $now->endOfDay()],
            '12m' => [$now->subMonthsNoOverflow(11)->startOfMonth(), $now->endOfDay()],
            'custom' => [
                self::parse($filters['start'] ?? null)?->startOfDay() ?? $now->subDays(29)->startOfDay(),
                self::parse($filters['end'] ?? null)?->endOfDay() ?? $now->endOfDay(),
            ],
            default => [$now->subDays(29)->startOfDay(), $now->endOfDay()],
        };

        if ($end->lessThan($start)) {
            [$start, $end] = [$end->startOfDay(), $start->endOfDay()];
        }

        return new self($start->utc(), $end->utc(), $compare, isset(self::PRESETS[$preset]) ? $preset : '30d');
    }

    /** The last $days local days up to now (e.g. last(1) = today), or the single local day $daysAgo days back. */
    public static function last(int $days): self
    {
        $now = CarbonImmutable::now(self::timezone());

        return new self($now->subDays($days - 1)->startOfDay()->utc(), $now->endOfDay()->utc(), 'previous', 'custom');
    }

    public static function day(int $daysAgo = 1, string $compare = 'previous'): self
    {
        $day = CarbonImmutable::now(self::timezone())->subDays($daysAgo);

        return new self($day->startOfDay()->utc(), $day->endOfDay()->utc(), $compare, 'custom');
    }

    /** start / end on the business clock (for labels, buckets). */
    public function localStart(): CarbonImmutable
    {
        return $this->start->setTimezone(self::timezone());
    }

    public function localEnd(): CarbonImmutable
    {
        return $this->end->setTimezone(self::timezone());
    }

    private static function parse(mixed $value): ?CarbonImmutable
    {
        try {
            return filled($value) ? CarbonImmutable::parse($value, self::timezone()) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function hasComparison(): bool
    {
        return $this->compare !== 'none';
    }

    /** The range compared against, or null for «No comparison». */
    public function previous(): ?self
    {
        return match ($this->compare) {
            'none' => null,
            'last_year' => new self($this->localStart()->subYear()->utc(), $this->localEnd()->subYear()->utc(), 'none'),
            default => new self(
                $this->start->subSeconds((int) $this->start->diffInSeconds($this->end) + 1),
                $this->start->subSecond(),
                'none',
            ),
        };
    }

    public function days(): int
    {
        return (int) $this->localStart()->startOfDay()->diffInDays($this->localEnd()->startOfDay()) + 1;
    }

    /** day | week | month */
    public function bucket(): string
    {
        return match (true) {
            $this->days() <= 62 => 'day',
            $this->days() <= 190 => 'week',
            default => 'month',
        };
    }

    /** Bucket key for a date (matches buckets()). */
    public function bucketKey(CarbonImmutable|\DateTimeInterface|string $date): string
    {
        $date = CarbonImmutable::parse($date)->setTimezone(self::timezone());

        return match ($this->bucket()) {
            'day' => $date->toDateString(),
            'week' => $date->startOfWeek()->toDateString(),
            default => $date->format('Y-m'),
        };
    }

    /** @return array<string, string> bucket key => chart label, oldest first */
    public function buckets(): array
    {
        $bucket = $this->bucket();
        $step = ['day' => '1 day', 'week' => '1 week', 'month' => '1 month'][$bucket];
        $start = $this->localStart();
        $from = match ($bucket) {
            'day' => $start->startOfDay(),
            'week' => $start->startOfWeek(),
            default => $start->startOfMonth(),
        };

        $out = [];
        foreach (CarbonPeriod::create($from, $step, $this->localEnd()) as $date) {
            $date = CarbonImmutable::instance($date)->setTimezone(self::timezone());
            $out[$this->bucketKey($date)] = match ($bucket) {
                'day' => $date->format('M j'),
                'week' => 'Wk '.$date->format('M j'),
                default => $date->format('M Y'),
            };
        }

        return $out;
    }

    public function label(): string
    {
        [$start, $end] = [$this->localStart(), $this->localEnd()];

        return $start->isSameDay($end)
            ? $start->format('D, M j, Y')
            : $start->format($start->isSameYear($end) ? 'M j' : 'M j, Y').' – '.$end->format('M j, Y');
    }

    public function compareLabel(): string
    {
        return match ($this->compare) {
            'last_year' => 'vs same period last year',
            'none' => '',
            default => 'vs previous '.($this->days() === 1 ? 'day' : $this->days().' days'),
        };
    }
}
