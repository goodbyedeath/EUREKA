{{-- 3D controls for one game location. Included by both the card and table views of the
     game manager so the two cannot drift apart.

     There is **one** store of models: the shared library (`ar_models`). The file input
     here is not a second one — `uploadArModel()` creates the library entry and then points
     this post at it, exactly as if it had been added from the library section and picked
     from the dropdown. That was true before but nowhere on screen said so, and a "choose"
     control sitting next to an "upload" control reads as two separate stores.

     The shortcut is kept rather than removed: an admin setting up a post should not have
     to leave the page to attach a model. What changed is that the wording now says where
     the file goes. --}}
<div class="mt-2 space-y-1">
    <div class="flex items-center gap-2 flex-wrap">
        <span class="px-2 py-0.5 text-[11px] rounded-full whitespace-nowrap
            {{ $game->usesAr()
                ? 'bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-200'
                : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
            {{ $game->usesAr() ? '3D / AR' : 'No model yet' }}
        </span>

        @if($game->usesAr())
            <a href="{{ route('ar.view', $game->id) }}"
               class="inline-block bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded text-xs whitespace-nowrap">
                <i class="fas fa-cube mr-1"></i>Open 3D
            </a>
        @endif
    </div>

    {{-- Names the one store both controls below feed from, so the pair stops reading as
         two places a file could live. --}}
    <p class="text-[11px] text-gray-500 dark:text-gray-400 pt-1">
        Model from the shared
        <a href="#ar-model-library" class="text-purple-600 dark:text-purple-400 underline">3D Model Library</a>
        &mdash; {{ $arModels->count() }} available. A post does not store its own copy.
    </p>

    {{-- Pick an existing asset --}}
    <select wire:change="setArModel({{ $game->id }}, $event.target.value)"
            class="w-full text-[11px] border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded px-1 py-1">
        <option value="">— no model —</option>
        @foreach($arModels as $model)
            <option value="{{ $model->id }}" @selected($game->ar_model_id === $model->id)>
                {{ $model->name }} ({{ $model->size_for_humans }})
            </option>
        @endforeach
    </select>

    {{-- Or add a new one to the library --}}
    <div class="flex items-center gap-2">
        <input type="file" accept=".glb,.gltf" wire:model="arModelUpload"
               class="text-[11px] text-gray-600 dark:text-gray-400 file:mr-2 file:py-0.5 file:px-2
                      file:rounded file:border-0 file:text-[11px] file:bg-purple-50 file:text-purple-700">
        <button type="button" wire:click="uploadArModel({{ $game->id }})"
                wire:loading.attr="disabled" wire:target="arModelUpload,uploadArModel"
                class="bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white px-2 py-1 rounded text-[11px] whitespace-nowrap">
            <span wire:loading.remove wire:target="uploadArModel">Add to library</span>
            <span wire:loading wire:target="uploadArModel">Uploading…</span>
        </button>
    </div>
    <p class="text-[11px] text-gray-400 dark:text-gray-500">
        Uploading puts the file in the library and selects it here, so every other post can
        reuse it. .glb or .gltf, up to 30&nbsp;MB.
    </p>
    @error('arModelUpload')
        <p class="text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
