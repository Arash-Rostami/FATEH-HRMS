@php($c = app(\App\Services\TenantService::class)->mailPalette())
@php($appLogo = tenantLogo(false))
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $notify->subject }}</title>
    <style>
        body,table,td,a{-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}
        table,td{mso-table-lspace:0;mso-table-rspace:0}
        img{-ms-interpolation-mode:bicubic;border:0}
        @media (max-width:620px){
            .px{padding-left:22px!important;padding-right:22px!important}
            .h1{font-size:20px!important}
        }
    </style>
</head>
<body style="margin:0;padding:0;width:100%;background-color:{{ $c['container'] }};direction:rtl;text-align:right;font-family:Vazirmatn,Tahoma,'Segoe UI',Arial,sans-serif;-webkit-font-smoothing:antialiased;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;mso-hide:all;">{{ $notify->lines[0] ?? $notify->subject }}&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:{{ $c['container'] }};">
    <tr>
        <td align="center" style="padding:32px 12px;">
            <table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:560px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 1px 3px rgba(15,23,42,.08),0 8px 24px rgba(15,23,42,.06);">
                <tr>
                    <td class="px" style="background-color:{{ $c['primary'] }};padding:20px 36px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                @if(filled($appLogo) && file_exists(public_path($appLogo)))
                                    <td valign="middle" width="1" style="padding-left:12px;">
                                        <img src="{{ asset($appLogo) }}" alt="{{ config('app.name') }}" height="32" style="display:block;height:32px;width:auto;">
                                    </td>
                                @endif
                                <td valign="middle" style="color:{{ $c['on_primary'] }};font-size:14px;font-weight:bold;">{{ config('app.name') }}</td>
                                @if(filled($notify->event))
                                    <td valign="middle" align="left" style="white-space:nowrap;">
                                        <span style="display:inline-block;border:1px solid {{ $c['on_primary'] }};color:{{ $c['on_primary'] }};font-size:11px;line-height:1;padding:6px 12px;border-radius:999px;">{{ $notify->event }}</span>
                                    </td>
                                @endif
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="px" style="padding:36px 36px 8px 36px;">
                        <div style="color:{{ $c['primary'] }};font-size:12px;font-weight:bold;line-height:1.6;margin:0 0 10px 0;">{{ $notify->modelLabel }}</div>
                        <h1 class="h1" dir="auto" style="text-align:start;margin:0;color:#0f172a;font-size:24px;line-height:1.6;font-weight:bold;">{{ $notify->subject }}</h1>
                    </td>
                </tr>
                <tr>
                    <td class="px" style="padding:16px 36px 32px 36px;">
                        @foreach ($notify->lines as $line)
                            <p dir="auto" style="text-align:start;margin:0 0 16px 0;color:{{ $loop->first ? '#1e293b' : '#475569' }};font-size:{{ $loop->first ? '16' : '15' }}px;line-height:2.1;">{{ $line }}</p>
                        @endforeach
                        @if($notify->url)
                            <a href="{{ $notify->url }}" style="display:inline-block;background-color:{{ $c['primary'] }};color:{{ $c['on_primary'] }};font-size:14px;font-weight:bold;line-height:1;text-decoration:none;padding:15px 28px;border-radius:10px;text-align:center;">{{ $notify->action ?? 'مشاهده' }}</a>
                        @endif
                    </td>
                </tr>
            </table>
            <table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:560px;">
                <tr>
                    <td align="center" style="padding:24px 16px 0 16px;color:{{ $c['on_container'] }};font-size:12px;line-height:2;">
                        @if(filled(config('app.company_logo')) && file_exists(public_path(config('app.company_logo'))))
                            <img src="{{ asset(config('app.company_logo')) }}" alt="{{ config('app.organization_name') }}" height="36" style="display:block;margin:0 auto 10px auto;height:36px;width:auto;">
                        @endif
                        <strong style="font-size:13px;">{{ config('app.organization_name') }}</strong><br>
                        <span style="opacity:.7;">این پیام به‌صورت خودکار از سامانهٔ {{ config('app.name') }} ارسال شده است. لطفاً به آن پاسخ ندهید.</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
