<?php

namespace App\Support;

final readonly class SystemStatistics
{
    /**
     * @param  list<array{month: string, users: int, trips: int}>  $months
     */
    public function __construct(
        public int $usersCount,
        public int $tripsCount,
        public array $months,
    ) {}
}
