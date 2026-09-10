<div class="container mx-auto px-4 py-6">

    @unless($showModal)
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Account Management</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Kelola pengguna dan akses sistem</p>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="mb-4 bg-green-100 dark:bg-green-800 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-200 px-4 py-3 rounded relative">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 bg-red-100 dark:bg-red-800 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-200 px-4 py-3 rounded relative">
            {{ session('error') }}
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Accounts</dt>
                        <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $totalUsers }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Admin</dt>
                        <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $totalAdmins }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Teams</dt>
                        <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $totalRegularUsers }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Actions -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
            <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
                <!-- Search -->
                <div class="relative">
                    <input type="text" 
                           wire:model.live.debounce.300ms="search" 
                           placeholder="Cari akun..."
                           class="w-full md:w-64 pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <!-- Role Filter -->
                <select wire:model.live="selectedRole" class="px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                    <option value="">Semua Role</option>
                    <option value="admin">Admin</option>
                    <option value="user">Team</option>
                </select>

                <!-- Team Filter -->
                <select wire:model.live="selectedTeam" class="px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                    <option value="">Semua Team</option>
                    @foreach($teams as $team)
                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Add User Button -->
            <button wire:click="openModal" 
                    class="bg-blue-600 dark:bg-blue-600 hover:bg-blue-700 dark:hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <span>Tambah Akun</span>
            </button>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <!-- Mobile Card View (Hidden on Desktop) -->
        <div class="block md:hidden">
            @forelse($users as $user)
                <div class="border-b border-gray-200 dark:border-gray-700 p-4 transition-colors duration-200">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </span>
                                </div>
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                            </div>
                        </div>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                            {{ $user->role === 'admin' ? 'bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-200' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200' }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <span class="font-medium text-gray-500 dark:text-gray-400">Team:</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ $user->team ? $user->team->name : '-' }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-500 dark:text-gray-400">Created Team:</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ $user->createdTeam ? $user->createdTeam->name : '-' }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-500 dark:text-gray-400">Joined:</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ $user->created_at->format('d M Y') }}</span>
                        </div>
                        <div class="flex space-x-2">
                            <button wire:click="edit({{ $user->id }})" 
                                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm transition-colors duration-200">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </button>
                            <button wire:click="delete({{ $user->id }})" 
                                    wire:confirm="Are you sure you want to delete this account? This action cannot be undone."
                                    class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 text-sm transition-colors duration-200">
                                <i class="fas fa-trash mr-1"></i>Hapus
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                    Tidak ada user yang ditemukan
                </div>
            @endforelse
        </div>

        <!-- Desktop Table View (Hidden on Mobile) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700 transition-colors duration-200">
                    <tr>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Account</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden lg:table-cell">Team</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden xl:table-cell">Session Timeout</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden xl:table-cell">Created Team</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden lg:table-cell">Joined</th>
                        <th class="px-4 lg:px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 transition-colors duration-200">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8 lg:h-10 lg:w-10">
                                        <div class="h-8 w-8 lg:h-10 lg:w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                            <span class="text-xs lg:text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ strtoupper(substr($user->name, 0, 2)) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-3 lg:ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</div>
                                        <div class="text-xs lg:text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $user->role === 'admin' ? 'bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-200' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200' }}">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </td>
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 hidden lg:table-cell">
                                {{ $user->team ? $user->team->name : '-' }}
                            </td>
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 hidden xl:table-cell">
                                @if($user->role === 'user')
                                    <div class="flex items-center space-x-2">
                                        @php($window = $user->accessWindowStatus())
                                        <span title="{{ $user->getFormattedSessionTimeout() }}"
                                              class="px-2 py-1 text-xs rounded-full whitespace-nowrap
                                              @class([
                                                  'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200' => $window['tone'] === 'green',
                                                  'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200' => $window['tone'] === 'blue',
                                                  'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200' => $window['tone'] === 'yellow',
                                                  'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200 font-semibold' => $window['tone'] === 'red',
                                              ])>
                                            {{ $window['label'] }}
                                        </span>
                                        <select wire:change="setSessionTimeout({{ $user->id }}, $event.target.value)" 
                                                class="text-xs border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded px-1 py-0.5">
                                            @foreach($sessionTimeoutOptions as $seconds => $label)
                                                <option value="{{ $seconds }}" {{ $user->getSessionTimeout() == $seconds ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs">N/A (Admin)</span>
                                @endif
                            </td>
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 hidden xl:table-cell">
                                {{ $user->createdTeam ? $user->createdTeam->name : '-' }}
                            </td>
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 hidden lg:table-cell">
                                {{ $user->created_at->format('d M Y') }}
                            </td>
                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    <button wire:click="edit({{ $user->id }})" 
                                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 p-1 transition-colors duration-200">
                                        <i class="fas fa-edit"></i>
                                        <span class="hidden lg:inline ml-1">Edit</span>
                                    </button>
                                    <button wire:click="delete({{ $user->id }})" 
                                            wire:confirm="Are you sure you want to delete this account? This action cannot be undone."
                                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 p-1 transition-colors duration-200">
                                        <i class="fas fa-trash"></i>
                                        <span class="hidden lg:inline ml-1">Hapus</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 lg:px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                Tidak ada user yang ditemukan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-4 lg:px-6 py-4 border-t border-gray-200 dark:border-gray-700 transition-colors duration-200">
            {{ $users->links() }}
        </div>
    </div>

    @endunless

    <!-- Modal for Add/Edit User -->
    @if($showModal)
    {{-- Not a modal: a form this size is a page. As an overlay it ran past the
         bottom of a phone screen, taking its save button with it, and the only way
         out was to find the X. --}}
    <div class="mb-4">
        <button type="button" wire:click="closeModal"
                class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
            &larr; Back to users
        </button>
    </div>
    {{-- The form kept the modal's fields but lost the modal's panel when it became
         a page, so it sat on the raw background. This is that container. --}}
    <div class="max-w-4xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-5 sm:p-6">
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-3">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $editMode ? 'Edit Akun' : 'Tambah Akun Baru' }}
                    </h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <form wire:submit.prevent="save">
                    <div class="space-y-4">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Nama Lengkap <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="name" 
                                   wire:model="name"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100"
                                   placeholder="Masukkan nama lengkap">
                            @error('name') 
                                <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input type="email" 
                                   id="email" 
                                   wire:model="email"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100"
                                   placeholder="Masukkan email">
                            @error('email') 
                                <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                            @enderror
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Password {{ $editMode ? '(kosongkan jika tidak ingin mengubah)' : '' }} 
                                @if(!$editMode)<span class="text-red-500">*</span>@endif
                            </label>
                            <input type="password" 
                                   id="password" 
                                   wire:model="password"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100"
                                   placeholder="Masukkan password">
                            @error('password') 
                                <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                            @enderror
                        </div>

                        <!-- Role -->
                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Role <span class="text-red-500">*</span>
                            </label>
                            <select id="role" 
                                    wire:model="role"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                                <option value="user">Team</option>
                                <option value="admin">Admin</option>
                            </select>
                            @error('role') 
                                <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                            @enderror
                        </div>

                        <!-- Team -->
                        <div>
                            <label for="team_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Tim (Opsional)
                            </label>
                            <select id="team_id" 
                                    wire:model="team_id"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                                <option value="">Pilih Tim</option>
                                @foreach($teams as $team)
                                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                                @endforeach
                            </select>
                            @error('team_id') 
                                <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                            @enderror
                        </div>

                        <!-- Session Timeout (Only for users) -->
                        <div x-show="$wire.role === 'user'" x-transition>
                            <label for="session_timeout" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Session Timeout
                                <span class="text-xs text-gray-500">(Auto logout timer)</span>
                            </label>
                            <select id="session_timeout" 
                                    wire:model="session_timeout"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                                @foreach($sessionTimeoutOptions as $seconds => $label)
                                    <option value="{{ $seconds }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('session_timeout') 
                                <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                            @enderror
                            <p class="text-xs text-gray-500 mt-1">Account will be automatically logged out after this period of inactivity</p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-end space-x-3 pt-6 mt-6 border-t border-gray-200 dark:border-gray-600">
                        <button type="button" 
                                wire:click="closeModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 rounded-md hover:bg-gray-200 dark:hover:bg-gray-500 transition-colors duration-200">
                            Batal
                        </button>
                        <button type="submit"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors duration-200">
                            <span wire:loading.remove wire:target="save">{{ $editMode ? 'Update' : 'Simpan' }}</span>
                            <span wire:loading wire:target="save" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Saving...
                            </span>
                        </button>
                    </div>
                </form>
        </div>
    @endif

</div>