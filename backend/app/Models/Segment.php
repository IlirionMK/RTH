<?php

namespace App\Models;

use App\Enums\Transport;
use Database\Factories\SegmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['from_point_id', 'to_point_id', 'transport', 'geometry', 'distance_km', 'is_approximate'])]
class Segment extends Model
{
    /** @use HasFactory<SegmentFactory> */
    use HasFactory;

    protected $attributes = [
        'transport' => 'car',
        'is_approximate' => false,
    ];

    /**
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * @return BelongsTo<Point, $this>
     */
    public function fromPoint(): BelongsTo
    {
        return $this->belongsTo(Point::class);
    }

    /**
     * @return BelongsTo<Point, $this>
     */
    public function toPoint(): BelongsTo
    {
        return $this->belongsTo(Point::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transport' => Transport::class,
            'geometry' => 'array',
            'distance_km' => 'float',
            'is_approximate' => 'boolean',
        ];
    }
}
