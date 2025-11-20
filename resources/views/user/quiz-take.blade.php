@extends('layouts.quiz')

@section('title', 'Quiz - ' . ($questionnaire->title ?? 'Loading...'))

@section('content')
<div x-data="quizTake({{ $attemptId ? "'" . $attemptId . "'" : 'null' }}, {{ $questionnaireId ? "'" . $questionnaireId . "'" : 'null' }})" @keydown.window="handleKeyDown($event)">
    <!-- Loading State -->
    <div x-show="loading" class="min-h-screen flex items-center justify-center">
        <div class="bg-white/90 backdrop-blur-xl rounded-3xl p-8 text-center shadow-2xl border border-white/20">
            <div class="w-12 h-12 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin mx-auto mb-4"></div>
            <p class="text-gray-700 font-medium">Loading quiz...</p>
        </div>
    </div>

    <!-- Error State -->
    <div x-show="error && !loading" class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white/80 backdrop-blur-xl rounded-3xl shadow-xl border border-white/20 p-8 text-center">
            <div class="w-20 h-20 bg-red-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-exclamation-circle text-red-600 text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Error</h2>
            <p class="text-gray-600 mb-8" x-text="error"></p>
            <a href="/user/dashboard" class="inline-flex items-center gap-2 bg-indigo-600 text-white px-6 py-3 rounded-2xl font-semibold hover:bg-indigo-700 transition-all duration-300">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Quiz Content -->
    <div x-show="!loading && !error && !isCompleted" class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50">
        {{-- Floating Background Elements --}}
        <div class="fixed inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-blue-200/30 to-purple-200/30 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-gradient-to-br from-purple-200/30 to-pink-200/30 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-gradient-to-br from-indigo-200/20 to-blue-200/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 4s;"></div>
        </div>

        {{-- Quiz Header --}}
        <div class="sticky top-0 z-40 backdrop-blur-xl bg-white/90 border-b border-white/30 shadow-2xl">
            <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
                <div class="py-3 sm:py-4">
                    {{-- Header Content --}}
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                        {{-- Quiz Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start gap-3 sm:gap-4">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg flex-shrink-0">
                                    <i class="fas fa-brain text-white text-lg sm:text-xl"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900 truncate" x-text="questionnaire?.title"></h1>
                                    <div class="flex flex-wrap items-center gap-2 sm:gap-3 mt-1 text-xs sm:text-sm text-gray-600">
                                        <span class="flex items-center gap-1 bg-indigo-50 px-2 py-1 rounded-full">
                                            <i class="fas fa-question-circle text-indigo-500"></i>
                                            <span class="font-medium" x-text="`${currentQuestionIndex + 1}/${questions.length}`"></span>
                                        </span>
                                        <template x-if="questionnaire?.time_limit">
                                            <span class="flex items-center gap-1 bg-purple-50 px-2 py-1 rounded-full">
                                                <i class="fas fa-clock text-purple-500"></i>
                                                <span class="font-medium" x-text="`${questionnaire.time_limit}min`"></span>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Timer Display --}}
                        <template x-if="questionnaire?.time_limit && timeRemaining !== null && timeRemaining > 0">
                            <div class="flex-shrink-0 w-full sm:w-auto mt-3 sm:mt-0">
                                <div class="timer-widget-enhanced" :class="timerClass">
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="timer-icon-container">
                                            <i class="fas" :class="{
                                                'fa-stopwatch text-blue-500': timeRemaining > 300,
                                                'fa-clock text-amber-500': timeRemaining <= 300 && timeRemaining > 60,
                                                'fa-exclamation-triangle text-red-500': timeRemaining <= 60
                                            }"></i>
                                        </div>
                                        <div class="flex-1">
                                            <div class="timer-display-enhanced" x-text="formattedTimeRemaining"></div>
                                            <div class="timer-status-enhanced" x-text="timeRemaining <= 60 ? 'CRITICAL!' : (timeRemaining <= 300 ? 'Warning' : 'Active')"></div>
                                        </div>
                                        <div class="timer-percentage-enhanced" x-text="`${timerPercentage}%`"></div>
                                    </div>
                                    <div class="timer-progress-enhanced">
                                        <div class="timer-progress-bar-enhanced" :class="{
                                            'bg-gradient-to-r from-blue-500 to-blue-600': timeRemaining > 300,
                                            'bg-gradient-to-r from-amber-500 to-amber-600': timeRemaining <= 300 && timeRemaining > 60,
                                            'bg-gradient-to-r from-red-500 to-red-600': timeRemaining <= 60
                                        }" :style="`width: ${timerPercentage}%`"></div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-if="questionnaire?.time_limit && timeRemaining !== null && timeRemaining <= 0">
                            <div class="flex items-center gap-2 px-4 py-2 bg-red-50 border border-red-200 rounded-xl text-red-700">
                                <i class="fas fa-clock"></i>
                                <span class="font-semibold">Time's Up!</span>
                            </div>
                        </template>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="mt-3 sm:mt-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs sm:text-sm text-gray-600 mb-2">
                            <span class="flex items-center gap-2">
                                <i class="fas fa-chart-line text-indigo-500"></i>
                                <span class="font-medium" x-text="`Progress: ${getProgressPercentage()}%`"></span>
                            </span>
                            <span class="flex items-center gap-2">
                                <i class="fas fa-check-circle text-emerald-500"></i>
                                <span class="font-medium" x-text="`Answered: ${getAnsweredPercentage()}%`"></span>
                            </span>
                        </div>
                        <div class="relative">
                            <div class="w-full bg-gray-200 rounded-full h-2 sm:h-3 overflow-hidden">
                                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 h-full rounded-full transition-all duration-500 ease-out shadow-sm"
                                     :style="`width: ${getProgressPercentage()}%`"></div>
                            </div>
                            <div class="absolute inset-0 bg-gradient-to-r from-emerald-500/30 to-emerald-600/30 rounded-full transition-all duration-500"
                                 :style="`width: ${getAnsweredPercentage()}%`"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Refresh Prevention Warning Banner --}}
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 pt-4">
            <div class="bg-amber-50 border-l-4 border-amber-500 rounded-lg p-3 sm:p-4 shadow-md">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <i class="fas fa-shield-alt text-amber-600 text-lg sm:text-xl"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm sm:text-base font-semibold text-amber-900 mb-1">Quiz Protection Active</h4>
                        <p class="text-xs sm:text-sm text-amber-800 leading-relaxed">
                            Browser refresh is <strong>disabled</strong> during this quiz. Your progress is auto-saved every 15 seconds. The timer continues even if you close the browser.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Question Content --}}
        <template x-if="getCurrentQuestion()">
            <div class="max-w-6xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8">
                <div class="bg-white/90 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-2xl border border-white/30 overflow-hidden">
                    {{-- Question Header --}}
                    <div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 p-4 sm:p-6 text-white">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 sm:gap-3 mb-3">
                                    <span class="bg-white/20 backdrop-blur-sm px-3 py-1 rounded-full text-sm font-medium" x-text="`Q${currentQuestionIndex + 1}`"></span>
                                    <template x-if="getCurrentQuestion().points > 0 && getCurrentQuestion().type !== 'brief'">
                                        <span class="bg-yellow-400/20 backdrop-blur-sm px-3 py-1 rounded-full text-sm font-medium flex items-center gap-1">
                                            <i class="fas fa-star text-yellow-300"></i>
                                            <span x-text="`${getCurrentQuestion().points}pts`"></span>
                                        </span>
                                    </template>
                                    <template x-if="getCurrentQuestion().type === 'brief'">
                                        <span class="bg-cyan-400/20 backdrop-blur-sm px-3 py-1 rounded-full text-sm font-medium flex items-center gap-1">
                                            <i class="fas fa-comment text-cyan-300"></i>
                                            Feedback
                                        </span>
                                    </template>
                                    <span class="bg-white/10 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-medium" x-text="getCurrentQuestion().type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())"></span>
                                </div>
                                <h2 class="text-lg sm:text-xl lg:text-2xl font-bold leading-relaxed break-words" x-text="getCurrentQuestion().question"></h2>
                            </div>
                            <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                                <i class="fas text-lg sm:text-xl" :class="{
                                    'fa-list-ul': getCurrentQuestion().type === 'multiple_choice',
                                    'fa-toggle-on': getCurrentQuestion().type === 'true_false',
                                    'fa-gamepad': getCurrentQuestion().type === 'fun_game',
                                    'fa-comment-dots': getCurrentQuestion().type === 'brief',
                                    'fa-edit': !['multiple_choice', 'true_false', 'fun_game', 'brief'].includes(getCurrentQuestion().type)
                                }"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Question Content --}}
                    <div class="p-4 sm:p-6 lg:p-8">
                        {{-- Multiple Choice --}}
                        <template x-if="getCurrentQuestion().type === 'multiple_choice'">
                            <div class="space-y-3">
                                <template x-for="(option, index) in getCurrentQuestion().options" :key="index">
                                    <template x-if="option && option.trim()">
                                        <label class="group block cursor-pointer transition-all duration-300">
                                            <div class="flex items-start gap-3 sm:gap-4 p-3 sm:p-4 rounded-xl sm:rounded-2xl border-2 border-gray-200 hover:border-indigo-300 hover:bg-indigo-50/50 active:scale-[0.98] transition-all duration-300">
                                                <div class="relative flex-shrink-0 mt-0.5 sm:mt-1">
                                                    <input type="radio"
                                                           :name="`question_${getCurrentQuestion().id}`"
                                                           :value="option"
                                                           x-model="answers[getCurrentQuestion().id]"
                                                           @change="saveAnswer(getCurrentQuestion().id, $event.target.value)"
                                                           class="w-4 h-4 sm:w-5 sm:h-5 text-indigo-600 border-2 border-gray-300 focus:ring-indigo-500 focus:ring-2">
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <span class="text-sm sm:text-base text-gray-800 font-medium leading-relaxed break-words" x-text="option"></span>
                                                </div>
                                            </div>
                                        </label>
                                    </template>
                                </template>
                            </div>
                        </template>

                        {{-- True/False --}}
                        <template x-if="getCurrentQuestion().type === 'true_false'">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                                <label class="group block cursor-pointer transition-all duration-300">
                                    <div class="flex items-center gap-3 sm:gap-4 p-4 sm:p-6 rounded-xl sm:rounded-2xl border-2 border-gray-200 hover:border-emerald-300 hover:bg-emerald-50/50 active:scale-[0.98] transition-all duration-300">
                                        <input type="radio"
                                               :name="`question_${getCurrentQuestion().id}`"
                                               value="true"
                                               x-model="answers[getCurrentQuestion().id]"
                                               @change="saveAnswer(getCurrentQuestion().id, $event.target.value)"
                                               class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-600 border-2 border-gray-300 focus:ring-emerald-500 focus:ring-2">
                                        <div class="flex items-center gap-2 sm:gap-3">
                                            <i class="fas fa-check-circle text-emerald-500 text-lg sm:text-xl"></i>
                                            <span class="text-base sm:text-lg font-semibold text-gray-800">True</span>
                                        </div>
                                    </div>
                                </label>
                                <label class="group block cursor-pointer transition-all duration-300">
                                    <div class="flex items-center gap-3 sm:gap-4 p-4 sm:p-6 rounded-xl sm:rounded-2xl border-2 border-gray-200 hover:border-red-300 hover:bg-red-50/50 active:scale-[0.98] transition-all duration-300">
                                        <input type="radio"
                                               :name="`question_${getCurrentQuestion().id}`"
                                               value="false"
                                               x-model="answers[getCurrentQuestion().id]"
                                               @change="saveAnswer(getCurrentQuestion().id, $event.target.value)"
                                               class="w-4 h-4 sm:w-5 sm:h-5 text-red-600 border-2 border-gray-300 focus:ring-red-500 focus:ring-2">
                                        <div class="flex items-center gap-2 sm:gap-3">
                                            <i class="fas fa-times-circle text-red-500 text-lg sm:text-xl"></i>
                                            <span class="text-base sm:text-lg font-semibold text-gray-800">False</span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </template>

                        {{-- Text Input --}}
                        <template x-if="getCurrentQuestion().type === 'text'">
                            <div class="space-y-4">
                                <div class="relative">
                                    <textarea x-model="answers[getCurrentQuestion().id]"
                                              @blur="saveAnswer(getCurrentQuestion().id, $event.target.value)"
                                              class="w-full px-3 sm:px-4 py-3 sm:py-4 border-2 border-gray-200 rounded-xl sm:rounded-2xl focus:outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-400 transition-all duration-300 resize-none text-sm sm:text-base bg-white"
                                              rows="4"
                                              placeholder="Type your answer here..."></textarea>
                                    <div class="absolute bottom-2 sm:bottom-3 right-2 sm:right-3 text-xs text-gray-400">
                                        <i class="fas fa-edit"></i>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Brief (Feedback) --}}
                        <template x-if="getCurrentQuestion().type === 'brief'">
                            <div class="space-y-4">
                                <template x-if="getCurrentQuestion().description">
                                    <div class="bg-cyan-50 border border-cyan-200 rounded-2xl p-6">
                                        <h4 class="text-lg font-semibold text-cyan-900 mb-3 flex items-center gap-2">
                                            <i class="fas fa-info-circle"></i>
                                            Feedback Context
                                        </h4>
                                        <div class="text-cyan-800 leading-relaxed whitespace-pre-line" x-text="getCurrentQuestion().description"></div>
                                    </div>
                                </template>

                                <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 mb-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-comment-dots text-blue-600 text-lg"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-semibold text-blue-900 mb-1">Feedback Question</h4>
                                            <p class="text-xs text-blue-700">This is a feedback question and will not affect your score. Please share your honest thoughts and experiences.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="relative">
                                    <textarea x-model="answers[getCurrentQuestion().id]"
                                              @blur="saveAnswer(getCurrentQuestion().id, $event.target.value)"
                                              class="w-full px-3 sm:px-4 py-3 sm:py-4 border-2 border-gray-200 rounded-xl sm:rounded-2xl focus:outline-none focus:ring-4 focus:ring-cyan-100 focus:border-cyan-400 transition-all duration-300 resize-none text-sm sm:text-base bg-white"
                                              rows="6"
                                              placeholder="Share your thoughts, experiences, or feedback here... (Optional)"></textarea>
                                    <div class="absolute bottom-2 sm:bottom-3 right-2 sm:right-3 flex items-center gap-2 text-xs text-gray-400">
                                        <span>Optional</span>
                                        <i class="fas fa-comment"></i>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center text-xs text-gray-500">
                                    <span>This feedback helps improve the experience for everyone</span>
                                    <span>Max 2000 characters</span>
                                </div>
                            </div>
                        </template>

                        {{-- Fun Game --}}
                        <template x-if="getCurrentQuestion().type === 'fun_game'">
                            <div class="space-y-6">
                                <div class="bg-gradient-to-r from-purple-500 via-pink-500 to-red-500 rounded-3xl p-8 text-white relative overflow-hidden">
                                    <div class="absolute inset-0 bg-black/10"></div>
                                    <div class="relative z-10">
                                        <div class="flex items-center justify-between mb-4">
                                            <h3 class="text-2xl lg:text-3xl font-bold" x-text="getCurrentQuestion().game_name || 'Fun Game Challenge'"></h3>
                                            <div class="flex items-center gap-2">
                                                <i class="fas fa-gamepad text-3xl"></i>
                                            </div>
                                        </div>

                                        <template x-if="questionnaire?.time_limit">
                                            <div class="flex items-center gap-2 text-white/90">
                                                <i class="fas fa-clock"></i>
                                                <span class="font-medium" x-text="`Time Limit: ${questionnaire.time_limit} minutes`"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <template x-if="getCurrentQuestion().images && getCurrentQuestion().images.length > 0">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                                        <template x-for="(image, idx) in getCurrentQuestion().images" :key="idx">
                                            <div class="group relative bg-white rounded-xl sm:rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 cursor-pointer"
                                                 @click="openImageModal(`/storage/${image}`)">
                                                <div class="aspect-w-16 aspect-h-12">
                                                    <img :src="`/storage/${image}`"
                                                         alt="Game Image"
                                                         class="w-full h-40 sm:h-48 object-cover group-hover:scale-105 transition-transform duration-300">
                                                </div>
                                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-all duration-300 flex items-center justify-center">
                                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <i class="fas fa-search-plus text-white text-xl sm:text-2xl"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="getCurrentQuestion().description">
                                    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-6">
                                        <h4 class="text-lg font-semibold text-blue-900 mb-3 flex items-center gap-2">
                                            <i class="fas fa-info-circle"></i>
                                            Game Instructions
                                        </h4>
                                        <div class="text-blue-800 leading-relaxed whitespace-pre-line" x-text="getCurrentQuestion().description"></div>
                                    </div>
                                </template>

                                <div class="bg-gradient-to-br from-gray-50 to-gray-100 border-2 border-dashed border-gray-300 rounded-2xl p-8">
                                    <template x-if="!isGameCompleted(getCurrentQuestion().id)">
                                        <div class="text-center">
                                            <div class="w-20 h-20 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                                                <i class="fas fa-play text-white text-2xl"></i>
                                            </div>
                                            <h4 class="text-xl font-bold text-gray-900 mb-2">Game in Progress</h4>
                                            <p class="text-gray-600 mb-6 max-w-md mx-auto">Follow the instructions above to complete this challenge!</p>

                                            <button @click="confirmCompleteGame(getCurrentQuestion().id)"
                                                    class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white px-8 py-4 rounded-2xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                                                <i class="fas fa-flag-checkered"></i>
                                                Complete Game
                                            </button>
                                        </div>
                                    </template>

                                    <template x-if="isGameCompleted(getCurrentQuestion().id)">
                                        <div class="text-center">
                                            <div class="w-20 h-20 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                                                <i class="fas fa-check-circle text-white text-2xl"></i>
                                            </div>
                                            <h4 class="text-xl font-bold text-gray-900 mb-4">Game Completed!</h4>
                                            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 max-w-md mx-auto">
                                                <div class="flex items-center justify-center gap-3 text-amber-800 mb-3">
                                                    <i class="fas fa-hourglass-half"></i>
                                                    <span class="font-semibold">Waiting for assessment...</span>
                                                </div>
                                                <p class="text-amber-700 text-sm leading-relaxed">Your game performance will be evaluated by an assessor. Assessment results will show your final score.</p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- Answer Status Indicator --}}
                        <template x-if="isQuestionAnswered(getCurrentQuestion().id)">
                            <div class="flex items-center gap-2 text-emerald-600 bg-emerald-50 px-4 py-3 rounded-2xl border border-emerald-200 mt-6">
                                <i class="fas fa-check-circle"></i>
                                <span class="font-medium">Answer saved</span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Navigation Controls --}}
                <div class="flex flex-col lg:flex-row items-center justify-between gap-4 mt-6 sm:mt-8">
                    <button @click="goToPreviousQuestion()"
                            :disabled="!canGoPrevious()"
                            class="flex items-center gap-2 px-4 sm:px-6 py-2 sm:py-3 text-gray-600 hover:text-gray-800 hover:bg-white/50 rounded-xl sm:rounded-2xl transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed backdrop-blur-sm text-sm sm:text-base order-2 lg:order-1">
                        <i class="fas fa-chevron-left"></i>
                        <span class="font-medium">Previous</span>
                    </button>

                    {{-- Question Navigator --}}
                    <div class="flex items-center gap-1 sm:gap-2 flex-wrap justify-center max-w-full overflow-x-auto pb-2 order-1 lg:order-2">
                        <template x-for="(question, index) in questions" :key="question.id">
                            <button @click="goToQuestion(index)"
                                    class="w-8 h-8 sm:w-10 sm:h-10 text-xs sm:text-sm font-bold rounded-lg sm:rounded-xl transition-all duration-300 flex-shrink-0"
                                    :class="{
                                        'bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-lg scale-110': index === currentQuestionIndex,
                                        'bg-gradient-to-br from-emerald-400 to-emerald-500 text-white hover:scale-105 shadow-md': index !== currentQuestionIndex && isQuestionAnswered(question.id),
                                        'bg-white/70 text-gray-600 hover:bg-white hover:scale-105 shadow-sm border border-gray-200': index !== currentQuestionIndex && !isQuestionAnswered(question.id)
                                    }"
                                    x-text="index + 1">
                            </button>
                        </template>
                    </div>

                    <div class="flex gap-2 sm:gap-3 order-3 lg:order-3">
                        <template x-if="!isLastQuestion()">
                            <button @click="goToNextQuestion()"
                                    :disabled="!canGoNext()"
                                    class="flex items-center gap-2 px-4 sm:px-8 py-2 sm:py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl sm:rounded-2xl font-semibold hover:from-indigo-600 hover:to-purple-700 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl transform hover:scale-105 text-sm sm:text-base">
                                <span>Next</span>
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </template>

                        <template x-if="isLastQuestion()">
                            <template x-if="areAllQuestionsAnswered()">
                                <button @click="submitQuiz()"
                                        class="flex items-center gap-2 px-4 sm:px-8 py-2 sm:py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl sm:rounded-2xl font-semibold hover:from-emerald-600 hover:to-emerald-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 text-sm sm:text-base">
                                    <i class="fas fa-camera mr-1"></i>
                                    <i class="fas fa-check"></i>
                                    <span>Take Photo & Submit</span>
                                </button>
                            </template>
                            <template x-if="!areAllQuestionsAnswered()">
                                <button @click="jumpToFirstUnanswered()"
                                        class="flex items-center gap-2 px-4 sm:px-8 py-2 sm:py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-xl sm:rounded-2xl font-semibold hover:from-amber-600 hover:to-amber-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 text-sm sm:text-base">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span>Complete All Questions</span>
                                </button>
                            </template>
                        </template>
                    </div>
                </div>

                {{-- Legend --}}
                <div class="flex items-center justify-center gap-6 mt-6 text-xs text-gray-600">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg"></div>
                        <span>Current</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-gradient-to-br from-emerald-400 to-emerald-500 rounded-lg"></div>
                        <span>Answered</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-white border-2 border-gray-200 rounded-lg"></div>
                        <span>Unanswered</span>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Quiz Completion State --}}
    <div x-show="!loading && !error && isCompleted" class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-indigo-50 via-white to-purple-50">
        <div class="max-w-md w-full bg-white/80 backdrop-blur-xl rounded-3xl shadow-xl border border-white/20 p-8 text-center">
            <div class="w-20 h-20 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                <i class="fas fa-check-circle text-white text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Quiz Complete!</h2>
            <p class="text-gray-600 mb-8">Redirecting to results page...</p>

            <div class="flex flex-col gap-3">
                <a :href="`/quiz/results/${attempt?.id}`"
                   class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-3 rounded-2xl font-semibold hover:from-blue-600 hover:to-blue-700 transition-all duration-300 shadow-lg">
                    <i class="fas fa-chart-bar"></i>
                    View Results
                </a>
                <a href="/user/dashboard"
                   class="inline-flex items-center justify-center gap-2 bg-white text-gray-700 px-6 py-3 rounded-2xl font-semibold hover:bg-gray-50 transition-all duration-300 shadow-md border border-gray-200">
                    <i class="fas fa-list"></i>
                    Back to Quiz List
                </a>
            </div>
        </div>
    </div>

    {{-- Complete Game Confirmation Modal --}}
    <div x-show="showCompleteGameModal"
         x-cloak
         class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         @click.self="cancelCompleteGame()">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 transform transition-all"
             @click.stop>
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-flag-checkered text-white text-2xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Are you sure?</h3>
                <p class="text-gray-600">
                    You will be redirected to the assessment form.
                </p>
            </div>

            <div class="flex gap-3">
                <button @click="cancelCompleteGame()"
                        class="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button @click="completeGame()"
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white font-semibold rounded-xl transition-all shadow-lg">
                    <i class="fas fa-check mr-2"></i>Confirm
                </button>
            </div>
        </div>
    </div>

    {{-- Image Modal for Fun Games --}}
    <div id="imageModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden" style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4" @click="closeImageModal()">
            <div class="relative max-w-5xl max-h-full" @click.stop>
                <img id="modalImage" src="" alt="Game Image" class="max-w-full max-h-full object-contain rounded-3xl shadow-2xl">
                <button @click="closeImageModal()" class="absolute -top-4 -right-4 bg-white text-gray-700 w-10 h-10 rounded-full flex items-center justify-center hover:bg-gray-100 transition-all duration-300 shadow-lg">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Timer Styles */
    .timer-widget-enhanced {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 1rem;
        padding: 1rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border: 2px solid transparent;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        min-width: 280px;
    }

    .timer-normal {
        border-color: rgba(59, 130, 246, 0.3);
        background: linear-gradient(135deg, rgba(219, 234, 254, 0.8) 0%, rgba(255, 255, 255, 0.95) 100%);
    }

    .timer-warning {
        border-color: rgba(245, 158, 11, 0.4);
        background: linear-gradient(135deg, rgba(254, 243, 199, 0.8) 0%, rgba(255, 255, 255, 0.95) 100%);
        animation: gentle-pulse 2s infinite;
    }

    .timer-critical {
        border-color: rgba(239, 68, 68, 0.4);
        background: linear-gradient(135deg, rgba(254, 202, 202, 0.8) 0%, rgba(255, 255, 255, 0.95) 100%);
        animation: urgent-pulse 1s infinite;
    }

    .timer-display-enhanced {
        font-size: 1.5rem;
        font-weight: 700;
        font-family: 'SF Mono', 'Monaco', monospace;
        color: #374151;
    }

    .timer-status-enhanced {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6b7280;
    }

    .timer-percentage-enhanced {
        font-size: 0.875rem;
        font-weight: 700;
        color: #4b5563;
    }

    .timer-progress-enhanced {
        width: 100%;
        height: 0.5rem;
        background: rgba(0, 0, 0, 0.1);
        border-radius: 0.25rem;
        overflow: hidden;
        margin-top: 0.75rem;
    }

    .timer-progress-bar-enhanced {
        height: 100%;
        border-radius: 0.25rem;
        transition: width 0.3s ease;
    }

    @keyframes gentle-pulse {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        50% {
            transform: scale(1.01);
            box-shadow: 0 15px 35px rgba(245, 158, 11, 0.15);
        }
    }

    @keyframes urgent-pulse {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        50% {
            transform: scale(1.02);
            box-shadow: 0 20px 40px rgba(239, 68, 68, 0.2);
        }
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .timer-widget-enhanced {
            min-width: 240px;
            padding: 0.75rem;
        }

        .timer-display-enhanced {
            font-size: 1.1rem;
        }
    }

    @media (max-width: 640px) {
        .timer-widget-enhanced {
            min-width: 200px;
            padding: 0.5rem;
        }

        .timer-display-enhanced {
            font-size: 1rem;
        }
    }
</style>
@endpush
@endsection
