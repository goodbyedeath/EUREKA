<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>QR START</title>
<style>
    @page { margin: {{ $large ? '18mm' : '10mm' }}; }
    body { font-family: "DejaVu Sans", sans-serif; color: #111827; font-size: 9pt; }
    .brand { font-size: 8pt; color: #6b7280; letter-spacing: .5pt; text-transform: uppercase; }
    .warn { color: #b45309; font-weight: bold; }
    .mono { font-family: "DejaVu Sans Mono", monospace; color: #374151; }

    /* grid */
    table.sheet { width: 100%; border-collapse: separate; border-spacing: 6mm 5mm; }
    td.card { width: 50%; border: 1px dashed #9ca3af; padding: 5mm; vertical-align: top; text-align: center; page-break-inside: avoid; }
    td.card .route { font-size: 15pt; font-weight: bold; margin: 1mm 0; }
    td.card .name { font-size: 10pt; color: #374151; }
    td.card img { width: 52mm; height: 52mm; margin: 2mm 0; }

    /* one per page */
    .page { text-align: center; page-break-after: always; }
    .page:last-child { page-break-after: auto; }
    .page .route { font-size: 34pt; font-weight: bold; margin: 6mm 0 1mm; }
    .page .name { font-size: 15pt; color: #374151; }
    .page img { width: 145mm; height: 145mm; margin: 6mm 0 4mm; }
    .page .hint { font-size: 12pt; color: #374151; }
    .page .clue { font-size: 11pt; color: #6b7280; margin-top: 3mm; }
</style>
</head>
<body>
@if ($large)
    @foreach ($cards as $card)
        <div class="page">
            <div class="brand">{{ $brand }} · QR START</div>
            {{-- The route first and biggest: the only thing that must not be mixed up when handing
                 these out, because it decides the order the team walks. --}}
            <div class="route">{{ $card['route'] ?? 'Outdoor' }}</div>
            <div class="name">{{ $card['name'] }}</div>
            <img src="{{ $card['qr'] }}" alt="">
            <div class="hint">Satu tim satu kode. Scan ini untuk memulai — jam race berjalan sejak dipindai.</div>
            @if ($card['clue'])
                <div class="clue">Pertanyaan START: {{ $card['clue'] }}</div>
            @endif
            <div class="mono" style="margin-top:4mm">{{ $card['code'] }}@unless ($card['active']) · <span class="warn">nonaktif</span> @endunless</div>
        </div>
    @endforeach
@else
    <table class="sheet">
        @foreach ($cards->chunk(2) as $row)
            <tr>
                @foreach ($row as $card)
                    <td class="card">
                        <div class="brand">{{ $brand }} · QR START</div>
                        <div class="route">{{ $card['route'] ?? 'Outdoor' }}</div>
                        <div class="name">{{ $card['name'] }}</div>
                        <img src="{{ $card['qr'] }}" alt="">
                        <div class="mono">{{ $card['code'] }}@unless ($card['active']) · <span class="warn">nonaktif</span> @endunless</div>
                    </td>
                @endforeach
                @if ($row->count() === 1)
                    <td></td>
                @endif
            </tr>
        @endforeach
    </table>
    <p style="margin-top:6mm; font-size:8pt; color:#6b7280">
        Satu tim satu kode. Nama rute di tiap kartu menentukan urutan pos tim itu; jam race berjalan sejak dipindai.
        Kode boleh dipakai berulang oleh tim berbeda dan di sesi berikutnya.
    </p>
@endif
</body>
</html>
