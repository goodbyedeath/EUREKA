# Quest Location Route Finding Setup

## Overview
The route finding feature allows users to get turn-by-turn directions from their current location to any quest location using OpenRouteService API.

## API Setup

### 1. Get OpenRouteService API Key
1. Visit https://openrouteservice.org/
2. Sign up for a free account
3. Get your API key (free tier: 2000 requests/day)

### 2. Configure Environment
Add to your `.env` file:
```
OPENROUTE_API_KEY=your_actual_api_key_here
```

## Features

### Frontend Features
- **Find Route Button**: Available on each quest location card when GPS is enabled
- **Interactive Route Map**: Shows the route with start/end markers
- **Distance & Duration**: Real-time calculations
- **Turn-by-turn Directions**: Step-by-step navigation instructions
- **Fallback Support**: Works even without API key (straight-line route)

### Backend Features
- **Walking Routes**: Optimized for pedestrian navigation
- **Error Handling**: Graceful fallbacks if API fails
- **Caching**: Efficient API usage
- **Logging**: Full error tracking

## Technical Implementation

### Route API Request
```php
POST https://api.openrouteservice.org/v2/directions/foot-walking
{
    "coordinates": [[startLng, startLat], [endLng, endLat]],
    "format": "geojson",
    "instructions": true
}
```

### Response Data Structure
```php
[
    'coordinates' => [[lng, lat], ...], // Route polyline
    'distance' => 1250,                 // Distance in meters
    'duration' => 900,                  // Duration in seconds
    'instructions' => [                 // Turn-by-turn steps
        ['instruction' => 'Head north', 'distance' => 100],
        ...
    ]
]
```

## Usage

### User Flow
1. User enables GPS location
2. Clicks "Find Route" on any quest location
3. Route modal opens with interactive map
4. User sees route, distance, duration, and directions
5. Option to open in Google Maps for full navigation

### Admin Benefits
- No additional configuration needed
- Works with existing quest location coordinates
- Automatic fallback ensures feature always works

## API Limits
- **Free Tier**: 2000 requests/day
- **Rate Limiting**: Built-in request throttling
- **Fallback**: Straight-line routes when API unavailable

## Map Features
- **Route Visualization**: Blue route line on map
- **Markers**: Green start, red destination
- **Auto-fit**: Map automatically zooms to show full route
- **Navigation Controls**: Zoom, pan controls included

This feature enhances the user experience by providing practical navigation assistance for quest completion.