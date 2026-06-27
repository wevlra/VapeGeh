@props(['location' => null])
<div style="text-align: center; margin-bottom: 16px;">
    <img src="{{ public_path('assets/images/logo-light-tr.png') }}" alt="{{ config('store.name') }}" style="max-height: 60px; margin-bottom: 8px;">
    <div style="font-size: 16px; font-weight: bold;">{{ config('store.name') }}</div>
    <div style="font-size: 11px;">{{ $location?->address ?? config('store.address') }}</div>
    <div style="font-size: 11px;">{{ config('store.phone') }}</div>
</div>
