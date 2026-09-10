@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Preview Header with Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6 p-6">
            <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <i class="fas fa-file-pdf text-red-600"></i>
                        Report Preview
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Review your user progress report before downloading
                    </p>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-3">
                    <form action="{{ route('admin.user-progress.clear-session') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Dashboard
                        </button>
                    </form>
                    
                    <a href="{{ route('admin.user-progress.download') }}" 
                       class="inline-flex items-center px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors duration-200 shadow-sm">
                        <i class="fas fa-download mr-2"></i>
                        Download PDF
                    </a>
                </div>
            </div>
        </div>

        <!-- Report Preview Container -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            
            <!-- Preview Toolbar -->
            <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Document Preview</span>
                        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <i class="fas fa-users"></i>
                            {{ count($users) }} users
                            <span class="mx-2">•</span>
                            <i class="fas fa-calendar"></i>
                            Last {{ $filters['selectedTimeframe'] }} days
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <button onclick="printReport()" 
                                class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition-colors duration-200">
                            <i class="fas fa-print mr-1"></i>
                            Print Preview
                        </button>
                    </div>
                </div>
            </div>

            <!-- Report Content (styled for web preview) -->
            <div class="report-preview-content">
                <style>
                    .report-preview-content {
                        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
                        line-height: 1.5;
                        color: #1F2937;
                        background-color: #FFFFFF;
                        padding: 2rem;
                        max-width: 210mm;
                        margin: 0 auto;
                    }
                    
                    .dark .report-preview-content {
                        background-color: #1F2937;
                        color: #F9FAFB;
                    }
                    
                    /* Enhanced Header */
                    .preview-header {
                        text-align: center;
                        margin-bottom: 2rem;
                        padding: 2rem 0;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        border-radius: 12px;
                        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                    }
                    
                    .preview-header h1 {
                        font-size: 2rem;
                        font-weight: bold;
                        margin-bottom: 0.5rem;
                        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
                    }
                    
                    .preview-header .subtitle {
                        font-size: 1.125rem;
                        margin-bottom: 1rem;
                        opacity: 0.9;
                    }
                    
                    .preview-header .generated-info {
                        font-size: 0.875rem;
                        opacity: 0.8;
                        background-color: rgba(255, 255, 255, 0.1);
                        padding: 0.5rem 1rem;
                        border-radius: 20px;
                        display: inline-block;
                    }
                    
                    /* Enhanced Filters Section */
                    .preview-filters {
                        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                        color: white;
                        padding: 1.5rem;
                        border-radius: 12px;
                        margin-bottom: 2rem;
                        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
                    }
                    
                    .preview-filters h3 {
                        font-size: 1.25rem;
                        font-weight: bold;
                        margin-bottom: 1rem;
                        text-align: center;
                    }
                    
                    .filters-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                        gap: 1rem;
                    }
                    
                    .filter-item {
                        background-color: rgba(255, 255, 255, 0.2);
                        padding: 1rem;
                        border-radius: 8px;
                        backdrop-filter: blur(10px);
                    }
                    
                    .filter-label {
                        font-weight: bold;
                        display: block;
                        margin-bottom: 0.25rem;
                        opacity: 0.9;
                        font-size: 0.875rem;
                    }
                    
                    .filter-value {
                        font-weight: normal;
                        opacity: 1;
                        font-size: 1rem;
                    }
                    
                    /* Enhanced Statistics */
                    .preview-stats {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                        gap: 1.5rem;
                        margin-bottom: 2rem;
                    }
                    
                    .stat-card {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        padding: 1.5rem;
                        border-radius: 12px;
                        text-align: center;
                        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
                        border: 1px solid rgba(255, 255, 255, 0.2);
                    }
                    
                    .stat-number {
                        font-size: 2rem;
                        font-weight: bold;
                        margin-bottom: 0.5rem;
                        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
                    }
                    
                    .stat-label {
                        font-size: 0.875rem;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        opacity: 0.9;
                    }
                    
                    /* Section Headers */
                    .section-header {
                        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                        color: white;
                        padding: 1rem 1.5rem;
                        margin: 2rem 0 1rem 0;
                        border-radius: 8px;
                        font-size: 1.25rem;
                        font-weight: bold;
                        text-align: center;
                        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
                    }
                    
                    /* Enhanced Table */
                    .table-container {
                        background-color: #FFFFFF;
                        border-radius: 12px;
                        overflow: hidden;
                        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                        border: 1px solid #E5E7EB;
                        margin-bottom: 2rem;
                    }
                    
                    .dark .table-container {
                        background-color: #374151;
                        border-color: #4B5563;
                    }
                    
                    .data-table {
                        width: 100%;
                        border-collapse: collapse;
                    }
                    
                    .data-table th {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        font-weight: bold;
                        padding: 1rem 0.75rem;
                        text-align: center;
                        border-right: 1px solid rgba(255, 255, 255, 0.2);
                        font-size: 0.875rem;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                    }
                    
                    .data-table th:last-child {
                        border-right: none;
                    }
                    
                    .data-table td {
                        padding: 1rem 0.75rem;
                        border-bottom: 1px solid #E5E7EB;
                        border-right: 1px solid #F3F4F6;
                        text-align: center;
                        vertical-align: middle;
                    }
                    
                    .dark .data-table td {
                        border-bottom-color: #4B5563;
                        border-right-color: #374151;
                    }
                    
                    .data-table td:last-child {
                        border-right: none;
                    }
                    
                    .data-table tbody tr:nth-child(even) {
                        background-color: #F8FAFC;
                    }
                    
                    .dark .data-table tbody tr:nth-child(even) {
                        background-color: #2D3748;
                    }
                    
                    .data-table tbody tr:nth-child(odd) {
                        background-color: #FFFFFF;
                    }
                    
                    .dark .data-table tbody tr:nth-child(odd) {
                        background-color: #374151;
                    }
                    
                    .data-table tbody tr:hover {
                        background-color: #EEF2FF !important;
                        transform: scale(1.01);
                        transition: all 0.2s ease;
                    }
                    
                    .dark .data-table tbody tr:hover {
                        background-color: #4C51BF !important;
                    }
                    
                    /* Enhanced Elements */
                    .user-name {
                        font-weight: bold;
                        color: #1F2937;
                        font-size: 1rem;
                    }
                    
                    .dark .user-name {
                        color: #F9FAFB;
                    }
                    
                    .user-email {
                        font-size: 0.75rem;
                        color: #6B7280;
                        margin-top: 0.25rem;
                    }
                    
                    .dark .user-email {
                        color: #9CA3AF;
                    }
                    
                    .points-breakdown {
                        font-size: 0.75rem;
                        color: #6B7280;
                        margin-top: 0.25rem;
                        line-height: 1.2;
                    }
                    
                    .dark .points-breakdown {
                        color: #9CA3AF;
                    }
                    
                    .completion-rate {
                        font-weight: bold;
                        padding: 0.375rem 0.75rem;
                        border-radius: 12px;
                        font-size: 0.875rem;
                        display: inline-block;
                    }
                    
                    .completion-rate.high { 
                        background-color: #D1FAE5; 
                        color: #059669; 
                    }
                    .completion-rate.medium { 
                        background-color: #FEF3C7; 
                        color: #D97706; 
                    }
                    .completion-rate.low { 
                        background-color: #FEE2E2; 
                        color: #DC2626; 
                    }
                    
                    .team-badge {
                        background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
                        color: #1F2937;
                        padding: 0.375rem 0.75rem;
                        border-radius: 12px;
                        font-size: 0.75rem;
                        font-weight: bold;
                        border: 1px solid #E5E7EB;
                        display: inline-block;
                    }
                    
                    .total-points {
                        font-weight: bold;
                        font-size: 1.125rem;
                        color: #1F2937;
                    }
                    
                    .dark .total-points {
                        color: #F9FAFB;
                    }
                    
                    .activity-date {
                        font-size: 0.875rem;
                        color: #6B7280;
                    }
                    
                    .dark .activity-date {
                        color: #9CA3AF;
                    }
                    
                    /* Performance Summary */
                    .performance-summary {
                        background: #F8FAFC; 
                        padding: 1.5rem; 
                        border-radius: 12px; 
                        margin-bottom: 2rem; 
                        border-left: 4px solid #667eea;
                        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
                    }
                    
                    .dark .performance-summary {
                        background: #2D3748;
                        border-left-color: #667eea;
                    }
                    
                    .performance-grid {
                        display: grid; 
                        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
                        gap: 1.5rem; 
                        font-size: 0.875rem;
                    }
                    
                    /* Enhanced Footer */
                    .preview-footer {
                        margin-top: 3rem;
                        padding: 2rem;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        text-align: center;
                        border-radius: 12px;
                        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                    }
                    
                    .footer-logo {
                        font-size: 1.5rem;
                        font-weight: bold;
                        margin-bottom: 0.75rem;
                    }
                    
                    .footer-info {
                        font-size: 0.875rem;
                        opacity: 0.9;
                        line-height: 1.6;
                    }
                    
                    /* Enhanced Print Styles */
                    @media print {
                        @page {
                            margin: 15mm;
                            size: A4 portrait;
                        }
                        
                        /* Hide non-essential elements during print */
                        .min-h-screen > div > div:first-child,
                        .bg-gray-50 > div > div:first-child {
                            display: none !important;
                        }
                        
                        .table-container ~ div.text-center {
                            display: none !important;
                        }
                        
                        body {
                            background: white !important;
                            color: black !important;
                        }
                        
                        .report-preview-content {
                            padding: 0 !important;
                            max-width: 100% !important;
                            margin: 0 !important;
                            background: white !important;
                            color: black !important;
                            font-size: 11px !important;
                        }
                        
                        .preview-header,
                        .preview-filters,
                        .stat-card,
                        .section-header,
                        .performance-summary,
                        .preview-footer {
                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                            color: white !important;
                            -webkit-print-color-adjust: exact !important;
                            print-color-adjust: exact !important;
                        }
                        
                        .preview-header h1 {
                            font-size: 20px !important;
                        }
                        
                        .preview-header .subtitle {
                            font-size: 14px !important;
                        }
                        
                        .preview-header .generated-info {
                            font-size: 11px !important;
                        }
                        
                        .stat-number {
                            font-size: 16px !important;
                        }
                        
                        .stat-label {
                            font-size: 9px !important;
                        }
                        
                        .data-table {
                            font-size: 9px !important;
                        }
                        
                        .data-table th {
                            font-size: 8px !important;
                            padding: 8px 4px !important;
                        }
                        
                        .data-table td {
                            padding: 6px 4px !important;
                        }
                        
                        .user-name {
                            font-size: 9px !important;
                        }
                        
                        .user-email {
                            font-size: 7px !important;
                        }
                        
                        .points-breakdown {
                            font-size: 7px !important;
                        }
                        
                        .completion-rate {
                            font-size: 8px !important;
                            padding: 2px 4px !important;
                        }
                        
                        .team-badge {
                            font-size: 7px !important;
                            padding: 2px 4px !important;
                        }
                        
                        .total-points {
                            font-size: 9px !important;
                        }
                        
                        .activity-date {
                            font-size: 8px !important;
                        }
                        
                        /* Print all users */
                        .data-table tbody tr {
                            page-break-inside: avoid;
                        }
                        
                        /* Force color printing */
                        * {
                            -webkit-print-color-adjust: exact !important;
                            print-color-adjust: exact !important;
                        }
                    }
                    
                    /* Loading overlay styles */
                    .loading-overlay {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0, 0, 0, 0.8);
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        z-index: 9999;
                        color: white;
                        font-size: 18px;
                    }
                    
                    .loading-content {
                        text-align: center;
                        padding: 2rem;
                        background: rgba(0, 0, 0, 0.9);
                        border-radius: 12px;
                        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
                    }
                    
                    .loading-spinner {
                        border: 4px solid #f3f3f3;
                        border-top: 4px solid #667eea;
                        border-radius: 50%;
                        width: 40px;
                        height: 40px;
                        animation: spin 1s linear infinite;
                        margin: 0 auto 1rem auto;
                    }
                    
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                    
                    .loading-title {
                        font-size: 1.25rem;
                        font-weight: bold;
                        margin-bottom: 0.5rem;
                    }
                    
                    .loading-subtitle {
                        font-size: 0.875rem;
                        opacity: 0.8;
                        margin-bottom: 1.5rem;
                    }
                    
                    .loading-progress {
                        width: 100%;
                        margin-top: 1rem;
                    }
                    
                    .progress-bar {
                        width: 100%;
                        height: 8px;
                        background-color: rgba(255, 255, 255, 0.2);
                        border-radius: 4px;
                        overflow: hidden;
                        margin-bottom: 1rem;
                    }
                    
                    .progress-fill {
                        height: 100%;
                        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
                        border-radius: 4px;
                        width: 0%;
                        transition: width 0.3s ease;
                    }
                    
                    .progress-steps {
                        display: grid;
                        grid-template-columns: repeat(4, 1fr);
                        gap: 0.5rem;
                        font-size: 0.75rem;
                    }
                    
                    .step {
                        text-align: center;
                        padding: 0.5rem;
                        border-radius: 4px;
                        background-color: rgba(255, 255, 255, 0.1);
                        opacity: 0.5;
                        transition: all 0.3s ease;
                    }
                    
                    .step.active {
                        background-color: rgba(255, 255, 255, 0.2);
                        opacity: 1;
                        transform: scale(1.05);
                    }
                    
                    .step.completed {
                        background-color: rgba(34, 197, 94, 0.3);
                        opacity: 1;
                    }
                </style>

                <!-- Header -->
                <div class="preview-header">
                    <h1>📊 {{ \App\Models\BrandSetting::appName() }} Learning Progress Report</h1>
                    <div class="subtitle">Comprehensive Analytics & Performance Overview</div>
                    <div class="generated-info">
                        📅 Generated on {{ now()->format('F j, Y \a\t g:i A') }} | 
                        📊 Report Period: Last {{ $filters['selectedTimeframe'] }} days
                    </div>
                </div>
                
                <!-- Enhanced Applied Filters -->
                <div class="preview-filters">
                    <h3>🔍 Applied Filters & Criteria</h3>
                    <div class="filters-grid">
                        <div class="filter-item">
                            <span class="filter-label">📅 Timeframe</span>
                            <span class="filter-value">Last {{ $filters['selectedTimeframe'] }} days</span>
                        </div>
                        <div class="filter-item">
                            <span class="filter-label">👥 Team</span>
                            <span class="filter-value">{{ $filters['selectedTeam'] === 'all' ? 'All Teams' : $teamName ?? 'Unknown Team' }}</span>
                        </div>
                        <div class="filter-item">
                            <span class="filter-label">👤 Role</span>
                            <span class="filter-value">{{ ucfirst($filters['selectedRole']) }}{{ $filters['selectedRole'] === 'all' ? ' Roles' : 's' }}</span>
                        </div>
                        <div class="filter-item">
                            <span class="filter-label">🔍 Search</span>
                            <span class="filter-value">{{ $filters['searchTerm'] ?: 'None Applied' }}</span>
                        </div>
                    </div>
                </div>
                
                <!-- Enhanced Summary Statistics -->
                <div class="preview-stats">
                    <div class="stat-card">
                        <div class="stat-number">{{ $stats['totalUsers'] }}</div>
                        <div class="stat-label">👥 Total Users</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ $stats['totalAttempts'] }}</div>
                        <div class="stat-label">🎯 Total Attempts</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ $stats['completedAttempts'] }}</div>
                        <div class="stat-label">✅ Completed</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ $stats['averagePoints'] }}</div>
                        <div class="stat-label">⭐ Avg Points</div>
                    </div>
                </div>
                
                <!-- Section Header -->
                <div class="section-header">
                    📋 Detailed User Performance Data
                </div>
                
                <!-- Enhanced User Data Table -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 22%;">👤 User</th>
                                <th style="width: 15%;">👥 Team</th>
                                <th style="width: 12%;">🎯 Attempts</th>
                                <th style="width: 12%;">✅ Completed</th>
                                <th style="width: 13%;">📊 Rate</th>
                                <th style="width: 15%;">🏆 Points</th>
                                <th style="width: 11%;">📅 Last Active</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users->take(20) as $user)
                                <tr>
                                    <td style="text-align: left;">
                                        <div class="user-name">{{ $user['name'] }}</div>
                                        <div class="user-email">{{ $user['email'] }}</div>
                                    </td>
                                    <td>
                                        <span class="team-badge">{{ $user['team'] }}</span>
                                    </td>
                                    <td><strong>{{ $user['total_attempts'] }}</strong></td>
                                    <td><strong>{{ $user['completed_attempts'] }}</strong></td>
                                    <td>
                                        <span class="completion-rate {{ $user['completion_rate'] >= 80 ? 'high' : ($user['completion_rate'] >= 50 ? 'medium' : 'low') }}">
                                            {{ $user['completion_rate'] }}%
                                        </span>
                                    </td>
                                    <td>
                                        <div class="total-points">{{ number_format($user['total_points']) }}</div>
                                        @if(isset($user['team_points_breakdown']) && $user['team_points_breakdown']['earned_points'] > 0)
                                            <div class="points-breakdown">
                                                Base: {{ $user['team_points_breakdown']['base_points'] ?? 0 }} |
                                                Earned: {{ $user['team_points_breakdown']['earned_points'] ?? 0 }}
                                                @if(($user['team_points_breakdown']['assessment_bonus'] ?? 0) != 0)
                                                    | Bonus: {{ $user['team_points_breakdown']['assessment_bonus'] }}
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="activity-date">
                                            {{ $user['last_activity'] ? $user['last_activity']->format('M j, Y') : 'Never' }}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if(count($users) > 20)
                    <div class="text-center py-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                        <p class="text-blue-800 dark:text-blue-200 font-medium">
                            <i class="fas fa-info-circle mr-2"></i>
                            Showing first 20 users. Complete report with all {{ count($users) }} users will be included in the PDF download.
                        </p>
                    </div>
                @endif
                
                <!-- Enhanced Performance Analytics Section -->
                @if(count($users) > 5 && isset($analytics))
                <div class="section-header">
                    📈 Advanced Performance Analytics & Insights
                </div>
                
                <!-- Performance Distribution Charts -->
                <div class="performance-summary">
                    <div class="performance-grid">
                        <div>
                            <strong>🏆 Top Performers ({{ count($analytics['topPerformers']) }}):</strong><br>
                            @foreach($analytics['topPerformers'] as $index => $performer)
                                {{ $index + 1 }}. {{ $performer['name'] }} ({{ number_format($performer['points']) }} pts, {{ $performer['completion_rate'] }}%)<br>
                            @endforeach
                        </div>
                        <div>
                            <strong>📊 Performance Distribution:</strong><br>
                            • High Performers (≥80%): {{ $analytics['performanceDistribution']['high_performers'] }} users<br>
                            • Medium Performers (50-79%): {{ $analytics['performanceDistribution']['medium_performers'] }} users<br>
                            • Needs Support (<50%): {{ $analytics['performanceDistribution']['low_performers'] }} users<br>
                            • Inactive Users: {{ $analytics['performanceDistribution']['inactive_users'] }} users
                        </div>
                    </div>
                </div>

                <!-- Team Comparison (if multiple teams) -->
                @if(count($analytics['teamComparison']) > 1)
                <div style="background: #FEF7F0; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; border-left: 4px solid #f59e0b; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);">
                    <div style="font-size: 0.875rem;">
                        <strong style="display: block; margin-bottom: 1rem; color: #d97706; font-size: 1.125rem;">🏅 Team Performance Comparison</strong>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                            @php 
                                $teamChunks = collect($analytics['teamComparison'])->chunk(ceil(count($analytics['teamComparison'])/2));
                            @endphp
                            @foreach($teamChunks as $column)
                                <div>
                                    @foreach($column as $team)
                                        <div style="margin-bottom: 0.75rem; padding: 1rem; background: white; border-radius: 8px; border: 1px solid #E5E7EB;">
                                            <strong style="color: #1F2937;">{{ $team['team'] }}</strong><br>
                                            <span style="color: #6B7280; font-size: 0.75rem;">
                                                Users: {{ $team['users'] }} | Avg Points: {{ number_format($team['avg_points']) }}<br>
                                                Completion Rate: {{ $team['avg_completion'] }}% | Total Attempts: {{ $team['total_attempts'] }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Engagement Metrics -->
                <div style="background: #F0F9FF; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; border-left: 4px solid #0ea5e9; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);">
                    <div style="font-size: 0.875rem;">
                        <strong style="display: block; margin-bottom: 1rem; color: #0369a1; font-size: 1.125rem;">📊 Engagement & Activity Metrics</strong>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                            <div>
                                <strong>📈 Activity Statistics:</strong><br>
                                <span style="color: #6B7280;">
                                • Avg Attempts per User: {{ $analytics['engagementMetrics']['average_attempts_per_user'] }}<br>
                                • Most Active User: {{ $analytics['engagementMetrics']['most_active_user'] }}<br>
                                • Highest Completion Rate: {{ $analytics['engagementMetrics']['highest_completion_rate'] }}%<br>
                                • Active Users ({{ $analytics['completionTrends']['timeframe'] }}): {{ $analytics['completionTrends']['active_users'] }}
                                </span>
                            </div>
                            <div>
                                <strong>🏆 Points Distribution:</strong><br>
                                <span style="color: #6B7280;">
                                • Highest Score: {{ number_format($analytics['engagementMetrics']['points_range']['highest']) }} pts<br>
                                • Lowest Score: {{ number_format($analytics['engagementMetrics']['points_range']['lowest']) }} pts<br>
                                • Median Score: {{ number_format($analytics['engagementMetrics']['points_range']['median']) }} pts<br>
                                • Completion Velocity: {{ $analytics['completionTrends']['completion_velocity'] }}/day
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                
                <!-- Enhanced Footer -->
                <div class="preview-footer">
                    <div class="footer-logo">🎓 {{ \App\Models\BrandSetting::appName() }} Learning Management System</div>
                    <div class="footer-info">
                        📊 This comprehensive report contains {{ count($users) }} user records<br>
                        Generated using advanced analytics for educational performance tracking<br>
                        For technical support or inquiries, contact your system administrator
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Loading Overlay (initially hidden) -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <div class="loading-title">Generating PDF Report...</div>
        <div class="loading-subtitle">Please wait while we prepare your document</div>
        <div class="loading-progress">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <div class="progress-steps" id="progressSteps">
                <div class="step active">📋 Preparing data</div>
                <div class="step">🎨 Capturing layout</div>
                <div class="step">📄 Creating PDF</div>
                <div class="step">💾 Finalizing</div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<!-- jsPDF and html2canvas for client-side PDF generation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
    async function downloadPDF() {
        // Show loading overlay
        document.getElementById('loadingOverlay').style.display = 'flex';
        
        // Get progress elements
        const progressFill = document.getElementById('progressFill');
        const progressSteps = document.getElementById('progressSteps');
        
        // Helper function to update progress
        function updateProgress(step, percentage) {
            // Update progress bar
            progressFill.style.width = `${percentage}%`;
            
            // Update step indicators
            const steps = progressSteps.querySelectorAll('.step');
            steps.forEach((stepElement, index) => {
                stepElement.classList.remove('active', 'completed');
                if (index < step) {
                    stepElement.classList.add('completed');
                } else if (index === step) {
                    stepElement.classList.add('active');
                }
            });
        }
        
        try {
            // Step 1: Preparing data
            updateProgress(0, 10);
            await new Promise(resolve => setTimeout(resolve, 300));
            
            // Prepare the content for PDF generation
            await prepareContentForPDF();
            updateProgress(0, 25);
            
            // Get the report content element
            const element = document.querySelector('.report-preview-content');
            
            if (!element) {
                throw new Error('Report content not found');
            }
            
            // Step 2: Capturing layout
            updateProgress(1, 30);
            await new Promise(resolve => setTimeout(resolve, 200));
            
            // Configure html2canvas options for better quality
            const canvas = await html2canvas(element, {
                scale: 2, // Higher resolution
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#ffffff',
                width: element.scrollWidth,
                height: element.scrollHeight,
                scrollX: 0,
                scrollY: 0,
                logging: false
            });
            
            updateProgress(1, 60);
            
            // Step 3: Creating PDF
            updateProgress(2, 65);
            await new Promise(resolve => setTimeout(resolve, 200));
            
            // Create PDF with the captured content
            const { jsPDF } = window.jspdf;
            
            // Calculate dimensions for A4 portrait
            const imgWidth = 210; // A4 width in mm
            const pageHeight = 297; // A4 height in mm
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            
            const pdf = new jsPDF('p', 'mm', 'a4');
            
            let position = 0;
            const imgData = canvas.toDataURL('image/png');
            
            updateProgress(2, 80);
            
            // Add pages if content is longer than one page
            if (imgHeight <= pageHeight) {
                // Single page
                pdf.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight);
            } else {
                // Multiple pages
                while (position < imgHeight) {
                    if (position > 0) {
                        pdf.addPage();
                    }
                    
                    const pageCanvas = document.createElement('canvas');
                    const pageCtx = pageCanvas.getContext('2d');
                    
                    pageCanvas.width = canvas.width;
                    pageCanvas.height = (pageHeight * canvas.width) / imgWidth;
                    
                    pageCtx.drawImage(
                        canvas,
                        0, (position * canvas.width) / imgWidth,
                        canvas.width, pageCanvas.height,
                        0, 0,
                        canvas.width, pageCanvas.height
                    );
                    
                    const pageImgData = pageCanvas.toDataURL('image/png');
                    pdf.addImage(pageImgData, 'PNG', 0, 0, imgWidth, pageHeight);
                    
                    position += pageHeight;
                }
            }
            
            // Generate filename
            const filename = `{{ \Illuminate\Support\Str::slug(\App\Models\BrandSetting::appName()) }}-Progress-Report-${new Date().toISOString().slice(0, 19).replace(/[:.]/g, '-')}.pdf`;
            
            // Step 4: Finalizing
            updateProgress(3, 90);
            await new Promise(resolve => setTimeout(resolve, 300));
            
            // Download the PDF
            pdf.save(filename);
            
            // Complete progress
            updateProgress(3, 100);
            await new Promise(resolve => setTimeout(resolve, 500));
            
            // Show success message
            showNotification('PDF downloaded successfully! 🎉', 'success');
            
        } catch (error) {
            console.error('PDF generation failed:', error);
            showNotification('Failed to generate PDF. Please try again.', 'error');
        } finally {
            // Hide loading overlay
            setTimeout(() => {
                document.getElementById('loadingOverlay').style.display = 'none';
                
                // Reset progress indicators
                const progressFill = document.getElementById('progressFill');
                const progressSteps = document.getElementById('progressSteps');
                
                if (progressFill) progressFill.style.width = '0%';
                if (progressSteps) {
                    progressSteps.querySelectorAll('.step').forEach((step, index) => {
                        step.classList.remove('active', 'completed');
                        if (index === 0) step.classList.add('active');
                    });
                }
            }, 1000);
            
            // Restore original content
            restoreContentAfterPDF();
        }
    }
    
    async function prepareContentForPDF() {
        // Add all users to the table for PDF generation (not just first 20)
        const tableBody = document.querySelector('.data-table tbody');
        const infoMessage = document.querySelector('.table-container ~ div.text-center');
        
        // Hide the "showing first 20 users" message
        if (infoMessage) {
            infoMessage.style.display = 'none';
        }
        
        // Add remaining users if there are more than 20
        if (tableBody && @json(count($users)) > 20) {
            const remainingUsers = @json($users->slice(20));
            
            remainingUsers.forEach(user => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td style="text-align: left;">
                        <div class="user-name">${user.name}</div>
                        <div class="user-email">${user.email}</div>
                    </td>
                    <td>
                        <span class="team-badge">${user.team}</span>
                    </td>
                    <td><strong>${user.total_attempts}</strong></td>
                    <td><strong>${user.completed_attempts}</strong></td>
                    <td>
                        <span class="completion-rate ${user.completion_rate >= 80 ? 'high' : (user.completion_rate >= 50 ? 'medium' : 'low')}">
                            ${user.completion_rate}%
                        </span>
                    </td>
                    <td>
                        <div class="total-points">${parseInt(user.total_points).toLocaleString()}</div>
                        ${user.team_points_breakdown && user.team_points_breakdown.earned_points > 0 ? 
                            `<div class="points-breakdown">
                                Base: ${user.team_points_breakdown.base_points || 0} |
                                Earned: ${user.team_points_breakdown.earned_points || 0}
                                ${user.team_points_breakdown.assessment_bonus !== 0 ? 
                                    `| Bonus: ${user.team_points_breakdown.assessment_bonus}` : ''}
                            </div>` : ''}
                    </td>
                    <td>
                        <div class="activity-date">
                            ${user.last_activity ? new Date(user.last_activity).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'}) : 'Never'}
                        </div>
                    </td>
                `;
                row.className = 'pdf-generation-row';
                tableBody.appendChild(row);
            });
        }
        
        // Wait a moment for DOM to update
        await new Promise(resolve => setTimeout(resolve, 100));
    }
    
    function restoreContentAfterPDF() {
        // Remove PDF-specific rows and restore original view
        document.querySelectorAll('.pdf-generation-row').forEach(row => row.remove());
        
        // Show the info message again if it was hidden
        const infoMessage = document.querySelector('.table-container ~ div.text-center');
        if (infoMessage && @json(count($users)) > 20) {
            infoMessage.style.display = '';
        }
    }
    
    function printReport() {
        // Hide the header navigation and toolbar for printing
        const headerElement = document.querySelector('.min-h-screen > div > div:first-child');
        const toolbarElement = document.querySelector('.bg-gray-50 > div > div:first-child');
        
        if (headerElement) headerElement.style.display = 'none';
        if (toolbarElement) toolbarElement.style.display = 'none';
        
        // Show all users for printing (not just the first 20)
        const hiddenRows = document.querySelectorAll('.data-table tbody tr:nth-child(n+21)');
        const infoMessage = document.querySelector('.table-container ~ div.text-center');
        
        // Hide the "showing first 20 users" message
        if (infoMessage) infoMessage.style.display = 'none';
        
        // Add print-specific content for complete data
        const tableBody = document.querySelector('.data-table tbody');
        if (tableBody && @json(count($users)) > 20) {
            // Add remaining users for print version
            const remainingUsers = @json($users->slice(20));
            remainingUsers.forEach(user => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td style="text-align: left;">
                        <div class="user-name">${user.name}</div>
                        <div class="user-email">${user.email}</div>
                    </td>
                    <td>
                        <span class="team-badge">${user.team}</span>
                    </td>
                    <td><strong>${user.total_attempts}</strong></td>
                    <td><strong>${user.completed_attempts}</strong></td>
                    <td>
                        <span class="completion-rate ${user.completion_rate >= 80 ? 'high' : (user.completion_rate >= 50 ? 'medium' : 'low')}">
                            ${user.completion_rate}%
                        </span>
                    </td>
                    <td>
                        <div class="total-points">${parseInt(user.total_points).toLocaleString()}</div>
                        ${user.team_points_breakdown && user.team_points_breakdown.earned_points > 0 ? 
                            `<div class="points-breakdown">
                                Base: ${user.team_points_breakdown.base_points || 0} |
                                Earned: ${user.team_points_breakdown.earned_points || 0}
                                ${user.team_points_breakdown.assessment_bonus !== 0 ? 
                                    `| Bonus: ${user.team_points_breakdown.assessment_bonus}` : ''}
                            </div>` : ''}
                    </td>
                    <td>
                        <div class="activity-date">
                            ${user.last_activity ? new Date(user.last_activity).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'}) : 'Never'}
                        </div>
                    </td>
                `;
                row.className = 'print-only-row';
                tableBody.appendChild(row);
            });
        }
        
        // Open print dialog
        window.print();
        
        // Restore hidden elements after printing
        setTimeout(() => {
            if (headerElement) headerElement.style.display = '';
            if (toolbarElement) toolbarElement.style.display = '';
            if (infoMessage) infoMessage.style.display = '';
            
            // Remove print-only rows
            document.querySelectorAll('.print-only-row').forEach(row => row.remove());
        }, 1000);
    }
    
    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transform translate-x-full transition-transform duration-300 ${
            type === 'success' ? 'bg-green-500 text-white' : 
            type === 'error' ? 'bg-red-500 text-white' : 
            'bg-blue-500 text-white'
        }`;
        
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-2"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Auto-hide after 4 seconds
        setTimeout(() => {
            notification.style.transform = 'translateX(full)';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 4000);
    }
    
    // Handle browser print event
    window.addEventListener('beforeprint', function() {
        document.body.classList.add('printing');
    });
    
    window.addEventListener('afterprint', function() {
        document.body.classList.remove('printing');
    });
    
    // Show loading state when navigating to export
    document.addEventListener('DOMContentLoaded', function() {
        // Check if we came from an export action
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('from') === 'export') {
            showNotification('Report preview loaded successfully!', 'success');
        }
    });
</script>
@endpush

@endsection