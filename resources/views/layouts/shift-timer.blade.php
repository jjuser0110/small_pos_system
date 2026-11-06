{{-- Staff only: log out automatically when the shift window closes (shift end + grace),
     even if the page is left open. Seconds come from the server so a wrong PC clock does not matter. --}}
@auth
    @php $shiftWindow = Auth::user()->isStaff() ? Auth::user()->currentShiftWindow() : null; @endphp
    @if($shiftWindow)
        @php $secondsLeft = max(0, now()->diffInSeconds($shiftWindow->logoutAt, false)); @endphp
        <form id="shift-auto-logout" action="{{ route('logout') }}" method="POST" style="display:none">
            @csrf
        </form>
        <div id="shift-ending-banner" style="display:none;position:fixed;bottom:12px;left:50%;transform:translateX(-50%);z-index:99999;background:#fff3cd;color:#664d03;border:1px solid #ffecb5;border-radius:8px;padding:8px 14px;font:14px sans-serif;box-shadow:0 4px 14px rgba(0,0,0,.15)">
            Your {{ $shiftWindow->shift }} shift ended at {{ $shiftWindow->end->format('H:i') }}. You will be logged out at {{ $shiftWindow->logoutAt->format('H:i') }}.
        </div>
        <script>
            (function () {
                var secondsLeft = {{ (int) $secondsLeft }};
                var graceSeconds = {{ (int) config('fsmart.grace_minutes') * 60 }};
                var banner = document.getElementById('shift-ending-banner');
                function showBanner() { if (banner) banner.style.display = 'block'; }
                if (secondsLeft <= graceSeconds) { showBanner(); }
                else { setTimeout(showBanner, (secondsLeft - graceSeconds) * 1000); }
                setTimeout(function () { document.getElementById('shift-auto-logout').submit(); }, secondsLeft * 1000);
            })();
        </script>
    @endif
@endauth
