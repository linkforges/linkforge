@php
    $brand = config('linkforge.theme.brand.600') ?: '#059669';
    $appName = config('linkforge.name');
    $appUrl = config('app.url');
    $logo = config('linkforge.logo');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;">
                    <tr>
                        <td style="padding:22px 32px;border-bottom:1px solid #f1f5f9;">
                            @if ($logo)
                                <img src="{{ $logo }}" alt="{{ $appName }}" style="height:28px;max-width:160px;">
                            @else
                                <span style="font-size:18px;font-weight:700;color:#0f172a;">{{ $appName }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;color:#334155;font-size:15px;line-height:1.65;">
                            {!! nl2br(e($bodyText)) !!}
                            @if ($actionUrl && $actionText)
                                <div style="margin-top:28px;">
                                    <a href="{{ $actionUrl }}" style="display:inline-block;background:{{ $brand }};color:#ffffff;text-decoration:none;font-weight:600;font-size:14px;padding:12px 24px;border-radius:10px;">{{ $actionText }}</a>
                                </div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 32px;border-top:1px solid #f1f5f9;color:#94a3b8;font-size:12px;">
                            Sent by {{ $appName }} &middot; <a href="{{ $appUrl }}" style="color:#94a3b8;">{{ $appUrl }}</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
