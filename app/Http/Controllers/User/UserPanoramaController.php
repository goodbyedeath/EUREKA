<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\GameLocation;
use Illuminate\Http\Request;

/**
 * UserPanoramaController - User Read-Only Panorama Viewing
 * 
 * Provides read-only access to 360° panoramic images for regular users.
 * Users can view panoramas and hotspots but cannot create or modify them.
 * 
 * Coordinate System:
 * - Database stores coordinates in RADIANS
 * - Frontend (Pannellum) expects coordinates in DEGREES  
 * - Conversion: degrees = radians * (180/π)
 * 
 * Pannellum Hotspot Configuration for Users:
 * - id: unique identifier
 * - pitch: vertical angle in degrees (converted from radians)
 * - yaw: horizontal angle in degrees (converted from radians)
 * - type: 'info' (read-only hotspots)
 * - text: hotspot title for hover tooltip
 * - cssClass: user-specific styling
 */

class UserPanoramaController extends Controller
{
    public function show($id)
    {
        $gameLocation = GameLocation::with('activeHotspots')->findOrFail($id);
        
        // Ensure the game location is active and has a panorama image
        if (!$gameLocation->is_active || !$gameLocation->map_image_path) {
            abort(404, 'This panoramic location is not available');
        }
        
        \Log::info('🎮 User accessing panorama view', [
            'game_location_id' => $gameLocation->id,
            'user_id' => auth()->id() ?? 'guest',
            'hotspots_count' => $gameLocation->activeHotspots->count()
        ]);
        
        return view('user.panorama.view', compact('gameLocation'));
    }
    
    /**
     * Get hotspots for user viewing (API endpoint)
     * 
     * Returns hotspots formatted for Pannellum with coordinates 
     * converted from radians (database) to degrees (Pannellum)
     */
    public function getHotspots($id)
    {
        $gameLocation = GameLocation::with('activeHotspots')->findOrFail($id);
        
        // Ensure user can access this location
        if (!$gameLocation->is_active) {
            return response()->json(['error' => 'Location not available'], 404);
        }
        
        // Return hotspots with official Pannellum configuration
        $hotspots = $gameLocation->activeHotspots->map(function ($hotspot) {
            $config = $hotspot->toPannellumConfig();
            // Override for user-specific settings
            $config['id'] = 'user-hotspot-' . $hotspot->id;
            $config['cssClass'] = 'user-hotspot';
            $config['type'] = 'info'; // Force read-only for users
            return $config;
        });
        
        \Log::info('🎮 Hotspots sent to user', [
            'game_location_id' => $id,
            'hotspots_count' => $hotspots->count(),
            'user_id' => auth()->id() ?? 'guest'
        ]);
        
        return response()->json([
            'success' => true,
            'hotspots' => $hotspots,
            'game_location_id' => $id
        ]);
    }
}
