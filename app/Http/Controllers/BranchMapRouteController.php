<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\OsrmRouter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchMapRouteController extends Controller
{
    public function __construct(private readonly OsrmRouter $router) {}

    /**
     * Proxies an OSRM routing request so the browser never has to talk to a
     * third party host directly (and so the response shape stays stable).
     */
    public function __invoke(Request $request, Branch $branch): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        if (! $branch->hasCoordinates()) {
            return response()->json([
                'message' => 'This branch has no coordinates yet, so directions cannot be plotted.',
            ], 422);
        }

        return response()->json(
            $this->router->route(
                originLat: (float) $validated['lat'],
                originLng: (float) $validated['lng'],
                destinationLat: (float) $branch->latitude,
                destinationLng: (float) $branch->longitude,
                profile: $request->string('profile')->toString() ?: 'driving',
            )
        );
    }
}
