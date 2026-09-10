{{-- id is the jump target for the "from the shared library" link on every post. --}}
<div id="ar-model-library" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 lg:p-6 mb-6 scroll-mt-24">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                <i class="fas fa-cubes mr-2 text-purple-600"></i>3D Model Library
            </h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Upload once, reuse at any location or object.
                {{ $models->total() }} model{{ $models->total() === 1 ? '' : 's' }},
                {{ $totalSize >= 1048576 ? round($totalSize / 1048576, 1) . ' MB' : round($totalSize / 1024) . ' KB' }} total.
            </p>
        </div>
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search models…"
               class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg">
    </div>

    @if(session('library_message'))
        <div class="mb-3 px-3 py-2 rounded bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">
            {{ session('library_message') }}
        </div>
    @endif
    @if(session('library_error'))
        <div class="mb-3 px-3 py-2 rounded bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 text-sm">
            {{ session('library_error') }}
        </div>
    @endif

    {{-- Add --}}
    <div class="flex flex-wrap items-center gap-2 mb-4 p-3 bg-gray-50 dark:bg-gray-700/40 rounded-lg">
        <input type="file" accept=".glb,.gltf" wire:model="upload"
               class="text-xs text-gray-600 dark:text-gray-300 file:mr-2 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:bg-purple-100 file:text-purple-700">
        <input type="text" wire:model="uploadName" placeholder="Name (optional)"
               class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg">
        <button type="button" wire:click="store" wire:loading.attr="disabled" wire:target="upload,store"
                class="bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white px-3 py-1.5 rounded-lg text-sm">
            <span wire:loading.remove wire:target="store">Add model</span>
            <span wire:loading wire:target="store">Uploading…</span>
        </button>
        @error('upload') <span class="text-red-500 text-xs w-full">{{ $message }}</span> @enderror
    </div>

    {{-- List --}}
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th class="text-left py-2 pr-3">Model</th>
                    <th class="text-left py-2 pr-3">Size</th>
                    <th class="text-left py-2 pr-3">In use</th>
                    <th class="text-right py-2">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($models as $model)
                    <tr>
                        <td class="py-2 pr-3">
                            @if($editingId === $model->id)
                                <div class="flex items-center gap-2">
                                    <input type="text" wire:model="editingName" wire:keydown.enter="saveRename"
                                           class="px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded">
                                    <button wire:click="saveRename" class="text-green-600 text-xs">Save</button>
                                    <button wire:click="cancelRename" class="text-gray-400 text-xs">Cancel</button>
                                </div>
                            @else
                                <span class="text-gray-900 dark:text-gray-100">{{ $model->name }}</span>
                            @endif
                        </td>
                        <td class="py-2 pr-3 text-gray-500 dark:text-gray-400">{{ $model->size_for_humans }}</td>
                        <td class="py-2 pr-3">
                            @if($model->game_locations_count || $model->hotspots_count)
                                <span class="px-2 py-0.5 text-[11px] rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200">
                                    {{ $model->game_locations_count }} location{{ $model->game_locations_count === 1 ? '' : 's' }},
                                    {{ $model->hotspots_count }} object{{ $model->hotspots_count === 1 ? '' : 's' }}
                                </span>
                            @else
                                <span class="text-gray-400 text-xs">unused</span>
                            @endif
                        </td>
                        <td class="py-2 text-right whitespace-nowrap">
                            <a href="{{ $model->url() }}" download class="text-blue-600 dark:text-blue-400 text-xs mr-3">Download</a>
                            <button wire:click="startRename({{ $model->id }})" class="text-indigo-600 dark:text-indigo-400 text-xs mr-3">Rename</button>
                            <button wire:click="destroy({{ $model->id }})"
                                    wire:confirm="Delete this model from the library?"
                                    class="text-red-600 dark:text-red-400 text-xs">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-6 text-center text-gray-400 text-sm">
                            No models yet — add a .glb or .gltf above.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($models->hasPages())
        <div class="mt-3">{{ $models->links() }}</div>
    @endif
</div>
