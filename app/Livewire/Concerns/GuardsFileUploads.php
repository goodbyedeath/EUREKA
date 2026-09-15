<?php

namespace App\Livewire\Concerns;

use Livewire\WithFileUploads;

/**
 * WithFileUploads, minus one HTTP 500.
 *
 * The browser half of a Livewire upload calls _finishUpload with the paths the upload request returned.
 * On 15 Sep an admin's logo upload reached _finishUpload with no paths — the file itself had arrived
 * intact, but the browser's upload queue had fallen out of step — and WithFileUploads read $tmpPath[0]:
 * "Undefined array key 0", a 500, then "Cannot read properties of undefined (reading 'shift')" in the
 * browser. The upload endpoint was verified healthy through the CDN up to 8 MB, so the fault is
 * client-side and cannot be fixed at its source here.
 *
 * An empty finish is therefore treated as a failed upload: an error on the field, and the browser's
 * queue released (upload:errored), so picking the file again starts clean.
 */
trait GuardsFileUploads
{
    use WithFileUploads {
        _finishUpload as protected livewireFinishUpload;
    }

    // Same signature and default as Livewire's own (WithFileUploads::_finishUpload, $append = false).
    public function _finishUpload($name, $tmpPath, $isMultiple, $append = false)
    {
        if (collect((array) $tmpPath)->filter()->isEmpty()) {
            $this->dispatch('upload:errored', name: $name)->self();
            $this->addError($name, 'Upload tidak lengkap. Pilih file lagi.');

            return null;
        }

        return $this->livewireFinishUpload($name, $tmpPath, $isMultiple, $append);
    }
}
