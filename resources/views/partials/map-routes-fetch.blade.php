{{-- Routes drawn in the GPS Tracker app, served from EUREKA's own copy (App\Services\TrackerMapSync).

     The three map pages used to call tracker.questerra-series.com straight from the browser. That
     host has no CDN in front of it and its export is public, so during an event it was a second
     thing that had to stay up — and a screen on the venue WiFi could see routes the crew had not
     chosen. Now every map reads /api/map/routes, which serves only what the crew switched on.

     The shape below matches the tracker's old /map-data payload on purpose, so each page's drawing
     code is unchanged. --}}
<script>
    window.eurekaMapRoutes = async function () {
        const res = await fetch('/api/map/routes', { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error('Map routes HTTP ' + res.status);

        const data = await res.json();

        return (data.routes || []).map(function (r) {
            const metres = r.distance_m || 0;

            return {
                session_id: r.id,
                session_name: r.name,
                color: r.color,
                distance: metres,
                distance_formatted: metres >= 1000 ? (metres / 1000).toFixed(2) + ' km' : metres + ' m',
                // Stored as [lng, lat] (GeoJSON order); the drawing code wants objects.
                route_points: (r.points || []).map(function (p) { return { lng: p[0], lat: p[1] }; }),
                markers: (r.markers || []).map(function (m) {
                    return {
                        lat: m.latitude, lng: m.longitude,
                        title: m.title, description: m.description,
                        icon: m.icon, color: m.color,
                    };
                }),
                // Pins that became posts are not in `markers` — these are their quest_location ids.
                quest_location_ids: r.quest_location_ids || [],
            };
        });
    };
</script>
