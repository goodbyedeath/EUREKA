<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ \App\Models\BrandSetting::iconUrl() }}">
    <title>Editor 3D — {{ $gameLocation->name }}</title>
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0b1120; color: #e2e8f0; font-size: 14px; }
        a { color: #93c5fd; }
        button { font: inherit; cursor: pointer; }
        input, select, textarea { font: inherit; color: #f8fafc; background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 7px 9px; }
        input[type=range] { padding: 0; background: none; border: 0; accent-color: #818cf8; }
        input[type=checkbox] { accent-color: #818cf8; width: 16px; height: 16px; }
        label.f { display: block; font-size: 12px; color: #94a3b8; margin: 12px 0 5px; }
        .muted { color: #94a3b8; font-size: 12px; line-height: 1.45; }

        .app { display: grid; grid-template-columns: 230px 1fr 370px; grid-template-rows: 52px 1fr; height: 100vh; }
        header { grid-column: 1 / -1; display: flex; align-items: center; gap: 14px; padding: 0 16px; border-bottom: 1px solid #1e293b; background: #0f172a; }
        header h1 { font-size: 15px; margin: 0; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        header .sp { flex: 1; }
        .btn { border: 0; border-radius: 8px; padding: 7px 12px; background: #334155; color: #e2e8f0; }
        .btn:hover { background: #475569; }
        .btn.primary { background: #4f46e5; color: #fff; }
        .btn.primary:hover { background: #4338ca; }
        .btn.danger { background: #7f1d1d; color: #fecaca; }
        .btn.small { padding: 4px 9px; font-size: 12px; }
        .btn[disabled] { opacity: .5; cursor: default; }

        aside.list { border-right: 1px solid #1e293b; overflow: auto; padding: 12px; background: #0f172a; }
        .obj { display: block; width: 100%; text-align: left; border: 1px solid transparent; background: #111827; color: #e2e8f0; border-radius: 10px; padding: 9px 10px; margin-bottom: 6px; }
        .obj:hover { border-color: #334155; }
        .obj.on { border-color: #818cf8; background: #1e1b4b; }
        .obj small { display: block; color: #94a3b8; font-size: 11px; margin-top: 2px; }
        .dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: #f59e0b; margin-left: 6px; vertical-align: middle; }

        main.stage { position: relative; overflow: hidden; background: #0f172a; }
        #stage { position: absolute; inset: 0; }
        #stage canvas { display: block; touch-action: none; }
        .views { position: absolute; top: 10px; left: 10px; display: flex; gap: 6px; flex-wrap: wrap; }
        .views .btn.on { background: #4f46e5; color: #fff; }
        .legend { position: absolute; left: 10px; bottom: 10px; right: 10px; pointer-events: none; }
        .legend span { display: inline-block; background: rgba(15,23,42,.85); border-radius: 8px; padding: 6px 9px; font-size: 12px; color: #cbd5e1; }
        .tag { position: absolute; transform: translate(-50%, -50%); pointer-events: none; font-size: 11px; font-weight: 600; color: #fbbf24; text-shadow: 0 1px 2px #000; white-space: nowrap; }
        .tag.sel { color: #c7d2fe; }

        aside.form { border-left: 1px solid #1e293b; display: flex; flex-direction: column; background: #0f172a; min-height: 0; }
        .steps { display: grid; grid-template-columns: repeat(4, 1fr); border-bottom: 1px solid #1e293b; }
        .steps button { border: 0; background: none; color: #94a3b8; padding: 11px 4px; font-size: 12px; border-bottom: 2px solid transparent; }
        .steps button b { display: block; font-size: 11px; color: #64748b; }
        .steps button.on { color: #e0e7ff; border-bottom-color: #818cf8; }
        .panel { flex: 1; overflow: auto; padding: 4px 16px 16px; min-height: 0; }
        .row { display: flex; gap: 8px; align-items: center; }
        .row input[type=range] { flex: 1; }
        .row input[type=number] { width: 84px; text-align: right; }
        .row .u { width: 20px; color: #94a3b8; font-size: 12px; }
        .presets { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; }
        .motion { border: 1px solid #1e293b; border-radius: 10px; padding: 8px 10px; margin-bottom: 8px; }
        .motion .row.head { justify-content: space-between; }
        .motion .sub { margin-top: 6px; }
        .foot { border-top: 1px solid #1e293b; padding: 10px 16px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .foot .msg { flex: 1 1 100%; font-size: 12px; color: #94a3b8; min-height: 16px; }
        .empty { padding: 26px 16px; }
        .thumb { max-width: 100%; border-radius: 8px; margin-top: 8px; }

        @media (max-width: 1000px) {
            .app { grid-template-columns: 1fr; grid-template-rows: 52px auto 55vh auto; height: auto; }
            aside.list { border-right: 0; border-bottom: 1px solid #1e293b; max-height: 160px; }
            main.stage { height: 55vh; }
            aside.form { border-left: 0; }
        }
    </style>
</head>
<body>
<div class="app">
    <header>
        <a href="{{ route('admin.games') }}">&larr; Games</a>
        <h1>Editor 3D · {{ $gameLocation->name }}</h1>
        <span class="sp"></span>
        <a class="btn small" href="{{ route('ar.view', $gameLocation->id) }}" title="Buka halaman ini di HP, berdiri di pos, arahkan ke QR, lalu cek dan rapikan posisinya.">Cek di HP (AR) &rarr;</a>
    </header>

    <aside class="list">
        <button type="button" class="btn primary" id="new-obj" style="width:100%;margin-bottom:10px">+ Objek baru</button>
        <div id="objects"></div>
        <p class="muted" style="margin-top:12px">
            Pemain berdiri di tengah (hijau) menghadap arah kamera (panah kuning). Seret objek di layar untuk
            memindahkannya. Seret area kosong untuk memutar kamera, klik kanan/Shift untuk menggeser,
            scroll untuk zoom.
        </p>
    </aside>

    <main class="stage">
        <div id="stage"></div>
        <div class="views">
            <button type="button" class="btn small on" data-view="3d">3D</button>
            <button type="button" class="btn small" data-view="top">Denah (atas)</button>
            <button type="button" class="btn small" data-view="eye">Mata pemain</button>
            <label class="btn small" style="display:flex;gap:6px;align-items:center"><input type="checkbox" id="play" checked> Gerak</label>
        </div>
        <div id="tags"></div>
        <div class="legend"><span id="legend">Lingkaran abu-abu: 5, 10, 20, 30 m dari pemain. Lantai ±1,4 m di bawah HP.</span></div>
    </main>

    <aside class="form">
        <div class="steps">
            <button type="button" data-step="1" class="on"><b>1</b>Objek</button>
            <button type="button" data-step="2"><b>2</b>Posisi</button>
            <button type="button" data-step="3"><b>3</b>Arah &amp; Gerak</button>
            <button type="button" data-step="4"><b>4</b>Konten</button>
        </div>

        <div class="panel" id="panel-empty">
            <div class="empty muted">Pilih objek di daftar atau di layar, atau buat <b>+ Objek baru</b>.</div>
        </div>

        <div class="panel" data-panel="1" hidden>
            <label class="f" for="f-title">Nama objek</label>
            <input id="f-title" data-field="title" maxlength="255" placeholder="mis. Koin emas" style="width:100%">

            <label class="f" for="f-model">Model 3D</label>
            <select id="f-model" data-field="model_id" style="width:100%">
                <option value="">— model pos (bawaan) —</option>
            </select>
            <p class="muted">Model diambil dari 3D Model Library di halaman Games.</p>

            <label class="f">Ukuran</label>
            <div class="row">
                <input type="range" id="f-scale-r" min="0" max="1000" step="1">
                <input type="number" id="f-scale" data-field="scale" min="0.01" max="50" step="0.01">
                <span class="u">×</span>
            </div>
            <p class="muted" id="real-size">&nbsp;</p>
        </div>

        <div class="panel" data-panel="2" hidden>
            <p class="muted">Posisi dihitung dari tempat pemain berdiri saat memindai QR. Arah 0° = lurus ke arah kamera (arah HP saat kalibrasi di QR), positif = ke kanan.</p>
            <label class="f">Arah dari pemain</label>
            <div class="row">
                <input type="range" data-field="bearing" data-mirror="f-bearing" min="-180" max="180" step="1">
                <input type="number" id="f-bearing" data-field="bearing" min="-180" max="180" step="1">
                <span class="u">°</span>
            </div>
            <label class="f">Jarak mendatar</label>
            <div class="row">
                <input type="range" data-field="horizontal" data-mirror="f-horizontal" min="0" max="50" step="0.1">
                <input type="number" id="f-horizontal" data-field="horizontal" min="0" max="50" step="0.1">
                <span class="u">m</span>
            </div>
            <label class="f">Tinggi dari HP</label>
            <div class="row">
                <input type="range" data-field="height" data-mirror="f-height" min="-10" max="10" step="0.1">
                <input type="number" id="f-height" data-field="height" min="-10" max="10" step="0.1">
                <span class="u">m</span>
            </div>
            <div class="presets">
                <button type="button" class="btn small" data-height="-1.4">Di lantai</button>
                <button type="button" class="btn small" data-height="0">Setinggi mata</button>
                <button type="button" class="btn small" data-height="1.5">Di atas kepala</button>
            </div>
            <p class="muted" id="pos-summary" style="margin-top:12px">&nbsp;</p>
        </div>

        <div class="panel" data-panel="3" hidden>
            <label class="f">Menghadap (putar Y)</label>
            <div class="row">
                <input type="range" data-field="ry" data-mirror="f-ry" min="-180" max="180" step="5">
                <input type="number" id="f-ry" data-field="ry" min="-360" max="360" step="1">
                <span class="u">°</span>
            </div>
            <div class="presets">
                <button type="button" class="btn small" id="face-player">Hadapkan ke pemain</button>
                <button type="button" class="btn small" id="face-away">Membelakangi pemain</button>
            </div>

            <label class="f">Miring (putar X)</label>
            <div class="row">
                <input type="range" data-field="rx" data-mirror="f-rx" min="-180" max="180" step="5">
                <input type="number" id="f-rx" data-field="rx" min="-360" max="360" step="1">
                <span class="u">°</span>
            </div>
            <div class="presets">
                <button type="button" class="btn small" data-rx="0">Rebah (0°)</button>
                <button type="button" class="btn small" data-rx="90">Berdiri (90°) — koin, papan</button>
            </div>

            <label class="f">Guling (putar Z)</label>
            <div class="row">
                <input type="range" data-field="rz" data-mirror="f-rz" min="-180" max="180" step="5">
                <input type="number" id="f-rz" data-field="rz" min="-360" max="360" step="1">
                <span class="u">°</span>
            </div>

            <label class="f" style="margin-top:18px">Gerakan (boleh lebih dari satu)</label>
            <div id="motions"></div>
            <p class="muted">Putar = berputar di tempat pada sumbu tegak, seperti gangsing. Untuk koin yang tipis, pilih <b>Berdiri (90°)</b> dulu.</p>
        </div>

        <div class="panel" data-panel="4" hidden>
            <label class="f" for="f-desc">Petunjuk saat objek diketuk</label>
            <textarea id="f-desc" data-field="description" rows="3" maxlength="2000" style="width:100%"></textarea>

            <label class="f" for="f-points">Poin</label>
            <input type="number" id="f-points" data-field="points" min="0" max="10000" step="1" style="width:120px">

            <label class="f" for="f-media">Saat diketuk, tampilkan</label>
            <select id="f-media" data-field="media_type" style="width:100%">
                <option value="none">Hanya petunjuk</option>
                <option value="link">Tautan</option>
                <option value="image">Gambar</option>
            </select>
            <div id="media-link" hidden>
                <label class="f" for="f-link">Alamat tautan</label>
                <input id="f-link" data-field="link" maxlength="2048" placeholder="https://…" style="width:100%">
            </div>
            <div id="media-image" hidden>
                <label class="f" for="f-image">Gambar (maks 5 MB)</label>
                <input type="file" id="f-image" accept="image/jpeg,image/png,image/webp,image/gif">
                <p class="muted" id="image-msg"></p>
                <img id="image-preview" class="thumb" alt="" hidden>
            </div>
        </div>

        <div class="foot" id="foot" hidden>
            <div class="msg" id="msg"></div>
            <button type="button" class="btn" id="prev">&larr; Kembali</button>
            <button type="button" class="btn" id="next">Lanjut &rarr;</button>
            <span style="flex:1"></span>
            <button type="button" class="btn danger" id="del">Hapus</button>
            <button type="button" class="btn primary" id="save">Simpan</button>
        </div>
    </aside>
</div>

<script type="application/json" id="ar-editor-config">@json($config)</script>

<script>
    // Pure placement maths, kept outside the module so it can be checked on its own.
    // Frame: player at the origin, y up, -z = towards the QR code (the heading every
    // hotspot's yaw is measured from). The server stores pitch/yaw/distance and derives
    //   x = d·cos(p)·sin(y),  y = d·sin(p),  z = -d·cos(p)·cos(y)
    // so the editor works in bearing / horizontal distance / height and converts.
    window.ArMath = {
        D2R: Math.PI / 180,
        fromPosition(p) {
            const h = Math.hypot(p.x, p.z);
            return { bearing: h < 1e-6 ? 0 : Math.atan2(p.x, -p.z) / this.D2R, horizontal: h, height: p.y };
        },
        toPosition(f) {
            const b = f.bearing * this.D2R;
            return { x: f.horizontal * Math.sin(b), y: f.height, z: -f.horizontal * Math.cos(b) };
        },
        // The controller's limits: distance 0.5–50 m, pitch ±π/2, yaw ±π.
        toPayload(f) {
            let h = Math.max(0, +f.horizontal || 0), y = +f.height || 0;
            let d = Math.hypot(h, y);
            if (d < 0.5) {
                if (d < 1e-6) { h = 0.5; } else { h *= 0.5 / d; y *= 0.5 / d; }
                d = 0.5;
            }
            if (d > 50) { h *= 50 / d; y *= 50 / d; d = 50; }
            const bearing = (((+f.bearing || 0) + 180) % 360 + 360) % 360 - 180;
            return {
                distance: +d.toFixed(3),
                pitch: +Math.atan2(y, h).toFixed(5),
                yaw: +(bearing * this.D2R).toFixed(5),
            };
        },
        // Heading that points a glTF model's front (+Z) at the player. Ry(θ) sends +Z to
        // (sin θ, 0, cos θ); from (x, z) the player lies along (-x, -z).
        faceBearing(f) {
            const p = this.toPosition(f);
            if (Math.hypot(p.x, p.z) < 0.01) return null;
            return Math.round(Math.atan2(-p.x, -p.z) / this.D2R);
        },
    };
</script>

<script type="importmap">
{ "imports": { "three": "/vendor/three/0.169.0/three.module.min.js" } }
</script>
<script type="module">
    import * as THREE from 'three';
    import { GLTFLoader } from '/vendor/three/0.169.0/loaders/GLTFLoader.js';

    const CFG = JSON.parse(document.getElementById('ar-editor-config').textContent);
    const M = window.ArMath;
    const D2R = Math.PI / 180, TAU = Math.PI * 2;
    const EYE = 1.4;                       // the phone is held ~1.4 m above the floor
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    const $ = id => document.getElementById(id);

    // ---------- scene ----------
    const wrap = $('stage');
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    wrap.appendChild(renderer.domElement);

    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0x0f172a);
    const camera = new THREE.PerspectiveCamera(50, 1, 0.05, 500);
    scene.add(new THREE.HemisphereLight(0xffffff, 0x334155, 2.2));
    const sun = new THREE.DirectionalLight(0xffffff, 1.6);
    sun.position.set(5, 10, 4);
    scene.add(sun);

    const grid = new THREE.GridHelper(100, 100, 0x475569, 0x1e293b);
    grid.position.y = -EYE;
    scene.add(grid);
    [5, 10, 20, 30].forEach(r => {
        const ring = new THREE.Mesh(new THREE.RingGeometry(r - 0.04, r + 0.04, 128),
            new THREE.MeshBasicMaterial({ color: 0x64748b, side: THREE.DoubleSide }));
        ring.rotation.x = -Math.PI / 2;
        ring.position.y = -EYE + 0.01;
        scene.add(ring);
    });

    const player = new THREE.Group();
    const body = new THREE.Mesh(new THREE.CylinderGeometry(0.2, 0.2, EYE - 0.15, 24),
        new THREE.MeshStandardMaterial({ color: 0x22c55e }));
    body.position.y = -EYE / 2 - 0.08;
    player.add(body);
    const phone = new THREE.Mesh(new THREE.BoxGeometry(0.09, 0.17, 0.02),
        new THREE.MeshStandardMaterial({ color: 0xf8fafc }));
    phone.position.z = -0.15;
    player.add(phone);
    player.add(new THREE.ArrowHelper(new THREE.Vector3(0, 0, -1), new THREE.Vector3(0, -EYE + 0.03, 0), 3, 0xf59e0b, 0.5, 0.3));
    scene.add(player);

    const selBox = new THREE.BoxHelper(new THREE.Object3D(), 0x818cf8);
    selBox.visible = false;
    scene.add(selBox);

    // ---------- camera ----------
    const orbit = { theta: 0, phi: 1.05, radius: 16, target: new THREE.Vector3(0, -0.6, -3) };
    const VIEWS = {
        '3d':  { theta: 0.35, phi: 1.05, radius: 16, target: [0, -0.6, -3] },
        'top': { theta: 0, phi: 0.02, radius: 34, target: [0, -EYE, -4] },
        'eye': { theta: 0, phi: Math.PI / 2 - 0.02, radius: 0.3, target: [0, 0, -0.3] },
    };
    function setView(name) {
        const v = VIEWS[name];
        orbit.theta = v.theta; orbit.phi = v.phi; orbit.radius = v.radius; orbit.target.set(...v.target);
        document.querySelectorAll('[data-view]').forEach(b => b.classList.toggle('on', b.dataset.view === name));
    }
    function placeCamera() {
        const t = orbit.target, r = orbit.radius, s = Math.sin(orbit.phi);
        camera.position.set(t.x + r * s * Math.sin(orbit.theta), t.y + r * Math.cos(orbit.phi), t.z + r * s * Math.cos(orbit.theta));
        camera.lookAt(t);
    }
    document.querySelectorAll('[data-view]').forEach(b => b.addEventListener('click', () => setView(b.dataset.view)));
    setView('3d');

    // ---------- items ----------
    const loader = new GLTFLoader();
    const items = [];
    let current = null;
    let step = 1;
    let playing = true;
    $('play').addEventListener('change', e => { playing = e.target.checked; });

    const libraryById = Object.fromEntries(CFG.library.map(m => [String(m.id), m]));
    const r2 = v => Math.round(v * 100) / 100;

    function formFrom(h) {
        const pos = M.fromPosition(h.position || { x: 0, y: 0, z: -3 });
        return {
            title: h.title || '', description: h.description || '', points: h.points ?? 0,
            model_id: h.model_id ? String(h.model_id) : '', scale: h.scale || 1,
            bearing: Math.round(pos.bearing), horizontal: r2(pos.horizontal), height: r2(pos.height),
            rx: h.rotation?.x || 0, ry: h.rotation?.y || 0, rz: h.rotation?.z || 0,
            motions: (h.animations || []).map(m => ({ type: m.type, speed: m.speed ?? 1, range: m.range ?? 1 })),
            media_type: h.media_type || 'none', link: h.link || '', image: h.image || null, media_path: null,
        };
    }

    function modelUrl(item) {
        const f = item.form;
        if (f.model_id && libraryById[f.model_id]) return libraryById[f.model_id].url;
        if (!f.model_id && item.saved && item.saved.model && !item.saved.model_id) return item.saved.model;
        return CFG.fallbackModel;
    }

    function placeholder() {
        return new THREE.Mesh(new THREE.BoxGeometry(0.4, 0.4, 0.4),
            new THREE.MeshStandardMaterial({ color: 0xa78bfa, transparent: true, opacity: 0.8 }));
    }

    function loadModel(item) {
        const holder = item.holder;
        const token = {};
        holder.userData.token = token;
        while (holder.children.length) holder.remove(holder.children[0]);
        holder.add(placeholder());
        holder.userData.size = null;
        const url = modelUrl(item);
        if (!url) { showRealSize(); return; }
        loader.load(url, g => {
            if (holder.userData.token !== token) return;
            while (holder.children.length) holder.remove(holder.children[0]);
            holder.add(g.scene);
            const size = new THREE.Box3().setFromObject(g.scene).getSize(new THREE.Vector3());
            holder.userData.size = size;
            showRealSize();
        }, undefined, () => { if (item === current) $('real-size').textContent = 'Model gagal dimuat — kotak ungu dipakai sebagai gantinya.'; });
    }

    function addItem(saved, form) {
        const holder = new THREE.Group();
        holder.rotation.order = 'YXZ';
        scene.add(holder);
        const item = { saved, form: form || formFrom(saved), holder, dirty: !saved };
        holder.userData.item = item;
        items.push(item);
        loadModel(item);
        return item;
    }

    // Same motion maths as animateObjects() in ar/view.blade.php — the preview must match the phone.
    function pose(item, t) {
        const f = item.form, h = item.holder, p = M.toPosition(f);
        let spin = 0, bob = 0, around = 0;
        const still = !playing || (drag && drag.mode === 'move' && drag.item === item);
        if (!still) {
            for (const a of f.motions) {
                const sp = a.speed || 1, rg = a.range || 0;
                if (a.type === 'spin') spin += t * sp * 30 * D2R;
                else if (a.type === 'bob') bob += Math.sin(TAU * t * sp / 4) * rg;
                else if (a.type === 'orbit') around += t * sp * 12 * D2R;
                else if (a.type === 'sway') around += Math.sin(TAU * t * sp / 6) * rg * D2R;
            }
        }
        h.scale.setScalar(f.scale || 1);
        h.rotation.set(f.rx * D2R, f.ry * D2R + spin, f.rz * D2R, 'YXZ');
        const c = Math.cos(around), s = Math.sin(around);
        h.position.set(p.x * c - p.z * s, p.y + bob, p.x * s + p.z * c);
    }

    // ---------- list ----------
    function renderList() {
        const box = $('objects');
        box.innerHTML = '';
        if (!items.length) {
            box.innerHTML = '<p class="muted">Belum ada objek di pos ini.</p>';
            return;
        }
        items.forEach(item => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'obj' + (item === current ? ' on' : '');
            const f = item.form;
            const model = f.model_id && libraryById[f.model_id] ? libraryById[f.model_id].name : 'model pos';
            b.innerHTML = '<span></span><small></small>';
            b.firstChild.textContent = f.title || '(tanpa nama)';
            if (item.dirty) b.firstChild.insertAdjacentHTML('afterend', '<span class="dot" title="Belum disimpan"></span>');
            b.querySelector('small').textContent = model + ' · ' + r2(f.horizontal) + ' m, ' + Math.round(f.bearing) + '°'
                + (item.saved ? '' : ' · baru');
            b.addEventListener('click', () => select(item));
            box.appendChild(b);
        });
    }

    // ---------- form ----------
    const modelSel = $('f-model');
    CFG.library.forEach(m => {
        const o = document.createElement('option');
        o.value = String(m.id);
        o.textContent = m.name + ' (' + m.size + ')';
        modelSel.appendChild(o);
    });
    if (!CFG.fallbackModel) modelSel.options[0].textContent = '— pilih model —';

    const SCALE_MIN = 0.01, SCALE_MAX = 50, LOG_MIN = Math.log(SCALE_MIN), LOG_SPAN = Math.log(SCALE_MAX) - LOG_MIN;
    const toSlider = s => Math.round(((Math.log(Math.min(SCALE_MAX, Math.max(SCALE_MIN, s))) - LOG_MIN) / LOG_SPAN) * 1000);
    const fromSlider = v => +Math.exp(LOG_MIN + (v / 1000) * LOG_SPAN).toFixed(3);

    const MOTIONS = [
        { type: 'spin', label: 'Putar di tempat', range: null },
        { type: 'bob', label: 'Naik turun', range: { min: 0, max: 10, step: 0.1, unit: 'm', def: 0.3 } },
        { type: 'orbit', label: 'Mengelilingi pemain', range: null },
        { type: 'sway', label: 'Bergoyang kiri-kanan', range: { min: 0, max: 90, step: 1, unit: '°', def: 20 } },
    ];
    function renderMotions() {
        const box = $('motions');
        box.innerHTML = '';
        MOTIONS.forEach(def => {
            const m = current.form.motions.find(x => x.type === def.type);
            const el = document.createElement('div');
            el.className = 'motion';
            el.innerHTML =
                '<div class="row head"><label style="display:flex;gap:8px;align-items:center"><input type="checkbox" class="on"> <span></span></label></div>'
                + '<div class="sub" hidden><div class="row"><span class="muted" style="width:52px">Cepat</span><input type="range" class="speed" min="0.1" max="5" step="0.1"><input type="number" class="speed-n" min="0.1" max="5" step="0.1"><span class="u">×</span></div>'
                + (def.range ? '<div class="row" style="margin-top:6px"><span class="muted" style="width:52px">Jarak</span><input type="range" class="range"><input type="number" class="range-n"><span class="u"></span></div>' : '')
                + '</div>';
            el.querySelector('label span').textContent = def.label;
            const on = el.querySelector('.on'), sub = el.querySelector('.sub');
            const speed = el.querySelector('.speed'), speedN = el.querySelector('.speed-n');
            const range = el.querySelector('.range'), rangeN = el.querySelector('.range-n');
            if (range) {
                [range, rangeN].forEach(i => { i.min = def.range.min; i.max = def.range.max; i.step = def.range.step; });
                el.querySelector('.range-n + .u').textContent = def.range.unit;
            }
            const sync = () => {
                on.checked = !!m;
                sub.hidden = !m;
                speed.value = speedN.value = m ? m.speed : 1;
                if (range) range.value = rangeN.value = m ? m.range : def.range.def;
            };
            let mm = m;
            const read = () => {
                const list = current.form.motions.filter(x => x.type !== def.type);
                if (on.checked) {
                    mm = { type: def.type, speed: clamp(+speedN.value || 1, 0.1, 5), range: range ? clamp(+rangeN.value || 0, def.range.min, def.range.max) : 1 };
                    list.push(mm);
                }
                current.form.motions = MOTIONS.map(d => list.find(x => x.type === d.type)).filter(Boolean);
                sub.hidden = !on.checked;
                touched();
            };
            on.addEventListener('change', read);
            speed.addEventListener('input', () => { speedN.value = speed.value; read(); });
            speedN.addEventListener('input', () => { speed.value = speedN.value; read(); });
            if (range) {
                range.addEventListener('input', () => { rangeN.value = range.value; read(); });
                rangeN.addEventListener('input', () => { range.value = rangeN.value; read(); });
            }
            sync();
            box.appendChild(el);
        });
    }

    const clamp = (v, lo, hi) => Math.min(hi, Math.max(lo, v));
    const NUMERIC = new Set(['scale', 'bearing', 'horizontal', 'height', 'rx', 'ry', 'rz', 'points']);

    function fillForm() {
        const f = current.form;
        document.querySelectorAll('[data-field]').forEach(el => {
            const v = f[el.dataset.field];
            if (el.type === 'range' && el.id !== 'f-scale-r') el.value = v;
            else if (el.tagName === 'SELECT' || el.type !== 'range') el.value = v ?? '';
        });
        $('f-scale').value = f.scale;
        $('f-scale-r').value = toSlider(f.scale);
        renderMotions();
        syncMedia();
        showRealSize();
        showSummary();
        $('image-msg').textContent = f.image ? 'Gambar sekarang dipakai kecuali Anda memilih yang baru.' : '';
        $('image-preview').hidden = !f.image;
        if (f.image) $('image-preview').src = f.image;
        $('f-image').value = '';
        $('del').textContent = current.saved ? 'Hapus' : 'Buang';
    }

    function readField(el) {
        if (!current) return;
        const k = el.dataset.field;
        let v = el.value;
        if (NUMERIC.has(k)) {
            v = parseFloat(v);
            if (!isFinite(v)) return;
        }
        if (k === 'bearing') v = clamp(v, -180, 180);
        if (k === 'horizontal') v = clamp(v, 0, 50);
        if (k === 'height') v = clamp(v, -10, 10);
        if (k === 'scale') v = clamp(v, SCALE_MIN, SCALE_MAX);
        current.form[k] = v;
        // Keep the slider and its number box in step.
        document.querySelectorAll('[data-field="' + k + '"]').forEach(o => { if (o !== el && o.id !== 'f-scale') o.value = v; });
        if (k === 'scale') $('f-scale-r').value = toSlider(v);
        if (k === 'model_id') loadModel(current);
        if (k === 'media_type') syncMedia();
        if (k === 'scale') showRealSize();
        touched();
    }
    document.querySelectorAll('[data-field]').forEach(el =>
        el.addEventListener(el.tagName === 'SELECT' ? 'change' : 'input', () => readField(el)));
    $('f-scale-r').addEventListener('input', e => {
        if (!current) return;
        current.form.scale = fromSlider(+e.target.value);
        $('f-scale').value = current.form.scale;
        showRealSize();
        touched();
    });

    function setField(k, v) {
        current.form[k] = v;
        document.querySelectorAll('[data-field="' + k + '"]').forEach(o => o.value = v);
        touched();
    }
    document.querySelectorAll('[data-height]').forEach(b => b.addEventListener('click', () => current && setField('height', +b.dataset.height)));
    document.querySelectorAll('[data-rx]').forEach(b => b.addEventListener('click', () => current && setField('rx', +b.dataset.rx)));
    $('face-player').addEventListener('click', () => {
        if (!current) return;
        const deg = M.faceBearing(current.form);
        if (deg === null) { msg('Objek tepat di atas pemain — geser dulu agar punya arah.'); return; }
        setField('ry', deg);
    });
    $('face-away').addEventListener('click', () => {
        if (!current) return;
        const deg = M.faceBearing(current.form);
        if (deg === null) return;
        setField('ry', deg > 0 ? deg - 180 : deg + 180);
    });

    function syncMedia() {
        const t = current.form.media_type;
        $('media-link').hidden = t !== 'link';
        $('media-image').hidden = t !== 'image';
    }

    $('f-image').addEventListener('change', async e => {
        const file = e.target.files[0];
        if (!file || !current) return;
        const item = current;
        $('image-msg').textContent = 'Mengunggah…';
        const fd = new FormData();
        fd.append('image', file);
        try {
            const res = await fetch(CFG.urls.media, { method: 'POST', body: fd, headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
            const d = await res.json().catch(() => null);
            if (!res.ok || !d || !d.success) throw new Error(reason(d, res));
            item.form.media_path = d.path;
            item.form.image = d.url;
            if (item === current) {
                $('image-msg').textContent = 'Terunggah. Simpan untuk memakainya.';
                $('image-preview').src = d.url;
                $('image-preview').hidden = false;
            }
            item.dirty = true;
            renderList();
        } catch (err) {
            if (item === current) $('image-msg').textContent = err.message || 'Gagal mengunggah.';
        }
    });

    function showRealSize() {
        if (!current) return;
        const s = current.holder.userData.size;
        $('real-size').textContent = s
            ? 'Ukuran nyata ≈ ' + [s.x, s.y, s.z].map(v => r2(v * current.form.scale)).join(' × ') + ' m (lebar × tinggi × tebal)'
            : 'Memuat model…';
    }
    function showSummary() {
        const f = current.form;
        const side = Math.abs(f.bearing) < 3 ? 'lurus ke arah kamera' : (f.bearing > 0 ? Math.round(f.bearing) + '° ke kanan' : Math.round(-f.bearing) + '° ke kiri');
        const pay = M.toPayload(f);
        $('pos-summary').textContent = r2(f.horizontal) + ' m ' + side + ', ' + (f.height >= 0 ? r2(f.height) + ' m di atas' : r2(-f.height) + ' m di bawah') + ' HP · jarak langsung ' + pay.distance + ' m';
    }

    function touched() {
        if (!current) return;
        current.dirty = true;
        showSummary();
        renderList();
    }

    function msg(t) { $('msg').textContent = t || ''; }
    // Say what actually failed. A bare "Gagal menyimpan" left a 404 in the console as the
    // only clue; the status and the likely cause are what the operator needs to act on.
    function reason(d, res) {
        const status = res ? res.status : 0;
        if (status === 404) return 'Objek ini tidak ditemukan di server (mungkin sudah dihapus di perangkat lain). Muat ulang halaman. (HTTP 404)';
        if (status === 419) return 'Sesi login habis. Muat ulang halaman lalu simpan lagi. (HTTP 419)';
        if (status === 413) return 'File terlalu besar untuk server. (HTTP 413)';
        if (d && d.errors) return Object.values(d.errors).flat()[0];
        if (d && d.message) return d.message + (status >= 400 ? ' (HTTP ' + status + ')' : '');
        return 'Gagal menyimpan' + (status >= 400 ? ' (HTTP ' + status + ')' : '') + '.';
    }

    function goStep(n) {
        step = clamp(n, 1, 4);
        document.querySelectorAll('[data-step]').forEach(b => b.classList.toggle('on', +b.dataset.step === step));
        document.querySelectorAll('[data-panel]').forEach(p => p.hidden = !current || +p.dataset.panel !== step);
        $('panel-empty').hidden = !!current;
        $('foot').hidden = !current;
        $('prev').disabled = step === 1;
        $('next').disabled = step === 4;
    }
    document.querySelectorAll('[data-step]').forEach(b => b.addEventListener('click', () => goStep(+b.dataset.step)));
    $('prev').addEventListener('click', () => goStep(step - 1));
    $('next').addEventListener('click', () => goStep(step + 1));

    function select(item) {
        if (item === current) return;
        current = item;
        selBox.visible = !!item;
        msg('');
        if (item) fillForm();
        renderList();
        goStep(item ? step : 1);
    }

    $('new-obj').addEventListener('click', () => {
        // Three metres ahead, a little to the side of anything already straight ahead.
        const taken = items.filter(i => Math.abs(i.form.bearing) < 10).length;
        const item = addItem(null, {
            title: '', description: '', points: 0,
            model_id: CFG.fallbackModel ? '' : (CFG.library[0] ? String(CFG.library[0].id) : ''),
            scale: 1, bearing: taken ? 20 * taken : 0, horizontal: 3, height: 0,
            rx: 0, ry: 0, rz: 0, motions: [], media_type: 'none', link: '', image: null, media_path: null,
        });
        item.form.ry = M.faceBearing(item.form) ?? 0;
        select(item);
        goStep(1);
        $('f-title').focus();
    });

    $('save').addEventListener('click', async () => {
        if (!current) return;
        const item = current, f = item.form;
        if (!f.title.trim()) { goStep(1); msg('Isi nama objek dulu.'); $('f-title').focus(); return; }
        if (!f.model_id && !CFG.fallbackModel) { goStep(1); msg('Pilih model 3D dulu.'); return; }
        if (f.media_type === 'link' && !/^https?:\/\//i.test(f.link.trim())) { goStep(4); msg('Tautan harus diawali http:// atau https://'); return; }
        if (f.media_type === 'image' && !f.media_path && !f.image) { goStep(4); msg('Pilih gambar dulu, atau ganti ke "Hanya petunjuk".'); return; }

        const body = {
            ...M.toPayload(f),
            scale: +(+f.scale).toFixed(3),
            rotation_x: +f.rx, rotation_y: +f.ry, rotation_z: +f.rz,
            ar_model_id: f.model_id ? parseInt(f.model_id, 10) : null,
            ar_motions: f.motions.map(m => ({ type: m.type, speed: m.speed, range: m.range })),
            title: f.title.trim(),
            description: f.description.trim() || null,
            points_value: Math.max(0, parseInt(f.points, 10) || 0),
            media_type: f.media_type,
            content: f.media_type === 'link' ? f.link.trim() : null,
            media_path: f.media_type === 'image' ? f.media_path : null,
        };
        const url = item.saved ? CFG.urls.update.replace('__ID__', item.saved.id) : CFG.urls.store;
        $('save').disabled = true;
        msg('Menyimpan…');
        try {
            const res = await fetch(url, {
                method: item.saved ? 'PATCH' : 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify(body),
            });
            const d = await res.json().catch(() => null);
            if (!res.ok || !d || !d.success) throw new Error(reason(d, res));
            // Re-read the server's numbers: it re-derives the position, and the phone uses those.
            item.saved = d.hotspot;
            item.form = formFrom(d.hotspot);
            item.dirty = false;
            if (item === current) { fillForm(); msg('Tersimpan. Cek di HP saat di lokasi.'); }
            renderList();
        } catch (err) {
            msg(err.message || 'Gagal menyimpan.');
        } finally {
            $('save').disabled = false;
        }
    });

    $('del').addEventListener('click', async () => {
        if (!current) return;
        const item = current;
        if (item.saved) {
            if (!confirm('Hapus "' + (item.form.title || 'objek') + '" dari pos ini? Pemain tidak akan melihatnya lagi.')) return;
            msg('Menghapus…');
            try {
                const res = await fetch(CFG.urls.destroy.replace('__ID__', item.saved.id), {
                    method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                });
                const d = await res.json().catch(() => null);
                if (!res.ok || !d || !d.success) throw new Error(reason(d, res));
            } catch (err) { msg(err.message || 'Gagal menghapus.'); return; }
        }
        scene.remove(item.holder);
        items.splice(items.indexOf(item), 1);
        select(null);                          // clears current, hides the form and the box
        msg('');
    });

    window.addEventListener('beforeunload', e => {
        if (items.some(i => i.dirty)) { e.preventDefault(); e.returnValue = ''; }
    });

    // ---------- pointer: pick, drag on the ground plane, orbit, pan, zoom ----------
    const ray = new THREE.Raycaster();
    const ndc = new THREE.Vector2();
    let drag = null;

    function setRay(e) {
        const r = renderer.domElement.getBoundingClientRect();
        ndc.set(((e.clientX - r.left) / r.width) * 2 - 1, -((e.clientY - r.top) / r.height) * 2 + 1);
        ray.setFromCamera(ndc, camera);
    }
    function pick(e) {
        setRay(e);
        const hits = ray.intersectObjects(items.map(i => i.holder), true);
        for (const hit of hits) {
            let o = hit.object;
            while (o && !o.userData.item) o = o.parent;
            if (o) return o.userData.item;
        }
        return null;
    }
    function groundAt(e, y) {
        setRay(e);
        const p = new THREE.Vector3();
        return ray.ray.intersectPlane(new THREE.Plane(new THREE.Vector3(0, 1, 0), -y), p) ? p : null;
    }

    const canvas = renderer.domElement;
    canvas.addEventListener('contextmenu', e => e.preventDefault());
    canvas.addEventListener('pointerdown', e => {
        canvas.setPointerCapture(e.pointerId);
        const item = e.button === 0 && !e.shiftKey ? pick(e) : null;
        if (item) {
            select(item);
            const pos = M.toPosition(item.form);
            const g = groundAt(e, item.form.height);
            drag = { mode: 'move', item, dx: g ? pos.x - g.x : 0, dz: g ? pos.z - g.z : 0 };
            if (step !== 2) goStep(2);
        } else {
            drag = { mode: e.button === 2 || e.shiftKey ? 'pan' : 'orbit', x: e.clientX, y: e.clientY };
        }
    });
    canvas.addEventListener('pointermove', e => {
        if (!drag) return;
        if (drag.mode === 'move') {
            const f = drag.item.form;
            const g = groundAt(e, f.height);
            if (!g) return;
            const x = g.x + drag.dx, z = g.z + drag.dz;
            const h = Math.min(50, Math.hypot(x, z));
            f.horizontal = Math.round(h * 10) / 10;
            f.bearing = h < 1e-6 ? f.bearing : Math.round(Math.atan2(x, -z) / D2R);
            ['bearing', 'horizontal'].forEach(k => document.querySelectorAll('[data-field="' + k + '"]').forEach(o => o.value = f[k]));
            touched();
            return;
        }
        const dx = e.clientX - drag.x, dy = e.clientY - drag.y;
        drag.x = e.clientX; drag.y = e.clientY;
        if (drag.mode === 'orbit') {
            orbit.theta -= dx * 0.006;
            orbit.phi = clamp(orbit.phi - dy * 0.006, 0.02, Math.PI - 0.02);
        } else {
            const k = orbit.radius * 0.0018;
            const right = new THREE.Vector3().setFromMatrixColumn(camera.matrix, 0);
            const fwd = new THREE.Vector3(Math.sin(orbit.theta), 0, Math.cos(orbit.theta));
            orbit.target.addScaledVector(right, -dx * k).addScaledVector(fwd, -dy * k);
        }
    });
    const endDrag = () => { drag = null; };
    canvas.addEventListener('pointerup', endDrag);
    canvas.addEventListener('pointercancel', endDrag);
    canvas.addEventListener('wheel', e => {
        e.preventDefault();
        orbit.radius = clamp(orbit.radius * Math.exp(e.deltaY * 0.001), 0.3, 150);
    }, { passive: false });

    // ---------- labels ----------
    const tags = $('tags');
    const qrTag = document.createElement('div');
    qrTag.className = 'tag';
    qrTag.textContent = 'Arah Kamera';
    tags.appendChild(qrTag);
    const selTag = document.createElement('div');
    selTag.className = 'tag sel';
    tags.appendChild(selTag);
    const v3 = new THREE.Vector3();
    function placeTag(el, point) {
        v3.copy(point).project(camera);
        const visible = v3.z < 1 && Math.abs(v3.x) <= 1.1 && Math.abs(v3.y) <= 1.1;
        el.style.display = visible ? 'block' : 'none';
        if (!visible) return;
        el.style.left = ((v3.x + 1) / 2 * wrap.clientWidth) + 'px';
        el.style.top = ((1 - v3.y) / 2 * wrap.clientHeight) + 'px';
    }

    // ---------- loop ----------
    const T0 = performance.now();
    function frame() {
        const w = wrap.clientWidth, h = wrap.clientHeight;
        if (canvas.width !== Math.floor(w * renderer.getPixelRatio()) || canvas.height !== Math.floor(h * renderer.getPixelRatio())) {
            renderer.setSize(w, h, false);
            canvas.style.width = w + 'px';
            canvas.style.height = h + 'px';
            camera.aspect = w / Math.max(1, h);
            camera.updateProjectionMatrix();
        }
        const t = (performance.now() - T0) / 1000;
        items.forEach(i => pose(i, t));
        placeCamera();
        if (current) {
            selBox.setFromObject(current.holder);
            placeTag(selTag, new THREE.Vector3(current.holder.position.x, current.holder.position.y + 0.6, current.holder.position.z));
            selTag.textContent = current.form.title || '(objek baru)';
        } else {
            selTag.style.display = 'none';
        }
        placeTag(qrTag, new THREE.Vector3(0, -EYE, -3.6));
        renderer.render(scene, camera);
        requestAnimationFrame(frame);
    }

    CFG.hotspots.forEach(h => addItem(h));
    renderList();
    goStep(1);
    requestAnimationFrame(frame);
</script>
</body>
</html>
