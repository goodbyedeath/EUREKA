<?php

namespace App\Livewire\User\Concerns;

trait HandlesTeamMemberFiltering
{
    public $search = '';
    public $filterPosition = '';
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    public $dateFrom = '';
    public $dateTo = '';
    public $hasEmail = false;
    public $hasPhone = false;
    public $statusFilter = '';

    public function initializeFilteringProperties()
    {
        $this->search = '';
        $this->filterPosition = '';
        $this->sortBy = 'name';
        $this->sortDirection = 'asc';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->hasEmail = false;
        $this->hasPhone = false;
        $this->statusFilter = '';
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterPosition()
    {
        $this->resetPage();
    }

    public function updatedSortBy()
    {
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->resetPage();
    }

    public function updatedHasEmail()
    {
        $this->resetPage();
    }

    public function updatedHasPhone()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->filterPosition = '';
        $this->sortBy = 'name';
        $this->sortDirection = 'asc';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->hasEmail = false;
        $this->hasPhone = false;
        $this->statusFilter = '';
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return !empty($this->search) 
            || !empty($this->filterPosition)
            || !empty($this->dateFrom)
            || !empty($this->dateTo)
            || $this->hasEmail
            || $this->hasPhone
            || !empty($this->statusFilter);
    }

    public function getSortOptions(): array
    {
        return [
            'name' => 'Nama',
            'email' => 'Email',
            'position' => 'Jabatan',
            'created_at' => 'Tanggal Bergabung',
        ];
    }

    public function getStatusOptions(): array
    {
        return [
            '' => 'Semua Status',
            'active' => 'Aktif',
            'inactive' => 'Tidak Aktif',
            'leader' => 'Pemimpin',
            'member' => 'Anggota'
        ];
    }

    protected function getTeamMemberListeners(): array
    {
        return array_merge(
            $this->getFilteringListeners() ?? [],
            $this->getActionListeners() ?? [],
            $this->getModalListeners() ?? []
        );
    }

    protected function getFilteringListeners(): array
    {
        return [
            'updatedSearch',
            'updatedFilterPosition',
            'updatedSortBy',
            'updatedDateFrom',
            'updatedDateTo',
            'updatedHasEmail',
            'updatedHasPhone',
            'updatedStatusFilter',
            'clearFilters',
            'sortBy',
        ];
    }

    protected function getActionListeners(): array
    {
        return [];
    }

    protected function getModalListeners(): array
    {
        return [];
    }
}
