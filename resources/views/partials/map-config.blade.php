{{-- Shared basemap configuration for every map surface. See config/maps.php. --}}
<script>
    window.EUREKA_MAP = Object.assign({
        tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
        satellite: [],
        satelliteAttribution: ''
    }, @json(config('maps')));
</script>
