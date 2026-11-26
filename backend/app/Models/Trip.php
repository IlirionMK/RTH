<?php

namespace App\Models;

use Database\Factories\TripFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'starts_on', 'ends_on', 'description'])]
class Trip extends Model
{
    /** @use HasFactory<TripFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Point, $this>
     */
    public function points(): HasMany
    {
        return $this->hasMany(Point::class)->orderBy('position');
    }

    /**
     * @return HasMany<Segment, $this>
     */
    public function segments(): HasMany
    {
        return $this->hasMany(Segment::class)
            ->orderBy(Point::query()->select('position')->whereColumn('points.id', 'segments.from_point_id'));
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withSummary(Builder $query): void
    {
        $query->with('points')
            ->withSum('segments as distance_km', 'distance_km')
            ->addSelect(['transports' => Segment::query()
                ->selectRaw("string_agg(distinct transport, ',' order by transport)")
                ->whereColumn('segments.trip_id', 'trips.id')]);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function startedIn(Builder $query, ?int $year): void
    {
        if ($year !== null) {
            $query->whereBetween('starts_on', ["{$year}-01-01", "{$year}-12-31"]);
        }
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }
}
