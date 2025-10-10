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
                            <span class="text-blue-700 dark:text-blue-300">{{ __('common.you_have_active_quiz') }}</span>
                        </div>
                    </div>
                </div>
                @livewire('user.quiz-take', ['questionnaireId' => $questionnaireId])
            @else
                {{-- Enhanced Dashboard Content with Better UX --}}
                <div class="space-y-8">
                    {{-- Welcome Section --}}
                    <div class="bg-gradient-to-r from-blue-50 via-purple-50 to-pink-50 dark:from-gray-800 dark:via-gray-800 dark:to-gray-800 rounded-xl p-6 border border-blue-100 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">
                                    Welcome back, {{ auth()->user()->name }}! 👋
                                </h2>
                                <p class="text-gray-600 dark:text-gray-400">
                                    Ready to explore? Choose your adventure below.
                                </p>
                            </div>
                            <div class="hidden sm:block">
                                <div class="w-16 h-16 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-rocket text-white text-2xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Feature Cards Grid with Enhanced Design --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        @if($this->featureEnabled('quiz_system'))
                        <div class="feature-card group">
                            <button 
                                wire:click="switchToQuizzes"
                                class="w-full p-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:border-blue-300 dark:hover:border-blue-600 hover:shadow-lg transition-all duration-300 text-left group-hover:transform group-hover:scale-105">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center group-hover:bg-blue-200 dark:group-hover:bg-blue-900/50 transition-colors">
                                        <i class="fas fa-clipboard-list text-blue-600 dark:text-blue-400 text-xl"></i>
                                    </div>
                                    <div class="text-xs bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 px-2 py-1 rounded-full">
                                        Active
                                    </div>
                                </div>
                                <h3 class="font-bold text-gray-900 dark:text-gray-100 text-lg mb-2">{{ __('common.browse_quizzes') }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ __('common.explore_available_quizzes') }}</p>
                                <div class="flex items-center text-blue-600 dark:text-blue-400 text-sm font-medium">
                                    <span>Get Started</span>
                                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                                </div>
                            </button>
                        </div>
                        @endif
                        
                        @if($this->featureEnabled('quiz_system'))
                        <div class="feature-card group">
                            <button 
                                wire:click="openQRScanner"
                                class="w-full p-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:border-green-300 dark:hover:border-green-600 hover:shadow-lg transition-all duration-300 text-left group-hover:transform group-hover:scale-105">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center group-hover:bg-green-200 dark:group-hover:bg-green-900/50 transition-colors">
                                        <i class="fas fa-qrcode text-green-600 dark:text-green-400 text-xl"></i>
                                    </div>
                                    <div class="text-xs bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-300 px-2 py-1 rounded-full">
                                        Quick
                                    </div>
                                </div>
                                <h3 class="font-bold text-gray-900 dark:text-gray-100 text-lg mb-2">{{ __('common.scan_qr') }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ __('common.quick_access_to_quizzes') }}</p>
                                <div class="flex items-center text-green-600 dark:text-green-400 text-sm font-medium">
                                    <span>Scan Now</span>
                                    <i class="fas fa-camera ml-2 group-hover:scale-110 transition-transform"></i>
                                </div>
                            </button>
                        </div>
                        @endif
                        
                        @if($this->featureEnabled('quest_locations'))
                        <div class="feature-card group">
                            <button 
                                wire:click="switchTab('quests')"
                                class="w-full p-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:border-orange-300 dark:hover:border-orange-600 hover:shadow-lg transition-all duration-300 text-left group-hover:transform group-hover:scale-105">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center group-hover:bg-orange-200 dark:group-hover:bg-orange-900/50 transition-colors">
                                        <i class="fas fa-map-marker-alt text-orange-600 dark:text-orange-400 text-xl"></i>
                                    </div>
                                    <div class="text-xs bg-orange-100 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300 px-2 py-1 rounded-full">
                                        GPS
                                    </div>
                                </div>
                                <h3 class="font-bold text-gray-900 dark:text-gray-100 text-lg mb-2">{{ __('games.quest_locations') }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ __('games.explore_quest_locations') }}</p>
                                <div class="flex items-center text-orange-600 dark:text-orange-400 text-sm font-medium">
                                    <span>Explore</span>
                                    <i class="fas fa-compass ml-2 group-hover:rotate-45 transition-transform"></i>
                                </div>
                            </button>
                        </div>
                        @endif
                        
                        @if($this->featureEnabled('game_dashboard'))
                        <div class="feature-card group">
                            <button 
                                wire:click="switchToGames"
                                class="w-full p-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:border-indigo-300 dark:hover:border-indigo-600 hover:shadow-lg transition-all duration-300 text-left group-hover:transform group-hover:scale-105">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center group-hover:bg-indigo-200 dark:group-hover:bg-indigo-900/50 transition-colors">
                                        <i class="fas fa-gamepad text-indigo-600 dark:text-indigo-400 text-xl"></i>
                                    </div>
                                    <div class="text-xs bg-indigo-100 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 px-2 py-1 rounded-full">
                                        Interactive
                                    </div>
                                </div>
                                <h3 class="font-bold text-gray-900 dark:text-gray-100 text-lg mb-2">{{ __('games.outdoor_games') }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ __('games.interactive_game_maps') }}</p>
                                <div class="flex items-center text-indigo-600 dark:text-indigo-400 text-sm font-medium">
                                    <span>Play Now</span>
                                    <i class="fas fa-play ml-2 group-hover:translate-x-1 transition-transform"></i>
                                </div>
                            </button>
                        </div>
                        @endif
                        
                        @if($this->featureEnabled('team_management'))
                        <div class="feature-card group">
                            <button 
                                wire:click="switchToMembers"
                                class="w-full p-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:border-purple-300 dark:hover:border-purple-600 hover:shadow-lg transition-all duration-300 text-left group-hover:transform group-hover:scale-105">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center group-hover:bg-purple-200 dark:group-hover:bg-purple-900/50 transition-colors">
                                        <i class="fas fa-users text-purple-600 dark:text-purple-400 text-xl"></i>
                                    </div>
                                    <div class="text-xs bg-purple-100 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 px-2 py-1 rounded-full">
                                        Team
                                    </div>
                                </div>
                                <h3 class="font-bold text-gray-900 dark:text-gray-100 text-lg mb-2">{{ __('teams.team_members') }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ __('teams.manage_your_team') }}</p>
                                <div class="flex items-center text-purple-600 dark:text-purple-400 text-sm font-medium">
                                    <span>View Team</span>
                                    <i class="fas fa-users-cog ml-2 group-hover:rotate-12 transition-transform"></i>
                                </div>
                            </button>
                        </div>
                        @endif
                    </div>
                    
                    {{-- Enhanced Recent Quiz Attempts Section --}}
                    @if($this->featureEnabled('quiz_system'))
                        <div class="space-y-6">
                            {{-- Quick Stats Cards --}}
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-900/40 rounded-xl p-4 border border-blue-200 dark:border-blue-700">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-clipboard-check text-white text-sm"></i>
                                        </div>
                                        <div>
                                            <div class="text-2xl font-bold text-blue-700 dark:text-blue-300" id="completed-quizzes">--</div>
                                            <div class="text-xs text-blue-600 dark:text-blue-400">Completed</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-900/40 rounded-xl p-4 border border-green-200 dark:border-green-700">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-star text-white text-sm"></i>
                                        </div>
                                        <div>
                                            <div class="text-2xl font-bold text-green-700 dark:text-green-300" id="average-score">--</div>
                                            <div class="text-xs text-green-600 dark:text-green-400">Avg Score</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-900/40 rounded-xl p-4 border border-purple-200 dark:border-purple-700">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-clock text-white text-sm"></i>
                                        </div>
                                        <div>
                                            <div class="text-2xl font-bold text-purple-700 dark:text-purple-300" id="total-time">--</div>
                                            <div class="text-xs text-purple-600 dark:text-purple-400">Total Time</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-900/40 rounded-xl p-4 border border-orange-200 dark:border-orange-700">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-fire text-white text-sm"></i>
                                        </div>
                                        <div>
                                            <div class="text-2xl font-bold text-orange-700 dark:text-orange-300" id="streak">--</div>
                                            <div class="text-xs text-orange-600 dark:text-orange-400">Streak</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Recent Attempts with Enhanced Design --}}
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                                <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center mr-3">
                                                <i class="fas fa-trophy text-yellow-600 dark:text-yellow-400"></i>
                                            </div>
                                            <div>
                                                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                                    Your Quiz Achievements
                                                </h3>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    Track your progress and performance
                                                </p>
                                            </div>
                                        </div>
                                        <button 
                                            wire:click="switchToQuizzes"
                                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center">
                                            <span>View All Quizzes</span>
                                            <i class="fas fa-arrow-right ml-2"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="p-6">
                                    @livewire('user.recent-attempts')
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Feature Availability Notice for Disabled Features --}}
                    @php
                        $allFeatures = [
                            'quiz_system' => 'Quiz System',
                            'quest_locations' => 'Quest Locations', 
                            'game_dashboard' => 'Game Dashboard',
                            'team_management' => 'Team Management'
                        ];
                        $disabledFeatures = [];
                        foreach($allFeatures as $key => $name) {
                            if(!$this->featureEnabled($key)) {
                                $disabledFeatures[$key] = $name;
                            }
                        }
                    @endphp
                    
                    @if(count($disabledFeatures) > 0)
                        <div class="mt-8">
                            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-xl p-6">
                                <div class="flex items-start">
                                    <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                        <i class="fas fa-info-circle text-amber-600 dark:text-amber-400"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-amber-800 dark:text-amber-200 mb-2">
                                            Coming Soon!
                                        </h3>
                                        <p class="text-amber-700 dark:text-amber-300 text-sm mb-3">
                                            These features are currently being prepared and will be available soon:
                                        </p>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($disabledFeatures as $feature)
                                                <span class="bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 px-3 py-1 rounded-full text-xs font-medium">
                                                    {{ $feature }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif

    {{-- Available Quizzes Tab --}}
    @if($activeTab === 'quizzes' && $this->featureEnabled('quiz_system'))
        <div class="fade-in" role="tabpanel">
            @if($scannedqr_code)
                <div class="mb-6">
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                <span class="text-green-700 dark:text-green-300">{{ __('common.qr_code_scanned_successfully') }}</span>
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
    @if($activeTab === 'members' && $this->featureEnabled('team_management'))
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

    {{-- Quest Locations Tab --}}
    @if($activeTab === 'quests' && $this->featureEnabled('quest_locations'))
        <div class="fade-in" role="tabpanel">
            @php
                // Get quest location data for the current request
                $questController = app(App\Http\Controllers\User\QuestLocationController::class);
                $questData = $questController->getQuestLocationData(request());
                extract($questData);
            @endphp
            @include('user.partials.quest-locations-content', $questData)
        </div>
    @endif

    {{-- Games Tab --}}
    @if($activeTab === 'games' && $this->featureEnabled('game_dashboard'))
        <div class="fade-in" role="tabpanel">
            @livewire('user.game-dashboard')
        </div>
    @endif
</div>

@push('styles')
<style>
/* Enhanced Feature Cards Animations */
.feature-card {
    position: relative;
    overflow: hidden;
}

.feature-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
    pointer-events: none;
}

