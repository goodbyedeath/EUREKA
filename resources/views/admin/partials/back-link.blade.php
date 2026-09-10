{{--
    The way back out of a page that replaced a modal.

    A modal has a visible X and a backdrop to click; a page has neither, so without this a
    screen like the question editor is a trap. Every converted screen uses the same link in
    the same place so the way out is always where it was last time.

    Usage: @include('admin.partials.back-link', ['action' => 'closeEditModal', 'label' => '...'])
--}}
<div class="mb-4">
    <button type="button" wire:click="{{ $action }}"
            class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300
                   hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
        <span aria-hidden="true">&larr;</span>
        <span>{{ $label ?? 'Back' }}</span>
    </button>
</div>
