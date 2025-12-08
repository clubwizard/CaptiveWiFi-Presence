<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthCheckController extends Controller
{
    /**
     * Health check endpoint for DigitalOcean App Platform.
     * Verifies database and Redis connectivity.
     *
     * @return JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        $status = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => [],
        ];

        // Check database connection
        try {
            DB::connection()->getPdo();
            $status['checks']['database'] = 'ok';
        } catch (\Exception $e) {
            $status['checks']['database'] = 'failed';
            $status['status'] = 'unhealthy';
        }

        // Check Redis connection
        try {
            Redis::ping();
            $status['checks']['redis'] = 'ok';
        } catch (\Exception $e) {
            $status['checks']['redis'] = 'failed';
            $status['status'] = 'unhealthy';
        }

        $httpStatus = $status['status'] === 'healthy' ? 200 : 503;

        return response()->json($status, $httpStatus);
    }
}
