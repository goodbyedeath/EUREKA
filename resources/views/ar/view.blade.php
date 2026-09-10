<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $gameLocation->name }} — AR</title>
    <style>
        *{box-sizing:border-box}
        html,body{margin:0;height:100%;overflow:hidden;background:#000;color:#f8fafc;
                  font-family:system-ui,-apple-system,sans-serif;overscroll-behavior:none;
                  -webkit-user-select:none;user-select:none}
        #cam,#gl{position:fixed;inset:0;width:100%;height:100%}
        #cam{object-fit:cover;z-index:0}
        #gl{z-index:1}

        /* z-index 36: above the placement sheet (35) so the button stays reachable
           while the sheet is open, and below the start gate (50) so it is covered
           until the scene exists. No script decides any of this. */
        .bar{position:fixed;top:0;left:0;right:0;z-index:36;display:flex;align-items:center;gap:12px;
             padding:calc(env(safe-area-inset-top) + 10px) 16px 10px;
             background:linear-gradient(#000c,#0000)}
        .bar a{color:#e2e8f0;text-decoration:none;font-size:14px}
        .bar h1{margin:0;font-size:15px;font-weight:600;flex:1}
        #fovbar{position:fixed;left:0;right:0;z-index:36;pointer-events:none;
                top:calc(env(safe-area-inset-top) + 52px);padding:0 16px;text-align:right}
        #fovbar > *{pointer-events:auto}
        #fov-toggle{padding:5px 11px;border:0;border-radius:9999px;background:#0009;color:#cbd5e1;
                    font-size:12px;font-weight:600;cursor:pointer}
        #fov-panel{margin-top:6px;padding:12px;border-radius:12px;background:#0f172aee;
                   text-align:left;max-width:340px;margin-left:auto}
        #fov-panel p{margin:0 0 10px;font-size:12px;line-height:1.5;color:#cbd5e1}
        .fov-row{display:flex;align-items:center;gap:8px}
        .fov-row input{flex:1}
        #fov-val{font-size:12px;color:#f8fafc;width:44px;text-align:right;font-variant-numeric:tabular-nums}
        #fov-reset{padding:5px 9px;border:0;border-radius:6px;background:#334155;color:#cbd5e1;font-size:11px;cursor:pointer}
        #fov-note{display:block;margin-top:8px;font-size:11px;color:#64748b}
        #p-toggle{flex:0 0 auto;padding:8px 14px;border:0;border-radius:9999px;
                  background:#7c3aed;color:#fff;font-weight:700;font-size:13px;cursor:pointer;
                  white-space:nowrap;box-shadow:0 2px 10px #0006}
        #p-toggle:active{background:#6d28d9}
        #p-preview{flex:0 0 auto;padding:8px 12px;border:0;border-radius:9999px;
                   background:#0009;color:#cbd5e1;font-weight:600;font-size:13px;cursor:pointer;
                   white-space:nowrap}
        #p-preview.on{background:#0ea5e9;color:#032e3f}
        .count{font-size:13px;font-weight:700;color:#4ade80;background:#0008;
               padding:4px 10px;border-radius:9999px}

        /* Reticle — tells the team where "tap" will land. */
        #reticle{position:fixed;left:50%;top:50%;width:44px;height:44px;margin:-22px 0 0 -22px;z-index:4;
                 border:2px solid #ffffff88;border-radius:50%;pointer-events:none;transition:.15s}
        #reticle.hot{border-color:#4ade80;box-shadow:0 0 0 6px #4ade8033;transform:scale(1.15)}

        #hint{position:fixed;left:16px;right:16px;bottom:calc(env(safe-area-inset-bottom) + 24px);
              z-index:5;text-align:center;font-size:13px;color:#e2e8f0;text-shadow:0 1px 4px #000}
        /* Objects sit at whatever bearing they were authored at — often behind you.
           Without a pointer they read as "missing" rather than "off screen". */
        #guide{position:fixed;left:50%;transform:translateX(-50%);
               bottom:calc(env(safe-area-inset-bottom) + 52px);z-index:6;
               padding:7px 14px;border-radius:9999px;background:#0f172ad9;color:#f8fafc;
               font-size:13px;font-weight:600;white-space:nowrap;pointer-events:none}
        #recenter{position:fixed;left:16px;bottom:calc(env(safe-area-inset-bottom) + 70px);z-index:36;
                  padding:9px 14px;border:0;border-radius:9999px;background:#1e293bd9;color:#cbd5e1;
                  font-size:12px;font-weight:600}

        /* Calibration / permission gate */
        /* z-index 50: the start gate is the opening screen and must cover everything,
           including the authoring pill (36) and Re-centre (36). It used to sit at 30,
           so those two floated on top of it before the scene existed. Hiding them with
           JS instead put the authoring button behind a line of script that has to run —
           and when it did not, the button was simply gone. Stacking cannot fail. */
        #gate{position:fixed;inset:0;z-index:50;background:#0f172a;display:flex;align-items:center;
              justify-content:center;padding:28px;text-align:center}
        #gate div{max-width:340px}
        #gate h2{margin:0 0 10px;font-size:20px}
        #gate p{margin:0 0 22px;font-size:14px;line-height:1.55;color:#cbd5e1}
        #gate button{width:100%;padding:14px;border:0;border-radius:12px;background:#22c55e;
                     color:#052e16;font-weight:700;font-size:16px;cursor:pointer}
        #gate small{display:block;margin-top:14px;color:#64748b;font-size:12px}

        .sheet{position:fixed;left:0;right:0;bottom:0;z-index:40;transform:translateY(110%);
               transition:transform .25s ease;background:#1e293b;border-radius:18px 18px 0 0;
               padding:22px 20px calc(env(safe-area-inset-bottom) + 26px);max-height:72vh;overflow:auto;
               box-shadow:0 -8px 30px #000a}
        .sheet.open{transform:translateY(0)}
        .sheet h2{margin:0 0 8px;font-size:19px}
        .sheet p{margin:0 0 16px;font-size:14px;line-height:1.6;color:#cbd5e1;white-space:pre-wrap}
        .pill{display:inline-block;background:#22c55e22;color:#4ade80;border-radius:9999px;
              padding:4px 12px;font-size:12px;font-weight:700;margin-bottom:10px}
        .sheet button{width:100%;padding:13px;border:0;border-radius:12px;background:#22c55e;
                      color:#052e16;font-weight:700;font-size:15px;cursor:pointer}
    </style>
</head>
<body>
    <video id="cam" playsinline muted autoplay></video>
    <canvas id="gl"></canvas>
    <div id="reticle"></div>

    <div class="bar">
        <a href="{{ $isAdmin ? route('admin.games') : route('user.game-dashboard') }}">&larr; {{ __('Back') }}</a>
        <h1>{{ $gameLocation->name }}</h1>
        <span class="count" id="count">0/{{ $objectCount }}</span>
        @if($isAdmin)
            {{-- The authoring control lives in the bar, not in a floating pill at the
                 bottom of the screen.

                 The pill was missed on both a laptop and a phone: it sat in a corner, at a
                 z-index that fought the start gate, and at one point behind a line of
                 script that had to run before it appeared. A control nobody can find is
                 the same as a control that does not exist. The bar is always on screen,
                 always rendered, and this button is simply part of it. --}}
            <button type="button" id="p-toggle">+ {{ __('Object') }}</button>
            {{-- An admin tapping an object always got the editor, so the thing they
                 had just authored — the message, the picture — could not be tried at
                 all from this side. This flips the tap to what a team gets. --}}
            <button type="button" id="p-preview">{{ __('Preview') }}</button>
        @endif
    </div>

    @if($isAdmin)
    {{-- Lens calibration. Anchored under the bar rather than floating, for the same
         reason the authoring button moved there: this is the one region of the screen
         that is always visible and never fights the gate or the sheet.

         Collapsed by default — it is set once per device and then forgotten. --}}
    <div id="fovbar">
        <button type="button" id="fov-toggle" title="{{ __('Match the 3D camera to this phone') }}">⌖ {{ __('Lens') }}</button>
        <div id="fov-panel" hidden>
            <p>{{ __('Put an object against something real — a doorway, a table edge. Turn the phone slowly. If the object slides off, drag until it stays put.') }}</p>
            <div class="fov-row">
                <input type="range" id="fov-range" min="40" max="120" step="1">
                <span id="fov-val"></span>
                <button type="button" id="fov-reset">{{ __('Reset') }}</button>
            </div>
            <small id="fov-note"></small>
        </div>
    </div>
    @endif

    <p id="hint">{{ __('Look around to find the objects') }}</p>
    <div id="guide" hidden></div>
    <button type="button" id="recenter" hidden>{{ __('Re-centre') }}</button>

    <div id="gate">
        <div>
            <h2>{{ __('Point at the QR code') }}</h2>
            <p>{{ __('Stand where the QR code is and point your phone straight at it, then tap Start. Objects are placed relative to this direction.') }}</p>
            <button type="button" id="start">{{ __('Start') }}</button>
            <small id="gate-note"></small>
        </div>
    </div>

    <div class="sheet" id="sheet">
        <span class="pill" id="sheet-pts" hidden></span>
        <h2 id="sheet-title"></h2>
        <p id="sheet-desc"></p>
        <img id="sheet-img" alt="" style="display:none;width:100%;border-radius:12px;margin-bottom:14px">
        <a id="sheet-link" target="_blank" rel="noopener noreferrer"
           style="display:none;margin-bottom:12px;padding:12px;border-radius:12px;background:#1d4ed8;color:#fff;
                  font-weight:700;font-size:15px;text-align:center;text-decoration:none"></a>
        <button type="button" id="sheet-close">{{ __('Close') }}</button>
    </div>

    @if($isAdmin)
    {{-- Authoring: stand at the outpost, aim, tap. Captures the direction the phone
         is facing relative to the QR calibration, so it writes the same pitch/yaw the
         panorama editor used to produce. --}}
    {{-- max-height + overflow matter: this panel has grown to fourteen rows, and a
         fixed element taller than the viewport pushes its own top off screen with no way
         to reach it. overscroll-behavior keeps a flick inside the panel. --}}
    <div id="place" style="position:fixed;left:0;right:0;bottom:0;z-index:35;background:#0f172aee;
         padding:14px 16px calc(env(safe-area-inset-bottom) + 16px);display:none;
         max-height:78vh;overflow-y:auto;overscroll-behavior:contain;-webkit-overflow-scrolling:touch">
        {{-- Which of the two things this sheet is doing, stated at the top and never
             ambiguous. The panel looks identical in both modes, and telling them apart by
             reading the save button at the bottom of a scrolling sheet is not good enough
             on a phone held at arm's length. --}}
        <div id="p-mode" style="display:inline-block;margin-bottom:10px;padding:4px 12px;border-radius:9999px;
                                background:#5b21b6;color:#ede9fe;font-size:12px;font-weight:700">New object</div>
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:8px">
            <label style="font-size:12px;color:#94a3b8;width:64px">Object</label>
            <select id="p-model" style="flex:1;padding:8px;border-radius:8px;border:1px solid #334155;background:#1e293b;color:#f8fafc;font-size:13px">
                <option value="">— location default —</option>
                @foreach($library as $m)
                    <option value="{{ $m['id'] }}" data-url="{{ $m['url'] }}">{{ $m['name'] }} ({{ $m['size'] }})</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:8px">
            <label style="font-size:12px;color:#94a3b8;width:64px">Distance</label>
            <input type="range" id="p-dist" min="1" max="20" step="0.5" value="3" style="flex:1">
            <span id="p-dist-v" style="font-size:12px;width:44px;text-align:right">3.0 m</span>
        </div>
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:6px">
            <label style="font-size:12px;color:#94a3b8;width:64px">Height</label>
            <input type="range" id="p-height" min="-10" max="10" step="0.1" value="0" style="flex:1">
            <span id="p-height-v" style="font-size:12px;width:44px;text-align:right">0.0 m</span>
        </div>
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:10px">
            <label style="font-size:12px;color:#94a3b8;width:64px">Size</label>
            <input type="range" id="p-scale" min="0" max="1000" step="1" value="500" style="flex:1">
            <input type="number" id="p-scale-n" min="0.01" max="50" step="0.01" value="1"
                   style="width:66px;padding:4px 6px;border-radius:6px;border:1px solid #334155;background:#1e293b;color:#f8fafc;font-size:12px;text-align:right">
            <span id="p-scale-v" style="font-size:12px;width:44px;text-align:right">1.0×</span>
        </div>
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:6px">
            <label style="font-size:12px;color:#94a3b8;width:64px">Rotate X</label>
            <input type="range" id="p-rx" min="-180" max="180" step="5" value="0" style="flex:1">
            <span id="p-rx-v" style="font-size:12px;width:44px;text-align:right">0°</span>
        </div>
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:6px">
            <label style="font-size:12px;color:#94a3b8;width:64px">Rotate Y</label>
            <input type="range" id="p-ry" min="-180" max="180" step="5" value="0" style="flex:1">
            <span id="p-ry-v" style="font-size:12px;width:44px;text-align:right">0°</span>
        </div>
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:10px">
            <label style="font-size:12px;color:#94a3b8;width:64px">Rotate Z</label>
            <input type="range" id="p-rz" min="-180" max="180" step="5" value="0" style="flex:1">
            <span id="p-rz-v" style="font-size:12px;width:44px;text-align:right">0°</span>
            <button type="button" id="p-rreset" style="padding:4px 8px;border:0;border-radius:6px;background:#334155;color:#cbd5e1;font-size:11px">reset</button>
        </div>
        <input id="p-title" placeholder="Object name (e.g. Treasure chest)"
               style="width:100%;padding:10px;margin-bottom:8px;border-radius:8px;border:1px solid #334155;background:#1e293b;color:#f8fafc;font-size:14px">
        <input id="p-desc" placeholder="Clue shown when tapped"
               style="width:100%;padding:10px;margin-bottom:8px;border-radius:8px;border:1px solid #334155;background:#1e293b;color:#f8fafc;font-size:14px">
        <div style="display:flex;gap:8px;align-items:center;margin-bottom:6px">
        <div style="margin-bottom:8px">
            <div style="font-size:12px;color:#94a3b8;margin-bottom:6px">Motion — tick any, they run together</div>
            <div id="p-motions">
                <!-- Each motion owns a different part of the pose, so they compose: spin turns
                     the object, bob lifts it, orbit and sway swing it around the player. Each
                     needs its own speed, and its own range where range means anything, because
                     bob is measured in metres and sway in degrees. -->
                <div class="motion-row" data-type="spin" style="display:flex;gap:8px;align-items:center;margin-bottom:5px">
                    <label style="display:flex;gap:6px;align-items:center;width:120px;font-size:13px;color:#e2e8f0">
                        <input type="checkbox" class="m-on"> Spin
                    </label>
                    <input type="range" class="m-speed" min="0.1" max="5" step="0.1" value="1" style="flex:1">
                    <span class="m-speed-v" style="font-size:11px;width:36px;text-align:right;color:#94a3b8">1.0×</span>
                    <span class="m-range-wrap" style="display:none;align-items:center;gap:6px">
                        <input type="range" class="m-range" min="0" max="10" step="0.1" value="1" style="width:70px">
                        <span class="m-range-v" style="font-size:11px;width:40px;text-align:right;color:#94a3b8"></span>
                    </span>
                </div>
                <div class="motion-row" data-type="bob" style="display:flex;gap:8px;align-items:center;margin-bottom:5px">
                    <label style="display:flex;gap:6px;align-items:center;width:120px;font-size:13px;color:#e2e8f0">
                        <input type="checkbox" class="m-on"> Up and down
                    </label>
                    <input type="range" class="m-speed" min="0.1" max="5" step="0.1" value="1" style="flex:1">
                    <span class="m-speed-v" style="font-size:11px;width:36px;text-align:right;color:#94a3b8">1.0×</span>
                    <span class="m-range-wrap" style="display:flex;align-items:center;gap:6px">
                        <input type="range" class="m-range" min="0" max="10" step="0.1" value="1" style="width:70px">
                        <span class="m-range-v" style="font-size:11px;width:40px;text-align:right;color:#94a3b8">1.0 m</span>
                    </span>
                </div>
                <div class="motion-row" data-type="orbit" style="display:flex;gap:8px;align-items:center;margin-bottom:5px">
                    <label style="display:flex;gap:6px;align-items:center;width:120px;font-size:13px;color:#e2e8f0">
                        <input type="checkbox" class="m-on"> Circle player
                    </label>
                    <input type="range" class="m-speed" min="0.1" max="5" step="0.1" value="1" style="flex:1">
                    <span class="m-speed-v" style="font-size:11px;width:36px;text-align:right;color:#94a3b8">1.0×</span>
                    <span class="m-range-wrap" style="display:none;align-items:center;gap:6px">
                        <input type="range" class="m-range" min="0" max="90" step="1" value="1" style="width:70px">
                        <span class="m-range-v" style="font-size:11px;width:40px;text-align:right;color:#94a3b8"></span>
                    </span>
                </div>
                <div class="motion-row" data-type="sway" style="display:flex;gap:8px;align-items:center;margin-bottom:10px">
                    <label style="display:flex;gap:6px;align-items:center;width:120px;font-size:13px;color:#e2e8f0">
                        <input type="checkbox" class="m-on"> Sway
                    </label>
                    <input type="range" class="m-speed" min="0.1" max="5" step="0.1" value="1" style="flex:1">
                    <span class="m-speed-v" style="font-size:11px;width:36px;text-align:right;color:#94a3b8">1.0×</span>
                    <span class="m-range-wrap" style="display:flex;align-items:center;gap:6px">
                        <input type="range" class="m-range" min="0" max="90" step="1" value="30" style="width:70px">
                        <span class="m-range-v" style="font-size:11px;width:40px;text-align:right;color:#94a3b8">30°</span>
                    </span>
                </div>
            </div>
        </div>
        {{-- The picture field only exists once "image" is chosen, which is reasonable but
             invisible: an admin looking for "where do I upload the picture" finds a
             narrow label reading "On tap" and nothing else. The heading and the note
             below say what the choice does and where the next control will appear. --}}
        <div style="margin-bottom:8px">
            <div style="font-size:12px;color:#94a3b8;margin-bottom:4px">
                {{ __('What the team sees when they tap this object') }}
            </div>
            <select id="p-itype" style="width:100%;padding:8px;border-radius:8px;border:1px solid #334155;background:#1e293b;color:#f8fafc;font-size:13px">
                <option value="none">{{ __('Message only') }}</option>
                <option value="link">{{ __('Message + link') }}</option>
                <option value="image">{{ __('Message + picture (choose the file below)') }}</option>
            </select>
        </div>
        <input id="p-link" placeholder="https://…" inputmode="url"
               style="display:none;width:100%;padding:10px;margin-bottom:8px;border-radius:8px;border:1px solid #334155;background:#1e293b;color:#f8fafc;font-size:14px">
        <div id="p-imgrow" style="display:none;margin-bottom:8px;padding:10px;border-radius:8px;
                                  border:1px dashed #475569;background:#1e293b66">
            <div style="font-size:12px;color:#e2e8f0;margin-bottom:6px">
                {{ __('Picture shown on tap') }}
            </div>
            <input type="file" id="p-img" accept="image/*"
                   style="width:100%;font-size:12px;color:#cbd5e1">
            {{-- Uploaded the moment a file is chosen, so a failure shows here and not
                 after the object has already been placed. --}}
            <p id="p-imgmsg" style="margin:6px 0 0;font-size:11px;color:#94a3b8"></p>
            <p style="margin:6px 0 0;font-size:11px;color:#64748b">
                {{ __('Uploads straight away. JPEG, PNG or WebP, up to 5 MB.') }}
            </p>
        </div>
        <div style="display:flex;gap:8px;position:sticky;bottom:0;padding:8px 0 0;
                    background:#0f172a;margin-top:4px">
            <input id="p-points" type="number" min="0" placeholder="Points" value="0"
                   style="width:90px;padding:10px;border-radius:8px;border:1px solid #334155;background:#1e293b;color:#f8fafc;font-size:14px">
            <button type="button" id="p-save" style="flex:1;padding:12px;border:0;border-radius:8px;background:#22c55e;color:#052e16;font-weight:700">Place here</button>
            <button type="button" id="p-del" style="display:none;padding:12px 14px;border:0;border-radius:8px;background:#7f1d1d;color:#fecaca;font-weight:700">Delete</button>
        </div>
        <p id="p-msg" style="margin:8px 0 0;font-size:12px;color:#94a3b8;text-align:center"></p>
    </div>
    @endif

    <script type="importmap">
    { "imports": { "three": "/vendor/three/0.169.0/three.module.min.js" } }
    </script>

    <script type="module">
        import * as THREE from 'three';
        import { GLTFLoader } from '/vendor/three/0.169.0/loaders/GLTFLoader.js';

        const IS_ADMIN = @json($isAdmin);
        // Where this outpost physically is. lat is null until an admin binds it on site.
        const OUTPOST = @json($outpost);
        const BIND_URL = @json($isAdmin ? route("ar.location.bind", $gameLocation->id) : null);
        // Empty for teams until the server hands them over — see OBJECTS_URL below.
        let HOTSPOTS = @json($hotspots);
        const OBJECT_TOTAL = @json($objectCount);
        const OBJECTS_URL = @json(route('user.ar.api', $gameLocation->id));
        const FALLBACK_MODEL = @json($gameLocation->ar_model_path ? Storage::url($gameLocation->ar_model_path) : null);

        const canvas = document.getElementById('gl');
        const video  = document.getElementById('cam');
        const gate   = document.getElementById('gate');
        const note   = document.getElementById('gate-note');
        const hint   = document.getElementById('hint');
        const sheet  = document.getElementById('sheet');
        const reticle= document.getElementById('reticle');
        const countEl= document.getElementById('count');
        const guide  = document.getElementById('guide');
        const recentreBtn = document.getElementById('recenter');

        const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
        renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
        const scene  = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(70, 1, 0.1, 200);

        scene.add(new THREE.HemisphereLight(0xffffff, 0x334155, 2.2));
        const key = new THREE.DirectionalLight(0xffffff, 1.4);
        key.position.set(1, 3, 2);
        scene.add(key);

        // ---- matching the virtual camera to the real one ------------------------
        //
        // The 3D camera used a hard-coded 70° vertical FOV while the passthrough video
        // is drawn with `object-fit: cover`, which crops it. Two unrelated numbers, so
        // the virtual world and the video turned at different angular rates: an object
        // placed against a doorway slid off it as you turned, and the further you turned
        // the worse it got.
        //
        // The crop is exact arithmetic — we know the video's intrinsic size and the
        // viewport. What the web cannot tell us is the lens's own field of view: no
        // browser exposes it reliably. So that one number is calibrated by eye, once per
        // device, and kept in localStorage.
        const FOV_KEY = 'eureka.ar.hfov';
        const FOV_DEFAULT = 65;             // typical rear main lens, horizontal degrees
        let nativeHFov = FOV_DEFAULT;
        try {
            const stored = parseFloat(localStorage.getItem(FOV_KEY));
            if (Number.isFinite(stored) && stored >= 40 && stored <= 120) nativeHFov = stored;
        } catch (e) { /* private mode — the default is fine */ }

        const DEG = Math.PI / 180;

        /**
         * The vertical FOV actually visible on screen, after `cover` has cropped the video.
         *
         * Falls back to the plain calibrated value while the video has no dimensions yet
         * (before the stream starts, or when the camera was refused) — a sane picture beats
         * a divide by zero.
         */
        function visibleVerticalFov() {
            const vw = video.videoWidth, vh = video.videoHeight;
            const w = innerWidth, h = innerHeight;

            if (!vw || !vh || !w || !h) return FOV_DEFAULT;

            // The calibrated angle belongs to the sensor's LONG axis, not to whichever
            // dimension the browser happens to call width. A phone hands the same camera
            // over as 1280×720 or 720×1280 depending on orientation; tying the number to
            // `videoWidth` would apply a 65° lens angle to the narrow side in portrait and
            // compute a ~97° vertical field, which no phone camera has. Anchoring it to
            // the long axis means a value calibrated in one orientation stays correct in
            // the other.
            const long = Math.max(vw, vh), short = Math.min(vw, vh);
            const longFov = nativeHFov;
            const shortFov = 2 * Math.atan(Math.tan(longFov / 2 * DEG) * (short / long)) / DEG;
            const nativeV = vh >= vw ? longFov : shortFov;

            // `cover` scales by the larger factor, so the overflowing axis is cropped.
            const scale = Math.max(w / vw, h / vh);
            const visibleFractionY = Math.min(1, (h / scale) / vh);

            return 2 * Math.atan(Math.tan(nativeV / 2 * DEG) * visibleFractionY) / DEG;
        }

        function resize() {
            const w = innerWidth, h = innerHeight;
            renderer.setSize(w, h, false);
            camera.aspect = w / h;
            camera.fov = visibleVerticalFov();
            camera.updateProjectionMatrix();
        }
        @if($isAdmin)
        // Calibration UI. Admin only: a team never needs it, and the value it writes
        // is per-device anyway.
        (function () {
            const bar = document.getElementById('fovbar');
            if (!bar) return;
            const panel = document.getElementById('fov-panel');
            const range = document.getElementById('fov-range');
            const val   = document.getElementById('fov-val');
            const note  = document.getElementById('fov-note');

            function show() {
                range.value = nativeHFov;
                val.textContent = nativeHFov + '°';
                // The number that matters is what ends up on screen, so show that too —
                // the slider sets the lens, the crop decides the rest.
                note.textContent = 'Lens ' + nativeHFov + '° horizontal → '
                    + visibleVerticalFov().toFixed(1) + '° vertical on screen'
                    + (video.videoWidth ? ' (video ' + video.videoWidth + '×' + video.videoHeight + ')' : '');
            }

            document.getElementById('fov-toggle').addEventListener('click', () => {
                panel.hidden = !panel.hidden;
                if (!panel.hidden) show();
            });

            range.addEventListener('input', () => {
                nativeHFov = parseFloat(range.value);
                resize();                       // live: the scene re-projects as you drag
                show();
            });

            // Written on release, not on every pixel of the drag.
            range.addEventListener('change', () => {
                try { localStorage.setItem(FOV_KEY, String(nativeHFov)); } catch (e) {}
            });

            document.getElementById('fov-reset').addEventListener('click', () => {
                nativeHFov = FOV_DEFAULT;
                try { localStorage.removeItem(FOV_KEY); } catch (e) {}
                resize(); show();
            });
        })();
        @endif

        addEventListener('resize', resize);
        // The intrinsic video size is unknown until the stream reports it, and that is
        // the moment the correct FOV becomes computable.
        video.addEventListener('loadedmetadata', resize);
        resize();

        // ---- place the objects -------------------------------------------------
        // Positions come straight from the hotspot's pitch/yaw, so panorama authoring
        // carries over: the same direction the team looked in the sphere is the
        // direction they must turn to here.
        const loader = new GLTFLoader();
        const targets = [];
        // Shared scope on purpose: the animation loop skips whichever object the
        // authoring panel is holding, and that loop runs for teams too — where the
        // panel does not exist. Declared only inside the admin block, this threw a
        // ReferenceError every frame and killed rendering for every team.
        let editing = null;
        let found = 0;

        function addPlaceholder(h) {
            const geo = new THREE.IcosahedronGeometry(0.28 * h.scale, 0);
            const mat = new THREE.MeshStandardMaterial({ color: 0x22c55e, roughness: .35, metalness: .1 });
            return new THREE.Mesh(geo, mat);
        }

        function spawnAll(list) {
          list.forEach(h => {
            const holder = new THREE.Group();
            holder.position.set(h.position.x, h.position.y, h.position.z);
            if (h.rotation) {
                const d2r = Math.PI / 180;
                holder.rotation.set(h.rotation.x * d2r, h.rotation.y * d2r, h.rotation.z * d2r);
            }
            holder.userData.hotspot = h;
            // The pose the admin authored. Every motion is expressed relative to this, so
            // an animated object always returns to exactly where it was placed.
            holder.userData.base = { x: holder.position.x, y: holder.position.y,
                                     z: holder.position.z, rotY: holder.rotation.y };
            scene.add(holder);
            targets.push(holder);

            const url = h.model || FALLBACK_MODEL;
            if (url) {
                loader.load(url, gltf => {
                    const obj = gltf.scene;
                    obj.scale.setScalar(h.scale);
                    holder.add(obj);
                }, undefined, () => holder.add(addPlaceholder(h)));
            } else {
                holder.add(addPlaceholder(h));
            }
          });
        }

        // Admins already hold the objects; teams receive nothing until they prove position.
        spawnAll(HOTSPOTS);

        // ---- where is the phone pointing? --------------------------------------
        // Absolute compass headings drift badly outdoors, so we never use them. The
        // orientation at "Start" becomes forward, which is why the team is asked to
        // point at the QR first — a direction they are standing at anyway.
        const zee = new THREE.Vector3(0, 0, 1);
        const euler = new THREE.Euler();
        const q0 = new THREE.Quaternion();
        const q1 = new THREE.Quaternion(-Math.sqrt(0.5), 0, 0, Math.sqrt(0.5));
        let device = null, screenAngle = 0, referenceYaw = null, haveSensor = false;
        // Absolute (magnetometer-referenced) heading, kept in alpha's own convention:
        // radians, counter-clockwise. Null on devices that expose no absolute source,
        // in which case everything below is skipped and behaviour is unchanged.
        let absHeading = null, absAtStart = null;
        const wrapPi = a => Math.atan2(Math.sin(a), Math.cos(a));
        let dragYaw = 0, dragPitch = 0, dragging = null;

        function absoluteHeadingFrom(e) {
            // iOS reports degrees clockwise from north; alpha runs the other way.
            if (typeof e.webkitCompassHeading === 'number' && !isNaN(e.webkitCompassHeading)) {
                return THREE.MathUtils.degToRad(360 - e.webkitCompassHeading);
            }
            if (e.absolute === true && e.alpha !== null) {
                return THREE.MathUtils.degToRad(e.alpha);
            }
            return null;
        }

        // iOS 13+ will not deliver a single orientation event until the user grants it,
        // and the request only counts when it comes straight off a tap. Everywhere else
        // the call does not exist, and sensors simply work.
        async function requestSensorPermission() {
            const DOE = window.DeviceOrientationEvent;
            if (!DOE || typeof DOE.requestPermission !== 'function') return true;
            try {
                return (await DOE.requestPermission()) === 'granted';
            } catch (e) {
                return false;   // thrown when denied, or when the user gesture was lost
            }
        }

        function orient(e) {
            haveSensor = true;
            device = e;
            const abs = absoluteHeadingFrom(e);
            if (abs !== null) absHeading = abs;
        }
        addEventListener('deviceorientation', orient, true);
        // Chrome on Android keeps the drift-free heading on a separate event; the plain
        // 'deviceorientation' alpha there is gyro-only, which is exactly what creeps.
        addEventListener('deviceorientationabsolute', e => {
            if (e.alpha !== null) absHeading = THREE.MathUtils.degToRad(e.alpha);
        }, true);
        addEventListener('orientationchange', () => { screenAngle = (screen.orientation?.angle || 0) * Math.PI / 180; });

        function applyOrientation() {
            if (device && device.alpha !== null) {
                const alpha = THREE.MathUtils.degToRad(device.alpha);
                const beta  = THREE.MathUtils.degToRad(device.beta || 0);
                const gamma = THREE.MathUtils.degToRad(device.gamma || 0);
                if (referenceYaw === null) {
                    referenceYaw = alpha;
                    absAtStart = absHeading;   // null here means no correction is possible
                }

                // Complementary filter. Gyro alone integrates its own error, so objects
                // creep steadily one way; the magnetometer is noisy but does not drift.
                // Nudge the zero point toward the compass slowly: the low gain averages
                // the noise away over a few seconds while cancelling the creep entirely.
                if (absHeading !== null && absAtStart !== null) {
                    const trueTurn = wrapPi(absHeading - absAtStart);
                    referenceYaw += wrapPi((alpha - trueTurn) - referenceYaw) * 0.004;
                }

                euler.set(beta, alpha - referenceYaw, -gamma, 'YXZ');
                camera.quaternion.setFromEuler(euler);
                camera.quaternion.multiply(q1);
                camera.quaternion.multiply(q0.setFromAxisAngle(zee, -screenAngle));
            } else {
                // No motion sensor: let them drag to look instead of stranding them.
                camera.rotation.set(dragPitch, dragYaw, 0, 'YXZ');
            }
        }

        canvas.addEventListener('touchstart', e => { dragging = e.touches[0]; }, { passive: true });
        canvas.addEventListener('touchmove', e => {
            if (!dragging || haveSensor) return;
            const t = e.touches[0];
            dragYaw   -= (t.clientX - dragging.clientX) * 0.005;
            dragPitch -= (t.clientY - dragging.clientY) * 0.005;
            dragPitch = Math.max(-1.4, Math.min(1.4, dragPitch));
            dragging = t;
        }, { passive: true });
        canvas.addEventListener('touchend', () => { dragging = null; }, { passive: true });

        // ---- interaction --------------------------------------------------------
        const ray = new THREE.Raycaster();
        const centre = new THREE.Vector2(0, 0);

        function aimed() {
            ray.setFromCamera(centre, camera);
            const hits = ray.intersectObjects(targets, true);
            if (!hits.length) return null;
            let o = hits[0].object;
            while (o && !o.userData.hotspot) o = o.parent;
            return o || null;
        }

        function openSheet(h, holder) {
            document.getElementById('sheet-title').textContent = h.title || 'Clue';
            document.getElementById('sheet-desc').textContent = h.description || '';
            const pts = document.getElementById('sheet-pts');
            pts.hidden = !h.points;
            pts.textContent = h.points ? ('+' + h.points + ' points') : '';

            // An object carries at most one extra: a picture or a link.
            const img = document.getElementById('sheet-img');
            if (h.image) { img.src = h.image; img.style.display = 'block'; }
            else { img.removeAttribute('src'); img.style.display = 'none'; }

            const link = document.getElementById('sheet-link');
            if (h.link) {
                link.href = h.link;
                link.textContent = 'Open link';
                link.style.display = 'block';
            } else {
                link.removeAttribute('href');
                link.style.display = 'none';
            }

            sheet.classList.add('open');

            if (!holder.userData.found) {
                holder.userData.found = true;
                found++;
                countEl.textContent = found + '/' + Math.max(OBJECT_TOTAL, targets.length);
                holder.traverse(n => { if (n.isMesh) n.material = n.material.clone?.() || n.material; });
                if (found === targets.length && targets.length) hint.textContent = 'All objects found!';
            }
        }

        document.getElementById('sheet-close').addEventListener('click', () => sheet.classList.remove('open'));
        canvas.addEventListener('click', () => {
            const t = aimed();
            if (!t) return;
            // An admin aiming at an object is almost always adjusting it; a team is
            // reading it. Same tap, different intent.
            // Preview on = see exactly what a team sees. Off = adjust it.
            if (window.__enterEdit && !window.__previewMode) window.__enterEdit(t);
            else openSheet(t.userData.hotspot, t);
        });

        // ---- camera + start -----------------------------------------------------
        async function startCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' } }, audio: false
                });
                video.srcObject = stream;
                await video.play();
                return true;
            } catch (e) {
                // No camera means no passthrough, but the 3D scene still works — the
                // outpost stays playable rather than dead.
                document.body.style.background =
                    'radial-gradient(circle at 50% 30%, #1e293b, #020617)';
                hint.textContent = 'Camera unavailable — drag to look around';
                return false;
            }
        }

        // ---- is the phone at the outpost? --------------------------------------
        // Objects are drawn relative to the phone, so nothing stops them rendering in
        // your living room. Binding the outpost to a coordinate and refusing to open
        // the scene elsewhere is how the object "stays at the office" — GPS, not AR.

        // Location is mandatory for everyone, so the reason for a failure has to reach
        // the screen — 'nothing happened' is the one outcome nobody can act on.
        // maximumAge 0 forces a fresh fix: a cached one from another part of town would
        // pin an outpost in the wrong place, permanently.
        function currentPosition() {
            if (!window.isSecureContext) return Promise.resolve({ error: 'insecure' });
            if (!navigator.geolocation) return Promise.resolve({ error: 'unsupported' });
            return new Promise(resolve => {
                navigator.geolocation.getCurrentPosition(
                    p => resolve({ lat: p.coords.latitude, lng: p.coords.longitude, acc: p.coords.accuracy }),
                    err => resolve({ error: err.code === 1 ? 'denied' : err.code === 3 ? 'timeout' : 'unavailable' }),
                    { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
                );
            });
        }

        const GPS_MESSAGE = {
            denied:      '{{ __('Location permission is required. Allow it for this site, then tap Start again.') }}',
            timeout:     '{{ __('No GPS fix yet. Move into the open and tap Start again.') }}',
            unavailable: '{{ __('Location unavailable. Turn GPS on, then tap Start again.') }}',
            unsupported: '{{ __('This device cannot report its location.') }}',
            insecure:    '{{ __('Location needs a secure (https) connection.') }}',
        };

        function metresBetween(aLat, aLng, bLat, bLng) {
            const R = 6371000, d2r = Math.PI / 180;
            const dLat = (bLat - aLat) * d2r, dLng = (bLng - aLng) * d2r;
            const s = Math.sin(dLat / 2) ** 2 +
                      Math.cos(aLat * d2r) * Math.cos(bLat * d2r) * Math.sin(dLng / 2) ** 2;
            return Math.round(2 * R * Math.atan2(Math.sqrt(s), Math.sqrt(1 - s)));
        }

        const humanDistance = m => m >= 1000 ? (m / 1000).toFixed(1) + ' km' : m + ' m';

        // Teams receive the objects only after the server has seen where they are.
        // The page never carried them, so a refusal here means an empty scene, not a
        // hidden one — there is nothing in the document to uncover.
        async function loadObjectsFor(fix) {
            if (IS_ADMIN) return { ok: true };          // already inline, and exempt
            try {
                // Indoor sends no position: there is none, and the server does not want
                // one — it checks the crew's unlock instead. Appending `lat=undefined`
                // would be a claim about where the team is that nobody measured.
                const url = (fix && fix.lat !== undefined && !fix.error)
                    ? OBJECTS_URL + '?lat=' + encodeURIComponent(fix.lat) +
                                    '&lng=' + encodeURIComponent(fix.lng)
                    : OBJECTS_URL;
                const res = await fetch(url, {
                    credentials: 'same-origin', headers: { 'Accept': 'application/json' }
                });
                const d = await res.json().catch(() => null);

                if (!res.ok || !d || !d.success) {
                    if (d && d.error === 'out_of_range') {
                        return { ok: false, message: 'You are ' + humanDistance(Math.round(d.distance)) +
                                                     ' from this outpost. Go there to see the objects.' };
                    }
                    return { ok: false, message: (d && d.message) || 'Could not load this outpost.' };
                }

                HOTSPOTS = d.objects || [];
                spawnAll(HOTSPOTS);
                return { ok: true };
            } catch (e) {
                return { ok: false, message: 'Could not reach the server. Check your connection.' };
            }
        }

        async function settleLocation(fix) {
            // Indoor outposts have no GPS gate at all — that is the entire point of the
            // mode. A person opens them from the Outpost Access desk, precisely because
            // satellites do not reach through a roof. Asking for a fix here blocked the
            // admin at this gate and made it impossible to place any object indoors: the
            // fix simply never arrives, so the door never opens.
            //
            // Nothing below applies either. There is no coordinate to bind (binding one
            // from wherever the phone thinks it is would be a lie), and no radius to
            // measure against.
            if (OUTPOST.mode === 'manual') {
                return { ok: true };
            }

            // Mandatory for admins and teams alike. An admin without a fix cannot bind the
            // outpost, and a team without one cannot be shown to be at it — in both cases
            // continuing produces a scene that means nothing.
            if (!fix || fix.error) {
                return { ok: false, message: GPS_MESSAGE[(fix && fix.error) || 'unavailable'] };
            }

            // Never bound: an admin standing here records it now, at no extra cost —
            // they had to come to the outpost to aim the phone anyway. Skipped when a
            // quest location owns the point — that coordinate belongs to the post.
            if (OUTPOST.lat === null) {
                if (OUTPOST.source === 'quest_location') {
                    return { ok: true, warning: '{{ __('This post has no coordinates yet — set them in Quest Locations.') }}' };
                }
                if (IS_ADMIN && BIND_URL) {
                    try {
                        const res = await fetch(BIND_URL, {
                            method: 'POST', credentials: 'same-origin',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                                       'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                            body: JSON.stringify({ latitude: fix.lat, longitude: fix.lng })
                        });
                        const d = await res.json().catch(() => null);
                        if (res.ok && d && d.success && d.changed) {
                            OUTPOST.lat = d.latitude; OUTPOST.lng = d.longitude; OUTPOST.radius = d.radius;
                            // Accuracy is shown because it decides whether the pin is trustworthy:
                            // a +/-80 m fix will send teams to the wrong side of the building.
                            const acc = fix.acc ? ' (±' + Math.round(fix.acc) + ' m)' : '';
                            return { ok: true, warning: 'Outpost pinned to this spot' + acc + '.' };
                        }
                        return { ok: true, warning: 'Could not save the outpost location — tap Start again.' };
                    } catch (e) {
                        return { ok: true, warning: 'Could not save the outpost location — tap Start again.' };
                    }
                }
                return { ok: true };
            }

            const away = metresBetween(fix.lat, fix.lng, OUTPOST.lat, OUTPOST.lng);
            if (away <= OUTPOST.radius) return { ok: true };

            if (IS_ADMIN) {
                return { ok: true, warning: 'You are ' + humanDistance(away) + ' away — placement is not accurate here.' };
            }
            return { ok: false, message: 'You are ' + humanDistance(away) + ' from this outpost. Go there to see the objects.' };
        }

        document.getElementById('start').addEventListener('click', async () => {
            // Must be the first thing awaited: on iOS the permission prompt is refused
            // once the tap that opened it has been handed off to another promise.
            const sensorOk = await requestSensorPermission();

            // Indoor: do not ask for a fix at all. getCurrentPosition waits its full 15s
            // timeout before failing under a roof, so asking would stall the start button
            // for fifteen seconds to obtain an answer nothing here uses.
            let fix = null;

            if (OUTPOST.mode !== 'manual') {
                note.textContent = '{{ __('Checking location…') }}';
                // One fix, used for both the gate and the object request. maximumAge is 0,
                // so asking twice would mean two cold GPS locks — up to 30 seconds.
                fix = await currentPosition();
            }

            const verdict = await settleLocation(fix);
            if (!verdict.ok) { note.textContent = verdict.message; return; }

            note.textContent = '{{ __('Loading objects…') }}';
            const objects = await loadObjectsFor(fix);
            if (!objects.ok) { note.textContent = objects.message; return; }

            note.textContent = 'Starting camera…';
            await startCamera();
            referenceYaw = null;               // this direction becomes "forward"
            gate.style.display = 'none';
            recentreBtn.hidden = false;

            if (verdict.warning) hint.textContent = verdict.warning;
            else if (!sensorOk) hint.textContent = 'Motion access denied — drag to look around';
            else if (!haveSensor) hint.textContent = 'Drag to look around';
        });

        // ---- authoring: place objects by aiming ---------------------------------
        // Wrapped so a team never downloads the authoring code at all; the endpoint is
        // separately behind the admin middleware, so this is tidiness, not the guard.
        @if($isAdmin)
        const STORE_URL = @json($isAdmin ? route('ar.hotspot.store', $gameLocation->id) : null);
        // Pull the real reason out of a rejected response. Laravel answers a failed
        // validation with {message, errors:{field:[…]}}; reporting that beats blaming
        // the network for a field the server refused.
        function reasonFrom(d) {
            if (d && d.errors) { const f = Object.values(d.errors)[0]; return Array.isArray(f) ? f[0] : String(f); }
            if (d && d.message) return d.message;
            return '';
        }

        let ghost = null, placing = false;

        function spawnObject(h) {
            const holder = new THREE.Group();
            holder.position.set(h.position.x, h.position.y, h.position.z);
            if (h.rotation) {
                const d2r = Math.PI / 180;
                holder.rotation.set(h.rotation.x * d2r, h.rotation.y * d2r, h.rotation.z * d2r);
            }
            holder.userData.hotspot = h;
            // The pose the admin authored. Every motion is expressed relative to this, so
            // an animated object always returns to exactly where it was placed.
            holder.userData.base = { x: holder.position.x, y: holder.position.y,
                                     z: holder.position.z, rotY: holder.rotation.y };
            scene.add(holder);
            targets.push(holder);
            const url = h.model || FALLBACK_MODEL;
            if (url) {
                loader.load(url, g => { const o = g.scene; o.scale.setScalar(h.scale); holder.add(o); },
                            undefined, () => holder.add(addPlaceholder(h)));
            } else {
                holder.add(addPlaceholder(h));
            }
            countEl.textContent = found + '/' + targets.length;
            return holder;
        }

        if (IS_ADMIN) {
            const panel = document.getElementById('place');
            const dist  = document.getElementById('p-dist');
            const sc    = document.getElementById('p-scale');
            const msg   = document.getElementById('p-msg');

            // A translucent preview at the aimed direction, so "where will it go?"
            // is answered before saving rather than after.
            ghost = new THREE.Mesh(
                // A box, not a ball: you cannot see rotation on a sphere.
                new THREE.BoxGeometry(0.42, 0.42, 0.42),
                new THREE.MeshStandardMaterial({ color: 0xa855f7, transparent: true, opacity: 0.55 })
            );
            ghost.visible = false;
            scene.add(ghost);

            const aimDir = new THREE.Vector3();
            function updateGhost() {
                if (!placing) { ghost.visible = false; return; }
                ghost.visible = true;
                camera.getWorldDirection(aimDir);
                const d = parseFloat(dist.value);
                // Horizontal bearing from the aim, vertical position from the slider.
                const flat = new THREE.Vector3(aimDir.x, 0, aimDir.z).normalize();
                const y = currentHeight();
                const horiz = Math.sqrt(Math.max(0, d * d - y * y));
                ghost.position.set(flat.x * horiz, y, flat.z * horiz);
                ghost.scale.setScalar(currentScale());
            }
            window.__updateGhost = updateGhost;

            // The size slider is logarithmic. A linear 0.1-5 range gave 50 stops and its
            // coarsest control at the small end, which is exactly where models need the
            // most precision. This gives 1000 stops across 0.01x-50x, so a nudge near 1x
            // moves by ~0.005 while the top end is still reachable.
            const SCALE_MIN = 0.01, SCALE_MAX = 50;
            const LOG_MIN = Math.log(SCALE_MIN), LOG_SPAN = Math.log(SCALE_MAX) - LOG_MIN;
            const scNum = document.getElementById('p-scale-n');

            function sliderToScale(v) { return Math.exp(LOG_MIN + (v / 1000) * LOG_SPAN); }
            function scaleToSlider(s) {
                const clamped = Math.min(SCALE_MAX, Math.max(SCALE_MIN, s));
                return Math.round(((Math.log(clamped) - LOG_MIN) / LOG_SPAN) * 1000);
            }
            function currentScale() {
                const n = parseFloat(scNum.value);
                return isFinite(n) && n > 0 ? Math.min(SCALE_MAX, Math.max(SCALE_MIN, n)) : 1;
            }
            function showScale(s) {
                // More decimals for small objects, where a hundredth is visible.
                scNum.value = s < 1 ? s.toFixed(3) : s.toFixed(2);
                document.getElementById('p-scale-v').textContent =
                    (s < 1 ? s.toFixed(2) : s.toFixed(1)) + '×';
            }

            const rx = document.getElementById('p-rx');
            const ry = document.getElementById('p-ry');
            const rz = document.getElementById('p-rz');
            const D2R = Math.PI / 180;

            function applyGhostRotation() {
                ghost.rotation.set(rx.value * D2R, ry.value * D2R, rz.value * D2R);
            }
            [['p-rx', rx], ['p-ry', ry], ['p-rz', rz]].forEach(([id, el]) => {
                el.addEventListener('input', () => {
                    document.getElementById(id + '-v').textContent = el.value + '°';
                    applyGhostRotation();
                });
            });
            document.getElementById('p-rreset').addEventListener('click', () => {
                [rx, ry, rz].forEach(el => { el.value = 0; el.dispatchEvent(new Event('input')); });
            });

            dist.addEventListener('input', () => {
                document.getElementById('p-dist-v').textContent = parseFloat(dist.value).toFixed(1) + ' m';
            });
            sc.addEventListener('input', () => showScale(sliderToScale(parseFloat(sc.value))));
            scNum.addEventListener('input', () => {
                const s = currentScale();
                sc.value = scaleToSlider(s);
                document.getElementById('p-scale-v').textContent = (s < 1 ? s.toFixed(2) : s.toFixed(1)) + '×';
            });
            showScale(1);
            sc.value = scaleToSlider(1);

            const hgt = document.getElementById('p-height');

            // Height is authored in metres, but stored as pitch — the elevation angle the
            // rest of the app already understands. At a given distance the reachable
            // height is bounded by that distance, so the slider is clamped to it.
            function currentHeight() {
                const d = parseFloat(dist.value);
                const raw = parseFloat(hgt.value);
                return Math.max(-d, Math.min(d, isFinite(raw) ? raw : 0));
            }

            function pitchFromHeight() {
                const d = parseFloat(dist.value) || 1;
                return Math.asin(Math.max(-1, Math.min(1, currentHeight() / d)));
            }

            function showHeight() {
                document.getElementById('p-height-v').textContent = currentHeight().toFixed(1) + ' m';
            }
            hgt.addEventListener('input', showHeight);
            dist.addEventListener('input', showHeight);   // the clamp moves with distance

            // ---- motion toggles -------------------------------------------------
            // Any combination may be on at once; each row carries its own speed, and its
            // own range where range means something. The unit differs per motion, so the
            // label is written per row rather than shared.
            const MOTION_UNIT = { bob: ' m', sway: '°' };

            function motionRows() {
                return Array.from(document.querySelectorAll('#p-motions .motion-row'));
            }

            function showMotion(row) {
                const type = row.dataset.type;
                const on = row.querySelector('.m-on').checked;
                const speed = row.querySelector('.m-speed');
                const rangeWrap = row.querySelector('.m-range-wrap');

                // A row that is off keeps its numbers but stops inviting adjustment.
                speed.disabled = !on;
                row.style.opacity = on ? '1' : '0.45';
                if (rangeWrap) {
                    const usesRange = type === 'bob' || type === 'sway';
                    rangeWrap.style.display = usesRange ? 'flex' : 'none';
                    const r = row.querySelector('.m-range');
                    if (r) r.disabled = !on;
                }

                row.querySelector('.m-speed-v').textContent = parseFloat(speed.value).toFixed(1) + '×';
                const r = row.querySelector('.m-range');
                const rv = row.querySelector('.m-range-v');
                if (r && rv) rv.textContent = parseFloat(r.value).toFixed(type === 'sway' ? 0 : 1) + (MOTION_UNIT[type] || '');
            }

            function syncMotions() { motionRows().forEach(showMotion); }

            motionRows().forEach(row => {
                row.querySelector('.m-on').addEventListener('change', () => showMotion(row));
                row.querySelectorAll('input[type=range]').forEach(el =>
                    el.addEventListener('input', () => showMotion(row)));
            });
            syncMotions();

            function animationPayload() {
                const list = [];
                motionRows().forEach(row => {
                    if (!row.querySelector('.m-on').checked) return;
                    const type = row.dataset.type;
                    list.push({
                        type: type,
                        speed: parseFloat(row.querySelector('.m-speed').value),
                        range: parseFloat(row.querySelector('.m-range').value),
                    });
                });
                // Always sent, even empty: that is how an admin clears every motion.
                return { ar_motions: list };
            }

            function loadMotions(list) {
                const by = {};
                (list || []).forEach(m => { by[m.type] = m; });
                motionRows().forEach(row => {
                    const m = by[row.dataset.type];
                    row.querySelector('.m-on').checked = !!m;
                    if (m) {
                        row.querySelector('.m-speed').value = m.speed ?? 1;
                        const r = row.querySelector('.m-range');
                        if (r && m.range !== undefined && m.range !== null) r.value = m.range;
                    }
                });
                syncMotions();
            }
            const itype = document.getElementById('p-itype');
            const linkIn = document.getElementById('p-link');
            const imgRow = document.getElementById('p-imgrow');
            const imgIn = document.getElementById('p-img');
            const imgMsg = document.getElementById('p-imgmsg');
            const MEDIA_URL = @json(route('ar.media.store', $gameLocation->id));
            let uploadedImagePath = null;

            function syncInteractionFields() {
                linkIn.style.display = itype.value === 'link' ? 'block' : 'none';
                imgRow.style.display = itype.value === 'image' ? 'block' : 'none';
            }
            itype.addEventListener('change', syncInteractionFields);
            syncInteractionFields();

            // Upload as soon as a picture is chosen, so the admin sees it land before
            // committing the object rather than discovering a failure on save.
            imgIn.addEventListener('change', async () => {
                const file = imgIn.files && imgIn.files[0];
                if (!file) return;
                imgMsg.textContent = 'Uploading…';
                const body = new FormData();
                body.append('image', file);
                try {
                    const res = await fetch(MEDIA_URL, {
                        method: 'POST', credentials: 'same-origin', body: body,
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                    });
                    const d = await res.json().catch(() => null);
                    if (!res.ok || !d || !d.success) throw new Error(reasonFrom(d));
                    uploadedImagePath = d.path;
                    imgMsg.textContent = 'Image ready.';
                } catch (e) {
                    uploadedImagePath = null;
                    imgMsg.textContent = e.message || 'Upload failed — try a smaller image.';
                }
            });

            function interactionPayload() {
                if (itype.value === 'link') {
                    return { media_type: 'link', content: linkIn.value.trim(), media_path: null };
                }
                if (itype.value === 'image') {
                    return { media_type: 'image', content: null, media_path: uploadedImagePath };
                }
                return { media_type: 'none', content: null, media_path: null };
            }

            const modelSel = document.getElementById('p-model');
            const delBtn = document.getElementById('p-del');
            const panelMode = document.getElementById('p-mode');
            const saveBtn = document.getElementById('p-save');
            const UPDATE_URL = @json(route('ar.hotspot.update', [$gameLocation->id, '__ID__']));
            const DELETE_URL = @json(route('ar.hotspot.destroy', [$gameLocation->id, '__ID__']));

            // null = placing something new; a holder = editing that object.

            function loadIntoPanel(h) {
                dist.value = h.distance ?? 3;
                document.getElementById('p-dist-v').textContent = parseFloat(dist.value).toFixed(1) + ' m';
                showScale(h.scale || 1);
                sc.value = scaleToSlider(h.scale || 1);
                rx.value = h.rotation?.x || 0; ry.value = h.rotation?.y || 0; rz.value = h.rotation?.z || 0;
                [['p-rx', rx], ['p-ry', ry], ['p-rz', rz]].forEach(([id, el]) =>
                    document.getElementById(id + '-v').textContent = el.value + '°');
                hgt.value = (h.position?.y ?? 0).toFixed(1);
                showHeight();
                modelSel.value = h.model_id || '';
                loadMotions(h.animations);
                itype.value = h.media_type || 'none';
                linkIn.value = h.link || '';
                uploadedImagePath = null;
                imgMsg.textContent = h.image ? 'Current image kept unless you choose a new one.' : '';
                syncInteractionFields();
            }

            window.__enterEdit = enterEdit;
            function enterEdit(holder) {
                editing = holder;
                const h = holder.userData.hotspot;
                panel.style.display = 'block';
                placing = false;                       // the ghost is for new objects only
                hint.style.display = 'none';
                document.getElementById('p-toggle').textContent = 'Done';
                // Removed a pair of lines that hid the title's parent and then immediately
                // showed the same element again — leftovers that cancelled each other out.
                saveBtn.textContent = 'Save changes';
                delBtn.style.display = 'inline-block';
                panelMode.textContent = 'Editing: ' + (h.title || 'object');
                panelMode.style.background = '#1d4ed8';
                loadIntoPanel(h);
                msg.textContent = 'Adjust and save. This changes the object you tapped.';
            }

            function exitEdit() {
                editing = null;
                saveBtn.textContent = 'Place here';
                delBtn.style.display = 'none';
                panelMode.textContent = 'New object';
                panelMode.style.background = '#5b21b6';
                document.getElementById('p-toggle').textContent = '+ Object';
                panel.style.display = 'none';
                hint.style.display = 'block';
                msg.textContent = '';
            }

            // Live preview while editing: move/scale/rotate the real object, not a ghost.
            function applyLiveEdit() {
                if (!editing) return;
                const d = parseFloat(dist.value);
                const h = editing.userData.hotspot;
                const flat = new THREE.Vector3(h.position.x, 0, h.position.z).normalize();
                const y = currentHeight();
                const horiz = Math.sqrt(Math.max(0, d * d - y * y));
                editing.position.set(flat.x * horiz, y, flat.z * horiz);
                editing.scale.setScalar(currentScale());
                editing.rotation.set(rx.value * D2R, ry.value * D2R, rz.value * D2R);
            }
            // hgt belongs here too: without it the height label moved but the object did
            // not, so an edited height looked like it had been ignored.
            [dist, hgt, sc, scNum, rx, ry, rz].forEach(el => el.addEventListener('input', applyLiveEdit));

            modelSel.addEventListener('change', () => {
                if (!editing) return;
                const url = modelSel.selectedOptions[0]?.dataset.url || FALLBACK_MODEL;
                while (editing.children.length) editing.remove(editing.children[0]);
                if (url) loader.load(url, g => { const o = g.scene; o.scale.setScalar(1); editing.add(o); });
            });

            delBtn.addEventListener('click', async () => {
                if (!editing) return;
                const h = editing.userData.hotspot;
                msg.textContent = 'Deleting…';
                try {
                    const res = await fetch(DELETE_URL.replace('__ID__', h.id), {
                        method: 'DELETE', credentials: 'same-origin',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                    });
                    if (!res.ok) throw new Error(reasonFrom(await res.json().catch(() => null)));
                    scene.remove(editing);
                    targets.splice(targets.indexOf(editing), 1);
                    countEl.textContent = found + '/' + targets.length;
                    exitEdit();
                } catch (e) { msg.textContent = e.message || 'Could not delete.'; }
            });

            // Two modes, never both, and the button always leaves one before entering the
            // other.
            //
            // They used to share this toggle, which only flipped `placing` and never
            // cleared `editing`. Tap an object once and `editing` stayed set for the rest
            // of the session, so every later "Place here" ran the update branch and
            // silently rewrote that same object. Nothing new ever appeared — which reads
            // exactly like "I cannot create 3D objects".
            function startCreating() {
                exitEdit();                     // clears `editing` and restores the labels
                resetPanelForNewObject();
                placing = true;
                panel.style.display = 'block';
                hint.style.display = 'none';
                document.getElementById('p-toggle').textContent = 'Cancel';
                msg.textContent = 'New object — aim, then tap Place here.';
            }

            function stopCreating() {
                placing = false;
                panel.style.display = 'none';
                hint.style.display = 'block';
                document.getElementById('p-toggle').textContent = '+ Object';
                msg.textContent = '';
            }

            /**
             * A new object starts from defaults, not from whatever was edited last.
             *
             * Carrying the previous object's scale, rotation and image over is how two
             * objects end up identical without anyone choosing that.
             */
            function resetPanelForNewObject() {
                document.getElementById('p-title').value = '';
                document.getElementById('p-desc').value = '';
                dist.value = 3;
                document.getElementById('p-dist-v').textContent = '3.0 m';
                sc.value = scaleToSlider(1);
                showScale(1);
                rx.value = 0; ry.value = 0; rz.value = 0;
                [['p-rx', rx], ['p-ry', ry], ['p-rz', rz]].forEach(([id, el]) =>
                    document.getElementById(id + '-v').textContent = '0°');
                hgt.value = '0.0';
                showHeight();
                modelSel.value = '';
                loadMotions([]);
                itype.value = 'none';
                linkIn.value = '';
                uploadedImagePath = null;
                imgMsg.textContent = '';
                syncInteractionFields();
            }

            // Preview: the tap behaves as a team's does, so the message and picture an
            // admin just authored can actually be tried without logging in as a team.
            window.__previewMode = false;
            const previewBtn = document.getElementById('p-preview');
            previewBtn.addEventListener('click', () => {
                window.__previewMode = !window.__previewMode;
                previewBtn.classList.toggle('on', window.__previewMode);
                previewBtn.textContent = window.__previewMode ? 'Previewing' : 'Preview';
                // Authoring and previewing are opposites: leaving one enters the other
                // cleanly, rather than leaving a half-open editor behind the sheet.
                if (window.__previewMode) { exitEdit(); stopCreating(); }
                msg.textContent = '';
                hint.textContent = window.__previewMode
                    ? 'Preview — tap an object to see what the team sees'
                    : 'Tap an object to edit it';
            });

            document.getElementById('p-toggle').addEventListener('click', () => {
                // In edit mode this button reads "Done", so leaving edit is what it must
                // do — not start a new object the admin did not ask for.
                if (editing) { exitEdit(); return; }

                placing ? stopCreating() : startCreating();
            });

            document.getElementById('p-save').addEventListener('click', async () => {
                // Editing an existing object only changes where/how big/what shape.
                if (editing) {
                    const h = editing.userData.hotspot;
                    msg.textContent = 'Saving…';
                    try {
                        const res = await fetch(UPDATE_URL.replace('__ID__', h.id), {
                            method: 'PATCH', credentials: 'same-origin',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                                       'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                            body: JSON.stringify({
                                distance: parseFloat(dist.value), scale: currentScale(),
                                pitch: pitchFromHeight(),
                                rotation_x: parseFloat(rx.value), rotation_y: parseFloat(ry.value), rotation_z: parseFloat(rz.value),
                                ar_model_id: modelSel.value ? parseInt(modelSel.value, 10) : null,
                                ...interactionPayload(), ...animationPayload()
                            })
                        });
                        const d = await res.json().catch(() => null);
                        if (!res.ok || !d || !d.success) throw new Error(reasonFrom(d));
                        editing.userData.hotspot = d.hotspot;
                        // Re-seat from the server's numbers. It recomputes position from
                        // pitch and distance, so this is the one place both clients agree —
                        // trusting the local preview would let the two drift apart.
                        if (d.hotspot.position) {
                            editing.position.set(d.hotspot.position.x, d.hotspot.position.y, d.hotspot.position.z);
                            editing.userData.base = { x: d.hotspot.position.x, y: d.hotspot.position.y,
                                                      z: d.hotspot.position.z, rotY: editing.rotation.y };
                        }
                        msg.textContent = 'Saved.';
                        setTimeout(exitEdit, 700);
                    } catch (e) { msg.textContent = e.message || 'Could not save the changes.'; }
                    return;
                }

                const title = document.getElementById('p-title').value.trim();
                if (!title) { msg.textContent = 'Give the object a name first.'; return; }

                // Convert the aimed direction back into the pitch/yaw the rest of the
                // app stores, so panorama-authored and AR-authored hotspots are identical.
                camera.getWorldDirection(aimDir);
                const pitch = pitchFromHeight();
                const yaw = Math.atan2(aimDir.x, -aimDir.z);

                msg.textContent = 'Saving…';
                try {
                    const res = await fetch(STORE_URL, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                        },
                        body: JSON.stringify({
                            pitch: pitch, yaw: yaw,
                            distance: parseFloat(dist.value),
                            scale: currentScale(),
                            rotation_x: parseFloat(rx.value),
                            rotation_y: parseFloat(ry.value),
                            rotation_z: parseFloat(rz.value),
                            ar_model_id: modelSel.value ? parseInt(modelSel.value, 10) : null,
                            ...interactionPayload(), ...animationPayload(),
                            title: title,
                            description: document.getElementById('p-desc').value,
                            points_value: parseInt(document.getElementById('p-points').value || '0', 10)
                        })
                    });
                    const data = await res.json().catch(() => null);
                    if (!res.ok || !data || !data.success) throw new Error(reasonFrom(data));

                    spawnObject(data.hotspot);
                    HOTSPOTS.push(data.hotspot);
                    msg.textContent = 'Placed "' + data.hotspot.title + '".';
                    document.getElementById('p-title').value = '';
                    document.getElementById('p-desc').value = '';
                } catch (e) {
                    msg.textContent = e.message || 'Could not save. Check your connection.';
                }
            });
        }

        @endif

        // ---- idle motion ---------------------------------------------------------
        // Driven from parameters, never baked keyframes, so the browser and the Android
        // client compute the same pose from the same numbers. Rates are fixed here and
        // documented in ArExperienceController::ANIMATIONS — change one, change both.
        const ANIM_T0 = performance.now();
        const TAU = Math.PI * 2;

        function animateObjects() {
            const t = (performance.now() - ANIM_T0) / 1000;
            const D2R = Math.PI / 180;

            for (const holder of targets) {
                if (holder === editing) continue;          // being dragged by the panel
                const base = holder.userData.base;
                const h = holder.userData.hotspot;
                const list = (h && h.animations) || [];
                if (!base || !list.length) continue;

                // Motions compose because each one owns a different part of the pose:
                // spin turns the object, bob lifts it, orbit and sway swing it around the
                // player. Accumulate first, apply once — writing the pose per motion would
                // let the last one silently overwrite the others.
                let spinAng = 0, bobY = 0, aroundAng = 0;

                for (const a of list) {
                    const sp = a.speed || 1;
                    const rg = a.range || 0;

                    if (a.type === 'spin') {
                        spinAng += t * sp * 30 * D2R;
                    } else if (a.type === 'bob') {
                        bobY += Math.sin(TAU * t * sp / 4) * rg;
                    } else if (a.type === 'orbit') {
                        aroundAng += t * sp * 12 * D2R;
                    } else if (a.type === 'sway') {
                        aroundAng += Math.sin(TAU * t * sp / 6) * rg * D2R;
                    }
                }

                holder.rotation.y = base.rotY + spinAng;
                holder.position.y = base.y + bobY;

                if (aroundAng !== 0) {
                    // Rotating the authored point about the player, who sits at the origin,
                    // keeps the distance the admin chose for the whole motion.
                    const c = Math.cos(aroundAng), s = Math.sin(aroundAng);
                    holder.position.x = base.x * c - base.z * s;
                    holder.position.z = base.x * s + base.z * c;
                } else {
                    holder.position.x = base.x;
                    holder.position.z = base.z;
                }
            }
        }

        // ---- point the way to objects that are off screen ----------------------
        // An object authored at yaw 148 deg is behind you when you start; the scene is
        // correct but looks empty, which reads as data loss. Say which way to turn.
        const _toTarget = new THREE.Vector3();
        const _fwd = new THREE.Vector3();
        let _guideTick = 0;

        function updateGuide() {
            if (!targets.length) { guide.hidden = true; return; }
            camera.getWorldDirection(_fwd);
            const facing = Math.atan2(_fwd.x, -_fwd.z);
            let nearest = null;
            for (const t of targets) {
                _toTarget.copy(t.position);
                const delta = Math.atan2(_toTarget.x, -_toTarget.z) - facing;
                const signed = Math.atan2(Math.sin(delta), Math.cos(delta)); // -pi..pi
                if (Math.abs(signed) < 0.42) { guide.hidden = true; return; }  // roughly on screen
                if (nearest === null || Math.abs(signed) < Math.abs(nearest)) nearest = signed;
            }
            const deg = Math.round(Math.abs(nearest) * 180 / Math.PI);
            guide.hidden = false;
            guide.textContent = deg > 150
                ? '↺ ' + '{{ __('Turn around') }}'
                : (nearest > 0 ? '▶ ' : '◀ ') + deg + '°';
        }

        // Gyro heading drifts — objects creep sideways over minutes. Re-aiming at the QR
        // and tapping this re-zeroes forward, the same reset the Start button performs.
        recentreBtn.addEventListener('click', () => {
            referenceYaw = null;
            hint.textContent = '{{ __('Re-centred on this direction') }}';
        });

        renderer.setAnimationLoop(() => {
            applyOrientation();
            if (window.__updateGhost) window.__updateGhost();
            const t = aimed();
            reticle.classList.toggle('hot', !!t);
            animateObjects();
            if ((_guideTick++ % 10) === 0) updateGuide();
            renderer.render(scene, camera);
        });
    </script>
</body>
</html>
