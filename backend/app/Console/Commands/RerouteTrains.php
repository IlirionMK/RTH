<?php

namespace App\Console\Commands;

use App\Actions\RouteSegment;
use App\Enums\Transport;
use App\Models\Segment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:reroute-trains')]
#[Description('Draw train segments again along the rails')]
class RerouteTrains extends Command
{
    public function handle(RouteSegment $routeSegment): int
    {
        $total = 0;
        $onRails = 0;

        foreach (Segment::where('transport', Transport::Train)->with(['fromPoint', 'toPoint'])->lazyById() as $segment) {
            $routeSegment->handle($segment);
            $total++;

            if (! $segment->is_approximate) {
                $onRails++;
            }
        }

        $this->info("Train segments on rails: {$onRails} of {$total}.");

        return self::SUCCESS;
    }
}
