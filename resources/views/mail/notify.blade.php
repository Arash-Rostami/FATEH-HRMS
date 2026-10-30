<!DOCTYPE html>
<html lang="fa" dir="rtl">
<body style="direction: rtl; text-align: right; font-family: Tahoma, sans-serif;">
<h3 dir="auto" style="text-align: start;">{{ $notify->subject }}</h3>
@foreach ($notify->lines as $line)
    <p dir="auto" style="text-align: start;">{{ $line }}</p>
@endforeach
@if($notify->url)
    <p><a href="{{ $notify->url }}">مشاهده</a></p>
@endif
</body>
</html>
