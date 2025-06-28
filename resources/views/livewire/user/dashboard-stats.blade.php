<div class="premium-stats-wrapper">
    <div class="premium-stats-grid">
        {{-- Total Attempts --}}
        <div class="premium-stat-card attempts">
            <div class="stat-card-glow"></div>
            <div class="stat-card-content">
                <div class="stat-header">
                    <div class="stat-icon-container">
                        <div class="stat-icon-bg attempts-bg"></div>
                        <i class="fas fa-clipboard-check stat-icon"></i>
                    </div>
                    <div class="stat-pulse-ring"></div>
                </div>
                <div class="stat-body">
                    <div class="stat-value-container">
                        <span class="stat-value">{{ $totalAttempts }}</span>
                        <div class="stat-value-effect"></div>
                    </div>
                    <div class="stat-label">Total Attempts</div>
                    <div class="stat-description">Quiz attempts made</div>
                </div>
            </div>
        </div>

        {{-- Completed Attempts --}}
        <div class="premium-stat-card completed">
            <div class="stat-card-glow"></div>
            <div class="stat-card-content">
                <div class="stat-header">
                    <div class="stat-icon-container">
                        <div class="stat-icon-bg completed-bg"></div>
                        <i class="fas fa-check-circle stat-icon"></i>
                    </div>
                    <div class="stat-pulse-ring"></div>
                </div>
                <div class="stat-body">
                    <div class="stat-value-container">
                        <span class="stat-value">{{ $completedAttempts }}</span>
                        <div class="stat-value-effect"></div>
                    </div>
                    <div class="stat-label">Completed</div>
                    <div class="stat-description">Successfully finished</div>
                </div>
            </div>
        </div>

        {{-- Average Score --}}
        <div class="premium-stat-card average">
            <div class="stat-card-glow"></div>
            <div class="stat-card-content">
                <div class="stat-header">
                    <div class="stat-icon-container">
                        <div class="stat-icon-bg average-bg"></div>
                        <i class="fas fa-star stat-icon"></i>
                    </div>
                    <div class="stat-pulse-ring"></div>
                </div>
                <div class="stat-body">
                    <div class="stat-value-container">
                        <span class="stat-value {{ $this->getScoreColor($averageScore) }}">{{ $averageScore }}</span>
                        <div class="stat-value-effect"></div>
                    </div>
                    <div class="stat-label">Average Score</div>
                    <div class="stat-description">Performance rating</div>
                </div>
            </div>
        </div>

        {{-- Completion Rate --}}
        <div class="premium-stat-card completion">
            <div class="stat-card-glow"></div>
            <div class="stat-card-content">
                <div class="stat-header">
                    <div class="stat-icon-container">
                        <div class="stat-icon-bg completion-bg"></div>
                        <div class="completion-circle">
                            <svg class="completion-ring" width="60" height="60" viewBox="0 0 60 60">
                                <circle cx="30" cy="30" r="25" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="3"/>
                                <circle cx="30" cy="30" r="25" fill="none" stroke="url(#completion-gradient)" stroke-width="4" 
                                        stroke-linecap="round" stroke-dasharray="{{ 2 * 3.14159 * 25 }}" 
                                        stroke-dashoffset="{{ 2 * 3.14159 * 25 * (1 - $completionRate / 100) }}"
                                        transform="rotate(-90 30 30)" class="completion-progress"/>
                            </svg>
                            <div class="completion-percentage">{{ $completionRate }}%</div>
                        </div>
                    </div>
                    <div class="stat-pulse-ring"></div>
                </div>
                <div class="stat-body">
                    <div class="stat-label">Completion Rate</div>
                    <div class="stat-description">Success percentage</div>
                </div>
            </div>
        </div>

        {{-- Team Points --}}
        @if($teamName)
            <div class="premium-stat-card team">
                <div class="stat-card-glow"></div>
                <div class="stat-card-content">
                    <div class="stat-header">
                        <div class="stat-icon-container">
                            <div class="stat-icon-bg team-bg"></div>
                            <i class="fas fa-coins stat-icon"></i>
                        </div>
                        <div class="stat-pulse-ring"></div>
                    </div>
                    <div class="stat-body">
                        <div class="stat-value-container">
                            <span class="stat-value {{ $teamPoints >= 1000 ? 'text-green-400' : ($teamPoints >= 500 ? 'text-yellow-400' : 'text-red-400') }}">{{ number_format($teamPoints, 0) }}</span>
                            <div class="stat-value-effect"></div>
                        </div>
                        <div class="stat-label">Team Points</div>
                        <div class="stat-description">{{ $teamName }}</div>
                    </div>
                </div>
            </div>
        @else
            <div class="premium-stat-card no-team">
                <div class="stat-card-glow"></div>
                <div class="stat-card-content">
                    <div class="stat-header">
                        <div class="stat-icon-container">
                            <div class="stat-icon-bg no-team-bg"></div>
                            <i class="fas fa-users stat-icon"></i>
                        </div>
                        <div class="stat-pulse-ring"></div>
                    </div>
                    <div class="stat-body">
                        <div class="stat-value-container">
                            <span class="stat-value text-gray-400">No Team</span>
                            <div class="stat-value-effect"></div>
                        </div>
                        <div class="stat-label">Team Status</div>
                        <div class="stat-description">Join a team to earn points</div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <style>
    /* Premium Stats Styles */
    .premium-stats-wrapper {
        margin-bottom: 32px;
    }
    
    .premium-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
    }
    
    .premium-stat-card {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(20px);
        border-radius: 20px;
        padding: 32px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        position: relative;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
    }
    
    .premium-stat-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }
    
    .premium-stat-card:hover .stat-card-glow {
        opacity: 1;
    }
    
    .premium-stat-card:hover .stat-pulse-ring {
        animation: pulseFast 1.5s infinite;
    }
    
    .stat-card-glow {
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        opacity: 0;
        transition: opacity 0.4s ease;
        pointer-events: none;
    }
    
    .premium-stat-card.attempts .stat-card-glow {
        background: conic-gradient(from 0deg, rgba(59, 130, 246, 0.15), rgba(79, 172, 254, 0.15), rgba(59, 130, 246, 0.15));
        animation: rotateGlow 20s linear infinite;
    }
    
    .premium-stat-card.completed .stat-card-glow {
        background: conic-gradient(from 0deg, rgba(16, 185, 129, 0.15), rgba(5, 150, 105, 0.15), rgba(16, 185, 129, 0.15));
        animation: rotateGlow 25s linear infinite;
    }
    
    .premium-stat-card.average .stat-card-glow {
        background: conic-gradient(from 0deg, rgba(245, 158, 11, 0.15), rgba(217, 119, 6, 0.15), rgba(245, 158, 11, 0.15));
        animation: rotateGlow 22s linear infinite;
    }
    
    .premium-stat-card.completion .stat-card-glow {
        background: conic-gradient(from 0deg, rgba(139, 92, 246, 0.15), rgba(124, 58, 237, 0.15), rgba(139, 92, 246, 0.15));
        animation: rotateGlow 18s linear infinite;
    }
    
    .premium-stat-card.team .stat-card-glow {
        background: conic-gradient(from 0deg, rgba(236, 72, 153, 0.15), rgba(219, 39, 119, 0.15), rgba(236, 72, 153, 0.15));
        animation: rotateGlow 24s linear infinite;
    }
    
    .premium-stat-card.no-team .stat-card-glow {
        background: conic-gradient(from 0deg, rgba(107, 114, 128, 0.1), rgba(75, 85, 99, 0.1), rgba(107, 114, 128, 0.1));
        animation: rotateGlow 30s linear infinite;
    }
    
    .stat-card-content {
        position: relative;
        z-index: 2;
    }
    
    .stat-header {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 24px;
        position: relative;
    }
    
    .stat-icon-container {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .stat-icon-bg {
        width: 80px;
        height: 80px;
        border-radius: 20px;
        position: absolute;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    }
    
    .attempts-bg {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    }
    
    .completed-bg {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }
    
    .average-bg {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }
    
    .completion-bg {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    }
    
    .team-bg {
        background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
    }
    
    .no-team-bg {
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
    }
    
    .stat-icon {
        font-size: 32px;
        color: white;
        position: relative;
        z-index: 2;
    }
    
    .stat-pulse-ring {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        transform: translate(-50%, -50%);
        background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
        animation: pulse 3s infinite;
    }
    
    .stat-body {
        text-align: center;
    }
    
    .stat-value-container {
        position: relative;
        margin-bottom: 12px;
    }
    
    .stat-value {
        font-size: 40px;
        font-weight: 900;
        color: #1f2937;
        line-height: 1;
        font-family: 'SF Mono', 'Monaco', monospace;
        position: relative;
        z-index: 2;
    }
    
    .stat-value-effect {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        animation: shimmerEffect 3s infinite;
        pointer-events: none;
    }
    
    .stat-label {
        font-size: 16px;
        font-weight: 700;
        color: #374151;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }
    
    .stat-description {
        font-size: 12px;
        font-weight: 500;
        color: #6b7280;
        opacity: 0.8;
    }
    
    /* Completion Circle Styles */
    .completion-circle {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .completion-ring {
        filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
    }
    
    .completion-progress {
        transition: stroke-dashoffset 2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .completion-percentage {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 14px;
        font-weight: 700;
        color: white;
        font-family: 'SF Mono', 'Monaco', monospace;
    }
    
    /* Score Color Classes */
    .text-green-400 { color: #4ade80; }
    .text-blue-400 { color: #60a5fa; }
    .text-yellow-400 { color: #facc15; }
    .text-orange-400 { color: #fb923c; }
    .text-red-400 { color: #f87171; }
    .text-gray-400 { color: #9ca3af; }
    
    /* Animations */
    @keyframes rotateGlow {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    @keyframes pulse {
        0%, 100% { 
            transform: translate(-50%, -50%) scale(1); 
            opacity: 0.3; 
        }
        50% { 
            transform: translate(-50%, -50%) scale(1.2); 
            opacity: 0.1; 
        }
    }
    
    @keyframes pulseFast {
        0%, 100% { 
            transform: translate(-50%, -50%) scale(1); 
            opacity: 0.5; 
        }
        50% { 
            transform: translate(-50%, -50%) scale(1.3); 
            opacity: 0.2; 
        }
    }
    
    @keyframes shimmerEffect {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .premium-stats-grid {
            grid-template-columns: 1fr;
        }
        
        .premium-stat-card {
            padding: 24px;
        }
        
        .stat-value {
            font-size: 32px;
        }
        
        .stat-icon-bg {
            width: 60px;
            height: 60px;
        }
        
        .stat-icon {
            font-size: 24px;
        }
        
        .stat-pulse-ring {
            width: 80px;
            height: 80px;
        }
    }
</style>

<!-- SVG Gradient Definitions -->
<svg style="position: absolute; width: 0; height: 0;">
    <defs>
        <linearGradient id="completion-gradient" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" style="stop-color:#8b5cf6;stop-opacity:1" />
            <stop offset="50%" style="stop-color:#a855f7;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#7c3aed;stop-opacity:1" />
        </linearGradient>
    </defs>
</svg>