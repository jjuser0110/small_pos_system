{{-- Counter banner: things waiting for the logged-in user (e.g. a stock transfer to accept).
     Checked on load and every 60 seconds. --}}
<div id="counter-notifications" style="position:fixed;top:10px;right:10px;z-index:9998;max-width:360px;font-family:'Segoe UI',sans-serif"></div>
<script>
    (function () {
        var box = document.getElementById('counter-notifications');
        function render(items) {
            box.innerHTML = '';
            items.forEach(function (item) {
                var a = document.createElement('a');
                a.href = item.url;
                a.textContent = '🔔 ' + item.text;
                a.style.cssText = 'display:block;margin-bottom:8px;padding:10px 14px;border-radius:8px;background:#fff3cd;color:#664d03;border:1px solid #ffda6a;text-decoration:none;font-size:13px;font-weight:600;box-shadow:0 4px 14px rgba(0,0,0,.15)';
                box.appendChild(a);
            });
        }
        function check() {
            fetch('{{ route('notifications') }}', { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : { items: [] }; })
                .then(function (data) { render(data.items || []); })
                .catch(function () {});
        }
        check();
        setInterval(check, 60000);
    })();
</script>
