<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #1a1a2e; margin: 0; padding: 20px; }
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid #1a1a2e; padding-bottom: 10px; }
        .logo-container { text-align: left; }
        .logo-container img { max-height: 120px; }
        .title-container { text-align: right; }
        .title-container h1 { margin: 0; font-size: 20px; color: #1a1a2e; }
        .meta { display: flex; flex-direction: column; gap: 2px; font-size: 10px; margin-top: 8px; opacity: 0.8; text-align: right; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; page-break-inside: auto; border: 1px solid #d1d5db; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        th { background: #1a1a2e; color: white; padding: 8px 10px; text-align: left; font-size: 10px; border: 1px solid #1a1a2e; }
        td { padding: 7px 10px; border: 1px solid #e5e7eb; }
        tr:nth-child(even) td { background: #f9fafb; }
        .footer { margin-top: 30px; font-size: 9px; color: #6b7280; text-align: right; border-top: 1px solid #e5e7eb; padding-top: 10px; }
        .total-row td { font-weight: bold; background: #f0f9ff !important; border-top: 2px solid #1a1a2e; border: 1px solid #d1d5db; }
        .badge { padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef08a; color: #854d0e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #e0f2fe; color: #075985; }
        .badge-gray { background: #f3f4f6; color: #374151; }
        .badge-primary { background: #dbeafe; color: #1e40af; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-container">
            @php
                $logoPath = public_path('assets/images/logo-stacked-dark-tr.png');
                $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
            @endphp
            @if($logoData)
                <img src="data:image/png;base64,{{ $logoData }}" alt="{{ config('store.name', 'VapeGeh') }}">
            @else
                <h2 style="margin:0;">{{ config('store.name', 'VapeGeh') }}</h2>
            @endif
        </div>
        <div class="title-container">
            <h1>{{ $title ?? 'Laporan' }}</h1>
            <div class="meta">
                <span>Waktu Cetak: {{ now()->format('d M Y, H:i') }}</span>
                <span>Oleh: {{ auth()->user()->name ?? 'Sistem' }}</span>
                @if(isset($filterInfo) && filled($filterInfo))
                    <span>Filter: {{ $filterInfo }}</span>
                @endif
            </div>
        </div>
    </div>
    
    @yield('content')
    
    <div class="footer">
        Laporan digenerate otomatis oleh sistem pada {{ now()->format('d/m/Y H:i:s') }}.
    </div>
</body>
</html>
