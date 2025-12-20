<div class="space-y-6" wire:poll.30s="refresh">
    {{-- Header with Manual Refresh --}}
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
            Real-Time Overview
        </h2>
        <button wire:click="refresh"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors flex items-center gap-2"
                wire:loading.attr="disabled">
            <svg wire:loading.remove class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <svg wire:loading class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span wire:loading.remove>Refresh</span>
            <span wire:loading>Refreshing...</span>
        </button>
    </div>

    {{-- Metric Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Active Clients --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Clients</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2">
                        {{ number_format($activeClientsCount) }}
                    </p>
                </div>
                <div class="bg-green-100 dark:bg-green-900 rounded-full p-3">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">
                Currently connected to WiFi
            </p>
        </div>

        {{-- Today's Visitors --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Today's Visitors</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2">
                        {{ number_format($todayVisitorsCount) }}
                    </p>
                </div>
                <div class="bg-blue-100 dark:bg-blue-900 rounded-full p-3">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">
                Unique devices detected today
            </p>
        </div>
    </div>

    {{-- Access Points Grid --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Access Points</h3>

        @if(empty($accessPoints))
            <div class="text-center py-8">
                <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <p class="text-gray-500 dark:text-gray-400">No access points configured</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($accessPoints as $ap)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:border-indigo-500 transition-colors">
                        <div class="flex items-start justify-between mb-2">
                            <h4 class="font-medium text-gray-900 dark:text-gray-100 truncate flex-1">
                                {{ $ap['name'] }}
                            </h4>
                            <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full
                                {{ $ap['status'] === 'online' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                                {{ ucfirst($ap['status']) }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                            {{ $ap['location'] }}
                        </p>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ $ap['active_count'] }}
                            </span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                active {{ $ap['active_count'] === 1 ? 'client' : 'clients' }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Recent Sessions Feed --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Recent Sessions</h3>

        @if(empty($recentSessions))
            <div class="text-center py-8">
                <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <p class="text-gray-500 dark:text-gray-400">No sessions recorded yet</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($recentSessions as $session)
                    <div class="flex items-center justify-between py-3 px-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center gap-4 flex-1">
                            {{-- Status Indicator --}}
                            <div class="flex-shrink-0">
                                @if($session['status'] === 'active')
                                    <span class="flex h-3 w-3">
                                        <span class="animate-ping absolute inline-flex h-3 w-3 rounded-full bg-green-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full h-3 w-3 bg-gray-400"></span>
                                @endif
                            </div>

                            {{-- Session Info --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                    {{ $session['ap_name'] }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $session['connected_at'] }}
                                </p>
                            </div>

                            {{-- Duration --}}
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $session['duration'] }}
                                </p>
                                @if($session['is_returning'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                        Returning
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Auto-refresh Indicator --}}
    <div class="text-center">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Auto-refreshes every 30 seconds
        </p>
    </div>
</div>
