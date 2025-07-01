<div>
    <!-- Score Breakdown Modal -->
    <x-modal name="score-breakdown" :show="$showModal" max-width="4xl">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <div class="modal-title-section">
                    <div class="modal-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div>
                        <h3 class="modal-title">Score Breakdown</h3>
                        <p class="modal-subtitle">{{ $team->name ?? 'Your Team' }} - Detailed Score Analysis</p>
                    </div>
                </div>
                <button wire:click="closeModal" class="modal-close-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
                <!-- Score Summary Cards -->
                <div class="score-summary-grid">
                    <div class="summary-card base-points">
                        <div class="summary-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="summary-content">
                            <div class="summary-value">{{ number_format($basePoints) }}</div>
                            <div class="summary-label">Base Points</div>
                            <div class="summary-description">Initial team allocation</div>
                        </div>
                    </div>

                    <div class="summary-card earned-points">
                        <div class="summary-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="summary-content">
                            <div class="summary-value">{{ number_format($earnedPoints) }}</div>
                            <div class="summary-label">Earned Points</div>
                            <div class="summary-description">From quiz challenges</div>
                        </div>
                    </div>

                    <div class="summary-card total-points">
                        <div class="summary-icon">
                            <i class="fas fa-crown"></i>
                        </div>
                        <div class="summary-content">
                            <div class="summary-value">{{ number_format($basePoints + $earnedPoints) }}</div>
                            <div class="summary-label">Total Score</div>
                            <div class="summary-description">Combined achievement</div>
                        </div>
                    </div>
                </div>

                <!-- Quiz Attempts Details -->
                @if(count($quizAttempts) > 0)
                    <div class="quiz-attempts-section">
                        <div class="section-header">
                            <h4 class="section-title">
                                <i class="fas fa-list-check"></i>
                                Quiz Completions ({{ count($quizAttempts) }})
                            </h4>
                        </div>

                        <div class="attempts-list">
                            @foreach($quizAttempts as $attempt)
                                <div class="attempt-item">
                                    <div class="attempt-info">
                                        <div class="attempt-user">
                                            <div class="user-avatar-small">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <span class="user-name">{{ $attempt['user_name'] }}</span>
                                        </div>
                                        <div class="attempt-details">
                                            <div class="quiz-title">{{ $attempt['questionnaire_title'] }}</div>
                                            <div class="completion-date">
                                                <i class="fas fa-calendar"></i>
                                                {{ $attempt['completed_at']->format('M d, Y H:i') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="attempt-score">
                                        <span class="score-value">+{{ number_format($attempt['score']) }}</span>
                                        <span class="score-unit">pts</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-clipboard-question"></i>
                        </div>
                        <div class="empty-title">No Quiz Completions Yet</div>
                        <div class="empty-description">
                            Team members haven't completed any quizzes yet. 
                            Start taking quizzes to earn points!
                        </div>
                    </div>
                @endif
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
                <button wire:click="closeModal" class="btn-secondary">
                    <i class="fas fa-times"></i>
                    Close
                </button>
            </div>
        </div>
    </x-modal>

    <style>
        .modal-content {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 24px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: white;
        }

        .modal-title-section {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .modal-icon {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .modal-title {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }

        .modal-subtitle {
            font-size: 14px;
            opacity: 0.9;
            margin: 4px 0 0 0;
        }

        .modal-close-btn {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 10px;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .modal-close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .modal-body {
            padding: 32px;
            flex: 1;
            overflow-y: auto;
        }

        .score-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .summary-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        }

        .summary-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }

        .base-points .summary-icon {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .earned-points .summary-icon {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .total-points .summary-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .summary-value {
            font-size: 24px;
            font-weight: 900;
            color: #1f2937;
            line-height: 1;
        }

        .summary-label {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-top: 4px;
        }

        .summary-description {
            font-size: 12px;
            color: #6b7280;
            margin-top: 2px;
        }

        .quiz-attempts-section {
            border-top: 1px solid #e5e7eb;
            padding-top: 32px;
        }

        .section-header {
            margin-bottom: 20px;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
        }

        .attempts-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-height: 300px;
            overflow-y: auto;
        }

        .attempt-item {
            background: #f9fafb;
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .attempt-item:hover {
            background: #f3f4f6;
            transform: translateX(4px);
        }

        .attempt-info {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 1;
        }

        .attempt-user {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 120px;
        }

        .user-avatar-small {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
        }

        .user-name {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
        }

        .attempt-details {
            flex: 1;
        }

        .quiz-title {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .completion-date {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #6b7280;
        }

        .attempt-score {
            display: flex;
            align-items: baseline;
            gap: 4px;
        }

        .score-value {
            font-size: 18px;
            font-weight: 700;
            color: #059669;
        }

        .score-unit {
            font-size: 12px;
            color: #6b7280;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            border-top: 1px solid #e5e7eb;
            margin-top: 32px;
        }

        .empty-icon {
            width: 64px;
            height: 64px;
            background: #f3f4f6;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #9ca3af;
            margin: 0 auto 16px;
        }

        .empty-title {
            font-size: 18px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .empty-description {
            font-size: 14px;
            color: #6b7280;
            max-width: 400px;
            margin: 0 auto;
        }

        .modal-footer {
            background: #f9fafb;
            padding: 20px 32px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .modal-header {
                padding: 20px;
            }

            .modal-body {
                padding: 20px;
            }

            .score-summary-grid {
                grid-template-columns: 1fr;
            }

            .attempt-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .attempt-info {
                width: 100%;
            }

            .attempt-score {
                align-self: flex-end;
            }
        }
    </style>
</div>