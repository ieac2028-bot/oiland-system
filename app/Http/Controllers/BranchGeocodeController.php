<?php

namespace App\Http\Controllers;

use App\Services\NominatimGeocoder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchGeocodeController extends Controller
{
    public function __invoke(Request $request, NominatimGeocoder $geocoder): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:200'],
        ]);

        return response()->json($geocoder->geocode($validated['q']));
    }
}
