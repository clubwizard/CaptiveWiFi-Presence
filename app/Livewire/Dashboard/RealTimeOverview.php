<?php

namespace App\Livewire\Dashboard;

use App\Models\AccessPoint;
use App\Models\ClientSession;
use Carbon\Carbon;
use Livewire\Component;

class RealTimeOverview extends Component
{
    public int $activeClientsCount = 0;
    public int $todayVisitorsCount = 0;
    public array $recentSessions = [];
    public array $accessPoints = [];

    /**
     * Initialize component data on mount.
     */
    public function mount(): void
    {
        $this->loadData();
    }

    /**
     * Load real-time data from database.
     */
    public function loadData(): void
    {
        // Get active sessions count (not yet disconnected)
        $this->activeClientsCount = ClientSession::active()->count();

        // Get today's unique visitors
        $today = Carbon::today();
        $this->todayVisitorsCount = ClientSession::whereDate('connected_at', $today)
            ->distinct('mac_address_hash')
            ->count('mac_address_hash');

        // Get 10 most recent sessions with access point info
        $this->recentSessions = ClientSession::with('accessPoint')
            ->latest('connected_at')
            ->limit(10)
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'connected_at' => $session->connected_at->diffForHumans(),
                    'ap_name' => $session->accessPoint->ap_name ?? 'Unknown AP',
                    'status' => $session->disconnected_at ? 'disconnected' : 'active',
                    'duration' => $session->dwell_time_minutes
                        ? round($session->dwell_time_minutes) . ' min'
                        : 'Active',
                    'is_returning' => $session->is_returning_visitor,
                ];
            })
            ->toArray();

        // Get access points with active client count
        $this->accessPoints = AccessPoint::withCount(['activeSessions'])
            ->get()
            ->map(function ($ap) {
                return [
                    'id' => $ap->id,
                    'name' => $ap->ap_name,
                    'location' => $ap->location_description ?? $ap->zone ?? 'Unknown',
                    'active_count' => $ap->active_sessions_count,
                    'status' => $ap->status,
                ];
            })
            ->toArray();
    }

    /**
     * Refresh data (called by polling or manual refresh).
     */
    public function refresh(): void
    {
        $this->loadData();
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('livewire.dashboard.real-time-overview');
    }
}
