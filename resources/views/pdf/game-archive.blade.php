@php
    $tz = config('app.timezone');
    $when = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->timezone($tz)->format('d M Y H:i') : '—';
    $num = fn ($n) => ($n < 0 ? '−' : '') . strrev(implode('.', str_split(strrev((string) abs((int) $n)), 3)));
    $signed = fn ($n) => is_null($n) ? '—' : (($n > 0 ? '+' : '') . $num($n));
    $brand = \App\Models\BrandSetting::appName();
@endphp
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>{{ $archive->name }}</title>
<style>
    @page { margin: 18mm 14mm 16mm 14mm; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 9.5pt; color: #1f2937; line-height: 1.35; }
    h1 { font-size: 17pt; margin: 0 0 2pt; color: #111827; }
    h2 { font-size: 12pt; margin: 14pt 0 6pt; color: #111827; border-bottom: 1px solid #d1d5db; padding-bottom: 3pt; }
    h3 { font-size: 10.5pt; margin: 10pt 0 4pt; color: #111827; }
    .muted { color: #6b7280; }
    .small { font-size: 8.5pt; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; font-weight: bold; color: #374151; background: #f3f4f6; padding: 4pt 5pt; border-bottom: 1px solid #d1d5db; }
    td { padding: 3.5pt 5pt; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
    .num { text-align: right; white-space: nowrap; }
    .neg { color: #b91c1c; }
    .pos { color: #15803d; }
    .warn { color: #b45309; }
    .team { page-break-before: always; }
    .station { border: 1px solid #e5e7eb; padding: 5pt 7pt; margin: 0 0 6pt; page-break-inside: avoid; }
    .photo { width: 88pt; height: auto; border: 1px solid #d1d5db; margin: 3pt 6pt 0 0; }
    .caption { font-size: 7.5pt; color: #6b7280; }
    .footer { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 7.5pt; color: #9ca3af; text-align: center; }
</style>
</head>
<body>
<div class="footer">{{ $brand }} · {{ $archive->name }} · dicetak {{ now()->timezone($tz)->format('d M Y H:i') }}</div>

<h1>{{ $archive->name }}</h1>
<p class="muted">
    Laporan akhir sesi · diarsipkan {{ $when($s['archived_at'] ?? $archive->archived_at) }}
    oleh {{ $s['archived_by']['name'] ?? '—' }} · {{ $archive->team_count }} tim · {{ $archive->account_count }} akun
</p>
@if (! empty($s['notes']))
    <p>{{ $s['notes'] }}</p>
@endif

<h2>Leaderboard akhir</h2>
<table>
    <thead>
        <tr>
            <th style="width:36pt">Peringkat</th>
            <th>Tim</th>
            <th class="num">Total</th>
            <th class="num">Awal</th>
            <th class="num">Kuis</th>
            <th class="num">Game</th>
            <th class="num">Pos selesai</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($s['leaderboard'] ?? [] as $row)
            <tr>
                <td><strong>{{ $row['rank'] }}</strong></td>
                <td>{{ $row['name'] }}</td>
                <td class="num"><strong>{{ $num($row['total']) }}</strong></td>
                <td class="num">{{ $num($row['base_points']) }}</td>
                <td class="num">{{ $num($row['earned_points']) }}</td>
                <td class="num {{ $row['assessment_points'] < 0 ? 'neg' : '' }}">{{ $signed($row['assessment_points']) }}</td>
                <td class="num">{{ $row['stations_done'] }}/{{ $row['stations_total'] }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="muted">Tidak ada tim dalam sesi ini.</td></tr>
        @endforelse
    </tbody>
</table>
<p class="small muted">
    Total = poin awal + poin kuis (jawaban benar) + poin game (nilai fasilitator dikurangi penalti).
    Hanya kuesioner yang sudah diserahkan yang dihitung.
</p>

@foreach ($s['teams'] ?? [] as $team)
    <div class="team">
        <h2>#{{ $team['rank'] }} · {{ $team['name'] }}</h2>
        <p>
            <strong>{{ $num($team['score']['total']) }} poin</strong>
            <span class="muted">
                ({{ $num($team['score']['base_points']) }} awal
                + {{ $num($team['score']['earned_points']) }} kuis
                {{ $team['score']['assessment_points'] < 0 ? '−' : '+' }} {{ $num(abs($team['score']['assessment_points'])) }} game)
                · pos selesai {{ $team['stations_done'] }}/{{ $team['stations_total'] }}
                @if (! empty($team['department'])) · {{ $team['department'] }} @endif
            </span>
        </p>

        <h3>Anggota</h3>
        <table>
            <thead><tr><th>Nama</th><th>Email</th><th>Telepon</th><th>Posisi</th></tr></thead>
            <tbody>
                @forelse ($team['members'] as $m)
                    <tr>
                        <td>{{ $m['name'] }}@if ($m['is_leader']) <span class="muted">(ketua)</span>@endif</td>
                        <td>{{ $m['email'] }}</td>
                        <td>{{ $m['phone'] ?? '' }}</td>
                        <td>{{ $m['position'] ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">—</td></tr>
                @endforelse
            </tbody>
        </table>

        <h3>Per pos</h3>
        @forelse ($team['stations'] as $station)
            <div class="station">
                <strong>{{ $station['title'] }}</strong>
                <span class="{{ $station['completed'] ? 'pos' : 'warn' }}">· {{ $station['completed'] ? 'selesai' : 'belum selesai' }}</span>
                @foreach ($station['attempts'] as $at)
                    <div class="small muted">
                        Mulai {{ $when($at['started_at']) }} · selesai {{ $when($at['completed_at']) }}
                        @if ($at['total_time_seconds']) · durasi {{ gmdate('H:i:s', (int) $at['total_time_seconds']) }} @endif
                        @if ($at['auto_submitted']) · waktu habis @endif
                    </div>

                    @php($answers = collect($at['answers'])->reject(fn ($a) => $a['type'] === 'fun_game'))
                    @if ($answers->isNotEmpty())
                        <table class="small">
                            <tbody>
                                @foreach ($answers as $ans)
                                    <tr>
                                        <td>{{ $ans['question'] }}</td>
                                        <td style="width:30%">{{ $ans['answer'] ?: '—' }}</td>
                                        <td class="num {{ $ans['is_correct'] ? 'pos' : '' }}" style="width:40pt">{{ $ans['is_correct'] ? '+'.$ans['points_earned'] : '0' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    @foreach ($at['games'] as $g)
                        <div style="margin-top:3pt">
                            <strong>{{ $g['game_name'] }}</strong>:
                            @if ($g['is_assessed'])
                                nilai {{ $g['additional_points'] }}/{{ $g['max_points'] }}, penalti {{ $g['penalty'] }},
                                <span class="{{ $g['gain'] < 0 ? 'neg' : 'pos' }}">{{ $signed($g['gain']) }}</span>
                                @if ($g['notes'])<div class="small" style="margin-top:2pt">Catatan: {!! nl2br(e($g['notes'])) !!}</div>@endif
                            @else
                                <span class="warn">belum dinilai fasilitator</span>
                            @endif
                        </div>
                    @endforeach

                    @if ($withPhotos)
                        @php($shots = collect($at['games'])->pluck('facilitator_photo')->prepend($at['verification_photo'])->filter(fn ($k) => $k && isset($images[$k])))
                        @if ($shots->isNotEmpty())
                            <table style="width:auto; margin-top:6pt"><tr>
                                @foreach ($shots as $k)
                                    <td style="border:0; padding:0 8pt 0 0; text-align:center">
                                        <img class="photo" src="{{ $images[$k] }}" alt="">
                                        <div class="caption">{{ str_starts_with($k, 'v-') ? 'verifikasi tim' : 'fasilitator' }}</div>
                                    </td>
                                @endforeach
                            </tr></table>
                        @endif
                    @endif
                @endforeach
            </div>
        @empty
            <p class="muted">Tim ini tidak mengerjakan pos apa pun.</p>
        @endforelse
    </div>
@endforeach

@if (! empty($s['accounts_without_team']))
    <h2>Akun tanpa tim</h2>
    <p class="small">{{ collect($s['accounts_without_team'])->map(fn ($a) => $a['name'].' <'.$a['email'].'>')->join(', ') }}</p>
@endif
</body>
</html>
