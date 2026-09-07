<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ config('app.name','Dashboard Pertanahan') }}</title>@vite(['resources/css/app.css','resources/js/app.js'])</head><body><div class="ui-shell"><aside id="sidebar" class="ui-sidebar"><div class="brand"><img src="{{ asset('atr-bpn-logo.png') }}" class="brand-logo"><strong>DASHBOARD<br>PERTANAHAN</strong></div><nav>
@if(auth()->user()->hasActiveRole('operator_pemda') || auth()->user()->hasActiveRole('operator_kantah'))<a href="{{ route('data-entry') }}" class="nav-item">Input Data</a>@endif
@if(auth()->user()->hasActiveRole('super_admin'))<a href="{{ route('review') }}" class="nav-item">Review Data</a>@endif
@foreach($categories as $category)
<div class="nav-group"><div class="nav-label">{{ $category->name }}</div>
@foreach($category->submenus as $submenu)<a href="{{ route('dashboard',['submenu'=>$submenu->id,'region'=>$selectedRegion?->id,'date'=>$selectedDate]) }}" class="submenu-link nav-item {{ $selectedSubmenu?->id === $submenu->id ? 'active' : '' }}">{{ $submenu->name }}</a>
@endforeach</div>
@endforeach
</nav></aside><main class="ui-main"><header class="topbar"><button id="open-sidebar" class="menu-button" type="button">☰</button><div><h1>Dashboard Pertanahan</h1></div><div class="top-actions"><button id="theme-toggle" class="theme-button" type="button">☾</button><div class="profile-mini"><span class="avatar">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span><span><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->email }}</small></span></div><form method="post" action="{{ route('web.logout') }}">@csrf<button class="logout-button">Keluar</button></form></div></header>@yield('content')</main></div></body></html>
