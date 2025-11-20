{{-- Alpine.js QR Scanner Modal Component --}}
<div x-data="qrScanner" x-init="init()" x-cloak>
    <div x-show="showModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">

        <div @click.outside="closeModal()"
             class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600">

            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('quiz.scan_qr_code') }}</h3>

                <!-- Scanner View -->
                <div x-show="isScanning">
                    <div id="qr-scanner-container" class="relative">
                        <video id="qr-video" class="w-full max-w-md mx-auto rounded-lg bg-black" playsinline autoplay muted></video>
                        <div id="qr-scanner-overlay" class="absolute inset-0 border-2 border-blue-500 rounded-lg pointer-events-none"></div>
                        <div class="absolute bottom-2 left-2 right-2 text-center">
                            <p class="text-white text-sm bg-black bg-opacity-50 rounded px-2 py-1">
                                {{ __('quiz.point_camera_at_qr') }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-4 text-center">
                        <button @click="stopScanning()"
                                class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition-colors">
                            {{ __('quiz.stop_scanning') }}
                        </button>
                    </div>
                </div>

                <!-- Start Scanning View -->
                <div x-show="!isScanning" class="text-center">
                    <button @click="startScanning()"
                            class="bg-blue-500 text-white px-4 py-2 rounded mb-4 hover:bg-blue-600 transition-colors">
                        {{ __('quiz.start_scanning') }}
                    </button>

                    <!-- Info Section -->
                    <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded text-sm text-blue-800 dark:text-blue-200">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <p class="font-medium text-blue-800 dark:text-blue-200">{{ __('quiz.scanning_tips') }}</p>
                                <ul class="list-disc list-inside mt-1 space-y-1 text-left text-blue-700 dark:text-blue-300">
                                    <li>{{ __('quiz.allow_camera_access') }}</li>
                                    <li>{{ __('quiz.ensure_good_lighting') }}</li>
                                    <li>{{ __('quiz.hold_qr_steady') }}</li>
                                    <li>{{ __('quiz.use_manual_entry') }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Manual Entry -->
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Manual Entry</label>
                    <div class="flex mt-1">
                        <input
                            type="text"
                            x-model="scannedCode"
                            @keydown.enter="submitManualCode()"
                            class="flex-1 border border-gray-300 dark:border-gray-600 rounded px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                            placeholder="Enter QR code manually"
                            autocomplete="off"
                            spellcheck="false">
                        <button
                            @click="submitManualCode()"
                            class="ml-2 bg-green-500 hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-700 text-white px-4 py-2 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            Submit
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Enter the QR code text manually if scanning doesn't work
                        <span x-show="scannedCode" class="text-green-600 dark:text-green-400">
                            • Code entered: <span x-text="scannedCode.length"></span> characters
                        </span>
                    </p>

                    <!-- Test Helper (only in development) -->
                    @if(config('app.debug'))
                        <div class="mt-2 p-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded text-xs">
                            <p class="font-medium text-yellow-800 dark:text-yellow-200">Test QR Codes:</p>
                            <div class="flex flex-wrap gap-1 mt-1">
                                <button @click="setCode('91ccdded-291a-43cc-a626-077cea5bc3b5')"
                                        class="px-2 py-1 bg-yellow-100 dark:bg-yellow-800 text-yellow-800 dark:text-yellow-200 rounded text-xs hover:bg-yellow-200 dark:hover:bg-yellow-700">
                                    Test 1
                                </button>
                                <button @click="setCode('55f2d997-fba1-47eb-8e0e-29b56a70c0fd')"
                                        class="px-2 py-1 bg-yellow-100 dark:bg-yellow-800 text-yellow-800 dark:text-yellow-200 rounded text-xs hover:bg-yellow-200 dark:hover:bg-yellow-700">
                                    Pos 1
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Error Display -->
                <div x-show="error"
                     x-transition
                     class="mt-4 p-4 bg-red-100 dark:bg-red-900/20 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 rounded">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-red-500 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <div>
                            <h4 class="font-semibold text-red-700 dark:text-red-300">Error</h4>
                            <p class="mt-1 text-red-700 dark:text-red-300" x-text="error"></p>
                        </div>
                    </div>
                </div>

                <!-- Questionnaire Display -->
                <div x-show="questionnaire"
                     x-transition
                     class="mt-4 p-4 bg-green-100 dark:bg-green-900/20 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 rounded">
                    <template x-if="questionnaire">
                        <div>
                            <h4 class="font-semibold text-lg text-green-700 dark:text-green-300" x-text="questionnaire.title"></h4>
                            <p class="mt-2 text-green-700 dark:text-green-300" x-text="questionnaire.description"></p>
                            <p x-show="questionnaire.time_limit" class="mt-2 text-sm text-green-600 dark:text-green-400">
                                <i class="fas fa-clock"></i> Time Limit: <span x-text="questionnaire.time_limit"></span> minutes
                            </p>
                            <div class="mt-4">
                                <button @click="startQuiz()"
                                        class="bg-green-600 hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-800 text-white px-6 py-2 rounded transition-colors w-full">
                                    Start Quiz
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Close Button -->
                <div class="mt-6 text-center">
                    <button @click="closeModal()"
                            class="bg-gray-500 hover:bg-gray-600 dark:bg-gray-600 dark:hover:bg-gray-700 text-white px-4 py-2 rounded transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