.feature-card:hover::before {
    left: 100%;
}

.feature-card button {
    overflow: hidden;
    position: relative;
}

/* Stats Animation */
@keyframes countUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.stat-animate {
    animation: countUp 0.6s ease-out;
}

/* Gradient Animation */
@keyframes gradientShift {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

.gradient-bg {
    background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
    background-size: 400% 400%;
    animation: gradientShift 15s ease infinite;
}

/* Hover Effects */
.hover-lift {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.hover-lift:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

/* Loading Skeleton */
.skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
}

@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.dark .skeleton {
    background: linear-gradient(90deg, #374151 25%, #4B5563 50%, #374151 75%);
    background-size: 200% 100%;
}

/* Feature Status Badges */
.feature-status {
    position: relative;
    overflow: hidden;
}

.feature-status::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
    transform: translateX(-100%);
    transition: transform 0.6s;
}

.feature-status:hover::after {
    transform: translateX(100%);
}

@keyframes ripple {
    0% { transform: scale(0); opacity: 1; }
    100% { transform: scale(1); opacity: 0; }
}
</style>
@endpush

@push('scripts')
<script>
// Enhanced Dashboard JavaScript Functions
let userMap = null;
let userLatitude = null;
let userLongitude = null;

// Feature interaction tracking
let featureInteractions = {
    quiz_clicks: 0,
    qr_scans: 0,
    quest_visits: 0,
    game_plays: 0,
    team_views: 0
};

// Enhanced page initialization
document.addEventListener('DOMContentLoaded', () => {
    // Initialize enhanced dashboard features
    initializeFeatureCards();
    loadUserStats();
    addInteractiveEffects();
    handleEnhancedFlashMessages();
    
    // Auto-hide flash messages
    setTimeout(() => {
        const messages = document.querySelectorAll('[class*="fixed top-4 right-4"]');
        messages.forEach(message => {
            setTimeout(() => {
                message.style.transition = 'opacity 0.5s';
                message.style.opacity = '0';
                setTimeout(() => message.remove(), 500);
            }, 5000);
        });
    }, 100);
});

// Request user location
function requestUserLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                userLatitude = position.coords.latitude;
                userLongitude = position.coords.longitude;
            },
            (error) => {
                // Location access denied - fail silently
            }
        );
    }
}

