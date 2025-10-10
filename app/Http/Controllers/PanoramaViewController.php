<?php

namespace App\Http\Controllers;

use App\Models\GameLocation;
use App\Models\Hotspot;
use Illuminate\Http\Request;

/**
 * PanoramaViewController - Admin Panorama Management
 * 
 * Handles 360° panoramic image viewing and hotspot management for admin users.
 * 
 * Coordinate System:
 * - Database stores coordinates in RADIANS
 * - Frontend (Pannellum) expects coordinates in DEGREES  
 * - Conversion: degrees = radians * (180/π), radians = degrees * (π/180)
 * 
 * Pannellum Hotspot Configuration:
 * - id: unique identifier
 * - pitch: vertical angle in degrees (-90 to 90)
 * - yaw: horizontal angle in degrees (-180 to 180)  
 * - type: 'info' or 'scene'
 * - text: hover tooltip
 * - cssClass: custom styling
 * - scale: boolean for scaling with field of view
 */

class PanoramaViewController extends Controller
{
    public function show($id)
    {
        $gameLocation = GameLocation::with('activeHotspots')->findOrFail($id);
        $gameLocations = GameLocation::where('is_active', true)->get(); // For navigation dropdown
        
        \Log::info('🎯 Loading panorama view', [
            'game_location_id' => $gameLocation->id,
            'hotspots_count' => $gameLocation->activeHotspots->count(),
            'available_locations' => $gameLocations->count()
        ]);
        
        if (!$gameLocation->map_image_path) {
            abort(404, '360° image not available for this location');
        }
        
        return view('panorama.view', compact('gameLocation', 'gameLocations'));
    }

    /**
     * Get hotspots for a specific game location (API endpoint)
     * 
     * Returns hotspots with coordinates converted from radians to degrees
     * for direct use with Pannellum viewer
     */
    public function getHotspots($gameLocationId)
    {
        $gameLocation = GameLocation::with('activeHotspots')->findOrFail($gameLocationId);
        
        $hotspots = $gameLocation->activeHotspots->map(function($hotspot) {
            return $hotspot->toPannellumConfig();
        });

        return response()->json([
            'success' => true,
            'hotspots' => $hotspots,
            'game_location_id' => $gameLocationId
        ]);
    }
    
    public function addHotspot(Request $request, $gameLocationId)
    {
        \Log::info('🎯 Hotspot creation request received', [
            'game_location_id' => $gameLocationId,
            'data' => $request->all(),
            'pitch_radians' => $request->get('pitch'),
            'yaw_radians' => $request->get('yaw'),
            'pitch_degrees' => $request->get('pitch') * (180 / pi()),
            'yaw_degrees' => $request->get('yaw') * (180 / pi()),
            'hotspot_type' => $request->get('hotspot_type'),
            'note' => 'Frontend sends radians, database stores radians'
        ]);

        try {
            // Frontend sends coordinates in radians, validate accordingly
            $request->validate([
                'pitch' => 'required|numeric|between:-1.571,1.571', // -π/2 to π/2 radians (~-90° to 90°)
                'yaw' => 'required|numeric|between:-3.142,3.142',   // -π to π radians (~-180° to 180°)
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'in:info,scene,custom',
                'hotspot_type' => 'in:info,navigation,quiz',
                'target_location_id' => 'nullable|exists:game_locations,id',
                'rich_content' => 'nullable|string',
                'quiz_data' => 'nullable|string', // JSON string from FormData
                'info_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
            ]);
            
            $gameLocation = GameLocation::findOrFail($gameLocationId);
            \Log::info('✅ GameLocation found:', ['id' => $gameLocation->id, 'name' => $gameLocation->name]);
            
            // Prepare extra_data based on hotspot type
            $extraData = [];
            $hotspotType = $request->get('hotspot_type', 'info');
            
            $extraData['hotspot_type'] = $hotspotType;
            
            if ($hotspotType === 'navigation') {
                $extraData['target_location_id'] = $request->get('target_location_id');
            } elseif ($hotspotType === 'quiz') {
                // Decode JSON string from FormData
                $quizDataJson = $request->get('quiz_data');
                if ($quizDataJson) {
                    $extraData['quiz_data'] = json_decode($quizDataJson, true);
                }
            }
            
            // Add rich content to extra_data
            if ($request->get('rich_content')) {
                $extraData['content'] = $request->get('rich_content');
            }
            
            // Handle image upload for info hotspots
            $imagePath = null;
            if ($request->hasFile('info_image') && $hotspotType === 'info') {
                $image = $request->file('info_image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $imagePath = $image->storeAs('hotspot-images', $imageName, 'public');
                $extraData['image_path'] = $imagePath;
                
                \Log::info('📷 Image uploaded for info hotspot:', [
                    'original_name' => $image->getClientOriginalName(),
                    'stored_path' => $imagePath,
                    'size_kb' => round($image->getSize() / 1024, 2)
                ]);
            }
            
            $hotspot = $gameLocation->hotspots()->create([
                'title' => $request->title,
                'description' => $request->description,
                'pitch' => $request->pitch,
                'yaw' => $request->yaw,
                'type' => $request->type ?? 'info',
                'css_class' => 'admin-hotspot-marker',
                'is_active' => true,
                'extra_data' => $extraData
            ]);
            
            \Log::info('✅ Hotspot created successfully:', [
                'id' => $hotspot->id,
                'pitch_radians' => $hotspot->pitch,
                'yaw_radians' => $hotspot->yaw,
                'pitch_degrees' => $hotspot->pitch * (180 / pi()),
                'yaw_degrees' => $hotspot->yaw * (180 / pi()),
                'hotspot_type' => $hotspotType,
                'extra_data' => $extraData,
                'note' => 'Stored in database as radians'
            ]);
            
            // Ensure coordinates are returned as proper numbers
            $hotspotData = $hotspot->toArray();
            $hotspotData['pitch'] = (float) $hotspot->pitch;
            $hotspotData['yaw'] = (float) $hotspot->yaw;
            
            return response()->json([
                'success' => true,
                'hotspot' => $hotspotData,
                'message' => 'Hotspot added successfully with tour features'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('❌ Error creating hotspot:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error creating hotspot: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function deleteHotspot($id)
    {
        $hotspot = Hotspot::findOrFail($id);
        $hotspot->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Hotspot deleted successfully'
        ]);
    }
}
