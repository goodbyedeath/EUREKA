<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeatureSetting;
use Illuminate\Http\Request;

class FeatureController extends Controller
{
    /**
     * Check if a specific feature is enabled
     */
    public function checkFeatureStatus(Request $request)
    {
        $feature = $request->query('feature');
        
        if (!$feature) {
            return response()->json([
                'error' => 'Feature parameter required'
            ], 400);
        }

        try {
            $enabled = FeatureSetting::isEnabled($feature);
            
            return response()->json([
                'feature' => $feature,
                'enabled' => $enabled
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'feature' => $feature,
                'enabled' => false,
                'error' => 'Feature check failed'
            ]);
        }
    }
}