<div>
    <style>
        /* Force premium styles to override Tailwind */
        .quiz-results-premium {
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 25%, #24243e 50%, #302b63 75%, #0f0c29 100%) !important;
            min-height: 100vh !important;
            position: relative !important;
            overflow-x: hidden !important;
        }
        
        .particles-bg {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            pointer-events: none !important;
            z-index: 1 !important;
        }
        
        .floating-particle {
            position: absolute !important;
            width: 4px !important;
            height: 4px !important;
            background: rgba(255, 255, 255, 0.3) !important;
            border-radius: 50% !important;
            animation: floatUp 20s infinite linear !important;
        }
        
        .orbs-bg {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            pointer-events: none !important;
            z-index: 0 !important;
        }
        
        .gradient-orb {
            position: absolute !important;
            border-radius: 50% !important;
            filter: blur(40px) !important;
            animation: orbFloat 30s infinite ease-in-out !important;
        }
        
        .orb1 {
            width: 300px !important;
            height: 300px !important;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.4) 0%, transparent 70%) !important;
            top: 10% !important;
            left: 10% !important;
        }
        
        .orb2 {
            width: 400px !important;
            height: 400px !important;
            background: radial-gradient(circle, rgba(236, 72, 153, 0.3) 0%, transparent 70%) !important;
            top: 50% !important;
            right: 10% !important;
            animation-delay: 10s !important;
        }
        
        .orb3 {
            width: 250px !important;
            height: 250px !important;
            background: radial-gradient(circle, rgba(34, 197, 94, 0.4) 0%, transparent 70%) !important;
            bottom: 10% !important;
            left: 30% !important;
            animation-delay: 20s !important;
        }
        
        .premium-header {
            background: rgba(255, 255, 255, 0.95) !important;
            border-radius: 24px !important;
            padding: 40px !important;
            margin-bottom: 40px !important;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            backdrop-filter: blur(20px) !important;
            position: relative !important;
            overflow: hidden !important;
            z-index: 10 !important;
        }
        
        .premium-content {
            position: relative !important;
            z-index: 2 !important;
        }
        
        .score-display-big {
            font-size: 72px !important;
            font-weight: 900 !important;
            background: linear-gradient(135deg, #1e293b 0%, #475569 50%, #1e293b 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            font-family: 'SF Mono', 'Monaco', monospace !important;
            line-height: 1 !important;
        }
        
        .premium-card {
            background: rgba(255, 255, 255, 0.95) !important;
            border-radius: 20px !important;
            padding: 32px !important;
            position: relative !important;
            overflow: hidden !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            backdrop-filter: blur(20px) !important;
            transition: all 0.3s ease !important;
        }
        
        .premium-card:hover {
            transform: translateY(-4px) !important;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15) !important;
        }
        
        .hero-score-card {
            background: rgba(255, 255, 255, 0.95) !important;
            border-radius: 32px !important;
            padding: 48px !important;
            box-shadow: 0 32px 64px rgba(0, 0, 0, 0.12) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            backdrop-filter: blur(20px) !important;
            position: relative !important;
            overflow: hidden !important;
            margin-bottom: 40px !important;
        }
        
        .stats-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)) !important;
            gap: 24px !important;
            margin-top: 40px !important;
        }
        
        .score-grid {
            position: relative !important;
            z-index: 2 !important;
            display: grid !important;
            grid-template-columns: auto 1fr auto !important;
            gap: 48px !important;
            align-items: center !important;
        }
        
        .badge-diamond {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%) !important;
            color: white !important;
            box-shadow: 0 8px 32px rgba(139, 92, 246, 0.4) !important;
        }
        
        .badge-gold {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
            color: white !important;
            box-shadow: 0 8px 32px rgba(245, 158, 11, 0.4) !important;
        }
        
        .badge-silver {
            background: linear-gradient(135deg, #64748b 0%, #475569 100%) !important;
            color: white !important;
            box-shadow: 0 8px 32px rgba(100, 116, 139, 0.4) !important;
        }
        
        .tier-badge {
            display: inline-flex !important;
            align-items: center !important;
            gap: 12px !important;
            padding: 16px 24px !important;
            border-radius: 50px !important;
            font-size: 16px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
        }
        
        @keyframes floatUp {
            0%, 100% { transform: translateY(0px) translateX(0px); }
            25% { transform: translateY(-20px) translateX(10px); }
            50% { transform: translateY(-40px) translateX(-5px); }
            75% { transform: translateY(-20px) translateX(-10px); }
        }
        
        @keyframes orbFloat {
            0%, 100% { transform: translateY(0px) translateX(0px) scale(1); }
            33% { transform: translateY(-30px) translateX(20px) scale(1.1); }
            66% { transform: translateY(15px) translateX(-15px) scale(0.9); }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .score-grid {
                grid-template-columns: 1fr !important;
                gap: 32px !important;
                text-align: center !important;
            }
            
            .score-display-big {
                font-size: 56px !important;
            }
            
            .stats-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
    
    <div class="quiz-results-premium">
        <!-- Floating Particles -->
        <div class="particles-bg">
            <div class="floating-particle" style="left: 10%; animation-delay: 0s; animation-duration: 15s;"></div>
            <div class="floating-particle" style="left: 20%; animation-delay: 2s; animation-duration: 18s;"></div>
            <div class="floating-particle" style="left: 30%; animation-delay: 4s; animation-duration: 22s;"></div>
            <div class="floating-particle" style="left: 40%; animation-delay: 6s; animation-duration: 16s;"></div>
            <div class="floating-particle" style="left: 60%; animation-delay: 8s; animation-duration: 19s;"></div>
            <div class="floating-particle" style="left: 70%; animation-delay: 10s; animation-duration: 17s;"></div>
            <div class="floating-particle" style="left: 80%; animation-delay: 12s; animation-duration: 21s;"></div>
            <div class="floating-particle" style="left: 90%; animation-delay: 14s; animation-duration: 20s;"></div>
        </div>
        
        <!-- Gradient Orbs -->
        <div class="orbs-bg">
            <div class="gradient-orb orb1"></div>
            <div class="gradient-orb orb2"></div>
            <div class="gradient-orb orb3"></div>
        </div>
        
        <div class="premium-content max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Premium Header -->
            <div class="premium-header">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 24px;">
                    <div>
                        <div style="display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(236, 72, 153, 0.1) 100%); backdrop-filter: blur(10px); border: 1px solid rgba(99, 102, 241, 0.2); color: #4f46e5; padding: 8px 16px; border-radius: 50px; font-size: 14px; font-weight: 600; margin-bottom: 12px;">
                            @if($this->getScorePercentage() >= 90)
                                <i class="fas fa-star"></i>
                                Excellent Performance
                            @elseif($this->getScorePercentage() >= 70)
                                <i class="fas fa-thumbs-up"></i>
                                Great Job
                            @else
                                <i class="fas fa-chart-line"></i>
                                Keep Improving
                            @endif
                        </div>
                        <h1 style="font-size: 36px; font-weight: 900; background: linear-gradient(135deg, #1e293b 0%, #475569 50%, #1e293b 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; line-height: 1.2; margin-bottom: 12px;">{{ $questionnaire->title }}</h1>
                        <div style="display: flex; gap: 24px; align-items: center; font-size: 14px; color: #64748b; font-weight: 500;">
                            <span style="display: flex; align-items: center; gap: 6px;">
                                <i class="fas fa-calendar"></i>
                                {{ $attempt->completed_at->format('F j, Y') }}
                            </span>
                            <span style="display: flex; align-items: center; gap: 6px;">
                                <i class="fas fa-stopwatch"></i>
                                {{ $this->formatDuration($attempt->total_time_seconds) }}
                            </span>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <button wire:click="backToDashboard" style="position: relative; display: inline-flex; align-items: center; justify-content: center; padding: 14px 28px; border-radius: 16px; font-weight: 600; font-size: 14px; border: none; cursor: pointer; background: rgba(255, 255, 255, 0.9); color: #64748b; border: 2px solid rgba(226, 232, 240, 0.8); backdrop-filter: blur(10px); min-width: 140px; gap: 8px; transition: all 0.3s ease;">
                            <i class="fas fa-arrow-left"></i>
                            <span>Back to Dashboard</span>
                        </button>
                        
                        @if($questionnaire->canUserAttempt(Auth::id()) && $questionnaire->isAvailable())
                            <button wire:click="retakeQuiz" style="position: relative; display: inline-flex; align-items: center; justify-content: center; padding: 14px 28px; border-radius: 16px; font-weight: 600; font-size: 14px; border: none; cursor: pointer; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3); min-width: 140px; gap: 8px; transition: all 0.3s ease;">
                                <i class="fas fa-redo"></i>
                                <span>Retake Quiz</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Hero Score Showcase -->
            <div class="hero-score-card">
                <div class="score-grid">
                    <!-- Tier Badge -->
                    <div class="tier-badge {{ $this->getScorePercentage() >= 90 ? 'badge-diamond' : ($this->getScorePercentage() >= 70 ? 'badge-gold' : 'badge-silver') }}">
                        <i class="fas {{ $this->getScorePercentage() >= 90 ? 'fa-gem' : ($this->getScorePercentage() >= 70 ? 'fa-star' : 'fa-award') }}"></i>
                        @if($this->getScorePercentage() >= 90)
                            Diamond Tier
                        @elseif($this->getScorePercentage() >= 70)
                            Gold Tier
                        @else
                            Silver Tier
                        @endif
                    </div>
                    
                    <!-- Main Score Display -->
                    <div style="text-align: center;">
                        <div style="display: flex; align-items: baseline; justify-content: center; gap: 4px; margin-bottom: 12px;">
                            <span class="score-display-big">{{ round($this->getScorePercentage()) }}</span>
                            <span style="font-size: 48px; font-weight: 700; color: #64748b; font-family: 'SF Mono', 'Monaco', monospace;">%</span>
                        </div>
                        <div style="font-size: 18px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Final Score</div>
                        <div style="font-size: 16px; color: #475569; font-weight: 500; margin-bottom: 8px;">
                            {{ number_format($this->getFinalScore(), 1) }} total points
                        </div>
                        <div style="font-size: 14px; color: #64748b; font-weight: 500;">
                            {{ number_format($this->getBasePoints()) }} base + {{ number_format($this->getBonusPoints()) }} bonus
                        </div>
                    </div>
                    
                    <!-- Circular Progress -->
                    <div style="position: relative; display: flex; align-items: center; justify-content: center;">
                        <svg width="160" height="160" viewBox="0 0 160 160" style="filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.1));">
                            <circle cx="80" cy="80" r="70" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="3"/>
                            <circle cx="80" cy="80" r="70" fill="none" 
                                    stroke="{{ $this->getScorePercentage() >= 90 ? '#8b5cf6' : ($this->getScorePercentage() >= 70 ? '#f59e0b' : '#64748b') }}" 
                                    stroke-width="6" 
                                    stroke-linecap="round"
                                    stroke-dasharray="{{ 2 * 3.14159 * 70 }}" 
                                    stroke-dashoffset="{{ 2 * 3.14159 * 70 * (1 - $this->getScorePercentage() / 100) }}"
                                    transform="rotate(-90 80 80)"
                                    style="transition: stroke-dashoffset 2s ease;"/>
                        </svg>
                        
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                            <div style="font-size: 24px; font-weight: 900; color: #1e293b; font-family: 'SF Mono', 'Monaco', monospace; margin-bottom: 4px;">{{ round($this->getScorePercentage()) }}%</div>
                            <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; {{ $this->isPassed() ? 'color: #059669;' : 'color: #dc2626;' }}">
                                @if($this->isPassed())
                                    <i class="fas fa-check"></i> PASSED
                                @else
                                    <i class="fas fa-times"></i> REVIEW
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Premium Stats Grid -->
            <div class="stats-grid">
                <!-- Status Card -->
                <div class="premium-card">
                    <div style="position: relative; margin-bottom: 24px; display: flex; align-items: center; justify-content: center;">
                        <div style="width: 64px; height: 64px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);">
                            <i class="fas {{ $this->isPassed() ? 'fa-shield-check' : 'fa-shield-exclamation' }}"></i>
                        </div>
                    </div>
                    <div style="text-align: center;">
                        <h3 style="font-size: 14px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">Quiz Status</h3>
                        <div style="font-size: 32px; font-weight: 900; color: #1e293b; line-height: 1.2; margin-bottom: 8px; font-family: 'SF Mono', 'Monaco', monospace;">{{ $this->isPassed() ? 'PASSED' : 'REVIEW' }}</div>
                        @if($questionnaire->pass_percentage)
                            <div style="font-size: 12px; color: #64748b; margin-bottom: 16px;">Required: {{ $questionnaire->pass_percentage }}%</div>
                        @endif
                    </div>
                </div>

                <!-- Questions Card -->
                <div class="premium-card">
                    <div style="position: relative; margin-bottom: 24px; display: flex; align-items: center; justify-content: center;">
                        <div style="width: 64px; height: 64px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; background: linear-gradient(135deg, #3b82f6, #1d4ed8); box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);">
                            <i class="fas fa-list-check"></i>
                        </div>
                    </div>
                    <div style="text-align: center;">
                        <h3 style="font-size: 14px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">Questions</h3>
                        <div style="font-size: 32px; font-weight: 900; color: #1e293b; line-height: 1.2; margin-bottom: 8px; font-family: 'SF Mono', 'Monaco', monospace;">{{ $this->getTotalQuestionsCount() }}</div>
                        <div style="display: flex; flex-direction: column; gap: 8px; font-size: 12px;">
                            @if($this->getRegularQuestionsCount() > 0)
                                <span style="display: flex; align-items: center; gap: 6px; font-weight: 500; color: #3b82f6; justify-content: center;">
                                    <i class="fas fa-edit"></i>
                                    {{ $this->getRegularQuestionsCount() }} Regular
                                </span>
                            @endif
                            @if($this->getFunGameQuestionsCount() > 0)
                                <span style="display: flex; align-items: center; gap: 6px; font-weight: 500; color: #10b981; justify-content: center;">
                                    <i class="fas fa-gamepad"></i>
                                    {{ $this->getFunGameQuestionsCount() }} Games
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Time Card -->
                <div class="premium-card">
                    <div style="position: relative; margin-bottom: 24px; display: flex; align-items: center; justify-content: center;">
                        <div style="width: 64px; height: 64px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 8px 24px rgba(245, 158, 11, 0.3);">
                            <i class="fas fa-hourglass-end"></i>
                        </div>
                    </div>
                    <div style="text-align: center;">
                        <h3 style="font-size: 14px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">Duration</h3>
                        <div style="font-size: 32px; font-weight: 900; color: #1e293b; line-height: 1.2; margin-bottom: 8px; font-family: 'SF Mono', 'Monaco', monospace;">{{ $this->formatDuration($attempt->total_time_seconds) }}</div>
                        @if($questionnaire->time_limit)
                            @php
                                $usedPercentage = min(100, ($attempt->total_time_seconds / ($questionnaire->time_limit * 60)) * 100);
                            @endphp
                            <div style="font-size: 12px; color: #64748b; margin-bottom: 16px;">Limit: {{ $this->formatDuration($questionnaire->time_limit * 60) }}</div>
                            <div style="width: 100%; height: 4px; background: rgba(226, 232, 240, 0.5); border-radius: 2px; overflow: hidden; margin-bottom: 8px;">
                                <div style="height: 100%; background: linear-gradient(90deg, #f59e0b, #d97706); border-radius: 2px; width: {{ $usedPercentage }}%; transition: width 1s ease;"></div>
                            </div>
                            <div style="font-size: 11px; color: #64748b; font-weight: 500;">{{ round($usedPercentage) }}% of time used</div>
                        @endif
                    </div>
                </div>

                <!-- Assessments Card (if any) -->
                @if($assessments->count() > 0)
                    <div class="premium-card">
                        <div style="position: relative; margin-bottom: 24px; display: flex; align-items: center; justify-content: center;">
                            @php
                                $status = $this->getAssessmentStatus();
                                $statusIcon = [
                                    'complete' => 'fa-check-double',
                                    'partial' => 'fa-clock-rotate-left',
                                    'pending' => 'fa-hourglass-half'
                                ][$status] ?? 'fa-question';
                            @endphp
                            <div style="width: 64px; height: 64px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; background: linear-gradient(135deg, #8b5cf6, #7c3aed); box-shadow: 0 8px 24px rgba(139, 92, 246, 0.3);">
                                <i class="fas {{ $statusIcon }}"></i>
                            </div>
                        </div>
                        <div style="text-align: center;">
                            <h3 style="font-size: 14px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">Assessments</h3>
                            <div style="font-size: 32px; font-weight: 900; color: #1e293b; line-height: 1.2; margin-bottom: 8px; font-family: 'SF Mono', 'Monaco', monospace;">
                                @if($status === 'complete')
                                    COMPLETE
                                @elseif($status === 'partial')
                                    PARTIAL
                                @elseif($status === 'pending')
                                    PENDING
                                @else
                                    NONE
                                @endif
                            </div>
                            @php
                                $assessedCount = $assessments->where('is_assessed', true)->count();
                                $totalCount = $assessments->count();
                                $assessmentPercentage = $totalCount > 0 ? ($assessedCount / $totalCount) * 100 : 0;
                            @endphp
                            <div style="width: 100%; height: 4px; background: rgba(226, 232, 240, 0.5); border-radius: 2px; overflow: hidden; margin-bottom: 8px;">
                                <div style="height: 100%; background: linear-gradient(90deg, #8b5cf6, #7c3aed); border-radius: 2px; width: {{ $assessmentPercentage }}%; transition: width 1s ease;"></div>
                            </div>
                            <div style="font-size: 11px; color: #64748b; font-weight: 500;">{{ $assessedCount }}/{{ $totalCount }} assessed</div>
                        </div>
                    </div>
                @endif

                <!-- Performance Grade Card -->
                <div class="premium-card">
                    <div style="position: relative; margin-bottom: 24px; display: flex; align-items: center; justify-content: center;">
                        <div style="width: 64px; height: 64px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; background: linear-gradient(135deg, #ec4899, #db2777); box-shadow: 0 8px 24px rgba(236, 72, 153, 0.3);">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div style="text-align: center;">
                        <h3 style="font-size: 14px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">Performance</h3>
                        <div style="font-size: 40px; font-weight: 900; line-height: 1; margin-bottom: 8px; font-family: 'SF Mono', 'Monaco', monospace; {{ $this->getScorePercentage() >= 90 ? 'color: #059669; text-shadow: 0 0 20px rgba(5, 150, 105, 0.3);' : ($this->getScorePercentage() >= 80 ? 'color: #0284c7; text-shadow: 0 0 20px rgba(2, 132, 199, 0.3);' : ($this->getScorePercentage() >= 70 ? 'color: #d97706; text-shadow: 0 0 20px rgba(217, 119, 6, 0.3);' : 'color: #dc2626; text-shadow: 0 0 20px rgba(220, 38, 38, 0.3);')) }}">
                            @if($this->getScorePercentage() >= 90)
                                A+
                            @elseif($this->getScorePercentage() >= 80)
                                B+
                            @elseif($this->getScorePercentage() >= 70)
                                C+
                            @else
                                D
                            @endif
                        </div>
                        <div style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8;">
                            @if($this->getScorePercentage() >= 90)
                                Outstanding
                            @elseif($this->getScorePercentage() >= 80)
                                Excellent
                            @elseif($this->getScorePercentage() >= 70)
                                Good
                            @else
                                Needs Work
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Content Section (if needed) -->
            @if($showDetails)
                <div class="premium-card" style="margin-top: 40px;">
                    <h2 style="font-size: 24px; font-weight: 700; color: #1e293b; margin-bottom: 24px; text-align: center;">
                        <i class="fas fa-chart-bar"></i>
                        Detailed Results
                    </h2>
                    <button wire:click="toggleDetails" style="background: #3b82f6; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; margin-bottom: 20px;">
                        Hide Details
                    </button>
                    
                    @if(config('app.debug'))
                        <button onclick="showDebugInfo()" style="background: #f59e0b; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 500; margin-left: 12px; font-size: 12px;">
                            🐛 Debug User Answers
                        </button>
                        
                        <script>
                        function showDebugInfo() {
                            @this.call('debugUserAnswers').then(result => {
                                console.log('Debug User Answers:', result);
                                alert('Debug info logged to console. Check browser developer tools.');
                            });
                        }
                        </script>
                    @endif
                    
                    <!-- Quiz Session Information -->
                    @php 
                        $sessionInfo = $this->getQuizSessionInfo();
                        $completionStats = $this->getCompletionStats();
                    @endphp
                    @if($sessionInfo['was_continued'])
                        <div style="margin-bottom: 32px;">
                            <h3 style="font-size: 20px; font-weight: 600; color: #1e293b; margin-bottom: 16px;">
                                <i class="fas fa-clock-rotate-left"></i> Quiz Session Details
                            </h3>
                            <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 12px; padding: 20px; border-left: 4px solid #f59e0b;">
                                <div style="display: flex; align-items: center; margin-bottom: 12px;">
                                    <i class="fas fa-info-circle" style="color: #d97706; margin-right: 8px;"></i>
                                    <h4 style="font-weight: 600; color: #92400e;">This quiz was continued from a previous session</h4>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-top: 16px;">
                                    <div>
                                        <div style="font-size: 12px; color: #78716c; margin-bottom: 4px;">Started</div>
                                        <div style="font-weight: 600; color: #1c1917;">{{ $sessionInfo['started_at']->format('M j, Y g:i A') }}</div>
                                    </div>
                                    <div>
                                        <div style="font-size: 12px; color: #78716c; margin-bottom: 4px;">Completed</div>
                                        <div style="font-weight: 600; color: #1c1917;">{{ $sessionInfo['completed_at']->format('M j, Y g:i A') }}</div>
                                    </div>
                                    <div>
                                        <div style="font-size: 12px; color: #78716c; margin-bottom: 4px;">Total Duration</div>
                                        <div style="font-weight: 600; color: #1c1917;">{{ $this->formatDuration($sessionInfo['total_duration']) }}</div>
                                    </div>
                                    <div>
                                        <div style="font-size: 12px; color: #78716c; margin-bottom: 4px;">Estimated Sessions</div>
                                        <div style="font-weight: 600; color: #1c1917;">{{ $sessionInfo['estimated_sessions'] ?? 1 }}</div>
                                    </div>
                                </div>

                                @if($sessionInfo['has_time_limit'])
                                    <div style="margin-top: 12px; padding: 8px; background: rgba(217, 119, 6, 0.1); border-radius: 6px; font-size: 12px; color: #92400e;">
                                        <i class="fas fa-stopwatch" style="margin-right: 4px;"></i>
                                        Time limit: {{ $sessionInfo['time_limit_minutes'] }} minutes
                                        (Quiz was paused and resumed within time limit)
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Completion Statistics -->
                    <div style="margin-bottom: 32px;">
                        <h3 style="font-size: 20px; font-weight: 600; color: #1e293b; margin-bottom: 16px;">
                            <i class="fas fa-chart-pie"></i> Completion Statistics
                        </h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                            <!-- Questions Completion -->
                            <div style="background: rgba(99, 102, 241, 0.1); border-radius: 12px; padding: 16px; border-left: 4px solid #6366f1;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <span style="font-size: 14px; color: #6366f1; font-weight: 600;">Questions Answered</span>
                                    <span style="font-size: 18px; font-weight: 700; color: #1e293b;">{{ $completionStats['completion_rate'] }}%</span>
                                </div>
                                <div style="background: rgba(99, 102, 241, 0.2); border-radius: 4px; height: 6px; margin-bottom: 8px;">
                                    <div style="background: #6366f1; height: 100%; border-radius: 4px; width: {{ $completionStats['completion_rate'] }}%;"></div>
                                </div>
                                <div style="font-size: 12px; color: #64748b;">{{ $completionStats['answered_questions'] }} / {{ $completionStats['total_questions'] }} questions</div>
                            </div>

                            <!-- Accuracy Rate -->
                            <div style="background: rgba(34, 197, 94, 0.1); border-radius: 12px; padding: 16px; border-left: 4px solid #22c55e;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <span style="font-size: 14px; color: #22c55e; font-weight: 600;">Accuracy Rate</span>
                                    <span style="font-size: 18px; font-weight: 700; color: #1e293b;">{{ $completionStats['accuracy_rate'] }}%</span>
                                </div>
                                <div style="background: rgba(34, 197, 94, 0.2); border-radius: 4px; height: 6px; margin-bottom: 8px;">
                                    <div style="background: #22c55e; height: 100%; border-radius: 4px; width: {{ $completionStats['accuracy_rate'] }}%;"></div>
                                </div>
                                <div style="font-size: 12px; color: #64748b;">{{ $completionStats['correct_answers'] }} correct answers</div>
                            </div>

                            @if($completionStats['total_games'] > 0)
                                <!-- Game Assessment Rate -->
                                <div style="background: rgba(168, 85, 247, 0.1); border-radius: 12px; padding: 16px; border-left: 4px solid #a855f7;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <span style="font-size: 14px; color: #a855f7; font-weight: 600;">Games Assessed</span>
                                        <span style="font-size: 18px; font-weight: 700; color: #1e293b;">{{ $completionStats['game_assessment_rate'] }}%</span>
                                    </div>
                                    <div style="background: rgba(168, 85, 247, 0.2); border-radius: 4px; height: 6px; margin-bottom: 8px;">
                                        <div style="background: #a855f7; height: 100%; border-radius: 4px; width: {{ $completionStats['game_assessment_rate'] }}%;"></div>
                                    </div>
                                    <div style="font-size: 12px; color: #64748b;">{{ $completionStats['assessed_games'] }} / {{ $completionStats['total_games'] }} games</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Bonus Points System Breakdown -->
                    @php 
                        $scoreBreakdown = $this->getScoreBreakdown(); 
                        $bonusBreakdown = $this->getBonusPointsBreakdown();
                    @endphp
                    <div style="margin-bottom: 32px;">
                        <h3 style="font-size: 20px; font-weight: 600; color: #1e293b; margin-bottom: 16px;">
                            <i class="fas fa-chart-bar"></i> Points System Breakdown
                        </h3>
                        
                        <!-- Main Points Formula -->
                        <div style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border-radius: 16px; padding: 24px; margin-bottom: 24px; border: 2px solid #cbd5e1;">
                            <div style="text-align: center; margin-bottom: 16px;">
                                <h4 style="font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 8px;">Total Points Formula</h4>
                                <div style="font-size: 24px; font-weight: 800; color: #1e293b; font-family: 'SF Mono', 'Monaco', monospace;">
                                    <span style="color: #059669;">{{ number_format($scoreBreakdown['base_points']) }}</span>
                                    <span style="color: #64748b; margin: 0 8px;">+</span>
                                    <span style="color: #dc2626;">{{ number_format($scoreBreakdown['bonus_points']) }}</span>
                                    <span style="color: #64748b; margin: 0 8px;">=</span>
                                    <span style="color: #1e293b;">{{ number_format($scoreBreakdown['total_points']) }}</span>
                                </div>
                                <div style="font-size: 14px; color: #64748b; margin-top: 8px;">
                                    <span style="color: #059669;">Base Points</span> + 
                                    <span style="color: #dc2626;">Bonus Points</span> = 
                                    <span style="color: #1e293b;">Total Points</span>
                                </div>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                            <!-- Base Points -->
                            <div style="background: rgba(5, 150, 105, 0.1); border-radius: 12px; padding: 20px; border-left: 4px solid #059669;">
                                <h4 style="font-weight: 600; color: #059669; margin-bottom: 8px;">
                                    <i class="fas fa-coins" style="margin-right: 8px;"></i>
                                    Base Points
                                </h4>
                                <div style="font-size: 32px; font-weight: 700; color: #1e293b; margin-bottom: 8px;">
                                    {{ number_format($scoreBreakdown['base_points']) }}
                                </div>
                                <div style="color: #64748b; font-size: 14px; margin-bottom: 8px;">
                                    Starting points from your team
                                </div>
                                <div style="font-size: 12px; color: #64748b;">
                                    <i class="fas fa-info-circle" style="margin-right: 4px;"></i>
                                    Every team member starts with these points
                                </div>
                            </div>
                            
                            <!-- Bonus Points -->
                            <div style="background: rgba(220, 38, 38, 0.1); border-radius: 12px; padding: 20px; border-left: 4px solid #dc2626;">
                                <h4 style="font-weight: 600; color: #dc2626; margin-bottom: 8px;">
                                    <i class="fas fa-star" style="margin-right: 8px;"></i>
                                    Bonus Points Earned
                                </h4>
                                <div style="font-size: 32px; font-weight: 700; color: #1e293b; margin-bottom: 8px;">
                                    +{{ number_format($scoreBreakdown['bonus_points']) }}
                                </div>
                                <div style="color: #64748b; font-size: 14px; margin-bottom: 8px;">
                                    Accumulated from correct answers & assessments
                                </div>
                                <div style="font-size: 12px; color: #64748b;">
                                    <i class="fas fa-plus-circle" style="margin-right: 4px;"></i>
                                    {{ $bonusBreakdown['regular_total'] }} from questions + {{ $bonusBreakdown['assessment_total'] }} from games
                                </div>
                            </div>

                            <!-- Total Points -->
                            <div style="background: rgba(30, 41, 59, 0.1); border-radius: 12px; padding: 20px; border-left: 4px solid #1e293b;">
                                <h4 style="font-weight: 600; color: #1e293b; margin-bottom: 8px;">
                                    <i class="fas fa-trophy" style="margin-right: 8px;"></i>
                                    Final Total Points
                                </h4>
                                <div style="font-size: 32px; font-weight: 700; color: #1e293b; margin-bottom: 8px;">
                                    {{ number_format($scoreBreakdown['total_points']) }}
                                </div>
                                <div style="color: #64748b; font-size: 14px; margin-bottom: 8px;">
                                    Your accumulated team points
                                </div>
                                <div style="font-size: 12px; color: #64748b;">
                                    <i class="fas fa-calculator" style="margin-right: 4px;"></i>
                                    {{ number_format($scoreBreakdown['base_points']) }} base + {{ number_format($scoreBreakdown['bonus_points']) }} bonus
                                </div>
                            </div>
                        </div>

                        <!-- Detailed Bonus Breakdown -->
                        @if($bonusBreakdown['grand_total'] > 0)
                            <div style="margin-top: 24px;">
                                <h4 style="font-size: 16px; font-weight: 600; color: #1e293b; margin-bottom: 16px;">
                                    <i class="fas fa-list-ul"></i> Detailed Bonus Points Breakdown
                                </h4>
                                
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px;">
                                    <!-- Correct Answers Bonus -->
                                    @if(count($bonusBreakdown['correct_answers']) > 0)
                                        <div style="background: rgba(59, 130, 246, 0.05); border-radius: 12px; padding: 16px; border: 1px solid rgba(59, 130, 246, 0.2);">
                                            <h5 style="font-weight: 600; color: #3b82f6; margin-bottom: 12px;">
                                                <i class="fas fa-check-circle" style="margin-right: 6px;"></i>
                                                Correct Answers (+{{ $bonusBreakdown['regular_total'] }})
                                            </h5>
                                            <div style="max-height: 120px; overflow-y: auto;">
                                                @foreach($bonusBreakdown['correct_answers'] as $answer)
                                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid rgba(59, 130, 246, 0.1);">
                                                        <span style="font-size: 12px; color: #64748b; flex: 1;">{{ Str::limit($answer['question'], 40) }}</span>
                                                        <span style="font-size: 12px; font-weight: 600; color: #3b82f6;">+{{ $answer['points'] }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Assessment Bonus -->
                                    @if(count($bonusBreakdown['assessments']) > 0)
                                        <div style="background: rgba(16, 185, 129, 0.05); border-radius: 12px; padding: 16px; border: 1px solid rgba(16, 185, 129, 0.2);">
                                            <h5 style="font-weight: 600; color: #10b981; margin-bottom: 12px;">
                                                <i class="fas fa-gamepad" style="margin-right: 6px;"></i>
                                                Game Assessment Bonus (+{{ $bonusBreakdown['assessment_total'] }})
                                            </h5>
                                            <div style="max-height: 120px; overflow-y: auto;">
                                                @foreach($bonusBreakdown['assessments'] as $assessment)
                                                    <div style="padding: 8px 0; border-bottom: 1px solid rgba(16, 185, 129, 0.1);">
                                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                                            <span style="font-size: 12px; color: #64748b; font-weight: 500;">{{ $assessment['game_name'] }}</span>
                                                            <span style="font-size: 12px; font-weight: 600; color: #10b981;">+{{ $assessment['bonus_earned'] }}</span>
                                                        </div>
                                                        <div style="font-size: 10px; color: #9ca3af;">
                                                            Total: {{ $assessment['total_earned'] }} - Base: {{ $assessment['base_used'] }} = Bonus: {{ $assessment['bonus_earned'] }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Regular Questions Results -->
                    @php $regularQuestions = $questions->where('type', '!=', 'fun_game'); @endphp
                    @if($regularQuestions->count() > 0)
                        <div style="margin-bottom: 32px;">
                            <h3 style="font-size: 20px; font-weight: 600; color: #1e293b; margin-bottom: 16px;">
                                <i class="fas fa-question-circle"></i> Question Results
                            </h3>
                            <div style="space-y: 16px;">
                                @foreach($regularQuestions as $index => $question)
                                    @php 
                                        $userAnswer = $userAnswers->get($question->id);
                                        $isCorrect = $userAnswer && $userAnswer->is_correct;
                                        
                                        // Debug: Log the user answer details
                                        if (config('app.debug')) {
                                            \Log::debug("Question {$question->id} - UserAnswer exists: " . ($userAnswer ? 'YES' : 'NO'));
                                            if ($userAnswer) {
                                                \Log::debug("Question {$question->id} - Answer value: " . ($userAnswer->answer ?? 'NULL'));
                                                \Log::debug("Question {$question->id} - Is correct: " . ($userAnswer->is_correct ? 'TRUE' : 'FALSE'));
                                            }
                                        }
                                    @endphp
                                    <div style="background: white; border-radius: 12px; padding: 20px; border: 1px solid #e5e7eb; margin-bottom: 16px;">
                                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                                            <div style="flex: 1;">
                                                <h4 style="font-weight: 600; color: #1e293b; margin-bottom: 8px;">
                                                    Question {{ $index + 1 }} 
                                                    <span style="display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 500; margin-left: 8px; background: #f1f5f9; color: #475569;">
                                                        {{ ucfirst(str_replace('_', ' ', $question->type)) }}
                                                    </span>
                                                    <span style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-left: 8px; {{ $isCorrect ? 'background: #dcfce7; color: #166534;' : 'background: #fee2e2; color: #991b1b;' }}">
                                                        {{ $isCorrect ? 'Correct' : 'Incorrect' }}
                                                    </span>
                                                </h4>
                                                <p style="color: #64748b; margin-bottom: 12px;">{{ $question->question }}</p>
                                            </div>
                                            <div style="text-align: right; margin-left: 16px;">
                                                <div style="font-weight: 600; color: {{ $isCorrect ? '#166534' : '#991b1b' }};">
                                                    {{ $isCorrect ? $question->points : 0 }} / {{ $question->points }}
                                                </div>
                                                <div style="font-size: 12px; color: #64748b;">points</div>
                                            </div>
                                        </div>
                                        
                                        <!-- Answer Details Section -->
                                        <div style="margin-top: 12px;">
                                            <div style="font-size: 14px; color: #64748b; margin-bottom: 8px;">Your answer:</div>
                                            
                                            @if($question->type === 'multiple_choice')
                                                <div style="color: #1e293b; font-weight: 500;">
                                                    @if($userAnswer && !empty($userAnswer->answer))
                                                        {{ $userAnswer->answer }}
                                                    @else
                                                        <span style="color: #ef4444; font-style: italic;">
                                                            No answer provided
                                                            @if(config('app.debug'))
                                                                (Debug: UserAnswer={{ $userAnswer ? 'exists' : 'null' }}, 
                                                                Answer={{ $userAnswer->answer ?? 'null' }})
                                                            @endif
                                                        </span>
                                                    @endif
                                                </div>
                                                @if($question->options)
                                                    <div style="margin-top: 8px;">
                                                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Available options:</div>
                                                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                                            @foreach($question->options as $option)
                                                                <span style="padding: 2px 8px; border-radius: 8px; font-size: 12px; 
                                                                    {{ $option === $question->correct_answer ? 'background: #dcfce7; color: #166534;' : 
                                                                       ($userAnswer && $option === $userAnswer->answer && !$isCorrect ? 'background: #fee2e2; color: #991b1b;' : 'background: #f1f5f9; color: #64748b;') }}">
                                                                    {{ $option }}
                                                                    @if($option === $question->correct_answer)
                                                                        <i class="fas fa-check" style="margin-left: 4px;"></i>
                                                                    @elseif($userAnswer && $option === $userAnswer->answer && !$isCorrect)
                                                                        <i class="fas fa-times" style="margin-left: 4px;"></i>
                                                                    @endif
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                                
                                            @elseif($question->type === 'true_false')
                                                <div style="color: #1e293b; font-weight: 500;">
                                                    {{ $userAnswer ? ($userAnswer->answer === 'true' || $userAnswer->answer === '1' ? 'True' : 'False') : 'No answer provided' }}
                                                </div>
                                                @if(!$isCorrect && $question->correct_answer)
                                                    <div style="margin-top: 8px; font-size: 14px;">
                                                        <span style="color: #64748b;">Correct answer:</span>
                                                        <span style="color: #166534; font-weight: 500;">
                                                            {{ $question->correct_answer === 'true' || $question->correct_answer === '1' ? 'True' : 'False' }}
                                                        </span>
                                                    </div>
                                                @endif
                                                
                                            @elseif($question->type === 'text')
                                                <div style="color: #1e293b; font-weight: 500; font-style: italic; padding: 8px; background: #f8fafc; border-radius: 6px; border-left: 3px solid {{ $isCorrect ? '#22c55e' : '#ef4444' }};">
                                                    "{{ $userAnswer ? $userAnswer->answer : 'No answer provided' }}"
                                                </div>
                                                @if(!$isCorrect && $question->correct_answer)
                                                    <div style="margin-top: 8px; font-size: 14px;">
                                                        <span style="color: #64748b;">Acceptable answer(s):</span>
                                                        <div style="color: #166534; font-weight: 500; font-style: italic; margin-top: 4px;">
                                                            @php
                                                                $acceptableAnswers = array_map('trim', explode(',', $question->correct_answer));
                                                            @endphp
                                                            @foreach($acceptableAnswers as $answer)
                                                                <span style="background: #dcfce7; color: #166534; padding: 2px 6px; border-radius: 4px; margin-right: 4px; font-size: 12px;">
                                                                    "{{ $answer }}"
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                                
                                            @else
                                                <div style="color: #1e293b; font-weight: 500;">
                                                    {{ $userAnswer ? $userAnswer->answer : 'No answer provided' }}
                                                </div>
                                            @endif
                                            
                                            <!-- Point breakdown -->
                                            <div style="margin-top: 12px; padding: 8px; background: {{ $isCorrect ? '#f0fdf4' : '#fef2f2' }}; border-radius: 6px; font-size: 12px;">
                                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                                    <span style="color: #64748b;">
                                                        <i class="fas fa-calculator" style="margin-right: 4px;"></i>
                                                        Point calculation:
                                                    </span>
                                                    <span style="font-weight: 600; color: {{ $isCorrect ? '#166534' : '#991b1b' }};">
                                                        {{ $isCorrect ? $question->points : 0 }} points earned
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Fun Game Assessment Results -->
                    @php 
                        $funGameQuestions = $questions->where('type', 'fun_game');
                        $assessmentDetails = $this->getAssessmentDetails();
                    @endphp
                    @if($funGameQuestions->count() > 0)
                        <div style="margin-bottom: 32px;">
                            <h3 style="font-size: 20px; font-weight: 600; color: #1e293b; margin-bottom: 16px;">
                                <i class="fas fa-gamepad"></i> Fun Game Assessment Results
                            </h3>
                            <div style="space-y: 16px;">
                                @foreach($assessmentDetails as $index => $assessment)
                                    <div style="background: white; border-radius: 12px; padding: 20px; border: 1px solid #e5e7eb; margin-bottom: 16px;">
                                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                                            <div style="flex: 1;">
                                                <h4 style="font-weight: 600; color: #1e293b; margin-bottom: 8px;">
                                                    {{ $assessment['question_name'] }}
                                                    <span style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-left: 8px; {{ $assessment['is_assessed'] ? 'background: #dcfce7; color: #166534;' : 'background: #fef3c7; color: #92400e;' }}">
                                                        {{ $assessment['is_assessed'] ? 'Assessed' : 'Pending Assessment' }}
                                                    </span>
                                                </h4>
                                                <p style="color: #64748b; margin-bottom: 12px;">{{ $assessment['question_text'] }}</p>
                                            </div>
                                            <div style="text-align: right; margin-left: 16px;">
                                                <div style="font-weight: 600; color: {{ $assessment['total_score'] >= 0 ? '#166534' : '#991b1b' }};">
                                                    {{ $assessment['total_score'] }}
                                                </div>
                                                <div style="font-size: 12px; color: #64748b;">total points</div>
                                            </div>
                                        </div>
                                        
                                        @if($assessment['is_assessed'])
                                            <div style="margin-top: 12px; background: #f8fafc; padding: 16px; border-radius: 8px;">
                                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 12px; margin-bottom: 12px;">
                                                    <div>
                                                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Base Points</div>
                                                        <div style="font-weight: 600; color: #059669;">+{{ $assessment['base_points'] }}</div>
                                                    </div>
                                                    <div>
                                                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Bonus Points</div>
                                                        <div style="font-weight: 600; color: #059669;">+{{ $assessment['additional_points'] }}</div>
                                                    </div>
                                                    <div>
                                                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Penalty</div>
                                                        <div style="font-weight: 600; color: #dc2626;">-{{ $assessment['penalty_points'] }}</div>
                                                    </div>
                                                </div>
                                                @if($assessment['notes'])
                                                    <div style="margin-top: 8px;">
                                                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Notes:</div>
                                                        <div style="color: #374151; font-style: italic;">{{ $assessment['notes'] }}</div>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <div style="margin-top: 12px; background: #fef3c7; padding: 12px; border-radius: 8px; color: #92400e;">
                                                <i class="fas fa-clock"></i> Assessment pending - points will be calculated after evaluation
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Answer Timeline for Continued Quizzes -->
                    @if($sessionInfo['was_continued'])
                        @php $timeline = $this->getAnswerTimeline(); @endphp
                        @if(!empty($timeline))
                            <div style="margin-bottom: 32px;">
                                <h3 style="font-size: 20px; font-weight: 600; color: #1e293b; margin-bottom: 16px;">
                                    <i class="fas fa-timeline"></i> Answer Timeline
                                </h3>
                                <div style="background: white; border-radius: 12px; padding: 20px; border: 1px solid #e5e7eb;">
                                    <div style="font-size: 14px; color: #64748b; margin-bottom: 16px;">
                                        Timeline showing when each answer was submitted across multiple sessions:
                                    </div>
                                    
                                    <div style="position: relative;">
                                        <!-- Timeline line -->
                                        <div style="position: absolute; left: 20px; top: 0; bottom: 0; width: 2px; background: #e5e7eb;"></div>
                                        
                                        @foreach($timeline as $index => $entry)
                                            <div style="position: relative; margin-bottom: 16px; padding-left: 50px;">
                                                <!-- Timeline dot -->
                                                <div style="position: absolute; left: 12px; top: 8px; width: 16px; height: 16px; border-radius: 50%; 
                                                     background: {{ $entry['is_correct'] ? '#22c55e' : '#ef4444' }}; border: 3px solid white; box-shadow: 0 0 0 2px {{ $entry['is_correct'] ? '#22c55e' : '#ef4444' }};"></div>
                                                
                                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                                    <div style="flex: 1;">
                                                        <div style="font-weight: 600; color: #1e293b; margin-bottom: 4px;">
                                                            Question {{ $entry['question_number'] }}
                                                            <span style="display: inline-block; padding: 2px 6px; border-radius: 8px; font-size: 10px; font-weight: 500; margin-left: 8px; background: #f1f5f9; color: #475569;">
                                                                {{ ucfirst(str_replace('_', ' ', $entry['question_type'])) }}
                                                            </span>
                                                        </div>
                                                        <div style="font-size: 12px; color: #64748b;">
                                                            {{ $entry['timestamp']->format('M j, Y g:i:s A') }}
                                                        </div>
                                                    </div>
                                                    <div style="text-align: right;">
                                                        <div style="font-weight: 600; color: {{ $entry['is_correct'] ? '#166534' : '#991b1b' }};">
                                                            {{ $entry['points_earned'] }} pts
                                                        </div>
                                                        <div style="font-size: 10px; padding: 2px 6px; border-radius: 8px; 
                                                             {{ $entry['is_correct'] ? 'background: #dcfce7; color: #166534;' : 'background: #fee2e2; color: #991b1b;' }}">
                                                            {{ $entry['is_correct'] ? 'Correct' : 'Incorrect' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    
                                    <div style="margin-top: 16px; padding: 12px; background: #f8fafc; border-radius: 8px; font-size: 12px; color: #64748b;">
                                        <i class="fas fa-info-circle" style="margin-right: 4px;"></i>
                                        This timeline shows the chronological order of answer submissions. Gaps of more than 5 minutes indicate session breaks.
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            @else
                <div style="text-align: center; margin-top: 40px;">
                    <button wire:click="toggleDetails" style="background: rgba(255, 255, 255, 0.9); color: #64748b; border: 2px solid rgba(226, 232, 240, 0.8); padding: 14px 28px; border-radius: 16px; cursor: pointer; font-weight: 600; backdrop-filter: blur(10px); transition: all 0.3s ease;">
                        <i class="fas fa-chart-bar"></i>
                        Show Detailed Results
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>