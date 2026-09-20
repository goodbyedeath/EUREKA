<?php

namespace App\Livewire\Admin;

use App\Models\UserGuideSection;
use Livewire\Component;

/**
 * The player's how-to, as the crew maintains it.
 *
 * Kept deliberately plain: a title, an emoji, and sentences one per line. The app renders those
 * lines as a list, so nobody has to learn markup to fix a sentence between events — and the crew
 * will, because they hear what people actually ask at the start line.
 */
class UserGuideManager extends Component
{
    public ?int $editingId = null;

    public string $icon = '';

    public string $title = '';

    public string $body = '';

    public int $sort_order = 0;

    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'icon' => 'nullable|string|max:8',
            'title' => 'required|string|max:120',
            'body' => 'required|string|max:4000',
            'sort_order' => 'required|integer|min:0|max:9999',
            'is_active' => 'boolean',
        ];
    }

    public function edit(int $id): void
    {
        $section = UserGuideSection::findOrFail($id);

        $this->editingId = $section->id;
        $this->icon = (string) $section->icon;
        $this->title = $section->title;
        $this->body = $section->body;
        $this->sort_order = (int) $section->sort_order;
        $this->is_active = (bool) $section->is_active;
    }

    public function addSection(): void
    {
        $this->resetForm();
        $this->sort_order = ((int) UserGuideSection::max('sort_order')) + 10;
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['icon'] = $this->icon ?: null;
        $data['updated_by'] = auth()->id();

        if ($this->editingId) {
            UserGuideSection::findOrFail($this->editingId)->update($data);
            session()->flash('guide_msg', 'Bagian panduan diperbarui. Aplikasi akan menampilkannya setelah sinkron berikutnya.');
        } else {
            UserGuideSection::create($data);
            session()->flash('guide_msg', 'Bagian panduan ditambahkan.');
        }

        $this->resetForm();
    }

    public function toggle(int $id): void
    {
        $section = UserGuideSection::findOrFail($id);
        $section->update(['is_active' => ! $section->is_active, 'updated_by' => auth()->id()]);

        session()->flash('guide_msg', $section->is_active
            ? "\"{$section->title}\" ditampilkan lagi."
            : "\"{$section->title}\" disembunyikan dari panduan.");
    }

    public function delete(int $id): void
    {
        $section = UserGuideSection::findOrFail($id);
        $title = $section->title;
        $section->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        session()->flash('guide_msg', "\"{$title}\" dihapus.");
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->icon = '';
        $this->title = '';
        $this->body = '';
        $this->sort_order = 0;
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.admin.user-guide-manager', [
            'sections' => UserGuideSection::ordered()->get(),
        ]);
    }
}
