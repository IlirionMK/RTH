<?php

namespace App\Console\Commands;

use App\Actions\ResolveCountry;
use App\Models\Point;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:resolve-countries')]
#[Description('Fill in the countries of points that have none')]
class ResolveCountries extends Command
{
    public function handle(ResolveCountry $resolveCountry): int
    {
        $resolved = 0;

        foreach (Point::whereNull('country_code')->lazyById() as $point) {
            $point->update(['country_code' => $resolveCountry->handle($point->latitude, $point->longitude)]);

            if ($point->country_code !== null) {
                $resolved++;
            }
        }

        $this->info("Countries resolved: {$resolved}.");

        return self::SUCCESS;
    }
}
