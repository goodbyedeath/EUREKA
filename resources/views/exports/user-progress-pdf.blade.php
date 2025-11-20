<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Kemajuan Pengguna EUREKA</title>
    <style>
        @page {
            margin: 180px 50px 120px 50px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            line-height: 1.4;
            color: #2D3748;
        }

        /* Fixed Header */
        header {
            position: fixed;
            top: -160px;
            left: 0;
            right: 0;
            height: 140px;
            background: white;
            border-bottom: 3px solid #1A365D;
            padding: 15px 20px;
        }

        .header-content {
            display: table;
            width: 100%;
        }

        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }

        .logo-section {
            margin-bottom: 10px;
        }

        .logo-section img {
            height: 50px;
            width: auto;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1A365D;
            margin-bottom: 3px;
        }

        .company-tagline {
            font-size: 8px;
            color: #4A5568;
            font-style: italic;
        }

        .report-metadata {
            font-size: 8px;
            color: #4A5568;
            line-height: 1.6;
        }

        .report-metadata strong {
            color: #2D3748;
        }

        .report-title-section {
            text-align: center;
            background: linear-gradient(135deg, #1A365D 0%, #2D3748 100%);
            color: white;
            padding: 12px 15px;
            margin-top: 10px;
            border-radius: 4px;
        }

        .report-title-section h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .report-subtitle {
            font-size: 9px;
            opacity: 0.9;
        }

        /* Fixed Footer */
        footer {
            position: fixed;
            bottom: -100px;
            left: 0;
            right: 0;
            height: 80px;
            background: #F7FAFC;
            border-top: 2px solid #CBD5E0;
            padding: 10px 20px;
            font-size: 7px;
            color: #4A5568;
        }

        .footer-content {
            display: table;
            width: 100%;
        }

        .footer-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .footer-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }

        .footer-divider {
            border-top: 1px solid #CBD5E0;
            margin: 5px 0;
        }

        .page-number {
            text-align: center;
            font-size: 8px;
            color: #4A5568;
            margin-top: 5px;
        }

        .page-number:after {
            content: "Halaman " counter(page);
        }

        .confidential-notice {
            background: #FED7D7;
            border: 1px solid #FC8181;
            color: #C53030;
            padding: 4px 8px;
            border-radius: 2px;
            font-size: 7px;
            text-align: center;
            font-weight: bold;
            display: inline-block;
        }

        /* Main Content Styles */
        .container {
            width: 100%;
        }

        /* Executive Summary */
        .executive-summary {
            background: #F7FAFC;
            border-left: 4px solid #2B6CB0;
            padding: 10px;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        .executive-summary h3 {
            font-size: 11px;
            font-weight: bold;
            color: #1A365D;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .executive-summary p {
            font-size: 9px;
            line-height: 1.6;
            color: #2D3748;
            margin-bottom: 10px;
        }

        .summary-stats-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .summary-stats-grid td {
            text-align: center;
            padding: 8px;
            background: white;
            border: 1px solid #E2E8F0;
            width: 25%;
        }

        .stat-number {
            font-size: 16px;
            font-weight: bold;
            color: #2B6CB0;
            display: block;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 7px;
            color: #4A5568;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* Report Parameters */
        .report-parameters {
            background: #EDF2F7;
            border: 1px solid #CBD5E0;
            border-radius: 3px;
            padding: 8px;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        .report-parameters h3 {
            font-size: 10px;
            font-weight: bold;
            color: #2D3748;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .parameters-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        .parameters-table td {
            background: white;
            padding: 6px 8px;
            border: 1px solid #E2E8F0;
            width: 25%;
        }

        .param-label {
            font-weight: bold;
            color: #4A5568;
            display: block;
            margin-bottom: 2px;
        }

        .param-value {
            color: #2D3748;
        }

        /* Section Headers */
        .section-header {
            background: #2D3748;
            color: white;
            padding: 6px 10px;
            margin: 15px 0 10px 0;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            border-left: 4px solid #2B6CB0;
            letter-spacing: 0.5px;
        }

        /* Data Table */
        .data-table-container {
            background: white;
            border: 1px solid #CBD5E0;
            margin-bottom: 15px;
            page-break-inside: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        .data-table thead {
            background: #2D3748;
            color: white;
        }

        .data-table th {
            padding: 6px 4px;
            text-align: center;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
            border-right: 1px solid #4A5568;
        }

        .data-table th:last-child {
            border-right: none;
        }

        .data-table td {
            padding: 5px 4px;
            border-bottom: 1px solid #E2E8F0;
            border-right: 1px solid #F7FAFC;
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

        .data-table tbody tr {
            page-break-inside: avoid;
        }

        /* User Details */
        .user-name {
            font-weight: bold;
            color: #1F2937;
            font-size: 8px;
        }

        .user-email {
            font-size: 6px;
            color: #6B7280;
            margin-top: 1px;
        }

        /* Team Badge */
        .team-badge {
            background: linear-gradient(135deg, #E0E7FF 0%, #C7D2FE 100%);
            color: #1E3A8A;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 7px;
            font-weight: bold;
            display: inline-block;
            border: 1px solid #A5B4FC;
        }

        /* Completion Rate Badge */
        .completion-rate {
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 8px;
            display: inline-block;
        }

        .completion-rate.high {
            background-color: #D1FAE5;
            color: #059669;
            border: 1px solid #10B981;
        }

        .completion-rate.medium {
            background-color: #FEF3C7;
            color: #D97706;
            border: 1px solid #F59E0B;
        }

        .completion-rate.low {
            background-color: #FEE2E2;
            color: #DC2626;
            border: 1px solid #EF4444;
        }

        /* Points Display */
        .total-points {
            font-weight: bold;
            font-size: 9px;
            color: #1F2937;
        }

        .points-breakdown {
            font-size: 6px;
            color: #6B7280;
            margin-top: 2px;
            line-height: 1.2;
        }

        /* Activity Date */
        .activity-date {
            font-size: 7px;
            color: #6B7280;
        }

        /* Analytics Section */
        .analytics-section {
            background: #F7FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        .analytics-title {
            font-size: 10px;
            font-weight: bold;
            color: #2D3748;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #CBD5E0;
        }

        .analytics-grid {
            display: table;
            width: 100%;
        }

        .analytics-column {
            display: table-cell;
            width: 50%;
            padding: 5px;
            vertical-align: top;
        }

        .analytics-item {
            background: white;
            padding: 8px;
            border-radius: 3px;
            border: 1px solid #E2E8F0;
            font-size: 8px;
            line-height: 1.5;
            margin-bottom: 8px;
        }

        .metric-label {
            font-weight: bold;
            color: #4A5568;
            margin-bottom: 5px;
            display: block;
        }

        /* Page Break Utilities */
        .page-break {
            page-break-before: always;
        }

        .no-break {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <!-- Fixed Header -->
    <header>
        <div class="header-content">
            <div class="header-left">
                <div class="logo-section">
                    <img src="{{ public_path('logo/horizonlogo.png') }}" alt="EUREKA LMS">
                </div>
                <div class="company-name">EUREKA LMS</div>
                <div class="company-tagline">Platform Manajemen Pembelajaran & Analitik</div>
            </div>
            <div class="header-right">
                <div class="report-metadata">
                    <strong>Jenis Laporan:</strong> Analisis Kemajuan Pengguna<br>
                    <strong>Dibuat:</strong> {{ now()->format('d F Y - H:i') }}<br>
                    <strong>ID Laporan:</strong> UPR-{{ now()->format('YmdHis') }}<br>
                    <strong>Periode:</strong> {{ $filters['selectedTimeframe'] }} Hari<br>
                    <strong>Status:</strong> <span class="confidential-notice">RAHASIA</span>
                </div>
            </div>
        </div>
        <div class="report-title-section">
            <h1>Laporan Kemajuan & Kinerja Pengguna</h1>
            <div class="report-subtitle">Analitik Pembelajaran dan Tinjauan Penilaian Komprehensif</div>
        </div>
    </header>

    <!-- Fixed Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-left">
                <strong>Sistem Manajemen Pembelajaran EUREKA</strong><br>
                Platform Analitik Pendidikan<br>
                <div class="footer-divider"></div>
                <strong>Info Laporan:</strong> {{ count($users) }} Pengguna | Periode {{ $filters['selectedTimeframe'] }} Hari
            </div>
            <div class="footer-right">
                <strong>Klasifikasi Dokumen</strong><br>
                Rahasia - Hanya untuk Personel yang Berwenang<br>
                <div class="footer-divider"></div>
                ID Laporan: UPR-{{ now()->format('YmdHis') }}
            </div>
        </div>
        <div class="page-number"></div>
    </footer>

    <!-- Main Content -->
    <div class="container">
        <!-- Executive Summary -->
        <div class="executive-summary no-break">
            <h3>Ringkasan Eksekutif</h3>
            <p>
                Laporan komprehensif ini menganalisis kemajuan pembelajaran dan metrik kinerja pengguna dalam Sistem Manajemen Pembelajaran EUREKA.
                Analisis mencakup {{ count($users) }} pengguna di {{ $filters['selectedTeam'] === 'all' ? 'semua tim' : 'tim yang dipilih' }}
                selama periode {{ $filters['selectedTimeframe'] }} hari yang berakhir pada {{ now()->format('d F Y') }}.
            </p>

            <table class="summary-stats-grid">
                <tr>
                    <td>
                        <span class="stat-number">{{ $stats['totalUsers'] }}</span>
                        <span class="stat-label">Pengguna Aktif</span>
                    </td>
                    <td>
                        <span class="stat-number">{{ $stats['totalAttempts'] }}</span>
                        <span class="stat-label">Total Percobaan</span>
                    </td>
                    <td>
                        <span class="stat-number">{{ $stats['completedAttempts'] }}</span>
                        <span class="stat-label">Diselesaikan</span>
                    </td>
                    <td>
                        <span class="stat-number">{{ $stats['completionRate'] }}%</span>
                        <span class="stat-label">Tingkat Keberhasilan</span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Report Parameters -->
        <div class="report-parameters no-break">
            <h3>Parameter Laporan & Filter yang Diterapkan</h3>
            <table class="parameters-table">
                <tr>
                    <td>
                        <span class="param-label">Periode Waktu</span>
                        <span class="param-value">{{ $filters['selectedTimeframe'] }} hari</span>
                    </td>
                    <td>
                        <span class="param-label">Cakupan Tim</span>
                        <span class="param-value">{{ $filters['selectedTeam'] === 'all' ? 'Semua Tim' : ($teamName ?? 'Tim Terpilih') }}</span>
                    </td>
                    <td>
                        <span class="param-label">Peran Pengguna</span>
                        <span class="param-value">{{ ucfirst($filters['selectedRole']) }}</span>
                    </td>
                    <td>
                        <span class="param-label">Filter Pencarian</span>
                        <span class="param-value">{{ $filters['searchTerm'] ?: 'Tidak Ada' }}</span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- User Performance Data -->
        <div class="section-header">Analisis Kinerja Pengguna</div>

        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 20%;">Detail Pengguna</th>
                        <th style="width: 13%;">Tim</th>
                        <th style="width: 10%;">Percobaan</th>
                        <th style="width: 10%;">Selesai</th>
                        <th style="width: 12%;">Tingkat Keberhasilan</th>
                        <th style="width: 18%;">Total Poin</th>
                        <th style="width: 12%;">Aktivitas Terakhir</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $index => $user)
                        @if($index > 0 && $index % 25 === 0)
                            </tbody>
                            </table>
                        </div>
                        <div class="page-break"></div>
                        <div class="section-header">Analisis Kinerja Pengguna (Lanjutan)</div>
                        <div class="data-table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th style="width: 20%;">Detail Pengguna</th>
                                        <th style="width: 13%;">Tim</th>
                                        <th style="width: 10%;">Percobaan</th>
                                        <th style="width: 10%;">Selesai</th>
                                        <th style="width: 12%;">Tingkat Keberhasilan</th>
                                        <th style="width: 18%;">Total Poin</th>
                                        <th style="width: 12%;">Aktivitas Terakhir</th>
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
                                @if(isset($user['team_points_breakdown']))
                                    <div class="points-breakdown">
                                        Dasar: {{ number_format($user['team_points_breakdown']['base_points'] ?? 0) }}
                                        @if(($user['team_points_breakdown']['earned_points'] ?? 0) > 0)
                                            | Diperoleh: {{ number_format($user['team_points_breakdown']['earned_points']) }}
                                        @endif
                                        @if(($user['team_points_breakdown']['assessment_bonus'] ?? 0) > 0)
                                            | Bonus: {{ number_format($user['team_points_breakdown']['assessment_bonus']) }}
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="activity-date">
                                    {{ $user['last_activity'] ? $user['last_activity']->format('d M Y') : 'Belum Ada' }}
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Performance Analytics -->
        @if(count($users) > 5 && isset($analytics))
        <div class="page-break"></div>
        <div class="section-header">Analitik Lanjutan & Wawasan Kinerja</div>

        <div class="analytics-section">
            <div class="analytics-title">Analisis Distribusi Kinerja</div>
            <div class="analytics-grid">
                <div class="analytics-column">
                    <div class="analytics-item">
                        <div class="metric-label">Pengguna Terbaik</div>
                        @foreach($analytics['topPerformers'] as $index => $performer)
                            {{ $index + 1 }}. {{ $performer['name'] }} - {{ number_format($performer['points']) }} poin ({{ $performer['completion_rate'] }}%)<br>
                        @endforeach
                    </div>
                </div>
                <div class="analytics-column">
                    <div class="analytics-item">
                        <div class="metric-label">Segmentasi Kinerja</div>
                        • Berkinerja Tinggi (≥80%): <strong>{{ $analytics['performanceDistribution']['high_performers'] }}</strong> pengguna<br>
                        • Berkinerja Sedang (50-79%): <strong>{{ $analytics['performanceDistribution']['medium_performers'] }}</strong> pengguna<br>
                        • Perlu Dukungan (<50%): <strong>{{ $analytics['performanceDistribution']['low_performers'] }}</strong> pengguna<br>
                        • Pengguna Tidak Aktif: <strong>{{ $analytics['performanceDistribution']['inactive_users'] }}</strong> pengguna
                    </div>
                </div>
            </div>
        </div>

        @if(count($analytics['teamComparison']) > 1)
        <div class="analytics-section">
            <div class="analytics-title">Perbandingan Kinerja Tim</div>
            <div class="analytics-grid">
                @php
                    $teamChunks = collect($analytics['teamComparison'])->chunk(ceil(count($analytics['teamComparison'])/2));
                @endphp
                @foreach($teamChunks as $chunkIndex => $column)
                    <div class="analytics-column">
                        @foreach($column as $team)
                            <div class="analytics-item">
                                <strong style="color: #2D3748;">{{ $team['team'] }}</strong><br>
                                Pengguna: {{ $team['users'] }} | Rata-rata Poin: {{ number_format($team['avg_points']) }}<br>
                                Penyelesaian: {{ $team['avg_completion'] }}% | Percobaan: {{ $team['total_attempts'] }}
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="analytics-section">
            <div class="analytics-title">Analisis Keterlibatan & Aktivitas</div>
            <div class="analytics-grid">
                <div class="analytics-column">
                    <div class="analytics-item">
                        <div class="metric-label">Statistik Aktivitas</div>
                        • Rata-rata Percobaan/Pengguna: <strong>{{ $analytics['engagementMetrics']['average_attempts_per_user'] }}</strong><br>
                        • Paling Aktif: {{ $analytics['engagementMetrics']['most_active_user'] }}<br>
                        • Penyelesaian Tertinggi: <strong>{{ $analytics['engagementMetrics']['highest_completion_rate'] }}%</strong><br>
                        • Pengguna Aktif: {{ $analytics['completionTrends']['active_users'] }}
                    </div>
                </div>
                <div class="analytics-column">
                    <div class="analytics-item">
                        <div class="metric-label">Distribusi Skor</div>
                        • Skor Tertinggi: <strong>{{ number_format($analytics['engagementMetrics']['points_range']['highest']) }}</strong> poin<br>
                        • Skor Terendah: {{ number_format($analytics['engagementMetrics']['points_range']['lowest']) }} poin<br>
                        • Skor Median: {{ number_format($analytics['engagementMetrics']['points_range']['median']) }} poin<br>
                        • Kecepatan Harian: {{ $analytics['completionTrends']['completion_velocity'] }}
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</body>
</html>