// Open map modal
async function openMapModal(lat, lng, title, radius = 50) {
    // Request user location when user actually opens map (user gesture)
    if (!userLatitude || !userLongitude) {
        requestUserLocation();
    }
    
    // Update modal content
    document.getElementById('modalLocationName').textContent = title;
    document.getElementById('modalLocationPoints').textContent = '';
    
    // Show modal
    document.getElementById('mapModal').classList.remove('hidden');
    
    // Load MapLibre GL JS if not already loaded
    if (!window.maplibregl) {
        try {
            await loadMapUtils();
        } catch (error) {
            return;
        }
    }
    
    // Initialize map
    setTimeout(() => {
        initializeMap(lat, lng, title, radius);
    }, 200);
}

// Close map modal
function closeMapModal() {
    document.getElementById('mapModal').classList.add('hidden');
    
    // Clean up map
    if (userMap) {
        try {
            userMap.remove();
            userMap = null;
        } catch (error) {
            // Error removing map - fail silently
        }
    }
}

// Initialize map
function initializeMap(lat, lng, title, radius = 50) {
    const container = document.getElementById('mapContainer');
    if (!container) {
        return;
    }

    try {
        userMap = new window.maplibregl.Map({
            container: 'mapContainer',
            style: {
                'version': 8,
                'sources': {
                    'osm': {
                        'type': 'raster',
                        'tiles': [
                            'https://a.tile.openstreetmap.org/{z}/{x}/{y}.png'
                        ],
                        'tileSize': 256,
                        'attribution': '© OpenStreetMap contributors'
                    }
                },
                'layers': [
                    {
                        'id': 'osm',
                        'type': 'raster',
                        'source': 'osm'
                    }
                ]
            },
            center: [lng, lat],
            zoom: 16
        });

        userMap.on('load', () => {
            // Add location marker
            new window.maplibregl.Marker({ color: '#3B82F6' })
                .setLngLat([lng, lat])
                .addTo(userMap);

            // Add radius circle
            userMap.addSource('radius', {
                'type': 'geojson',
                'data': {
                    'type': 'Feature',
                    'geometry': {
                        'type': 'Point',
                        'coordinates': [lng, lat]
                    }
                }
            });

            userMap.addLayer({
                'id': 'radius-circle',
                'type': 'circle',
                'source': 'radius',
                'paint': {
                    'circle-radius': {
                        'stops': [
                            [0, 0],
                            [20, radius * 2]
                        ],
                        'base': 2
                    },
                    'circle-color': '#3B82F6',
                    'circle-opacity': 0.1,
                    'circle-stroke-color': '#3B82F6',
                    'circle-stroke-width': 2,
                    'circle-stroke-opacity': 0.8
                }
            });

            // Add user location if available
            if (userLatitude && userLongitude) {
                new window.maplibregl.Marker({ color: '#10B981' })
                    .setLngLat([userLongitude, userLatitude])
                    .addTo(userMap);
                    
                // Adjust zoom to show both locations
                const bounds = new window.maplibregl.LngLatBounds();
                bounds.extend([lng, lat]);
                bounds.extend([userLongitude, userLatitude]);
                userMap.fitBounds(bounds, { padding: 50 });
            }
        });

        userMap.addControl(new window.maplibregl.NavigationControl());
        
    } catch (error) {
        container.innerHTML = '<div class="flex items-center justify-center h-full text-red-500">Error creating map. Please try again.</div>';
    }
}

// Check in function
async function checkIn(locationId) {
    if (!userLatitude || !userLongitude) {
        alert('Location access required. Please enable GPS and refresh the page.');
        return;
    }

    try {
        // Show loading state
        const button = event.target;
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = 'Checking in...';

        const response = await fetch('{{ route("user.quest-locations.checkin") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                location_id: locationId,
                user_latitude: userLatitude,
                user_longitude: userLongitude
            })
        });

        const result = await response.json();

        if (result.success) {
            alert(result.message);
            location.reload(); // Reload to show updated progress
        } else {
            alert(result.message || 'Check-in failed. Please try again.');
        }

    } catch (error) {
        alert('Check-in failed. Please try again.');
    }
}
</script>
@endpush