@php
    $formatValue = static function ($item): string {
        $revision = $item->latestRevision;
        $value = $revision?->value_decimal ?? $revision?->value_integer ?? $revision?->value_text;
        if ($value === null || $value === '') return 'Data belum tersedia';
        $unit = trim((string) ($item->indicator?->unit ?? ''));
        if (! is_numeric($value)) return (string) $value;
        $number = (float) $value;
        $normalizedUnit = strtolower($unit);
        if (in_array($normalizedUnit, ['rp', 'rupiah'], true)) return 'Rp'.number_format($number, 0, ',', '.');
        if ($unit === '%') return number_format($number, 2, ',', '.').'%';
        $decimals = abs($number - round($number)) < 0.000001 ? 0 : 2;
        return trim(number_format($number, $decimals, ',', '.').' '.$unit);
    };
@endphp
@extends('layouts.app')
@section('content')
<div class="dashboard-layout">
    <section>
        <div class="page-intro"><div><h2>Dashboard {{ $submenu->name }}</h2><p>{{ $selectedRegion?->name ?? 'Jawa Barat' }} � Periode {{ optional($snapshot?->as_of_date)->format('d F Y') ?? '4 Agustus 2026' }}</p><form method="GET" action="{{ route('dashboard') }}" class="dashboard-filter"><input type="hidden" name="submenu" value="{{ $selectedSubmenu->id }}"><label>Wilayah<select name="region">@foreach($allowed as $item)<option value="{{ $item->id }}" @selected($selectedRegion?->id === $item->id)>{{ $item->name }}</option>@endforeach</select></label><label>Periode<select name="date">@foreach($snapshots as $item)<option value="{{ $item->as_of_date->format('Y-m-d') }}" @selected($selectedDate === $item->as_of_date->format('Y-m-d'))>{{ $item->as_of_date->format('d F Y') }}</option>@endforeach</select></label><button type="submit">Terapkan</button></form></div></div>
        @if($observations->isNotEmpty())
            <div class="metric-grid">
                @foreach($observations->take(3) as $observation)
                    <article class="metric-card"><span class="metric-label">{{ $observation->indicator->name }}</span><strong>{{ $formatValue($observation) }}</strong></article>
                @endforeach
            </div>
        @endif
        <div class="draft-banner">⚠ PRATINJAU DRAFT — BELUM DIPUBLIKASIKAN</div>
        <div class="panel"><div class="panel-heading"><h3>Data indikator {{ $selectedRegion?->name ?? 'Jawa Barat' }}</h3><span class="status-dot">● Terhubung</span></div>
            @if($observations->isEmpty())<div class="empty-state">Data belum tersedia</div>@else
            <div class="table-wrap"><table><thead><tr><th>Indikator</th><th>Wilayah</th><th>Nilai</th><th>Sumber</th><th>Status</th></tr></thead><tbody>
            @foreach($observations as $observation)<tr><td><b>{{ $observation->indicator->name }}</b></td><td>{{ $observation->region->name }}</td><td>{{ $formatValue($observation) }}</td><td>{{ $observation->source->name ?? '-' }}</td><td>@if($observation->latestRevision === null)<span class="no-data-badge">No data</span>@elseif(!$observation->publishedValue)<span class="draft-badge">Draft</span>@else<span class="published-badge">Published</span>@endif</td></tr>@endforeach
            </tbody></table></div>@endif
        </div>
    </section>
    <aside class="right-panel"><div class="side-card"><div class="side-title">Profil pengguna</div><div class="profile-large"><span class="avatar avatar-lg">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span><h3>{{ auth()->user()->name }}</h3><p>{{ auth()->user()->email }}</p><small>Kementerian ATR/BPN</small></div></div><div class="side-card"><div class="side-title">Status sistem</div><div class="system-row"><span class="ok-dot"></span><span>Excel Jawa Barat</span><b>Aktif</b></div><div class="system-row"><span class="ok-dot"></span><span>Snapshot</span><b>04 Agu 2026</b></div><div class="system-row"><span class="ok-dot"></span><span>Publikasi</span><b>Pratinjau</b></div></div></aside>
</div>
@endsection
