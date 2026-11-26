<?php

namespace App\Actions;

use App\Models\Trip;
use App\Models\User;
use App\Support\SystemStatistics;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CollectSystemStatistics
{
    private const MONTHS = 12;

    public function handle(): SystemStatistics
    {
        $start = CarbonImmutable::now()->startOfMonth()->subMonths(self::MONTHS - 1);
        $users = $this->countByMonth(User::query(), $start);
        $trips = $this->countByMonth(Trip::query(), $start);
        $months = [];

        for ($index = 0; $index < self::MONTHS; $index++) {
            $month = $start->addMonths($index)->format('Y-m');
            $months[] = ['month' => $month, 'users' => (int) ($users[$month] ?? 0), 'trips' => (int) ($trips[$month] ?? 0)];
        }

        return new SystemStatistics(
            usersCount: User::query()->count(),
            tripsCount: Trip::query()->count(),
            months: $months,
        );
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Collection<array-key, mixed>
     */
    private function countByMonth(Builder $query, CarbonImmutable $start): Collection
    {
        return $query->where('created_at', '>=', $start)
            ->toBase()
            ->selectRaw("to_char(created_at, 'YYYY-MM') as month, count(*) as count")
            ->groupBy('month')
            ->pluck('count', 'month');
    }
}
