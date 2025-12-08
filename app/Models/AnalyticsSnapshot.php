<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'snapshot_date',
        'snapshot_hour',
        'unique_visitors',
        'total_sessions',
        'avg_dwell_time_minutes',
        'peak_connected_count',
        'returning_visitor_percentage',
        'new_visitor_count',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'snapshot_hour' => 'integer',
        'unique_visitors' => 'integer',
        'total_sessions' => 'integer',
        'avg_dwell_time_minutes' => 'decimal:2',
        'peak_connected_count' => 'integer',
        'returning_visitor_percentage' => 'decimal:2',
        'new_visitor_count' => 'integer',
    ];

    /**
     * Scope to get daily snapshots (hourly breakdown is null).
     */
    public function scopeDaily($query)
    {
        return $query->whereNull('snapshot_hour');
    }

    /**
     * Scope to get hourly snapshots.
     */
    public function scopeHourly($query)
    {
        return $query->whereNotNull('snapshot_hour');
    }

    /**
     * Scope to get snapshots for a specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('snapshot_date', $date);
    }

    /**
     * Scope to get snapshots within a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('snapshot_date', [$startDate, $endDate]);
    }
}
