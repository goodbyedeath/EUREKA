{{-- Routes drawn in the GPS Tracker app and posts from Quest Locations, both served by EUREKA
     (App\Services\TrackerMapSync, Api\MapRouteController@web).

     The three map pages used to call tracker.questerra-series.com straight from the browser. That
     host has no CDN in front of it and its export is public, so during an event it was a second
     thing that had to stay up — and a screen on the venue WiFi could see routes the crew had not
     chosen. Now every map reads /api/map/routes, which serves only what the crew switched on.

     Posts matter here because a tracker pin that has become a post is deliberately dropped from
     `markers` (one place, one symbol). Without drawing posts, promoting every pin emptied the map. --}}
<script>
    (function () {
        function adaptRoutes(data) {
            // Shaped like the tracker's old /map-data payload, so each page's drawing code is unchanged.
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
                    quest_location_ids: r.quest_location_ids || [],
                };
            });
        }

        async function load() {
            const res = await fetch('/api/map/routes', { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Map routes HTTP ' + res.status);

            const data = await res.json();

            return { sessions: adaptRoutes(data), posts: data.posts || [] };
        }

        window.eurekaMapData = load;

        // Kept for callers that only draw the line.
        window.eurekaMapRoutes = async function () {
            return (await load()).sessions;
        };

        const drawn = new WeakMap();

        /**
         * Draw the check-in posts: the symbol only. The radius circle was dropped at the
         * operator's request (16 Sep) — on a venue-sized map the discs swamped the symbols.
         * Safe to call on every refresh: the previous markers are removed first.
         */
        window.eurekaDrawPosts = function (map, posts, options) {
            if (!map || !posts) return [];
            options = options || {};

            (drawn.get(map) || []).forEach(function (m) { m.remove(); });

            const markers = posts.map(function (p) {
                const el = document.createElement('div');
                el.style.cssText = 'display:flex;align-items:center;justify-content:center;width:34px;height:34px;'
                    + 'border-radius:50%;font-size:18px;line-height:1;cursor:pointer;'
                    + 'background:' + (p.color || '#3B82F6') + ';border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4)';
                // The symbol the crew drew in the tracker; a post typed in by hand has none.
                el.textContent = p.icon || '📍';
                el.title = p.name;

                const marker = new maplibregl.Marker({ element: el, anchor: 'center' })
                    .setLngLat([p.longitude, p.latitude]);

                if (!options.noPopup) {
                    const name = String(p.name || '').replace(/[<>&"]/g, '');
                    marker.setPopup(new maplibregl.Popup({ offset: 20 }).setHTML(
                        '<div style="font-family:system-ui;font-size:13px">'
                        + '<strong>' + name + '</strong><br>'
                        + 'Check-in radius ' + (p.radius || 0) + ' m'
                        + (p.points ? '<br>' + p.points + ' poin' : '')
                        + '</div>'
                    ));
                }

                return marker.addTo(map);
            });

            drawn.set(map, markers);

            return markers;
        };

        const fitted = new WeakSet();

        /** Frame everything once, so a venue far from the page's default centre is not off-screen. */
        window.eurekaFitOnce = function (map, data) {
            if (!map || fitted.has(map)) return;

            const coords = [];
            (data.sessions || []).forEach(function (s) {
                (s.route_points || []).forEach(function (p) { coords.push([p.lng, p.lat]); });
                (s.markers || []).forEach(function (m) { coords.push([parseFloat(m.lng), parseFloat(m.lat)]); });
            });
            (data.posts || []).forEach(function (p) { coords.push([p.longitude, p.latitude]); });

            if (!coords.length) return;

            const bounds = coords.reduce(function (b, c) { return b.extend(c); },
                new maplibregl.LngLatBounds(coords[0], coords[0]));

            map.fitBounds(bounds, { padding: 60, maxZoom: 16, duration: 0 });
            fitted.add(map);
        };
    })();
</script>
