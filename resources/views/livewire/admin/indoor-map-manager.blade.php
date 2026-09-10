{{--
    Indoor map authoring.

    Deliberately modal-free. The two forms here carry thirteen fields between them, and a
    thirteen-field form floating over the very picture you need to look at — taller than a
    phone screen, with no Back button — is the pattern this app had too much of. Each form
    replaces the workspace instead, and returns to it.

    Modals are kept only for confirmations, where what is behind must stay on screen.
--}}
<div class="container mx-auto px-4 py-8">

    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Indoor Maps</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 max-w-2xl">
                A top-view plan of the venue with the outposts marked on it. Teams use this instead of
                the GPS map indoors, where satellites cannot reach.
            </p>
        </div>
        <div class="flex items-center gap-3">
            @if (session('indoor_msg'))
                <span class="px-3 py-2 rounded-md bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-200 text-sm">
                    {{ session('indoor_msg') }}
                </span>
            @endif
            @unless ($showMapModal || $showSpotModal)
                <button type="button" wire:click="openMapModal"
                        class="px-4 py-2 rounded-md bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium">
                    + New plan
                </button>
            @endunless
        </div>
    </div>

    {{-- ============================== PLAN FORM ============================== --}}
    @if ($showMapModal)
        <button type="button" wire:click="$set('showMapModal', false)"
                class="mb-4 inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
            &larr; Back to plans
        </button>

        <div class="max-w-2xl space-y-5">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ $editingMapId ? 'Edit plan' : 'New plan' }}
            </h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                <input type="text" wire:model="name" placeholder="e.g. Ballroom, level 2"
                       class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                <textarea wire:model="description" rows="2"
                          class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Plan image {{ $editingMapId ? '(leave empty to keep the current one)' : '' }}
                </label>
                <input type="file" wire:model="imageUpload" accept="image/*"
                       class="mt-1 w-full text-sm text-gray-600 dark:text-gray-300">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    A top-view drawing or photo of the venue. JPEG, PNG or WebP, up to 8 MB.
                </p>
                @error('imageUpload') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                <div wire:loading wire:target="imageUpload" class="text-xs text-blue-600 mt-1">Uploading…</div>
            </div>

            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Opening sequence</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                    Teams scan the START code to begin, answer the clue, and only then see this plan.
                    Leave the clue empty to send them straight to the plan.
                </p>

                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                    The START code now lives in <span class="font-medium">Race Start</span>, because an
                    outdoor event has a start line too and no plan to hang it from. Link a start code
                    to this plan there, and scanning it will lead teams to the clue below.
                </p>

                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mt-3">Clue question</label>
                <textarea wire:model="clueQuestion" rows="2" placeholder="Where the first act ends…"
                          class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"></textarea>

                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mt-3">Accepted answer</label>
                <input type="text" wire:model="clueAnswer" placeholder="backstage"
                       class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Case and spacing are ignored when matching.</p>
                @error('clueAnswer') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="flex gap-2 pt-2">
                <button type="button" wire:click="saveMap"
                        class="px-4 py-2.5 text-sm rounded-md bg-blue-600 hover:bg-blue-700 text-white font-medium">Save plan</button>
                <button type="button" wire:click="$set('showMapModal', false)"
                        class="px-4 py-2.5 text-sm rounded-md bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200">Cancel</button>
            </div>
        </div>

    {{-- ============================== SPOT FORM ============================== --}}
    @elseif ($showSpotModal)
        <button type="button" wire:click="$set('showSpotModal', false)"
                class="mb-4 inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
            &larr; Back to the plan
        </button>

        {{-- Appearance is previewed entirely in the browser.

             It used to be `wire:model.live` on the colour, size and shape: a colour input
             emits `input` continuously while the pointer moves inside the wheel, and each
             event was one POST to /livewire/update that re-rendered this whole component
             and shipped ~7 KB of HTML back. One drag across the colour wheel was hundreds
             of requests, which is what tripped the host's rate limiter into 429s.

             Nothing about picking a colour needs the server. Alpine holds the values,
             the preview reads them, and `wire:model` (deferred, no request) carries them
             along on Save. --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6"
             x-data="{
                 colour: @js($spotColor),
                 size: {{ (int) $spotSize }},
                 shape: @js($spotShape),
                 get radius() {
                     return this.shape === 'circle' ? '9999px'
                          : this.shape === 'pin'    ? '9999px 9999px 9999px 2px'
                          : '4px';
                 },
             }">
            {{-- The plan stays beside the form on a wide screen: you are describing a place,
                 and it helps to see where the marker you are describing actually sits. --}}
            @if ($map)
                <div class="order-2 lg:order-1">
                    <div class="relative inline-block w-full border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <img src="{{ $map->imageUrl() }}" alt="{{ $map->name }}" class="block w-full">
                        <span :style="`position:absolute; left:{{ $spotX }}%; top:{{ $spotY }}%;
                                       width:${size}px; height:${size}px; background:${colour};
                                       transform:translate(-50%,-50%) ${shape === 'diamond' ? 'rotate(45deg)' : ''};
                                       border-radius:${radius};
                                       border:3px solid #fff; box-shadow:0 0 0 4px rgba(59,130,246,.6)`"></span>
                    </div>
                </div>
            @endif

            <div class="order-1 lg:order-2 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ $spotId ? 'Edit spot' : 'New spot' }}
                    <span class="text-sm font-normal text-gray-500">at {{ round($spotX) }}%, {{ round($spotY) }}%</span>
                </h2>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                    <input type="text" wire:model="spotName" placeholder="e.g. Outpost 3 — stage left"
                           class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                    @error('spotName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shape</label>
                        <select wire:model="spotShape" x-model="shape"
                                class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                            <option value="circle">Circle</option>
                            <option value="square">Square</option>
                            <option value="diamond">Diamond</option>
                            <option value="pin">Pin</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Colour</label>
                        <input type="color" wire:model="spotColor" x-model="colour"
                               class="mt-1 w-full h-[42px] px-1 py-1 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Size</label>
                        <input type="number" wire:model="spotSize" x-model.number="size" min="12" max="72"
                               class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Text shown on tap</label>
                    <textarea wire:model="spotContent" rows="3" placeholder="The clue, or a description of the outpost"
                              class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Photo shown on tap</label>
                    <input type="file" wire:model="spotImageUpload" accept="image/*"
                           class="mt-1 w-full text-sm text-gray-600 dark:text-gray-300">
                    @error('spotImageUpload') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    <div wire:loading wire:target="spotImageUpload" class="text-xs text-blue-600 mt-1">Uploading…</div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Linked outpost</label>
                    <select wire:model="spotGameLocationId"
                            class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                        <option value="">— none —</option>
                        @foreach ($gameLocations as $gl)
                            <option value="{{ $gl->id }}">{{ $gl->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Optional. Links this marker to the AR outpost it stands for.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2 pt-2">
                    <button type="button" wire:click="saveSpot"
                            class="px-4 py-2.5 text-sm rounded-md bg-blue-600 hover:bg-blue-700 text-white font-medium">Save spot</button>
                    <button type="button" wire:click="$set('showSpotModal', false)"
                            class="px-4 py-2.5 text-sm rounded-md bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200">Cancel</button>
                    @if ($spotId)
                        <button type="button" wire:click="deleteSpot({{ $spotId }})"
                                wire:confirm="Remove this spot?"
                                class="ml-auto px-4 py-2.5 text-sm rounded-md bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300">
                            Remove
                        </button>
                    @endif
                </div>
            </div>
        </div>

    {{-- ============================== WORKSPACE ============================== --}}
    @else
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

            <div class="lg:col-span-1 space-y-1">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Plans</h2>
                @forelse ($maps as $m)
                    <div class="flex items-stretch gap-1" wire:key="plan-{{ $m->id }}">
                        <button type="button" wire:click="selectMap({{ $m->id }})"
                                class="flex-1 text-left px-3 py-2 rounded-md text-sm transition-colors
                                       {{ $map && $map->id === $m->id
                                          ? 'bg-blue-600 text-white'
                                          : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                            {{ $m->name }}
                        </button>
                        <button type="button" wire:click="openMapModal({{ $m->id }})" title="Edit plan"
                                class="px-2 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 text-xs">
                            &#9998;
                        </button>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No plans yet. Upload one to begin.</p>
                @endforelse
            </div>

            <div class="lg:col-span-3">
                @if (! $map)
                    <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-10 text-center text-gray-500 dark:text-gray-400">
                        Upload a plan, or pick one on the left.
                    </div>
                @else
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ $map->name }}</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $map->spots->count() }} spot</span>
                                on this plan. Tap anywhere on the picture to add another — there is no
                                limit, and each one can point at a different outpost. Drag a marker to
                                move it; tap a marker to edit it.
                            </p>
                        </div>
                        <button type="button" wire:click="deleteMap({{ $map->id }})"
                                wire:confirm="Delete this plan and all its spots?"
                                class="px-3 py-1.5 text-xs rounded-md bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 hover:bg-red-200">
                            Delete plan
                        </button>
                    </div>

                    {{-- Click-to-place, drag-to-move. Coordinates go to the server as
                         percentages so they survive any display width; the same numbers
                         drive the team's view.

                         The editor object is written out inline rather than registered
                         with Alpine.data() in a Livewire script block. Those blocks run on
                         Livewire's "effect" hook, which fires AFTER Alpine has already
                         evaluated x-data on this element — so the factory did not exist
                         yet and x-data threw a ReferenceError. That killed the whole
                         editor: no click-to-place, no drag, and no error an admin could
                         see. Inline has no such ordering problem.

                         An earlier version of this same logic did not drag in the browser
                         even though the logic itself was correct in isolation, so this
                         rewrite removes every plausible meeting point with Alpine at once
                         rather than guessing which one it was:

                         - Magics are reached through `this` (`this.$refs`, `this.$wire`),
                           the documented form, instead of bare `$refs` / `$wire` relying on
                           the `with` scope surviving into a method body.
                         - Move and release listeners are bound to the marker itself, not to
                           window. setPointerCapture retargets every later move to it, so
                           this is both simpler and independent of bubbling.
                         - The dragged element lives in a plain closure variable, never on
                           the Alpine object. Only the boolean stays reactive.

                         Pointer events, not mouse events, so the same code works with a
                         finger on a tablet. --}}
                    <div class="relative inline-block w-full border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-gray-50 dark:bg-gray-900"
                         x-data="{
                             moved: false,

                             pct(el, ev) {
                                 const r = el.getBoundingClientRect();
                                 return {
                                     x: Math.min(100, Math.max(0, ((ev.clientX - r.left) / r.width) * 100)),
                                     y: Math.min(100, Math.max(0, ((ev.clientY - r.top) / r.height) * 100)),
                                 };
                             },

                             place(ev) {
                                 if (this.moved) return;
                                 const p = this.pct(this.$refs.plan, ev);
                                 this.$wire.placeSpot(p.x, p.y);
                             },

                             startDrag(ev, id) {
                                 const el = ev.currentTarget;
                                 const plan = this.$refs.plan;
                                 const wire = this.$wire;
                                 const self = this;
                                 let moved = false;

                                 el.style.cursor = 'grabbing';
                                 try { el.setPointerCapture(ev.pointerId); } catch (err) {}

                                 const move = (e) => {
                                     moved = true;
                                     self.moved = true;
                                     const p = self.pct(plan, e);
                                     el.style.left = p.x + '%';
                                     el.style.top = p.y + '%';
                                 };

                                 const up = (e) => {
                                     el.removeEventListener('pointermove', move);
                                     el.removeEventListener('pointerup', up);
                                     el.removeEventListener('pointercancel', up);
                                     el.style.cursor = 'grab';
                                     if (moved) {
                                         const p = self.pct(plan, e);
                                         wire.moveSpot(id, p.x, p.y);
                                     }
                                     setTimeout(() => { self.moved = false; }, 0);
                                 };

                                 el.addEventListener('pointermove', move);
                                 el.addEventListener('pointerup', up);
                                 el.addEventListener('pointercancel', up);
                             },

                             maybeEdit(id) {
                                 if (this.moved) return;
                                 this.$wire.editSpot(id);
                             },
                         }">

                        <img src="{{ $map->imageUrl() }}" alt="{{ $map->name }}"
                             class="block w-full select-none" draggable="false"
                             x-ref="plan" @click="place($event)">

                        @foreach ($map->spots as $spot)
                            {{-- touch-action:none stops the page panning under a finger that
                                 is dragging a marker. No preventDefault on pointerdown: it
                                 would also cancel the tap that opens the spot for editing. --}}
                            <button type="button" wire:key="spot-{{ $spot->id }}"
                                    @pointerdown.stop="startDrag($event, {{ $spot->id }})"
                                    @click.stop="maybeEdit({{ $spot->id }})"
                                    title="{{ $spot->name }} — drag to move, tap to edit"
                                    style="position:absolute; left:{{ $spot->x }}%; top:{{ $spot->y }}%;
                                           width:{{ $spot->size }}px; height:{{ $spot->size }}px;
                                           background:{{ $spot->color }};
                                           transform:translate(-50%,-50%) {{ $spot->shape === 'diamond' ? 'rotate(45deg)' : '' }};
                                           border-radius:{{ $spot->shape === 'circle' ? '9999px' : ($spot->shape === 'pin' ? '9999px 9999px 9999px 2px' : '4px') }};
                                           opacity:{{ $spot->is_active ? 1 : 0.35 }};
                                           border:2px solid rgba(255,255,255,.85); box-shadow:0 1px 6px rgba(0,0,0,.45);
                                           cursor:grab; touch-action:none;">
                            </button>
                        @endforeach
                    </div>

                    @if ($map->spots->count())
                        <div class="mt-4 overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="text-left px-4 py-2 font-semibold text-gray-600 dark:text-gray-300">Spot</th>
                                        <th class="text-left px-4 py-2 font-semibold text-gray-600 dark:text-gray-300">Shows on tap</th>
                                        <th class="text-left px-4 py-2 font-semibold text-gray-600 dark:text-gray-300">Outpost</th>
                                        <th class="text-right px-4 py-2 font-semibold text-gray-600 dark:text-gray-300">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($map->spots as $spot)
                                        <tr wire:key="row-{{ $spot->id }}" class="{{ $spot->is_active ? '' : 'opacity-50' }}">
                                            <td class="px-4 py-2">
                                                <span class="inline-block w-3 h-3 rounded-full align-middle mr-2" style="background:{{ $spot->color }}"></span>
                                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $spot->name }}</span>
                                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $spot->shape }} · {{ round($spot->x) }}%, {{ round($spot->y) }}%</span>
                                            </td>
                                            <td class="px-4 py-2 text-gray-600 dark:text-gray-400">
                                                @if ($spot->hasContent())
                                                    {{ $spot->image_path ? 'photo' : '' }}{{ $spot->image_path && $spot->content ? ' + ' : '' }}{{ $spot->content ? 'text' : '' }}
                                                @else
                                                    <span class="text-gray-400">marker only</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ $spot->gameLocation->name ?? '—' }}</td>
                                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                                <button type="button" wire:click="toggleSpot({{ $spot->id }})"
                                                        class="px-2 py-1 text-xs rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                                                    {{ $spot->is_active ? 'Hide' : 'Show' }}
                                                </button>
                                                <button type="button" wire:click="editSpot({{ $spot->id }})"
                                                        class="px-2 py-1 text-xs rounded bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                                                    Edit
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endif
</div>
