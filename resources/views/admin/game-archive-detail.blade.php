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
    <a href="{{ route('admin.game-archives') }}" class="text-sm text-blue-600 hover:underline">&larr; Semua arsip</a>

    @if (session('success'))
        <div class="rounded-md bg-green-50 border border-green-200 p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $archive->name }}</h1>
        <p class="text-sm text-gray-600">
            Diarsipkan {{ $when($s['archived_at'] ?? $archive->archived_at) }} oleh {{ $s['archived_by']['name'] ?? '—' }}
            · {{ $archive->team_count }} tim · {{ $archive->account_count }} akun
        </p>
        @if (! empty($s['notes']))
            <p class="mt-2 text-gray-700 whitespace-pre-line">{{ $s['notes'] }}</p>
        @endif
    </div>

    <section class="bg-white rounded-lg shadow-sm border border-gray-200">
        <h2 class="p-4 border-b border-gray-200 text-lg font-semibold text-gray-900">Leaderboard akhir</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
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
                <tbody class="divide-y divide-gray-100">
                    @forelse ($s['leaderboard'] ?? [] as $row)
                        <tr>
                            <td class="px-4 py-2 font-semibold">{{ $row['rank'] }}</td>
                            <td class="px-4 py-2 font-medium text-gray-900">{{ $row['name'] }}</td>
                            <td class="px-4 py-2 text-right font-semibold tabular-nums">{{ number_format($row['total']) }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">{{ number_format($row['base_points']) }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">{{ number_format($row['earned_points']) }}</td>
                            <td class="px-4 py-2 text-right tabular-nums {{ $row['assessment_points'] < 0 ? 'text-red-700' : '' }}">{{ $signed($row['assessment_points']) }}</td>
                            <td class="px-4 py-2">{{ $row['stations_done'] }}/{{ $row['stations_total'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-3 text-gray-500">Tidak ada tim dalam sesi ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="space-y-3">
        <h2 class="text-lg font-semibold text-gray-900">Rincian tim</h2>
        @foreach ($s['teams'] ?? [] as $team)
            <details class="bg-white rounded-lg shadow-sm border border-gray-200">
                <summary class="p-4 cursor-pointer flex flex-wrap items-baseline gap-x-3">
                    <span class="font-semibold text-gray-900">#{{ $team['rank'] }} {{ $team['name'] }}</span>
                    <span class="text-sm text-gray-600 tabular-nums">{{ number_format($team['score']['total']) }} poin · pos {{ $team['stations_done'] }}/{{ $team['stations_total'] }}</span>
                </summary>
                <div class="px-4 pb-4 space-y-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 mb-1">Anggota</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($team['members'] as $m)
                                        <tr>
                                            <td class="py-1 pr-4">{{ $m['name'] }}@if ($m['is_leader']) <span class="text-xs text-violet-700">(ketua)</span>@endif</td>
                                            <td class="py-1 pr-4 text-gray-600">{{ $m['email'] }}</td>
                                            <td class="py-1 pr-4 text-gray-600">{{ $m['phone'] ?? '' }}</td>
                                            <td class="py-1 text-gray-600">{{ $m['position'] ?? '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if (! empty($team['accounts']))
                            <p class="mt-1 text-xs text-gray-500">Akun login: {{ collect($team['accounts'])->map(fn ($a) => $a['email'])->join(', ') }}</p>
                        @endif
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 mb-1">Per pos</h3>
                        @forelse ($team['stations'] as $station)
                            <div class="border border-gray-100 rounded-md p-3 mb-2">
                                <p class="font-medium text-gray-900">
                                    {{ $station['title'] }}
                                    <span class="text-xs {{ $station['completed'] ? 'text-green-700' : 'text-amber-700' }}">{{ $station['completed'] ? 'selesai' : 'belum selesai' }}</span>
                                </p>
                                @foreach ($station['attempts'] as $at)
                                    <div class="mt-2 text-sm text-gray-700 space-y-1">
                                        <p class="text-xs text-gray-500">
                                            Mulai {{ $when($at['started_at']) }} · selesai {{ $when($at['completed_at']) }}
                                            @if ($at['total_time_seconds']) · {{ gmdate('H:i:s', (int) $at['total_time_seconds']) }} @endif
                                            @if ($at['auto_submitted']) · waktu habis @endif
                                        </p>
                                        @foreach ($at['answers'] as $ans)
                                            @continue($ans['type'] === 'fun_game')
                                            <p>{{ $ans['question'] }} — <span class="{{ $ans['is_correct'] ? 'text-green-700' : 'text-gray-600' }}">{{ $ans['answer'] ?: '—' }}</span> @if ($ans['points_earned']) (+{{ $ans['points_earned'] }}) @endif</p>
                                        @endforeach
                                        @foreach ($at['games'] as $g)
                                            <div class="flex flex-wrap items-start gap-3">
                                                <div class="flex-1 min-w-[12rem]">
                                                    <p class="font-medium">{{ $g['game_name'] }}</p>
                                                    @if ($g['is_assessed'])
                                                        <p>Nilai {{ $g['additional_points'] }}/{{ $g['max_points'] }} · penalti {{ $g['penalty'] }} · <span class="{{ $g['gain'] < 0 ? 'text-red-700' : 'text-green-700' }}">{{ $signed($g['gain']) }}</span></p>
                                                        @if ($g['notes']) <p class="text-gray-600 whitespace-pre-line">{{ $g['notes'] }}</p> @endif
                                                    @else
                                                        <p class="text-amber-700">Belum dinilai fasilitator</p>
                                                    @endif
                                                </div>
                                                @if ($g['facilitator_photo'])
                                                    <a href="{{ $photo($g['facilitator_photo']) }}" target="_blank" rel="noopener" class="text-xs text-blue-600 text-center">
                                                        <img src="{{ $photo($g['facilitator_photo']) }}" alt="Foto fasilitator" loading="lazy" class="h-16 w-16 rounded object-cover border border-gray-200">
                                                        fasilitator
                                                    </a>
                                                @endif
                                            </div>
                                        @endforeach
                                        @if ($at['verification_photo'])
                                            <a href="{{ $photo($at['verification_photo']) }}" target="_blank" rel="noopener" class="inline-block text-xs text-blue-600 text-center">
                                                <img src="{{ $photo($at['verification_photo']) }}" alt="Foto verifikasi tim" loading="lazy" class="h-16 w-16 rounded object-cover border border-gray-200">
                                                verifikasi tim
                                            </a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Tim ini tidak mengerjakan pos apa pun.</p>
                        @endforelse
                    </div>
                </div>
            </details>
        @endforeach

        @if (! empty($s['accounts_without_team']))
            <p class="text-sm text-gray-600">Akun tanpa tim yang ikut ditutup: {{ collect($s['accounts_without_team'])->map(fn ($a) => $a['email'])->join(', ') }}</p>
        @endif
    </section>
</div>
@endsection
