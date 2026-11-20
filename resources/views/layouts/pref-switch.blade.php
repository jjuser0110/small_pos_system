{{-- Language menu and light / dark toggle. $variant: 'nav' (admin top bar) or 'plain' (counter, login). --}}
@php
    $variant = $variant ?? 'nav';
    $currentLocale = \App\Support\Preferences::locale();
    $isDark = \App\Support\Preferences::dark();
    $short = ['en' => 'EN', 'ms' => 'BM', 'zh_CN' => '中'];
@endphp
@if($variant === 'nav')
    <li class="nav-item dropdown me-2 me-xl-1">
        <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown" title="{{ __('Language') }}">
            <i class="bx bx-globe bx-sm"></i> <span class="fw-semibold">{{ $short[$currentLocale] }}</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
            @foreach(\App\Support\Preferences::LOCALES as $code => $name)
                <li>
                    <form method="post" action="{{ route('preferences.update') }}" class="m-0">
                        @csrf
                        <input type="hidden" name="locale" value="{{ $code }}">
                        <button type="submit" class="dropdown-item {{ $code === $currentLocale ? 'active' : '' }}">{{ $name }}</button>
                    </form>
                </li>
            @endforeach
        </ul>
    </li>
    <li class="nav-item me-2 me-xl-1">
        <form method="post" action="{{ route('preferences.update') }}" class="m-0">
            @csrf
            <input type="hidden" name="theme" value="{{ $isDark ? 'light' : 'dark' }}">
            <button type="submit" class="nav-link btn btn-link px-2" title="{{ $isDark ? __('Light mode') : __('Dark mode') }}">
                <i class="bx {{ $isDark ? 'bx-sun' : 'bx-moon' }} bx-sm"></i>
            </button>
        </form>
    </li>
@else
    <div class="pref-switch" style="display:inline-flex;gap:6px;align-items:center">
        <form method="post" action="{{ route('preferences.update') }}" style="margin:0">
            @csrf
            <select name="locale" onchange="this.form.submit()" title="{{ __('Language') }}"
                    style="padding:6px 8px;border-radius:8px;border:1px solid #ccc;font-size:13px;cursor:pointer">
                @foreach(\App\Support\Preferences::LOCALES as $code => $name)
                    <option value="{{ $code }}" @selected($code === $currentLocale)>{{ $name }}</option>
                @endforeach
            </select>
        </form>
        <form method="post" action="{{ route('preferences.update') }}" style="margin:0">
            @csrf
            <input type="hidden" name="theme" value="{{ $isDark ? 'light' : 'dark' }}">
            <button type="submit" title="{{ $isDark ? __('Light mode') : __('Dark mode') }}"
                    style="padding:6px 10px;border-radius:8px;border:1px solid #ccc;background:{{ $isDark ? '#2d2d40' : '#fff' }};color:{{ $isDark ? '#fde047' : '#333' }};cursor:pointer;font-size:14px">
                {{ $isDark ? '☀' : '☾' }}
            </button>
        </form>
    </div>
@endif
