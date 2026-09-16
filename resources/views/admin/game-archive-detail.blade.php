{{-- One archived session, read-only. Plain Blade: nothing on this page changes anything. --}}
@extends('layouts.admin')

@section('title', $archive->name)
@section('page-title', 'Game Archive')

@section('content')
@php
    $s = $archive->snapshot;
    $when = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->timezone(config('app.timezone'))->format('d M Y H:i') : '—';
    $photo = fn ($key) => route('admin.game-archives.photo', [$archive->id, $key]);
    $signed = fn ($n) => is_null($n) ? '—' : (($n > 0 ? '+' : '') . number_format($n));
@endphp
<div class="p-4 sm:p-6 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.game-archives') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">&larr; Semua arsip</a>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.game-archives.pdf', $archive->id) }}"
               class="inline-flex items-center gap-2 px-3 py-2 rounded-md text-sm text-white bg-blue-600 hover:bg-blue-700">
                <i class="fas fa-file-pdf"></i> Unduh PDF
            </a>
            <a href="{{ route('admin.game-archives.pdf', [$archive->id, 'photos' => 1]) }}"
               class="inline-flex items-center gap-2 px-3 py-2 rounded-md text-sm text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 hover:bg-blue-100">
                <i class="fas fa-images"></i> Unduh PDF + foto
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-md bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 p-3 text-sm text-green-800 dark:text-green-200">{{ session('success') }}</div>
    @endif

    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $archive->name }}</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Diarsipkan {{ $when($s['archived_at'] ?? $archive->archived_at) }} oleh {{ $s['archived_by']['name'] ?? '—' }}
            · {{ $archive->team_count }} tim · {{ $archive->account_count }} akun
        </p>
        @if (! empty($s['notes']))
            <p class="mt-2 text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $s['notes'] }}</p>
        @endif
    </div>

    <section class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <h2 class="p-4 border-b border-gray-200 dark:border-gray-700 text-lg font-semibold text-gray-900 dark:text-gray-100">Leaderboard akhir</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900 text-left text-gray-600 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-2 font-medium">Peringkat</th>
                        <th class="px-4 py-2 font-medium">Tim</th>
                        <th class="px-4 py-2 font-medium text-right">Total</th>
                        <th class="px-4 py-2 font-medium text-right">Awal</th>
                        <th class="px-4 py-2 font-medium text-right">Kuis</th>
                        <th class="px-4 py-2 font-medium text-right">Game</th>
                        <th class="px-4 py-2 font-medium">Pos selesai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($s['leaderboard'] ?? [] as $row)
                        <tr>
                            <td class="px-4 py-2 font-semibold">{{ $row['rank'] }}</td>
                            <td class="px-4 py-2 font-medium text-gray-900 dark:text-gray-100">{{ $row['name'] }}</td>
                            <td class="px-4 py-2 text-right font-semibold tabular-nums">{{ number_format($row['total']) }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">{{ number_format($row['base_points']) }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">{{ number_format($row['earned_points']) }}</td>
                            <td class="px-4 py-2 text-right tabular-nums {{ $row['assessment_points'] < 0 ? 'text-red-700 dark:text-red-300' : '' }}">{{ $signed($row['assessment_points']) }}</td>
                            <td class="px-4 py-2">{{ $row['stations_done'] }}/{{ $row['stations_total'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-3 text-gray-500 dark:text-gray-400">Tidak ada tim dalam sesi ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="space-y-3">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Rincian tim</h2>
        @foreach ($s['teams'] ?? [] as $team)
            <details class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <summary class="p-4 cursor-pointer flex flex-wrap items-baseline gap-x-3">
                    <span class="font-semibold text-gray-900 dark:text-gray-100">#{{ $team['rank'] }} {{ $team['name'] }}</span>
                    <span class="text-sm text-gray-600 dark:text-gray-400 tabular-nums">{{ number_format($team['score']['total']) }} poin · pos {{ $team['stations_done'] }}/{{ $team['stations_total'] }}</span>
                </summary>
                <div class="px-4 pb-4 space-y-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-1">Anggota</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach ($team['members'] as $m)
                                        <tr>
                                            <td class="py-1 pr-4">{{ $m['name'] }}@if ($m['is_leader']) <span class="text-xs text-violet-700">(ketua)</span>@endif</td>
                                            <td class="py-1 pr-4 text-gray-600 dark:text-gray-400">{{ $m['email'] }}</td>
                                            <td class="py-1 pr-4 text-gray-600 dark:text-gray-400">{{ $m['phone'] ?? '' }}</td>
                                            <td class="py-1 text-gray-600 dark:text-gray-400">{{ $m['position'] ?? '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if (! empty($team['accounts']))
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Akun login: {{ collect($team['accounts'])->map(fn ($a) => $a['email'])->join(', ') }}</p>
                        @endif
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-1">Per pos</h3>
                        @forelse ($team['stations'] as $station)
                            <div class="border border-gray-100 dark:border-gray-700 rounded-md p-3 mb-2">
                                <p class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $station['title'] }}
                                    <span class="text-xs {{ $station['completed'] ? 'text-green-700 dark:text-green-300' : 'text-amber-700 dark:text-amber-300' }}">{{ $station['completed'] ? 'selesai' : 'belum selesai' }}</span>
                                </p>
                                @foreach ($station['attempts'] as $at)
                                    <div class="mt-2 text-sm text-gray-700 dark:text-gray-300 space-y-1">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Mulai {{ $when($at['started_at']) }} · selesai {{ $when($at['completed_at']) }}
                                            @if ($at['total_time_seconds']) · {{ gmdate('H:i:s', (int) $at['total_time_seconds']) }} @endif
                                            @if ($at['auto_submitted']) · waktu habis @endif
                                        </p>
                                        @foreach ($at['answers'] as $ans)
                                            @continue($ans['type'] === 'fun_game')
                                            <p>{{ $ans['question'] }} — <span class="{{ $ans['is_correct'] ? 'text-green-700 dark:text-green-300' : 'text-gray-600 dark:text-gray-400' }}">{{ $ans['answer'] ?: '—' }}</span> @if ($ans['points_earned']) (+{{ $ans['points_earned'] }}) @endif</p>
                                        @endforeach
                                        @foreach ($at['games'] as $g)
                                            <div class="flex flex-wrap items-start gap-3">
                                                <div class="flex-1 min-w-[12rem]">
                                                    <p class="font-medium">{{ $g['game_name'] }}</p>
                                                    @if ($g['is_assessed'])
                                                        <p>Nilai {{ $g['additional_points'] }}/{{ $g['max_points'] }} · penalti {{ $g['penalty'] }} · <span class="{{ $g['gain'] < 0 ? 'text-red-700 dark:text-red-300' : 'text-green-700 dark:text-green-300' }}">{{ $signed($g['gain']) }}</span></p>
                                                        @if ($g['notes']) <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $g['notes'] }}</p> @endif
                                                    @else
                                                        <p class="text-amber-700 dark:text-amber-300">Belum dinilai fasilitator</p>
                                                    @endif
                                                </div>
                                                @if ($g['facilitator_photo'])
                                                    <a href="{{ $photo($g['facilitator_photo']) }}" target="_blank" rel="noopener" class="text-xs text-blue-600 dark:text-blue-400 text-center">
                                                        <img src="{{ $photo($g['facilitator_photo']) }}" alt="Foto fasilitator" loading="lazy" class="h-16 w-16 rounded object-cover border border-gray-200 dark:border-gray-700">
                                                        fasilitator
                                                    </a>
                                                @endif
                                            </div>
                                        @endforeach
                                        @if ($at['verification_photo'])
                                            <a href="{{ $photo($at['verification_photo']) }}" target="_blank" rel="noopener" class="inline-block text-xs text-blue-600 dark:text-blue-400 text-center">
                                                <img src="{{ $photo($at['verification_photo']) }}" alt="Foto verifikasi tim" loading="lazy" class="h-16 w-16 rounded object-cover border border-gray-200 dark:border-gray-700">
                                                verifikasi tim
                                            </a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">Tim ini tidak mengerjakan pos apa pun.</p>
                        @endforelse
                    </div>
                </div>
            </details>
        @endforeach

        @if (! empty($s['accounts_without_team']))
            <p class="text-sm text-gray-600 dark:text-gray-400">Akun tanpa tim yang ikut ditutup: {{ collect($s['accounts_without_team'])->map(fn ($a) => $a['email'])->join(', ') }}</p>
        @endif
    </section>
</div>
@endsection
