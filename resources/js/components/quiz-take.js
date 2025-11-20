// Alpine.js Quiz Take Component
export default function (Alpine) {
    Alpine.data('quizTake', (attemptId = null, questionnaireId = null) => ({
        // State
        questionnaire: null,
        attempt: null,
        questions: [],
        answers: {},
        currentQuestionIndex: 0,
        timeRemaining: null,
        totalPoints: 0,
        isCompleted: false,
        quizLocked: false,
        preventNavigation: true,
        timerExpired: false,
        autoSubmitted: false,
        loading: true,
        error: null,
        successMessage: null,

        // Timer
        timerInterval: null,
        autoSaveInterval: null,
        lastSaveTime: null,
        isSaving: false,

        // Navigation blocker
        beforeUnloadHandler: null,
        boundHandlePopState: null,
        boundHandleContextMenu: null,
        boundHandleKeyDown: null,

        // Complete game confirmation
        showCompleteGameModal: false,
        pendingCompleteQuestionId: null,

        async init() {
            // Bind event handlers to maintain 'this' context
            this.boundHandlePopState = this.handlePopState.bind(this);
            this.boundHandleContextMenu = this.handleContextMenu.bind(this);
            this.boundHandleKeyDown = this.handleKeyDown.bind(this);
            try {
                console.log('INIT - attemptId:', attemptId, 'questionnaireId:', questionnaireId);

                // Load quiz data
                if (attemptId) {
                    console.log('Calling loadAttempt with attemptId:', attemptId);
                    await this.loadAttempt(attemptId);
                } else if (questionnaireId) {
                    console.log('Calling startQuiz with questionnaireId:', questionnaireId);
                    await this.startQuiz(questionnaireId);
                } else {
                    throw new Error('Invalid quiz access');
                }

                // Initialize
                this.quizLocked = true;
                this.setupNavigationBlocker();
                this.setupKeyboardShortcuts();

                // Start timer if needed
                if (this.questionnaire.time_limit && !this.isCompleted) {
                    this.startTimer();
                }

                // Start auto-save
                this.startAutoSave();

                this.loading = false;
            } catch (error) {
                console.error('Quiz initialization error:', error);
                this.error = error.message || 'Failed to load quiz';
                this.loading = false;
            }
        },

        async loadAttempt(attemptId) {
            const response = await fetch(`/api/quiz/continue/${attemptId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
            });

            const data = await response.json();

            if (!data.success) {
                if (data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                throw new Error(data.message);
            }

            console.log('Quiz data loaded:', data);
            console.log('Time remaining from API:', data.timeRemaining, typeof data.timeRemaining);

            this.questionnaire = data.questionnaire;
            this.attempt = data.attempt;
            this.questions = data.questions;
            this.answers = data.answers || {};
            this.totalPoints = data.totalPoints;
            this.timeRemaining = parseInt(data.timeRemaining) || null;

            // Initialize missing answers
            this.questions.forEach(q => {
                if (!(q.id in this.answers)) {
                    this.answers[q.id] = '';
                }
            });

            if (data.continued) {
                this.showNotification('Quiz Continued', 'Resuming from where you left off', 'success');
            }
        },

        async startQuiz(questionnaireId) {
            console.log('Starting quiz with questionnaireId:', questionnaireId);

            const response = await fetch(`/api/quiz/start/${questionnaireId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
            });

            console.log('Response status:', response.status);

            const data = await response.json();
            console.log('Response data:', data);

            if (!data.success) {
                console.error('Quiz start failed:', data.message);
                throw new Error(data.message);
            }

            console.log('Time remaining from API:', data.timeRemaining, typeof data.timeRemaining);

            this.questionnaire = data.questionnaire;
            this.attempt = data.attempt;
            this.questions = data.questions;
            this.totalPoints = data.totalPoints;
            this.timeRemaining = parseInt(data.timeRemaining) || null;

            console.log('Quiz loaded successfully:', {
                questionnaire: this.questionnaire,
                questions: this.questions.length,
                attempt: this.attempt
            });

            // Initialize answers (use existing answers if available, otherwise empty)
            this.answers = data.answers || {};
            this.questions.forEach(q => {
                if (!(q.id in this.answers)) {
                    this.answers[q.id] = '';
                }
            });
        },

        async saveAnswer(questionId, answer, silent = false) {
            try {
                const response = await fetch('/api/quiz/save-answer', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        attempt_id: this.attempt.id,
                        question_id: questionId,
                        answer: answer
                    })
                });

                const data = await response.json();

                if (!data.success) {
                    if (!silent) {
                        this.showNotification('Error', data.message, 'error');
                    }
                    throw new Error(data.message);
                }

                return data;

            } catch (error) {
                if (!silent) {
                    console.error('Save answer error:', error);
                }
                throw error;
            }
        },

        goToNextQuestion() {
            if (this.currentQuestionIndex < this.questions.length - 1) {
                this.currentQuestionIndex++;
            } else {
                // If on last question, jump to first unanswered
                this.jumpToFirstUnanswered();
            }
        },

        goToPreviousQuestion() {
            if (this.currentQuestionIndex > 0) {
                this.currentQuestionIndex--;
            }
        },

        goToQuestion(index) {
            if (index >= 0 && index < this.questions.length) {
                this.currentQuestionIndex = index;
            }
        },

        jumpToFirstUnanswered() {
            // Find first unanswered question
            for (let i = 0; i < this.questions.length; i++) {
                const question = this.questions[i];
                if (!this.isQuestionAnswered(question.id)) {
                    this.currentQuestionIndex = i;
                    this.showNotification('Notice', 'Jumped to first unanswered question', 'info');
                    return;
                }
            }
            // All questions answered
            this.showNotification('Success', 'All questions have been answered!', 'success');
        },

        async submitQuiz() {
            try {
                // Validate all questions are answered
                if (!this.areAllQuestionsAnswered()) {
                    this.showNotification('Incomplete Quiz', 'Please answer all questions before submitting', 'warning');
                    this.jumpToFirstUnanswered();
                    return;
                }

                // Capture verification photo (REQUIRED)
                let verificationPhoto = null;

                if (window.CameraCapture && window.CameraCapture.isSupported()) {
                    const allowCamera = await window.CameraCapture.showCameraPermissionModal();

                    if (!allowCamera) {
                        this.showNotification('Photo Required', 'You must take a verification photo to submit the quiz', 'error');
                        return;
                    }

                    window.CameraCapture.showCaptureProgressModal();
                    const camera = new window.CameraCapture();
                    const photoResult = await camera.capturePhoto();
                    window.CameraCapture.hideCaptureProgressModal();

                    if (photoResult.error) {
                        this.showNotification('Camera Error', `Failed to capture photo: ${photoResult.message}. Please try again.`, 'error');
                        return;
                    }

                    verificationPhoto = photoResult;
                    window.CameraCapture.showCaptureSuccessModal();
                } else {
                    // Camera not supported - show error
                    this.showNotification('Camera Required', 'Camera access is required to submit the quiz. Please use a device with a camera.', 'error');
                    return;
                }

                // Submit quiz
                const response = await fetch('/api/quiz/submit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        attempt_id: this.attempt.id,
                        verification_photo: verificationPhoto
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.isCompleted = true;
                    this.quizLocked = false;
                    this.preventNavigation = false;
                    this.removeNavigationBlocker();
                    this.stopTimer();

                    this.showNotification('Success', data.message, 'success');

                    // Redirect to results
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                } else {
                    this.showNotification('Error', data.message, 'error');
                }

            } catch (error) {
                console.error('Submit error:', error);
                this.showNotification('Error', 'Failed to submit quiz', 'error');
            }
        },

        // Show confirmation modal for completing game
        confirmCompleteGame(questionId) {
            this.pendingCompleteQuestionId = questionId;
            this.showCompleteGameModal = true;
        },

        // Cancel complete game
        cancelCompleteGame() {
            this.showCompleteGameModal = false;
            this.pendingCompleteQuestionId = null;
        },

        // Proceed with completing game after confirmation
        async completeGame() {
            const questionId = this.pendingCompleteQuestionId;
            this.showCompleteGameModal = false;

            // CRITICAL: Set completion flags BEFORE any async operations
            // This prevents beforeunload from triggering during the API call
            this.isCompleted = true;
            this.quizLocked = false;
            this.preventNavigation = false;

            // Stop timer and auto-save immediately
            this.stopTimer();
            this.stopAutoSave();

            // Aggressively remove ALL navigation blockers
            this.removeNavigationBlocker();

            try {
                const response = await fetch('/api/quiz/complete-game', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        attempt_id: this.attempt.id,
                        question_id: questionId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.answers[questionId] = 'completed';

                    // Use setTimeout to ensure all event cleanup is processed before redirect
                    setTimeout(() => {
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        }
                    }, 100);
                } else {
                    // Error: restore navigation protection
                    this.isCompleted = false;
                    this.quizLocked = true;
                    this.preventNavigation = true;
                    this.setupNavigationBlocker();
                    this.showNotification('Error', data.message, 'error');
                }

            } catch (error) {
                console.error('Complete game error:', error);
                // Error: restore navigation protection
                this.isCompleted = false;
                this.quizLocked = true;
                this.preventNavigation = true;
                this.setupNavigationBlocker();
                this.showNotification('Error', 'Failed to complete game', 'error');
            }
        },

        // Timer functionality
        startTimer() {
            if (!this.questionnaire.time_limit || this.timeRemaining === null) {
                return;
            }

            // Stop any existing timer first to prevent multiple intervals
            if (this.timerInterval) {
                console.warn('Timer already running, stopping old timer before starting new one');
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }

            // Ensure timeRemaining is an integer
            this.timeRemaining = parseInt(this.timeRemaining);
            console.log('Timer started with timeRemaining:', this.timeRemaining);

            this.timerInterval = setInterval(() => {
                if (this.timeRemaining > 0) {
                    this.timeRemaining--;
                } else {
                    this.handleTimeExpiry();
                }
            }, 1000);
        },

        stopTimer() {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }
        },

        handleTimeExpiry() {
            if (this.isCompleted || this.autoSubmitted) {
                return;
            }

            this.timerExpired = true;
            this.autoSubmitted = true;
            this.stopTimer();
            this.showNotification('Time Expired', 'Quiz time has expired. Submitting automatically.', 'warning');

            setTimeout(() => {
                this.submitQuiz();
            }, 1000);
        },

        get formattedTimeRemaining() {
            if (this.timeRemaining === null) {
                return null;
            }

            const timeRemainingInt = parseInt(this.timeRemaining);
            const minutes = Math.floor(timeRemainingInt / 60);
            const seconds = timeRemainingInt % 60;

            // Debug log for garbage values
            if (minutes > 999 || seconds > 59) {
                console.error('Invalid timer values detected!', {
                    timeRemaining: this.timeRemaining,
                    timeRemainingType: typeof this.timeRemaining,
                    timeRemainingInt,
                    minutes,
                    seconds
                });
            }

            return `${minutes}:${seconds.toString().padStart(2, '0')}`;
        },

        // Auto-save functionality
        startAutoSave() {
            if (this.isCompleted) {
                return;
            }

            // Stop any existing auto-save first to prevent multiple intervals
            if (this.autoSaveInterval) {
                console.warn('Auto-save already running, stopping old interval before starting new one');
                clearInterval(this.autoSaveInterval);
                this.autoSaveInterval = null;
            }

            // Auto-save every 15 seconds
            this.autoSaveInterval = setInterval(() => {
                this.performAutoSave();
            }, 15000);
        },

        stopAutoSave() {
            if (this.autoSaveInterval) {
                clearInterval(this.autoSaveInterval);
                this.autoSaveInterval = null;
            }
        },

        async performAutoSave() {
            if (this.isSaving || this.isCompleted) {
                return;
            }

            try {
                this.isSaving = true;

                // Save all current answers
                const savePromises = [];
                for (const [questionId, answer] of Object.entries(this.answers)) {
                    if (answer && answer !== '') {
                        savePromises.push(this.saveAnswer(questionId, answer, true)); // true = silent mode
                    }
                }

                await Promise.all(savePromises);
                this.lastSaveTime = new Date();
                console.log('Auto-save completed at', this.lastSaveTime.toISOString());

            } catch (error) {
                console.error('Auto-save error:', error);
                // Retry after 5 seconds on failure
                setTimeout(() => {
                    this.performAutoSave();
                }, 5000);
            } finally {
                this.isSaving = false;
            }
        },

        get timerClass() {
            if (this.timeRemaining <= 60) {
                return 'timer-critical';
            } else if (this.timeRemaining <= 300) {
                return 'timer-warning';
            } else {
                return 'timer-normal';
            }
        },

        get timerPercentage() {
            if (!this.questionnaire.time_limit || this.timeRemaining === null) {
                return 100;
            }

            const totalSeconds = this.questionnaire.time_limit * 60;
            return Math.round((this.timeRemaining / totalSeconds) * 100);
        },

        // Navigation
        setupNavigationBlocker() {
            this.beforeUnloadHandler = (e) => {
                if (this.quizLocked && !this.isCompleted) {
                    e.preventDefault();
                    e.returnValue = 'Are you sure?';
                    return 'Are you sure?';
                }
            };

            window.addEventListener('beforeunload', this.beforeUnloadHandler);

            // Block browser back button
            if (this.quizLocked && !this.isCompleted) {
                history.pushState(null, '', location.href);

                window.addEventListener('popstate', this.boundHandlePopState);
            }

            // Block right-click
            document.addEventListener('contextmenu', this.boundHandleContextMenu);
        },

        handlePopState(event) {
            if (this.quizLocked && !this.isCompleted) {
                history.pushState(null, '', location.href);

                if (confirm('Quiz in progress! Are you sure you want to abandon this quiz? Your progress will be lost.')) {
                    this.forceExit();
                }
            }
        },

        handleContextMenu(e) {
            if (this.quizLocked && !this.isCompleted) {
                e.preventDefault();
                return false;
            }
        },

        removeNavigationBlocker() {
            // Aggressively remove ALL event listeners
            if (this.beforeUnloadHandler) {
                window.removeEventListener('beforeunload', this.beforeUnloadHandler);
                this.beforeUnloadHandler = null;
            }

            if (this.boundHandlePopState) {
                window.removeEventListener('popstate', this.boundHandlePopState);
                this.boundHandlePopState = null;
            }

            if (this.boundHandleContextMenu) {
                document.removeEventListener('contextmenu', this.boundHandleContextMenu);
                this.boundHandleContextMenu = null;
            }

            if (this.boundHandleKeyDown) {
                document.removeEventListener('keydown', this.boundHandleKeyDown);
                this.boundHandleKeyDown = null;
            }

            // Extra safety: remove window-level listeners by creating a clean handler
            window.onbeforeunload = null;
        },

        forceExit() {
            this.quizLocked = false;
            this.removeNavigationBlocker();
            window.location.href = '/user/dashboard';
        },

        // Keyboard shortcuts
        setupKeyboardShortcuts() {
            document.addEventListener('keydown', this.boundHandleKeyDown);
        },

        handleKeyDown(e) {
            // Prevent all refresh attempts (F5, Ctrl+R, Cmd+R)
            if (!this.isCompleted && (
                e.key === 'F5' ||
                (e.ctrlKey && e.key === 'r') ||
                (e.metaKey && e.key === 'r') ||  // Mac Command+R
                (e.ctrlKey && e.key === 'R') ||
                (e.metaKey && e.key === 'R')     // Mac Command+Shift+R (hard refresh)
            )) {
                e.preventDefault();
                e.stopPropagation();
                this.showNotification('Refresh Disabled', 'Browser refresh is disabled during the quiz to prevent timer manipulation', 'warning');
                return false;
            }

            // Alt + Arrow keys for navigation
            if (!this.isCompleted && e.altKey) {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    this.goToPreviousQuestion();
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    this.goToNextQuestion();
                }
            }

            // Ctrl + Enter to submit
            if (!this.isCompleted && e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                this.submitQuiz();
            }

            // Escape to close image modal
            if (e.key === 'Escape') {
                this.closeImageModal();
            }
        },

        // Utility methods
        getCurrentQuestion() {
            return this.questions[this.currentQuestionIndex] || null;
        },

        getProgressPercentage() {
            if (this.questions.length === 0) {
                return 0;
            }
            return Math.round(((this.currentQuestionIndex + 1) / this.questions.length) * 100);
        },

        getAnsweredQuestionsCount() {
            return Object.values(this.answers).filter(a => a && a.trim() !== '').length;
        },

        getAnsweredPercentage() {
            if (this.questions.length === 0) {
                return 0;
            }
            return Math.round((this.getAnsweredQuestionsCount() / this.questions.length) * 100);
        },

        isQuestionAnswered(questionId) {
            const answer = this.answers[questionId];
            return answer && answer.trim() !== '';
        },

        canGoNext() {
            return this.currentQuestionIndex < this.questions.length - 1;
        },

        canGoPrevious() {
            return this.currentQuestionIndex > 0;
        },

        isLastQuestion() {
            return this.currentQuestionIndex === this.questions.length - 1;
        },

        isGameCompleted(questionId) {
            return this.answers[questionId] === 'completed';
        },

        areAllQuestionsAnswered() {
            for (const question of this.questions) {
                if (!this.isQuestionAnswered(question.id)) {
                    return false;
                }
            }
            return this.questions.length > 0;
        },

        // Image modal
        openImageModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            if (modal && modalImage) {
                modalImage.src = imageSrc;
                modal.classList.remove('hidden');
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        },

        closeImageModal() {
            const modal = document.getElementById('imageModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        },

        // Notifications
        showNotification(title, message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 max-w-md bg-white border-2 rounded-xl p-4 shadow-xl notification-${type}`;

            const iconClass = type === 'success' ? 'fa-check-circle text-green-600' :
                type === 'error' ? 'fa-exclamation-circle text-red-600' :
                    type === 'warning' ? 'fa-exclamation-triangle text-yellow-600' :
                        'fa-info-circle text-blue-600';

            notification.innerHTML = `
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-gray-100 rounded-xl flex items-center justify-center">
                        <i class="fas ${iconClass}"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-900">${title}</h4>
                        <p class="text-sm text-gray-600">${message}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;

            document.body.appendChild(notification);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 5000);
        },

        // Cleanup
        destroy() {
            this.stopTimer();
            this.stopAutoSave();
            this.removeNavigationBlocker();
            if (this.boundHandleKeyDown) {
                document.removeEventListener('keydown', this.boundHandleKeyDown);
            }
        }
    }));
}
