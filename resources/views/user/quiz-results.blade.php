@extends('layouts.appUser')

@section('title', 'Quiz Results')

@section('content')
<div>
    <style>
        /* Premium responsive quiz results with dark mode support */
        .quiz-results-premium {
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 25%, #24243e 50%, #302b63 75%, #0f0c29 100%) !important;
            min-height: 100vh !important;
            position: relative !important;
            overflow-x: hidden !important;
            transition: all 0.3s ease !important;
        }
        
        /* Dark mode background */
        @media (prefers-color-scheme: dark) {
            .quiz-results-premium {
                background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 25%, #16213e 50%, #1a1a2e 75%, #0a0a0a 100%) !important;
            }
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
        
        @media (prefers-color-scheme: dark) {
            .floating-particle {
                background: rgba(255, 255, 255, 0.2) !important;
            }
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
        
        /* Responsive orbs */
        @media (max-width: 768px) {
            .orb1, .orb2, .orb3 {
                width: 200px !important;
                height: 200px !important;
                filter: blur(30px) !important;
            }
        }
        
        .premium-header {
            background: rgba(255, 255, 255, 0.95) !important;
            border-radius: 24px !important;
            padding: 32px !important;
            margin-bottom: 32px !important;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            backdrop-filter: blur(20px) !important;
            position: relative !important;
            overflow: hidden !important;
            z-index: 10 !important;
            transition: all 0.3s ease !important;
        }
        
        /* Dark mode header */
        @media (prefers-color-scheme: dark) {
            .premium-header {
                background: rgba(17, 24, 39, 0.95) !important;
                border: 1px solid rgba(55, 65, 81, 0.3) !important;
                box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3) !important;
            }
        }
        
        /* Responsive header */
        @media (max-width: 768px) {
            .premium-header {
                padding: 20px !important;
                margin-bottom: 20px !important;
                border-radius: 16px !important;
            }
        }
        
        .premium-content {
            position: relative !important;
            z-index: 2 !important;
            padding: 0 16px !important;
        }
        
        @media (min-width: 768px) {
            .premium-content {
                padding: 0 24px !important;
            }
        }
        
        @media (min-width: 1024px) {
            .premium-content {
                padding: 0 32px !important;
            }
        }
        
        .score-display-big {
            font-size: clamp(64px, 12vw, 96px) !important;
            font-weight: 900 !important;
            background: linear-gradient(135deg, #1e293b 0%, #475569 50%, #1e293b 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            font-family: 'SF Mono', 'Monaco', monospace !important;
            line-height: 1 !important;
        }
        
        @media (prefers-color-scheme: dark) {
            .score-display-big {
                background: linear-gradient(135deg, #f1f5f9 0%, #cbd5e1 50%, #f1f5f9 100%) !important;
                -webkit-background-clip: text !important;
                -webkit-text-fill-color: transparent !important;
                background-clip: text !important;
            }
        }
        
        .hero-score-card {
            background: rgba(255, 255, 255, 0.95) !important;
            border-radius: 24px !important;
            padding: 32px !important;
            box-shadow: 0 32px 64px rgba(0, 0, 0, 0.12) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            backdrop-filter: blur(20px) !important;
            position: relative !important;
            overflow: hidden !important;
            margin-bottom: 32px !important;
            transition: all 0.3s ease !important;
        }
        
        /* Dark mode score card */
        @media (prefers-color-scheme: dark) {
            .hero-score-card {
                background: rgba(17, 24, 39, 0.95) !important;
                border: 1px solid rgba(55, 65, 81, 0.3) !important;
                box-shadow: 0 32px 64px rgba(0, 0, 0, 0.25) !important;
            }
        }
        
        /* Responsive score card */
        @media (max-width: 768px) {
            .hero-score-card {
                padding: 20px !important;
                border-radius: 16px !important;
                margin-bottom: 20px !important;
            }
        }
        
        .score-grid {
            position: relative !important;
            z-index: 2 !important;
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 24px !important;
            align-items: center !important;
            text-align: center !important;
        }
        
        @media (min-width: 768px) {
            .score-grid {
                grid-template-columns: auto 1fr auto !important;
                gap: 32px !important;
                text-align: left !important;
            }
        }
        
        @media (min-width: 1024px) {
            .score-grid {
                gap: 48px !important;
            }
        }
        
        .tier-badge {
            padding: 12px 20px !important;
            border-radius: 16px !important;
            font-weight: 700 !important;
            font-size: 12px !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 8px !important;
            white-space: nowrap !important;
            margin: 0 auto !important;
        }
        
        @media (min-width: 768px) {
            .tier-badge {
                font-size: 14px !important;
                padding: 12px 24px !important;
                margin: 0 !important;
            }
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
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%) !important;
            color: white !important;
            box-shadow: 0 8px 32px rgba(107, 114, 128, 0.4) !important;
        }
        
        .header-content {
            display: flex !important;
            flex-direction: column !important;
            gap: 20px !important;
        }
        
        @media (min-width: 768px) {
            .header-content {
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: center !important;
                gap: 24px !important;
            }
        }
        
        .header-info h1 {
            font-size: clamp(24px, 5vw, 36px) !important;
            font-weight: 900 !important;
            background: linear-gradient(135deg, #1e293b 0%, #475569 50%, #1e293b 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            line-height: 1.2 !important;
            margin-bottom: 12px !important;
        }
        
        @media (prefers-color-scheme: dark) {
            .header-info h1 {
                background: linear-gradient(135deg, #f1f5f9 0%, #cbd5e1 50%, #f1f5f9 100%) !important;
                -webkit-background-clip: text !important;
                -webkit-text-fill-color: transparent !important;
                background-clip: text !important;
            }
        }
        
        .performance-badge {
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(236, 72, 153, 0.1) 100%) !important;
            backdrop-filter: blur(10px) !important;
            border: 1px solid rgba(99, 102, 241, 0.2) !important;
            color: #4f46e5 !important;
            padding: 6px 12px !important;
            border-radius: 50px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            margin-bottom: 12px !important;
        }
        
        @media (min-width: 768px) {
            .performance-badge {
                font-size: 14px !important;
                padding: 8px 16px !important;
            }
        }
        
        @media (prefers-color-scheme: dark) {
            .performance-badge {
                background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(236, 72, 153, 0.15) 100%) !important;
                border: 1px solid rgba(99, 102, 241, 0.3) !important;
                color: #818cf8 !important;
            }
        }
        
        .meta-info {
            display: flex !important;
            flex-direction: column !important;
            gap: 12px !important;
            font-size: 12px !important;
            color: #64748b !important;
            font-weight: 500 !important;
        }
        
        @media (min-width: 768px) {
            .meta-info {
                flex-direction: row !important;
                gap: 24px !important;
                font-size: 14px !important;
            }
        }
        
        @media (prefers-color-scheme: dark) {
            .meta-info {
                color: #94a3b8 !important;
            }
        }
        
        .meta-item {
            display: flex !important;
            align-items: center !important;
            gap: 6px !important;
        }
        
        .action-buttons {
            display: flex !important;
            flex-direction: column !important;
            gap: 12px !important;
            width: 100% !important;
        }
        
        @media (min-width: 768px) {
            .action-buttons {
                flex-direction: row !important;
                width: auto !important;
            }
        }
        
        .btn-back {
            position: relative !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 12px 20px !important;
            border-radius: 12px !important;
            font-weight: 600 !important;
            font-size: 13px !important;
            border: none !important;
            cursor: pointer !important;
            background: rgba(255, 255, 255, 0.9) !important;
            color: #64748b !important;
            border: 2px solid rgba(226, 232, 240, 0.8) !important;
            backdrop-filter: blur(10px) !important;
            min-width: 120px !important;
            gap: 8px !important;
            transition: all 0.3s ease !important;
            text-decoration: none !important;
        }
        
        @media (min-width: 768px) {
            .btn-back {
                padding: 14px 28px !important;
                border-radius: 16px !important;
                font-size: 14px !important;
                min-width: 140px !important;
            }
        }
        
        .btn-back:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1) !important;
            background: rgba(255, 255, 255, 1) !important;
        }
        
        @media (prefers-color-scheme: dark) {
            .btn-back {
                background: rgba(17, 24, 39, 0.9) !important;
                color: #94a3b8 !important;
                border: 2px solid rgba(55, 65, 81, 0.8) !important;
            }
            
            .btn-back:hover {
                background: rgba(17, 24, 39, 1) !important;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3) !important;
            }
        }
        
        .score-center {
            text-align: center !important;
        }
        
        .score-percentage {
            font-size: clamp(28px, 4vw, 36px) !important;
            font-weight: 600 !important;
            color: #64748b !important;
            font-family: 'SF Mono', 'Monaco', monospace !important;
        }
        
        @media (prefers-color-scheme: dark) {
            .score-percentage {
                color: #94a3b8 !important;
            }
        }
        
        .score-label {
            font-size: 14px !important;
            font-weight: 600 !important;
            color: #64748b !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            margin-bottom: 8px !important;
        }
        
        @media (min-width: 768px) {
            .score-label {
                font-size: 18px !important;
            }
        }
        
        @media (prefers-color-scheme: dark) {
            .score-label {
                color: #94a3b8 !important;
            }
        }
        
        .score-total {
            font-size: 14px !important;
            color: #475569 !important;
            font-weight: 500 !important;
            margin-bottom: 8px !important;
        }
        
        @media (min-width: 768px) {
            .score-total {
                font-size: 16px !important;
            }
        }
        
        @media (prefers-color-scheme: dark) {
            .score-total {
                color: #cbd5e1 !important;
            }
        }
        
        .score-breakdown {
            font-size: 12px !important;
            color: #64748b !important;
            font-weight: 500 !important;
        }
        
        @media (min-width: 768px) {
            .score-breakdown {
                font-size: 14px !important;
            }
        }
        
        @media (prefers-color-scheme: dark) {
            .score-breakdown {
                color: #94a3b8 !important;
            }
        }
        
        .progress-circle {
            position: relative !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin: 0 auto !important;
        }
        
        .progress-svg {
            width: 120px !important;
            height: 120px !important;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.1)) !important;
        }
        
        @media (min-width: 768px) {
            .progress-svg {
                width: 160px !important;
                height: 160px !important;
                filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.1)) !important;
            }
        }
        
        .progress-text {
            position: absolute !important;
            text-align: center !important;
        }
        
        .progress-percentage {
            font-size: 18px !important;
            font-weight: 900 !important;
            color: #1e293b !important;
            font-family: 'SF Mono', 'Monaco', monospace !important;
        }
        
        @media (min-width: 768px) {
            .progress-percentage {
                font-size: 24px !important;
            }
        }
        
        @media (prefers-color-scheme: dark) {
            .progress-percentage {
                color: #f1f5f9 !important;
            }
        }
        
        .progress-label {
            font-size: 10px !important;
            color: #64748b !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
        }
        
        @media (min-width: 768px) {
            .progress-label {
                font-size: 12px !important;
            }
        }
        
        @media (prefers-color-scheme: dark) {
            .progress-label {
                color: #94a3b8 !important;
            }
        }

        /* Animations */
        @keyframes floatUp {
            0% { transform: translateY(100vh) translateX(0px) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100px) translateX(100px) rotate(360deg); opacity: 0; }
        }
        
        @keyframes orbFloat {
            0%, 100% { transform: translateY(0px) translateX(0px) scale(1); }
            25% { transform: translateY(-40px) translateX(30px) scale(1.1); }
            50% { transform: translateY(20px) translateX(-20px) scale(0.9); }
            75% { transform: translateY(-20px) translateX(-30px) scale(1.05); }
        }
        
        /* Assessment Results Dark Mode */
        @media (prefers-color-scheme: dark) {
            .assessment-title {
                background: linear-gradient(135deg, #f1f5f9 0%, #cbd5e1 50%, #f1f5f9 100%) !important;
                -webkit-background-clip: text !important;
                -webkit-text-fill-color: transparent !important;
                background-clip: text !important;
            }
            
            .assessment-card {
                background: rgba(17, 24, 39, 0.9) !important;
                border: 1px solid rgba(55, 65, 81, 0.8) !important;
            }
            
            .assessment-title-text {
                color: #f1f5f9 !important;
            }
            
            .assessment-subtitle-text {
                color: #94a3b8 !important;
            }
            
            .assessment-stat-blue {
                background: rgba(30, 64, 175, 0.2) !important;
                color: #60a5fa !important;
            }
            
            .assessment-stat-green {
                background: rgba(6, 95, 70, 0.2) !important;
                color: #34d399 !important;
            }
            
            .assessment-notes {
                background: rgba(17, 24, 39, 0.8) !important;
                color: #d1d5db !important;
            }
            
            .assessment-notes-label {
                color: #9ca3af !important;
            }
            
            .assessment-breakdown-label {
                color: #94a3b8 !important;
            }
            
            .assessment-breakdown-value {
                color: #f1f5f9 !important;
            }
            
            .assessment-breakdown-positive {
                color: #10b981 !important;
            }
            
            .assessment-breakdown-negative {
                color: #ef4444 !important;
            }
            
            .pending-assessment {
                background: rgba(180, 83, 9, 0.2) !important;
                border: 1px solid rgba(217, 119, 6, 0.3) !important;
            }
            
            .pending-title {
                color: #fbbf24 !important;
            }
            
            .pending-subtitle, .pending-text {
                color: #fcd34d !important;
            }
            
            .assessment-summary {
                background: rgba(17, 24, 39, 0.8) !important;
                border: 1px solid rgba(55, 65, 81, 0.6) !important;
            }
            
            .assessment-summary-title {
                color: #d1d5db !important;
            }
            
            .assessment-summary-value {
                color: #f1f5f9 !important;
            }
            
            .assessment-summary-label {
                color: #94a3b8 !important;
            }
        }

        /* Reduce motion for accessibility */
        @media (prefers-reduced-motion: reduce) {
            .floating-particle,
            .gradient-orb {
                animation: none !important;
            }
            
            .progress-svg circle {
                transition: none !important;
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
        
        <div class="premium-content max-w-7xl mx-auto py-8">
            @php
                $user = auth()->user();
                $basePoints = $user->team ? ($user->team->initial_points ?? 1000) : 1000;
                
                // Calculate bonus points from correct answers
                $userAnswers = \App\Models\UserAnswer::where('quiz_attempt_id', $attempt->id)
                    ->with(['question'])
                    ->where('is_correct', true)
                    ->get();
                    
                $bonusPoints = $userAnswers->sum(function($answer) {
                    return $answer->question->points ?? 0;
                });
                
                // Add assessment bonus
                $assessments = \App\Models\GameAssessment::where('quiz_attempt_id', $attempt->id)
                    ->where('user_id', $attempt->user_id)
                    ->where('is_assessed', true)
                    ->get();
                    
                foreach ($assessments as $assessment) {
                    $assessmentBonus = ($assessment->total_deposit ?? 0) - $basePoints;
                    $bonusPoints += max(0, $assessmentBonus);
                }
                
                $finalScore = $basePoints + $bonusPoints;
                
                // Calculate percentage (simplified)
                $totalPossible = $basePoints + ($attempt->questionnaire->questions->sum('points') ?? 0);
                $percentage = $totalPossible > 0 ? round(($finalScore / $totalPossible) * 100, 1) : 0;
                
                // Calculate duration
                $seconds = $attempt->total_time_seconds ?? 0;
                if ($seconds < 60) {
                    $duration = $seconds . ' seconds';
                } elseif ($seconds < 3600) {
                    $minutes = floor($seconds / 60);
                    $remainingSeconds = $seconds % 60;
                    $duration = $minutes . 'm ' . $remainingSeconds . 's';
                } else {
                    $hours = floor($seconds / 3600);
                    $minutes = floor(($seconds % 3600) / 60);
                    $remainingSeconds = $seconds % 60;
                    $duration = $hours . 'h ' . $minutes . 'm ' . $remainingSeconds . 's';
                }
            @endphp
            
            <!-- Premium Header -->
            <div class="premium-header">
                <div class="header-content">
                    <div class="header-info">
                        <div class="performance-badge">
                            @if($percentage >= 90)
                                <i class="fas fa-star"></i>
                                Excellent Performance
                            @elseif($percentage >= 70)
                                <i class="fas fa-thumbs-up"></i>
                                Great Job
                            @else
                                <i class="fas fa-chart-line"></i>
                                Keep Improving
                            @endif
                        </div>
                        <h1>{{ $attempt->questionnaire->title }}</h1>
                        <div class="meta-info">
                            <div class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <span>{{ $attempt->completed_at->format('F j, Y') }}</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-stopwatch"></i>
                                <span>{{ $duration }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="{{ route('user.dashboard') }}" class="btn-back">
                            <i class="fas fa-arrow-left"></i>
                            <span>Back to Dashboard</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Hero Score Showcase -->
            <div class="hero-score-card">
                <div class="score-grid">
                    <!-- Tier Badge -->
                    <div class="tier-badge {{ $percentage >= 90 ? 'badge-diamond' : ($percentage >= 70 ? 'badge-gold' : 'badge-silver') }}">
                        <i class="fas {{ $percentage >= 90 ? 'fa-gem' : ($percentage >= 70 ? 'fa-star' : 'fa-award') }}"></i>
                        <span>
                            @if($percentage >= 90)
                                Diamond Tier
                            @elseif($percentage >= 70)
                                Gold Tier
                            @else
                                Silver Tier
                            @endif
                        </span>
                    </div>
                    
                    <!-- Main Score Display -->
                    <div class="score-center">
                        <div style="display: flex; align-items: baseline; justify-content: center; gap: 8px; margin-bottom: 16px;">
                            <span class="score-display-big">{{ number_format($finalScore) }}</span>
                            <span style="font-size: clamp(14px, 2vw, 18px); color: #64748b; font-weight: 500;">pts</span>
                        </div>
                        <div class="score-label">Total Points</div>
                        <div class="score-total" style="display: flex; align-items: baseline; justify-content: center; gap: 4px; margin-top: 8px;">
                            <span class="score-percentage">{{ round($percentage) }}</span>
                            <span style="font-size: clamp(18px, 3vw, 24px); color: #64748b; font-weight: 500;">%</span>
                        </div>
                        <div class="score-breakdown">
                            {{ number_format($basePoints) }} base + {{ number_format($bonusPoints) }} bonus
                        </div>
                    </div>
                    
                    <!-- Circular Progress -->
                    <div class="progress-circle">
                        <svg class="progress-svg" viewBox="0 0 160 160">
                            <circle cx="80" cy="80" r="70" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="3"/>
                            <circle cx="80" cy="80" r="70" fill="none" stroke="url(#scoreGradient)" stroke-width="6" 
                                    stroke-linecap="round" stroke-dasharray="{{ 2 * 3.14159 * 70 }}" 
                                    stroke-dashoffset="{{ 2 * 3.14159 * 70 * (1 - $percentage / 100) }}"
                                    transform="rotate(-90 80 80)" style="transition: stroke-dashoffset 2s ease;"/>
                        </svg>
                        <div class="progress-text">
                            <div class="progress-percentage">{{ round($percentage) }}%</div>
                            <div class="progress-label">Complete</div>
                        </div>
                    </div>
                </div>
            </div>

            @php
                // Get all questions and answers for detailed breakdown
                $allQuestions = $attempt->questionnaire->questions ?? collect();
                $automaticQuestions = $allQuestions->whereIn('type', ['text', 'multiple_choice', 'true_false']);
                $funGameQuestions = $allQuestions->where('type', 'fun_game');
                
                // Get manual assessments (fun_game only)
                $manualAssessments = \App\Models\GameAssessment::where('quiz_attempt_id', $attempt->id)
                    ->where('user_id', $attempt->user_id)
                    ->get();
                    
                $hasManualAssessments = $manualAssessments->count() > 0;
                $completedManualAssessments = $manualAssessments->where('is_assessed', true);
            @endphp

            <!-- Question Results Breakdown -->
            <div class="hero-score-card">
                <div style="text-align: center; margin-bottom: 24px;">
                    <h2 class="assessment-title" style="font-size: clamp(20px, 4vw, 28px); font-weight: 800; background: linear-gradient(135deg, #1e293b 0%, #475569 50%, #1e293b 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-bottom: 8px;">
                        Question Results Breakdown
                    </h2>
                    <p class="assessment-subtitle-text" style="color: #64748b; font-size: 14px; font-weight: 500;">
                        Detailed performance analysis by question type
                    </p>
                </div>

                <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
                    
                    <!-- Automatic Assessment Questions -->
                    @if($automaticQuestions->count() > 0)
                    <div class="assessment-card" style="background: rgba(255, 255, 255, 0.9); border-radius: 16px; padding: 20px; border: 1px solid rgba(226, 232, 240, 0.8); backdrop-filter: blur(10px);">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                            <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-check-circle" style="color: white; font-size: 18px;"></i>
                            </div>
                            <div>
                                <h3 class="assessment-title-text" style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0;">Automatic Assessment</h3>
                                <p class="assessment-subtitle-text" style="font-size: 12px; color: #64748b; margin: 0; font-weight: 500;">Text, Multiple Choice & True/False Questions</p>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 16px;">
                            @php
                                $correctAutomatic = 0;
                                $totalAutomaticPoints = 0;
                                foreach($automaticQuestions as $question) {
                                    $userAnswer = $userAnswers->firstWhere('question_id', $question->id);
                                    if($userAnswer && $userAnswer->is_correct) {
                                        $correctAutomatic++;
                                        $totalAutomaticPoints += $question->points ?? 0;
                                    }
                                }
                            @endphp
                            
                            <div class="assessment-stat-green" style="text-align: center; padding: 12px; background: rgba(236, 253, 245, 0.6); border-radius: 12px;">
                                <div style="font-size: 20px; font-weight: 800; color: #065f46; font-family: 'SF Mono', monospace;">
                                    {{ $correctAutomatic }}/{{ $automaticQuestions->count() }}
                                </div>
                                <div class="assessment-breakdown-label" style="font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                    Correct Answers
                                </div>
                            </div>
                            
                            <div class="assessment-stat-blue" style="text-align: center; padding: 12px; background: rgba(239, 246, 255, 0.6); border-radius: 12px;">
                                <div style="font-size: 20px; font-weight: 800; color: #1e40af; font-family: 'SF Mono', monospace;">
                                    {{ number_format($totalAutomaticPoints) }}
                                </div>
                                <div class="assessment-breakdown-label" style="font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                    Points Earned
                                </div>
                            </div>
                        </div>

                        <!-- Question Type Breakdown -->
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; font-size: 12px;">
                            @php
                                $textQuestions = $automaticQuestions->where('type', 'text');
                                $mcQuestions = $automaticQuestions->where('type', 'multiple_choice');
                                $tfQuestions = $automaticQuestions->where('type', 'true_false');
                                
                                $textCorrect = 0;
                                $mcCorrect = 0;
                                $tfCorrect = 0;
                                
                                foreach($textQuestions as $q) {
                                    $ua = $userAnswers->firstWhere('question_id', $q->id);
                                    if($ua && $ua->is_correct) $textCorrect++;
                                }
                                foreach($mcQuestions as $q) {
                                    $ua = $userAnswers->firstWhere('question_id', $q->id);
                                    if($ua && $ua->is_correct) $mcCorrect++;
                                }
                                foreach($tfQuestions as $q) {
                                    $ua = $userAnswers->firstWhere('question_id', $q->id);
                                    if($ua && $ua->is_correct) $tfCorrect++;
                                }
                            @endphp
                            
                            <div style="text-align: center;">
                                <div class="assessment-breakdown-label" style="color: #64748b; font-weight: 500;">Text</div>
                                <div class="assessment-breakdown-value" style="color: #1e293b; font-weight: 700;">{{ $textCorrect }}/{{ $textQuestions->count() }}</div>
                            </div>
                            <div style="text-align: center;">
                                <div class="assessment-breakdown-label" style="color: #64748b; font-weight: 500;">Multiple Choice</div>
                                <div class="assessment-breakdown-value" style="color: #1e293b; font-weight: 700;">{{ $mcCorrect }}/{{ $mcQuestions->count() }}</div>
                            </div>
                            <div style="text-align: center;">
                                <div class="assessment-breakdown-label" style="color: #64748b; font-weight: 500;">True/False</div>
                                <div class="assessment-breakdown-value" style="color: #1e293b; font-weight: 700;">{{ $tfCorrect }}/{{ $tfQuestions->count() }}</div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Manual Assessment Questions (Fun Games) -->
                    @if($funGameQuestions->count() > 0)
                    <div class="assessment-card" style="background: rgba(255, 255, 255, 0.9); border-radius: 16px; padding: 20px; border: 1px solid rgba(226, 232, 240, 0.8); backdrop-filter: blur(10px);">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                            <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-gamepad" style="color: white; font-size: 18px;"></i>
                            </div>
                            <div>
                                <h3 class="assessment-title-text" style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0;">Manual Assessment</h3>
                                <p class="assessment-subtitle-text" style="font-size: 12px; color: #64748b; margin: 0; font-weight: 500;">Fun Game Activities</p>
                            </div>
                        </div>

                        @if($completedManualAssessments->count() > 0)
                            <!-- Completed Assessments -->
                            <div style="margin-bottom: 16px;">
                                <h4 class="assessment-title-text" style="font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 12px;">Completed Games</h4>
                                
                                @foreach($completedManualAssessments as $assessment)
                                    @php
                                        $question = $funGameQuestions->firstWhere('id', $assessment->question_id);
                                        $gameName = $question ? ($question->game_name ?? 'Fun Game') : 'Unknown Game';
                                        $assessmentBonus = ($assessment->total_deposit ?? 0) - $basePoints;
                                    @endphp
                                    
                                    <div class="assessment-card" style="background: rgba(248, 250, 252, 0.8); border-radius: 12px; padding: 16px; margin-bottom: 12px; border: 1px solid rgba(226, 232, 240, 0.6);">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                            <div>
                                                <h5 class="assessment-title-text" style="font-size: 14px; font-weight: 700; color: #1e293b; margin: 0;">{{ $gameName }}</h5>
                                                <p class="assessment-subtitle-text" style="font-size: 11px; color: #64748b; margin: 0;">Game Assessment</p>
                                            </div>
                                            <div style="background: rgba(34, 197, 94, 0.1); color: #059669; padding: 4px 8px; border-radius: 6px; font-size: 10px; font-weight: 600;">
                                                COMPLETED
                                            </div>
                                        </div>
                                        
                                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 12px;">
                                            <div style="text-align: center;">
                                                <div class="assessment-breakdown-value" style="font-size: 14px; font-weight: 700; color: #1e40af;">{{ number_format($assessment->deposit ?? 0) }}</div>
                                                <div class="assessment-breakdown-label" style="font-size: 9px; color: #64748b; font-weight: 500;">Base</div>
                                            </div>
                                            <div style="text-align: center;">
                                                <div class="assessment-breakdown-positive" style="font-size: 14px; font-weight: 700; color: #059669;">+{{ number_format($assessment->additional_points ?? 0) }}</div>
                                                <div class="assessment-breakdown-label" style="font-size: 9px; color: #64748b; font-weight: 500;">Bonus</div>
                                            </div>
                                            <div style="text-align: center;">
                                                <div class="assessment-breakdown-negative" style="font-size: 14px; font-weight: 700; color: #dc2626;">-{{ number_format($assessment->penalty ?? 0) }}</div>
                                                <div class="assessment-breakdown-label" style="font-size: 9px; color: #64748b; font-weight: 500;">Penalty</div>
                                            </div>
                                            <div style="text-align: center;">
                                                <div class="assessment-breakdown-value" style="font-size: 14px; font-weight: 700; color: #7c3aed;">{{ number_format($assessment->total_deposit ?? 0) }}</div>
                                                <div class="assessment-breakdown-label" style="font-size: 9px; color: #64748b; font-weight: 500;">Total</div>
                                            </div>
                                        </div>
                                        
                                        @if($assessment->notes)
                                        <div class="assessment-notes" style="background: rgba(236, 253, 245, 0.6); border-radius: 8px; padding: 10px; border-left: 3px solid #10b981;">
                                            <div class="assessment-notes-label" style="font-size: 10px; color: #059669; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                                                Assessment Notes
                                            </div>
                                            <div class="assessment-title-text" style="font-size: 12px; color: #374151; line-height: 1.4;">
                                                {{ $assessment->notes }}
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if($manualAssessments->where('is_assessed', false)->count() > 0)
                            <!-- Pending Assessments -->
                            <div class="pending-assessment" style="background: rgba(254, 243, 199, 0.8); border-radius: 12px; padding: 16px; border: 1px solid rgba(251, 191, 36, 0.3);">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                    <i class="fas fa-clock" style="color: #d97706; font-size: 16px;"></i>
                                    <h4 class="pending-title" style="font-size: 14px; font-weight: 700; color: #92400e; margin: 0;">Pending Assessment</h4>
                                </div>
                                <p class="pending-text" style="font-size: 12px; color: #a16207; line-height: 1.4; margin: 0;">
                                    {{ $manualAssessments->where('is_assessed', false)->count() }} fun game(s) are awaiting manual evaluation by the instructor. 
                                    Results will be updated once assessment is complete.
                                </p>
                            </div>
                        @endif

                        <!-- Fun Game Summary -->
                        <div class="assessment-summary" style="margin-top: 16px; padding: 16px; background: rgba(248, 250, 252, 0.8); border-radius: 12px; border: 1px solid rgba(226, 232, 240, 0.6);">
                            <h4 class="assessment-summary-title" style="font-size: 12px; font-weight: 700; color: #374151; margin: 0 0 12px 0; text-transform: uppercase; letter-spacing: 0.5px;">
                                Fun Game Summary
                            </h4>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                                <div style="text-align: center;">
                                    <div class="assessment-summary-value" style="font-size: 16px; font-weight: 800; color: #1e293b; font-family: 'SF Mono', monospace;">
                                        {{ $completedManualAssessments->count() }}/{{ $funGameQuestions->count() }}
                                    </div>
                                    <div class="assessment-summary-label" style="font-size: 10px; color: #64748b; font-weight: 600;">Assessed</div>
                                </div>
                                <div style="text-align: center;">
                                    <div class="assessment-summary-value" style="font-size: 16px; font-weight: 800; color: #059669; font-family: 'SF Mono', monospace;">
                                        {{ number_format($completedManualAssessments->sum('total_deposit')) }}
                                    </div>
                                    <div class="assessment-summary-label" style="font-size: 10px; color: #64748b; font-weight: 600;">Points Earned</div>
                                </div>
                                <div style="text-align: center;">
                                    <div class="assessment-summary-value" style="font-size: 16px; font-weight: 800; color: #7c3aed; font-family: 'SF Mono', monospace;">
                                        {{ number_format($completedManualAssessments->sum(function($a) use ($basePoints) { return max(0, ($a->total_deposit ?? 0) - $basePoints); })) }}
                                    </div>
                                    <div class="assessment-summary-label" style="font-size: 10px; color: #64748b; font-weight: 600;">Bonus</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    <!-- SVG Gradient Definitions -->
    <svg style="position: absolute; width: 0; height: 0;">
        <defs>
            <linearGradient id="scoreGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                @if($percentage >= 90)
                    <stop offset="0%" style="stop-color:#8b5cf6;stop-opacity:1" />
                    <stop offset="100%" style="stop-color:#7c3aed;stop-opacity:1" />
                @elseif($percentage >= 70)
                    <stop offset="0%" style="stop-color:#f59e0b;stop-opacity:1" />
                    <stop offset="100%" style="stop-color:#d97706;stop-opacity:1" />
                @else
                    <stop offset="0%" style="stop-color:#6b7280;stop-opacity:1" />
                    <stop offset="100%" style="stop-color:#4b5563;stop-opacity:1" />
                @endif
            </linearGradient>
        </defs>
    </svg>
</div>
@endsection