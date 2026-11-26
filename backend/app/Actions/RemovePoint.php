<?php

namespace App\Actions;

use App\Models\Point;
use Illuminate\Support\Facades\DB;

class RemovePoint
{
    public function __construct(private SyncSegments $syncSegments) {}

    public function handle(Point $point): void
    {
        DB::transaction(function () use ($point): void {
            $trip = $point->trip;
            $point->delete();

            Point::whereBelongsTo($trip)->where('position', '>', $point->position)->decrement('position');
            $this->syncSegments->handle($trip);
        });
    }
}
