@extends('layouts.admin')

@section('page-title', 'GPS Tracking Map')

@section('content')
<div class="w-full h-full">
    <!-- GPS Tracking Map Iframe -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden" style="height: calc(100vh - 120px);">
        <iframe
            src="https://tracker.questerra-series.com/eureka-live-map.html"
            style="width: 100%; height: 100%; border: none;"
            title="GPS Live Tracking Map"
            allow="geolocation">
        </iframe>
    </div>

    <!-- Info Panel (Optional) -->
    <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-medium text-blue-800">Live GPS Tracking Information</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p>This map displays real-time GPS tracking data from the tracker system:</p>
                    <ul class="list-disc list-inside mt-2 space-y-1">
                        <li><strong>Live Teams:</strong> Animated markers show active teams' current locations</li>
                        <li><strong>Routes:</strong> Blue lines display completed marathon routes</li>
                        <li><strong>Markers:</strong> Emoji icons show route waypoints (water stations, facilities, etc.)</li>
                        <li><strong>Auto-Refresh:</strong> Map updates every 10 seconds automatically</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
