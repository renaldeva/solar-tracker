<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TelemetryResource;
use App\Models\Telemetry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TelemetryController extends Controller
{
    /**
     * Get the latest telemetry packet.
     */
    public function latest(): JsonResponse|TelemetryResource
    {
        $telemetry = Telemetry::latest('received_at')->first();
        if (! $telemetry) {
            $telemetry = Telemetry::latest('id')->first();
        }

        if (! $telemetry) {
            return response()->json(['message' => 'No telemetry data available'], 404);
        }

        return new TelemetryResource($telemetry);
    }

    /**
     * Get telemetry historical data.
     */
    public function history(Request $request): AnonymousResourceCollection
    {
        $limit = min((int) $request->input('limit', 50), 200);
        $telemetries = Telemetry::latest('received_at')->paginate($limit);

        return TelemetryResource::collection($telemetries);
    }
}
