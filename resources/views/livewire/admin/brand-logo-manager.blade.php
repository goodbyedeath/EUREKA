{{--
    Logo of the event.

    Two variants because the app uses two shapes and one cannot stand in for the other: a
    wide wordmark in headers, a square mark for the favicon and app icon. Each is shown at
    the size it will actually appear, on both a light and a dark ground — a logo that only
    works on white is the commonest thing to discover too late.
--}}
<div class="mb-8">

    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Event identity</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 max-w-2xl mt-1">
                The name and logo, used everywhere at once: the browser tab, the landing page,
                the login screen, this admin panel, the team app, the LED board and the
                exported PDF report. Set it once and the whole event is dressed.
            </p>
        </div>
        @if (session('brand_msg'))
            <span class="px-3 py-2 rounded-md bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-200 text-sm whitespace-nowrap">
                {{ session('brand_msg') }}
            </span>
        @endif
    </div>

    {{-- ------------------------------------------------------------ name --}}
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 mb-4">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Name</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
            Replaces &ldquo;EUREKA&rdquo; wherever it appeared &mdash; including the browser tab,
            which is where you have been seeing it.
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Event name</label>
                <input type="text" wire:model="appName" maxlength="60" placeholder="Questerra"
                       class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                @error('appName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Tagline <span class="font-normal text-gray-400">(optional)</span>
                </label>
                <input type="text" wire:model="tagline" maxlength="120" placeholder="FEXDI X IFSE 2026"
                       class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                @error('tagline') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- The tab is exactly where the operator noticed the old name, so show what it
             will read rather than describing it. --}}
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <span class="text-xs text-gray-500 dark:text-gray-400">Browser tab will read:</span>
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-t-md bg-gray-100 dark:bg-gray-700 border border-b-0 border-gray-300 dark:border-gray-600 text-sm text-gray-800 dark:text-gray-100">
                <img src="{{ \App\Models\BrandSetting::iconUrl() }}" alt="" class="w-4 h-4 object-contain">
                <span>Admin - <span class="font-medium">{{ $appName ?: '…' }}</span></span>
            </span>
        </div>

        <button type="button" wire:click="saveName"
                class="mt-4 px-4 py-2 text-sm rounded-md bg-blue-600 hover:bg-blue-700 text-white font-medium">
            Save name
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- ------------------------------------------------------- wide logo --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5">
            <div class="flex items-center justify-between gap-2 mb-1">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Wide logo</h3>
                <span class="text-xs px-2 py-0.5 rounded {{ $brand->usesCustomHorizontal()
                        ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300'
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">
                    {{ $brand->usesCustomHorizontal() ? 'Custom' : 'Default' }}
                </span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                Headers and the PDF report. Landscape, roughly 4:1. PNG with a transparent
                background reads best. Up to 2&nbsp;MB.
            </p>

            {{-- Shown on both grounds: the admin panel and the team app each have a dark
                 mode, and the LED board is dark all the time. --}}
            <div class="grid grid-cols-2 gap-2 mb-3">
                <div class="rounded-lg border border-gray-200 bg-white p-3 flex items-center justify-center min-h-[68px]">
                    <img src="{{ \App\Models\BrandSetting::horizontalUrl() }}" alt="Wide logo on white" class="h-8 w-auto max-w-full">
                </div>
                <div class="rounded-lg border border-gray-700 bg-gray-900 p-3 flex items-center justify-center min-h-[68px]">
                    <img src="{{ \App\Models\BrandSetting::horizontalUrl() }}" alt="Wide logo on dark" class="h-8 w-auto max-w-full">
                </div>
            </div>

            <input type="file" wire:model="horizontalUpload" accept="image/png,image/jpeg,image/webp"
                   class="w-full text-sm text-gray-600 dark:text-gray-300">
            <div wire:loading wire:target="horizontalUpload" class="text-xs text-blue-600 mt-1">Uploading&hellip;</div>
            @error('horizontalUpload') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

            <div class="flex flex-wrap gap-2 mt-3">
                <button type="button" wire:click="saveHorizontal" @disabled(! $horizontalUpload)
                        class="px-4 py-2 text-sm rounded-md bg-blue-600 hover:bg-blue-700 text-white font-medium disabled:opacity-40 disabled:cursor-not-allowed">
                    Use this logo
                </button>
                @if ($brand->usesCustomHorizontal())
                    <button type="button" wire:click="resetHorizontal"
                            wire:confirm="Go back to the logo the app shipped with?"
                            class="px-4 py-2 text-sm rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                        Restore default
                    </button>
                @endif
            </div>
        </div>

        {{-- ---------------------------------------------------- square icon --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5">
            <div class="flex items-center justify-between gap-2 mb-1">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Square icon</h3>
                <span class="text-xs px-2 py-0.5 rounded {{ $brand->usesCustomIcon()
                        ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300'
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">
                    {{ $brand->usesCustomIcon() ? 'Custom' : 'Default' }}
                </span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                Browser tab, phone home screen, landing and login pages.
                <strong>Upload it square, at least 512&times;512.</strong> A non-square file
                still works &mdash; it is padded to a square automatically &mdash; but it will
                sit in the middle with space around it.
            </p>

            <div class="grid grid-cols-2 gap-2 mb-3">
                <div class="rounded-lg border border-gray-200 bg-white p-3 flex items-center justify-center gap-3 min-h-[68px]">
                    <img src="{{ \App\Models\BrandSetting::iconUrl() }}" alt="Icon on white" class="w-10 h-10 object-contain">
                    <img src="{{ \App\Models\BrandSetting::iconUrl() }}" alt="Icon at tab size" class="w-4 h-4 object-contain">
                </div>
                <div class="rounded-lg border border-gray-700 bg-gray-900 p-3 flex items-center justify-center gap-3 min-h-[68px]">
                    <img src="{{ \App\Models\BrandSetting::iconUrl() }}" alt="Icon on dark" class="w-10 h-10 object-contain">
                    <img src="{{ \App\Models\BrandSetting::iconUrl() }}" alt="Icon at tab size on dark" class="w-4 h-4 object-contain">
                </div>
            </div>

            {{-- What the phone will actually get. The manifest icon is generated, so this
                 shows the generated square rather than the raw upload — otherwise the
                 preview would flatter a file that Android is about to reject. --}}
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 mb-3">
                <div class="flex items-center gap-3">
                    <div class="shrink-0 w-14 h-14 rounded-2xl bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 flex items-center justify-center overflow-hidden">
                        <img src="{{ \App\Services\BrandIconService::url(192) }}" alt="Home screen icon" class="w-12 h-12 object-contain">
                    </div>
                    <div class="min-w-0 text-xs">
                        <div class="font-semibold text-gray-800 dark:text-gray-200">On a phone's home screen</div>
                        @if ($iconSize)
                            @php $square = $iconSize['width'] === $iconSize['height']; @endphp
                            <div class="text-gray-500 dark:text-gray-400 mt-0.5">
                                Your file is {{ $iconSize['width'] }}&times;{{ $iconSize['height'] }}.
                                @if ($square && $iconSize['width'] >= 512)
                                    <span class="text-green-600 dark:text-green-400">Ideal.</span>
                                @elseif ($square)
                                    <span class="text-amber-600 dark:text-amber-400">Square, but under 512&nbsp;px &mdash; it will look soft on a high-density screen.</span>
                                @else
                                    <span class="text-amber-600 dark:text-amber-400">Not square &mdash; padded to fit, so it will look smaller than it could.</span>
                                @endif
                            </div>
                        @endif
                        <div class="text-gray-500 dark:text-gray-400 mt-1">
                            192 and 512&nbsp;px squares are generated from your file automatically.
                        </div>
                    </div>
                </div>
            </div>

            <input type="file" wire:model="iconUpload" accept="image/png,image/jpeg,image/webp"
                   class="w-full text-sm text-gray-600 dark:text-gray-300">
            <div wire:loading wire:target="iconUpload" class="text-xs text-blue-600 mt-1">Uploading&hellip;</div>
            @error('iconUpload') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

            <div class="flex flex-wrap gap-2 mt-3">
                <button type="button" wire:click="saveIcon" @disabled(! $iconUpload)
                        class="px-4 py-2 text-sm rounded-md bg-blue-600 hover:bg-blue-700 text-white font-medium disabled:opacity-40 disabled:cursor-not-allowed">
                    Use this icon
                </button>
                @if ($brand->usesCustomIcon())
                    <button type="button" wire:click="resetIcon"
                            wire:confirm="Go back to the icon the app shipped with?"
                            class="px-4 py-2 text-sm rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                        Restore default
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-4">
        <p class="text-sm text-blue-900 dark:text-blue-200 mb-2">
            <strong>A new logo appears immediately</strong> on every page &mdash; the address
            carries a version stamp, so nobody has to clear their cache.
        </p>
        <p class="text-sm text-blue-900 dark:text-blue-200 mb-0">
            <strong>Two exceptions, and both are the phone's doing, not the app's.</strong>
            The browser-tab icon can lag a few minutes because the browser caches it hard. And
            if the app has already been <em>installed</em> to a home screen, that shortcut keeps
            the icon it was created with &mdash; Android and iOS both bake it in at install
            time. To see a new icon there, remove the shortcut and add it again.
        </p>
    </div>

    <hr class="mt-8 border-gray-200 dark:border-gray-700">
</div>
