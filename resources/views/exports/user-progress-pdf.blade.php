<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>EUREKA Learning Management System - User Progress Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #2D3748;
            background-color: #FFFFFF;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 100%;
            margin: 0;
            padding: 3px;
        }
        
        /* Corporate Header */
        .corporate-header {
            border-bottom: 2px solid #1A365D;
            margin-bottom: 8px;
            padding-bottom: 4px;
        }
        
        .company-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 4px;
        }
        
        .company-logo {
            margin-bottom: 1px;
        }
        
        .company-logo img {
            height: 60px;
            width: auto;
        }
        
        .company-tagline {
            font-size: 8px;
            color: #4A5568;
            font-style: italic;
        }
        
        .report-info {
            text-align: right;
            font-size: 8px;
            color: #4A5568;
        }
        
        .report-title {
            text-align: center;
            background: linear-gradient(135deg, #2D3748 0%, #1A365D 100%);
            color: white;
            padding: 6px 10px;
            margin-bottom: 6px;
            border-radius: 3px;
        }
        
        .report-title h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .report-title .subtitle {
            font-size: 10px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .report-title .period {
            font-size: 8px;
            background-color: rgba(255, 255, 255, 0.15);
            padding: 3px 8px;
            border-radius: 10px;
            display: inline-block;
        }
        
        /* Executive Summary Box */
        .executive-summary {
            background: #F7FAFC;
            border-left: 3px solid #2B6CB0;
            padding: 4px;
            margin-bottom: 5px;
        }
        
        .executive-summary h3 {
            font-size: 10px;
            font-weight: bold;
            color: #2D3748;
            margin-bottom: 2px;
            text-transform: uppercase;
        }
        
        .summary-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        
        .summary-grid td {
            text-align: center;
            padding: 2px;
            background: white;
            border: 1px solid #E2E8F0;
            width: 25%;
        }
        
        .summary-item .number {
            font-size: 12px;
            font-weight: bold;
            color: #2B6CB0;
            display: block;
            margin-bottom: 1px;
        }
        
        .summary-item .label {
            font-size: 7px;
            color: #4A5568;
            text-transform: uppercase;
        }
        
        /* Report Parameters Section */
        .report-parameters {
            background: #EDF2F7;
            border: 1px solid #CBD5E0;
            padding: 3px;
            margin-bottom: 5px;
        }
        
        .report-parameters h3 {
            font-size: 9px;
            font-weight: bold;
            color: #2D3748;
            margin-bottom: 1px;
            text-transform: uppercase;
        }
        
        .parameters-grid {
            width: 100%;
            border-collapse: collapse;
            font-size: 7px;
        }
        
        .parameters-grid td {
            background: white;
            padding: 2px;
            border: 1px solid #E2E8F0;
            width: 25%;
        }
        
        .parameter-label {
            font-weight: bold;
            color: #4A5568;
            display: block;
            margin-bottom: 1px;
        }
        
        .parameter-value {
            color: #2D3748;
            font-weight: 500;
        }
        
        /* Enhanced Statistics Cards */
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 6px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .stat-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.9;
        }
        
        /* Professional Section Headers */
        .section-header {
            background: #2D3748;
            color: white;
            padding: 3px 6px;
            margin: 4px 0 2px 0;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            border-left: 3px solid #2B6CB0;
        }
        
        .section-divider {
            border-top: 1px solid #CBD5E0;
            margin: 3px 0;
        }
        
        /* Professional Data Table */
        .table-container {
            background-color: #FFFFFF;
            overflow: hidden;
            border: 1px solid #CBD5E0;
            margin-bottom: 5px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        
        .data-table th {
            background: #2D3748;
            color: white;
            font-weight: bold;
            padding: 3px 2px;
            text-align: center;
            border-right: 1px solid #4A5568;
            font-size: 8px;
            text-transform: uppercase;
        }
        
        .data-table th:last-child {
            border-right: none;
        }
        
        .data-table td {
            padding: 2px 1px;
            border-bottom: 1px solid #E2E8F0;
            border-right: 1px solid #EDF2F7;
            text-align: center;
            vertical-align: middle;
        }
        
        .data-table td:last-child {
            border-right: none;
        }
        
        .data-table tbody tr:nth-child(even) {
            background-color: #F7FAFC;
        }
        
        .data-table tbody tr:nth-child(odd) {
            background-color: #FFFFFF;
        }
        
        /* Enhanced Styling Elements */
        .user-name {
            font-weight: bold;
            color: #1F2937;
        }
        
        .user-email {
            font-size: 6px;
            color: #6B7280;
        }
        
        .points-breakdown {
            font-size: 6px;
            color: #6B7280;
            margin-top: 1px;
            line-height: 1.1;
        }
        
        .completion-rate {
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 9px;
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
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            border: 1px solid #E5E7EB;
        }
        
        .total-points {
            font-weight: bold;
            font-size: 11px;
            color: #1F2937;
        }
        
        .activity-date {
            font-size: 9px;
            color: #6B7280;
        }
        
        /* Corporate Footer */
        .corporate-footer {
            margin-top: 25px;
            border-top: 2px solid #2D3748;
            padding-top: 15px;
            font-size: 8px;
            color: #4A5568;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .footer-left {
            text-align: left;
        }
        
        .footer-right {
            text-align: right;
        }
        
        .confidentiality {
            background: #FED7D7;
            border: 1px solid #FC8181;
            color: #C53030;
            padding: 5px;
            border-radius: 2px;
            margin-bottom: 8px;
            font-size: 7px;
            text-align: center;
            font-weight: bold;
        }
        
        /* Analytics Sections */
        .analytics-section {
            background: #F7FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 5px;
            padding: 12px;
            margin-bottom: 15px;
        }
        
        .analytics-title {
            font-size: 12px;
            font-weight: bold;
            color: #2D3748;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #CBD5E0;
        }
        
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            font-size: 9px;
        }
        
        .analytics-item {
            background: white;
            padding: 8px;
            border-radius: 3px;
            border: 1px solid #E2E8F0;
        }
        
        .metric-label {
            font-weight: bold;
            color: #4A5568;
            margin-bottom: 4px;
        }
        
        /* Print Optimization */
        @page {
            margin: 10mm;
            size: A4 portrait;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        /* Responsive adjustments for portrait */
        .narrow-table th,
        .narrow-table td {
            padding: 8px 4px;
            font-size: 9px;
        }
        
        .compact-stats {
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        
        .compact-stats .stat-card {
            padding: 12px;
        }
        
        .compact-stats .stat-number {
            font-size: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Corporate Header -->
        <div class="corporate-header">
            <div class="company-info">
                <div>
                    <div class="company-logo">
                        <img src="{{ public_path('logo/horizonlogo.png') }}" alt="EUREKA LMS">
                    </div>
                    <div class="company-tagline">Advanced Learning Management & Analytics Platform</div>
                </div>
                <div class="report-info">
                    <strong>Report Type:</strong> User Progress Analysis<br>
                    <strong>Generated:</strong> {{ now()->format('M j, Y g:i A') }}<br>
                    <strong>Report ID:</strong> UPR-{{ now()->format('YmdHi') }}
                </div>
            </div>
        </div>

        <!-- Report Title -->
        <div class="report-title">
            <h1>User Progress & Performance Report</h1>
            <div class="subtitle">Comprehensive Learning Analytics and Assessment Overview</div>
            <div class="period">Reporting Period: {{ $filters['selectedTimeframe'] }} Days | {{ count($users) }} Users Analyzed</div>
        </div>

        <!-- Confidentiality Notice -->
        <div class="confidentiality">
            ⚠️ CONFIDENTIAL - This report contains sensitive educational data. Distribution restricted to authorized personnel only.
        </div>

        <!-- Executive Summary -->
        <div class="executive-summary">
            <h3>Executive Summary</h3>
            <p style="font-size: 10px; line-height: 1.4; margin-bottom: 10px;">
                This report provides a comprehensive analysis of user learning progress and performance metrics within the EUREKA Learning Management System. 
                The data encompasses user engagement, completion rates, assessment scores, and comparative performance analytics across 
                {{ $filters['selectedTeam'] === 'all' ? 'all teams' : 'the selected team' }} over the past {{ $filters['selectedTimeframe'] }} days.
            </p>
            
            <table class="summary-grid">
                <tr>
                    <td>
                        <div class="summary-item">
                            <span class="number">{{ $stats['totalUsers'] }}</span>
                            <span class="label">Active Users</span>
                        </div>
                    </td>
                    <td>
                        <div class="summary-item">
                            <span class="number">{{ $stats['totalAttempts'] }}</span>
                            <span class="label">Total Attempts</span>
                        </div>
                    </td>
                    <td>
                        <div class="summary-item">
                            <span class="number">{{ $stats['completedAttempts'] }}</span>
                            <span class="label">Completed</span>
                        </div>
                    </td>
                    <td>
                        <div class="summary-item">
                            <span class="number">{{ $stats['completionRate'] }}%</span>
                            <span class="label">Success Rate</span>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Report Parameters -->
        <div class="report-parameters">
            <h3>Report Parameters & Filters</h3>
            <table class="parameters-grid">
                <tr>
                    <td>
                        <span class="parameter-label">Time Period</span><br>
                        <span class="parameter-value">{{ $filters['selectedTimeframe'] }} days</span>
                    </td>
                    <td>
                        <span class="parameter-label">Team Scope</span><br>
                        <span class="parameter-value">{{ $filters['selectedTeam'] === 'all' ? 'All Teams' : $teamName ?? 'Selected Team' }}</span>
                    </td>
                    <td>
                        <span class="parameter-label">User Role</span><br>
                        <span class="parameter-value">{{ ucfirst($filters['selectedRole']) }}</span>
                    </td>
                    <td>
                        <span class="parameter-label">Search Filter</span><br>
                        <span class="parameter-value">{{ $filters['searchTerm'] ?: 'None Applied' }}</span>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Section Header -->
        <div class="section-header">
            User Performance Analysis
        </div>
        
        <!-- Enhanced User Data Table -->
        <div class="table-container">
            <table class="data-table narrow-table">
                <thead>
                    <tr>
                        <th style="width: 22%;">User Details</th>
                        <th style="width: 15%;">Team</th>
                        <th style="width: 12%;">Attempts</th>
                        <th style="width: 12%;">Completed</th>
                        <th style="width: 13%;">Success Rate</th>
                        <th style="width: 15%;">Total Points</th>
                        <th style="width: 11%;">Last Activity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $index => $user)
                        @if($index > 0 && $index % 20 === 0)
                            </tbody>
                            </table>
                        </div>
                        <div class="page-break"></div>
                        <div class="section-header">
                            📋 Detailed User Performance Data (Continued)
                        </div>
                        <div class="table-container">
                            <table class="data-table narrow-table">
                                <thead>
                                    <tr>
                                        <th style="width: 22%;">User Details</th>
                                        <th style="width: 15%;">Team</th>
                                        <th style="width: 12%;">Attempts</th>
                                        <th style="width: 12%;">Completed</th>
                                        <th style="width: 13%;">Success Rate</th>
                                        <th style="width: 15%;">Total Points</th>
                                        <th style="width: 11%;">Last Activity</th>
                                    </tr>
                                </thead>
                                <tbody>
                        @endif
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
        
        <!-- Performance Analytics Section -->
        @if(count($users) > 5 && isset($analytics))
        <div class="section-header">
            Advanced Analytics & Performance Insights
        </div>
        
        <!-- Performance Distribution Analysis -->
        <div class="analytics-section">
            <div class="analytics-title">Performance Distribution Analysis</div>
            <div class="analytics-grid">
                <div class="analytics-item">
                    <div class="metric-label">Top Performers ({{ count($analytics['topPerformers']) }})</div>
                    @foreach($analytics['topPerformers'] as $index => $performer)
                        {{ $index + 1 }}. {{ $performer['name'] }} ({{ number_format($performer['points']) }} pts, {{ $performer['completion_rate'] }}%)<br>
                    @endforeach
                </div>
                <div class="analytics-item">
                    <div class="metric-label">Performance Segmentation</div>
                    • High Performers (≥80%): {{ $analytics['performanceDistribution']['high_performers'] }} users<br>
                    • Medium Performers (50-79%): {{ $analytics['performanceDistribution']['medium_performers'] }} users<br>
                    • Needs Support (<50%): {{ $analytics['performanceDistribution']['low_performers'] }} users<br>
                    • Inactive Users: {{ $analytics['performanceDistribution']['inactive_users'] }} users
                </div>
            </div>
        </div>

        <!-- Team Comparison Analysis -->
        @if(count($analytics['teamComparison']) > 1)
        <div class="analytics-section">
            <div class="analytics-title">Comparative Team Performance</div>
            <div class="analytics-grid">
                @php 
                    $teamChunks = collect($analytics['teamComparison'])->chunk(ceil(count($analytics['teamComparison'])/2));
                @endphp
                @foreach($teamChunks as $column)
                    <div class="analytics-item">
                        @foreach($column as $team)
                            <div style="margin-bottom: 6px; padding: 6px; background: #F7FAFC; border-radius: 3px; border: 1px solid #E2E8F0;">
                                <strong>{{ $team['team'] }}</strong><br>
                                <span style="font-size: 8px;">
                                Users: {{ $team['users'] }} | Avg Points: {{ number_format($team['avg_points']) }}<br>
                                Completion: {{ $team['avg_completion'] }}% | Attempts: {{ $team['total_attempts'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Engagement and Activity Metrics -->
        <div class="analytics-section">
            <div class="analytics-title">Engagement & Activity Analysis</div>
            <div class="analytics-grid">
                <div class="analytics-item">
                    <div class="metric-label">Activity Statistics</div>
                    • Avg Attempts per User: {{ $analytics['engagementMetrics']['average_attempts_per_user'] }}<br>
                    • Most Active User: {{ $analytics['engagementMetrics']['most_active_user'] }}<br>
                    • Highest Completion Rate: {{ $analytics['engagementMetrics']['highest_completion_rate'] }}%<br>
                    • Active Users ({{ $analytics['completionTrends']['timeframe'] }}): {{ $analytics['completionTrends']['active_users'] }}
                </div>
                <div class="analytics-item">
                    <div class="metric-label">Score Distribution</div>
                    • Highest Score: {{ number_format($analytics['engagementMetrics']['points_range']['highest']) }} points<br>
                    • Lowest Score: {{ number_format($analytics['engagementMetrics']['points_range']['lowest']) }} points<br>
                    • Median Score: {{ number_format($analytics['engagementMetrics']['points_range']['median']) }} points<br>
                    • Daily Completion Velocity: {{ $analytics['completionTrends']['completion_velocity'] }}
                </div>
            </div>
        </div>
        @endif
        
        <div class="section-divider"></div>
        
        <!-- Corporate Footer -->
        <div class="corporate-footer">
            <div class="footer-left">
                <strong>EUREKA Learning Management System</strong><br>
                Advanced Educational Analytics Platform<br><br>
                <strong>Report Summary:</strong><br>
                • Total Records Analyzed: {{ count($users) }}<br>
                • Data Period: {{ $filters['selectedTimeframe'] }} days<br>
                • Generated: {{ now()->format('M j, Y g:i A') }}
            </div>
            <div class="footer-right">
                <strong>Document Information</strong><br>
                Report ID: UPR-{{ now()->format('YmdHi') }}<br>
                Classification: Confidential<br>
                Distribution: Authorized Personnel Only<br><br>
                For technical support or data inquiries,<br>
                contact your system administrator.
            </div>
        </div>
    </div>
</body>
</html>