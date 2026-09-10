{{--
    Race Start.

    Three states, never stacked: the list, the form, and the printable QR page. One root
    element holding one branch chain — Livewire needs exactly one root, and a state that
    renders two siblings comes apart on every update.
--}}
<div class="container mx-auto px-4 py-8">

@if ($showQr && $qrStart)

    @include('admin.partials.back-link', ['action' => 'closeQr', 'label' => 'Back to start codes'])

    <div class="max-w-lg">
        <div id="start-qr-sheet"
             class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 text-center">
            <h2 class="text-lg font-semibold text-gray-900">{{ $qrStart->name }}</h2>
            <p class="text-sm text-gray-500 mt-1">
                {{ $qrStart->isIndoor() ? 'Indoor · ' . ($qrStart->indoorMap->name ?? 'plan removed') : 'Outdoor' }}
            </p>

            {{-- White ground and dark ink regardless of the admin's theme: this block is
                 what gets printed, and a dark-mode QR photographs as an unreadable grey. --}}
            <div class="mt-5 inline-block bg-white p-4 rounded-lg border-2 border-gray-100">
                {!! $this->qrSvg(260) !!}
            </div>

            <p class="mt-4 font-mono text-lg font-bold tracking-wider text-gray-900">{{ $qrStart->code }}</p>
            <p class="text-xs text-gray-500 mt-1">
                Scan at the start line, or type this code in if the camera will not focus.
            </p>
        </div>

        @unless ($qrStart->is_active)
            <p class="mt-3 text-sm px-3 py-2 rounded-md bg-amber-50 border border-amber-200 text-amber-800">
                This code is <strong>deactivated</strong> — scanning it will not start anyone's clock.
                Activate it from the list before the event.
            </p>
        @endunless

        <div class="mt-4 grid grid-cols-2 gap-3">
            <button type="button" wire:click="downloadQr('png', 1024)"
                    class="px-4 py-2.5 text-sm rounded-md bg-blue-600 hover:bg-blue-700 text-white font-medium">
                <i class="fas fa-download mr-1"></i> Download PNG
            </button>
            <button type="button" wire:click="downloadQr('svg', 1024)"
                    class="px-4 py-2.5 text-sm rounded-md bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-medium">
                <i class="fas fa-download mr-1"></i> Download SVG
            </button>
            <button type="button" onclick="printStartQr()"
                    class="px-4 py-2.5 text-sm rounded-md bg-gray-100 hover:bg-gray-200 text-gray-800 border border-gray-300">
                <i class="fas fa-print mr-1"></i> Print
            </button>
            <button type="button" onclick="copyStartCode(@js($qrStart->code))"
                    class="px-4 py-2.5 text-sm rounded-md bg-gray-100 hover:bg-gray-200 text-gray-800 border border-gray-300">
                <i class="fas fa-copy mr-1"></i> Copy code
            </button>
        </div>

        <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
            SVG scales to any paper size without going blurry — use it if you are printing A4 or
            larger. PNG is 1024&nbsp;px, which is enough for anything up to A5.
        </p>
    </div>


@elseif ($showForm)

    @include('admin.partials.back-link', ['action' => 'closeForm', 'label' => 'Back to start codes'])

    <div class="max-w-2xl space-y-5">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ $editingId ? 'Edit start code' : 'New start code' }}
        </h2>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
            <input type="text" wire:model="name" placeholder="e.g. Company outing, 12 Sept"
                   class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">For your own reference; teams never see it.</p>
            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">The code</label>
            <div class="flex gap-2 mt-1">
                <input type="text" wire:model="code"
                       class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 font-mono">
                <button type="button" wire:click="generate"
                        class="px-3 py-2 text-sm rounded-md bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-100 whitespace-nowrap">
                    Generate
                </button>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Save first, then print it from the QR button in the list. Keep it unguessable — a
                team that reads it early could start their clock before the race does.
            </p>
            @error('code') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mode</label>
            <select wire:model="indoorMapId"
                    class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                <option value="">Outdoor — start the clock, go to the GPS map</option>
                @foreach ($maps as $m)
                    <option value="{{ $m->id }}">Indoor — {{ $m->name }} (clue, then the plan)</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                This is the only difference between the two modes. The clock itself behaves
                identically: it starts on this scan and stops when the last counting post is cleared.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" wire:model="isActive" id="isActive"
                   class="h-4 w-4 rounded border-gray-300">
            <label for="isActive" class="text-sm text-gray-900 dark:text-gray-100">Active</label>
        </div>

        <div class="flex gap-2 pt-2">
            <button type="button" wire:click="save"
                    class="px-4 py-2.5 text-sm rounded-md bg-blue-600 hover:bg-blue-700 text-white font-medium">Save</button>
            <button type="button" wire:click="closeForm"
                    class="px-4 py-2.5 text-sm rounded-md bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200">Cancel</button>
        </div>
    </div>

