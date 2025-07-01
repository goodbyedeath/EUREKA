{{-- resources/views/livewire/user/dashboard-content.blade.php --}}
<div>
    {{-- Dashboard Tab --}}
    @if($activeTab === 'dashboard')
        <div class="fade-in" role="tabpanel">
            @if($questionnaireId)
                {{-- Show the Quiz Interface --}}
                <div class="mb-6">
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                            <span class="text-blue-700 dark:text-blue-300">You have an active quiz session.</span>
                        </div>
                    </div>
                </div>
                @livewire('user.quiz-take', ['questionnaireId' => $questionnaireId])
            @else
                {{-- Normal Dashboard Content --}}
                <div class="space-y-8">
                    @livewire('user.quest-location-dashboard')

                    {{-- Recent Activity --}}
                    @livewire('user.recent-attempts')
                    
                    {{-- Quick Actions --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 sm:p-6">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('common.quick_actions') }}</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                            <button 
                                wire:click="switchToQuizzes"
                                class="p-3 sm:p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-left">
                                <i class="fas fa-clipboard-list text-blue-500 text-lg sm:text-xl mb-2"></i>
                                <div class="font-medium text-gray-900 dark:text-gray-100 text-sm sm:text-base">{{ __('common.browse_quizzes') }}</div>
                            </button>
                            
                            <button 
                                wire:click="openQRScanner"
                                class="p-3 sm:p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-left">
                                <i class="fas fa-qrcode text-green-500 text-lg sm:text-xl mb-2"></i>
                                <div class="font-medium text-gray-900 dark:text-gray-100 text-sm sm:text-base">{{ __('common.scan_qr') }}</div>
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Available Quizzes Tab --}}
    @if($activeTab === 'quizzes')
        <div class="fade-in" role="tabpanel">
            @if($scannedqr_code)
                <div class="mb-6">
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                <span class="text-green-700 dark:text-green-300">QR code scanned successfully!</span>
                            </div>
                            <button 
                                onclick="Livewire.dispatch('clear-qr-code')"
                                class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-200">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endif
            
            @livewire('user.available-quest', ['qr_code' => $scannedqr_code])
        </div>
    @endif

    {{-- Members Tab --}}
    @if($activeTab === 'members')
        <div class="fade-in" role="tabpanel">
            @if($team)
                {{-- Include the team member view component --}}
                @livewire('user.team-member-view', ['team' => $team])
            @else
                {{-- No team found --}}
                <div class="text-center py-8 sm:py-12 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                    <div class="mx-auto w-20 h-20 sm:w-24 sm:h-24 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-users text-gray-400 dark:text-gray-500 text-2xl sm:text-3xl"></i>
                    </div>
                    <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-gray-100 mb-2 px-4">
                        {{ __('common.not_joined_team') }}
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto text-sm sm:text-base px-4">
                        {{ __('common.not_joined_team_desc') }}
                    </p>
                    <div class="flex flex-col sm:flex-row justify-center space-y-3 sm:space-y-0 sm:space-x-4 px-4">
                        <button 
                            class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg transition-colors inline-flex items-center justify-center text-sm sm:text-base"
                            onclick="alert('{{ __('common.not_joined_team_desc') }}')">
                            <i class="fas fa-envelope mr-2"></i>
                            {{ __('common.contact_admin') }}
                        </button>
                        <button 
                            onclick="Livewire.dispatch('refresh-dashboard')"
                            class="bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg transition-colors inline-flex items-center justify-center text-sm sm:text-base">
                            <i class="fas fa-sync mr-2"></i>
                            {{ __('common.refresh') }}
                        </button>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Games Tab --}}
    @if($activeTab === 'games')
        <div class="fade-in" role="tabpanel">
            @livewire('user.game-dashboard')
        </div>
    @endif
</div>