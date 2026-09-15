<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Kartu Login Tim</title>
<style>
    @page { margin: 10mm; }
    body { font-family: "DejaVu Sans", sans-serif; color: #111827; font-size: 9pt; }
    table.sheet { width: 100%; border-collapse: separate; border-spacing: 6mm 5mm; }
    td.card { width: 50%; border: 1px dashed #9ca3af; padding: 5mm; vertical-align: top; page-break-inside: avoid; }
    .brand { font-size: 8pt; color: #6b7280; letter-spacing: 0.5pt; text-transform: uppercase; }
    .title { font-size: 13pt; font-weight: bold; margin: 1mm 0 3mm; }
    .qr { text-align: center; }
    .qr img { width: 42mm; height: 42mm; }
    .hint { font-size: 8pt; color: #374151; text-align: center; margin: 2mm 0 3mm; }
    .fallback { font-size: 7.5pt; color: #4b5563; border-top: 1px solid #e5e7eb; padding-top: 2mm; }
    .mono { font-family: "DejaVu Sans Mono", monospace; color: #111827; }
    .batch { font-size: 6.5pt; color: #9ca3af; text-align: right; }
</style>
</head>
<body>
<table class="sheet">
    @foreach ($cards->chunk(2) as $row)
        <tr>
            @foreach ($row as $card)
                <td class="card">
                    <div class="brand">{{ $brand }} · Kartu Login Tim</div>
                    <div class="title">{{ $card['name'] }}</div>
                    <div class="qr"><img src="{{ $card['qr'] }}" alt=""></div>
                    <div class="hint">Buka aplikasi → <strong>Scan kartu login</strong> → arahkan kamera ke QR ini.</div>
                    <div class="fallback">
                        Tanpa kamera? Login manual:<br>
                        Email: <span class="mono">{{ $card['email'] }}</span><br>
                        Password: <span class="mono">{{ $card['password'] }}</span>
                    </div>
                    <div class="batch">batch {{ $card['batch'] }} · simpan kartu ini, jangan dibagikan ke tim lain</div>
                </td>
            @endforeach
            @if ($row->count() === 1)
                <td></td>
            @endif
        </tr>
    @endforeach
</table>
</body>
</html>
