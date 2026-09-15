<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>QR Kuesioner</title>
<style>
    @page { margin: {{ $large ? '18mm' : '10mm' }}; }
    body { font-family: "DejaVu Sans", sans-serif; color: #111827; font-size: 9pt; }
    .brand { font-size: 8pt; color: #6b7280; letter-spacing: .5pt; text-transform: uppercase; }
    .warn { color: #b45309; font-weight: bold; }
    .mono { font-family: "DejaVu Sans Mono", monospace; color: #374151; }

    /* grid */
    table.sheet { width: 100%; border-collapse: separate; border-spacing: 6mm 5mm; }
    td.card { width: 50%; border: 1px dashed #9ca3af; padding: 5mm; vertical-align: top; text-align: center; page-break-inside: avoid; }
    td.card .title { font-size: 12pt; font-weight: bold; margin: 1mm 0 1mm; }
    td.card img { width: 52mm; height: 52mm; margin: 2mm 0; }

    /* one per page */
    .page { text-align: center; page-break-after: always; }
    .page:last-child { page-break-after: auto; }
    .page .title { font-size: 26pt; font-weight: bold; margin: 6mm 0 2mm; }
    .page .post { font-size: 14pt; color: #374151; }
    .page img { width: 150mm; height: 150mm; margin: 8mm 0 4mm; }
    .page .hint { font-size: 13pt; color: #374151; }
</style>
</head>
<body>
@if ($large)
    @foreach ($cards as $card)
        <div class="page">
            <div class="brand">{{ $brand }} · Pos kuesioner</div>
            <div class="title">{{ $card['title'] }}</div>
            @if ($card['post'])
                <div class="post">{{ $card['post'] }}</div>
            @else
                <div class="post warn">Belum ada pos — QR ini belum bisa discan</div>
            @endif
            <img src="{{ $card['qr'] }}" alt="">
            <div class="hint">Scan QR START dan check-in di pos ini dulu, lalu scan QR ini di aplikasi.</div>
            <div class="mono" style="margin-top:4mm">{{ $card['code'] }}@if (! $card['active']) · nonaktif @endif</div>
        </div>
    @endforeach
@else
    <table class="sheet">
        @foreach ($cards->chunk(2) as $row)
            <tr>
                @foreach ($row as $card)
                    <td class="card">
                        <div class="brand">{{ $brand }} · Pos kuesioner</div>
                        <div class="title">{{ $card['title'] }}</div>
                        @if ($card['post'])
                            <div>{{ $card['post'] }}</div>
                        @else
                            <div class="warn">Belum ada pos — belum bisa discan</div>
                        @endif
                        <img src="{{ $card['qr'] }}" alt="">
                        <div class="mono">{{ $card['code'] }}@if (! $card['active']) · nonaktif @endif</div>
                    </td>
                @endforeach
                @if ($row->count() === 1)
                    <td></td>
                @endif
            </tr>
        @endforeach
    </table>
@endif
</body>
</html>