@else

    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Race Start</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 max-w-2xl">
                The code a team scans at the start line. Print it as a QR and put it where the race
                begins. Scanning it starts that team's clock; the clock stops on its own once they
                have cleared every post that counts toward finishing.
            </p>
        </div>
        <div class="flex items-center gap-3">
            @if (session('race_msg'))
                <span class="px-3 py-2 rounded-md bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-200 text-sm">
                    {{ session('race_msg') }}
                </span>
            @endif
            <button type="button" wire:click="openForm"
                    class="px-4 py-2 rounded-md bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium">
                + New start code
            </button>
        </div>
    </div>

    @if ($starts->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-10 text-center text-gray-500 dark:text-gray-400">
            No start code yet. Without one no race clock runs — teams can still play, they just
            will not be timed.
        </div>
    @else
        <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="text-left px-4 py-2 font-semibold text-gray-600 dark:text-gray-300">Name</th>
                        <th class="text-left px-4 py-2 font-semibold text-gray-600 dark:text-gray-300">Code</th>
                        <th class="text-left px-4 py-2 font-semibold text-gray-600 dark:text-gray-300">Mode</th>
                        <th class="text-left px-4 py-2 font-semibold text-gray-600 dark:text-gray-300">Used by</th>
                        <th class="text-right px-4 py-2 font-semibold text-gray-600 dark:text-gray-300">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($starts as $start)
                        <tr wire:key="start-{{ $start->id }}" class="{{ $start->is_active ? '' : 'opacity-50' }}">
                            <td class="px-4 py-2 font-medium text-gray-900 dark:text-gray-100">{{ $start->name }}</td>
                            <td class="px-4 py-2 font-mono text-gray-700 dark:text-gray-300">{{ $start->code }}</td>
                            <td class="px-4 py-2 text-gray-600 dark:text-gray-400">
                                @if ($start->isIndoor())
                                    Indoor &middot; {{ $start->indoorMap->name ?? 'plan removed' }}
                                @else
                                    Outdoor
                                @endif
                            </td>
                            <td class="px-4 py-2 text-gray-600 dark:text-gray-400">
                                {{ $usage[$start->id] ?? 0 }} team(s)
                            </td>
                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                <button type="button" wire:click="openQr({{ $start->id }})"
                                        class="px-2 py-1 text-xs rounded bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300">
                                    <i class="fas fa-qrcode mr-1"></i>QR
                                </button>
                                <button type="button" wire:click="toggle({{ $start->id }})"
                                        class="px-2 py-1 text-xs rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                                    {{ $start->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                                <button type="button" wire:click="openForm({{ $start->id }})"
                                        class="px-2 py-1 text-xs rounded bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                                    Edit
                                </button>
                                <button type="button" wire:click="delete({{ $start->id }})"
                                        wire:confirm="Remove this start code? Runs already timed with it keep their times."
                                        class="px-2 py-1 text-xs rounded bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300">
                                    Remove
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

@endif

    @script
    <script>
        // Print just the sheet. Sending the whole admin page to the printer wastes a sheet
        // on the sidebar and the topbar before it reaches the code.
        window.printStartQr = function () {
            const sheet = document.getElementById('start-qr-sheet');
            if (!sheet) return;

            const w = window.open('', '_blank');
            w.document.write(
                '<html><head><title>Start code</title><style>' +
                'body{font-family:system-ui,Arial,sans-serif;text-align:center;padding:40px;margin:0}' +
                'svg{width:320px;height:320px}' +
                '.code{font-family:monospace;font-size:26px;font-weight:bold;margin-top:18px;letter-spacing:2px}' +
                '</style></head><body>' + sheet.innerHTML + '</body></html>'
            );
            w.document.close();
            w.onload = () => { w.print(); w.close(); };
        };

        window.copyStartCode = function (code) {
            const done = () => window.dispatchEvent(new CustomEvent('start-code-copied'));
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(code).then(done).catch(() => fallback(code, done));
            } else {
                fallback(code, done);
            }

            function fallback(text, cb) {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.left = '-9999px';
                document.body.appendChild(ta);
                ta.select();
                try { document.execCommand('copy'); cb(); } catch (e) { /* nothing to do */ }
                document.body.removeChild(ta);
            }
        };
    </script>
    @endscript


</div>
