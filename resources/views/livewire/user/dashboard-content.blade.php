{{-- resources/views/livewire/user/dashboard-content.blade.php --}}
<div>
    {{-- Dashboard Tab --}}
    @if($activeTab === 'dashboard')
        <div class="fade-in" role="tabpanel">
            @if($questionnaireId)
                {{-- Show the Quiz Interface --}}
                <div class="mb-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                            <span class="text-blue-700">You have an active quiz session.</span>
                        </div>
                    </div>
                </div>
                @livewire('user.quiz-take', ['questionnaireId' => $questionnaireId])
            @else
                {{-- Normal Dashboard Content --}}
                <div class="space-y-8">
                    @livewire('user.quest-location-dashboard')
                    {{-- Stats Cards --}}
                    @livewire('user.dashboard-stats')

                    {{-- Recent Activity --}}
                    @livewire('user.recent-attempts')
                    
                    {{-- Quick Actions --}}
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <button 
                                wire:click="switchToQuizzes"
                                class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-left">
                                <i class="fas fa-clipboard-list text-blue-500 text-xl mb-2"></i>
                                <div class="font-medium text-gray-900">Browse Quizzes</div>
                                <div class="text-sm text-gray-500">View available questionnaires</div>
                            </button>
                            
                            <button 
                                onclick="Livewire.dispatch('open-qr-scanner')"
                                class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-left">
                                <i class="fas fa-qr_code text-green-500 text-xl mb-2"></i>
                                <div class="font-medium text-gray-900">Scan QR Code</div>
                                <div class="text-sm text-gray-500">Quick access to quizzes</div>
                            </button>
                            
                            <button 
                                onclick="Livewire.dispatch('refresh-dashboard')"
                                class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-left">
                                <i class="fas fa-sync-alt text-purple-500 text-xl mb-2"></i>
                                <div class="font-medium text-gray-900">Refresh Data</div>
                                <div class="text-sm text-gray-500">Update dashboard content</div>
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
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                <span class="text-green-700">QR code scanned successfully!</span>
                            </div>
                            <button 
                                onclick="Livewire.dispatch('clear-qr-code')"
                                class="text-green-600 hover:text-green-800">
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
                <div class="text-center py-12 bg-white rounded-xl border border-gray-200">
                    <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-users text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                        Belum Bergabung dalam Tim
                    </h3>
                    <p class="text-gray-500 mb-6 max-w-md mx-auto">
                        Anda belum bergabung dalam tim manapun. Hubungi administrator untuk bergabung dalam tim atau buat tim baru.
                    </p>
                    <div class="flex justify-center space-x-4">
                        <button 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-colors inline-flex items-center"
                            onclick="alert('Hubungi administrator untuk bergabung dalam tim')">
                            <i class="fas fa-envelope mr-2"></i>
                            Hubungi Admin
                        </button>
                        <button 
                            onclick="Livewire.dispatch('refresh-dashboard')"
                            class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg transition-colors inline-flex items-center">
                            <i class="fas fa-sync mr-2"></i>
                            Refresh
                        </button>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>