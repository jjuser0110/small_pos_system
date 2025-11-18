<meta charset="utf-8" />
<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

<title>Wuks System</title>

<meta name="description" content="" />

<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    }
});
</script>