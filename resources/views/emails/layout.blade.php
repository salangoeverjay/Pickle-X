{{--
    Shared email frame: 600px column, wordmark + label, white card, footer.
    Email-safe HTML: tables for layout, inline styles, no SVG. Dark mode for clients
    that support prefers-color-scheme (Apple Mail, iOS); others get the light version.
    Sections: label, content, why. $footerLink = [label, url].
--}}
@php($footerLink ??= ['Notification settings', route('settings')])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>PickleX</title>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700..800&amp;family=Instrument+Sans:wght@400..700&amp;family=Barlow+Condensed:wght@500;600;700&amp;display=swap" rel="stylesheet">
    <style>
        body { margin: 0; padding: 0; -webkit-text-size-adjust: 100%; }
        a { color: #12170F; }
        @media (max-width: 540px) {
            .em-outer { padding: 18px 12px 32px !important; }
            .em-pad { padding: 24px 20px 26px !important; }
            .em-h1 { font-size: 30px !important; }
            .em-btn { display: block !important; text-align: center !important; }
            .em-half { display: inline-block !important; width: 50% !important; box-sizing: border-box; }
            .em-full { display: block !important; width: 100% !important; box-sizing: border-box; }
            .em-code { font-size: 38px !important; }
        }
        @media (prefers-color-scheme: dark) {
            .em-page { background: #0A0E0B !important; }
            .em-card { background: #131A15 !important; border-color: #26312A !important; }
            .em-soft { background: #1A221C !important; border-color: #36433B !important; }
            .em-ink { color: #EDF2E3 !important; }
            .em-ink2 { color: #B1BBAA !important; }
            .em-ink3 { color: #8E988B !important; }
            .em-line { border-color: #26312A !important; }
            .em-lime-text { color: #D4F53C !important; }
            .em-lime-soft { background: rgba(212, 245, 60, .10) !important; }
            .em-ink-bg { background: #EDF2E3 !important; color: #131A15 !important; }
            .em-hot-soft { background: rgba(255, 145, 82, .12) !important; border-color: rgba(255, 145, 82, .42) !important; }
            .em-hot { color: #FF9152 !important; }
            .em-court-bg { background: #2F62AD !important; border-color: #2F62AD !important; }
        }
    </style>
</head>
<body class="em-page" style="margin: 0; padding: 0; background: #F3F2EA;">
    <div style="display: none; max-height: 0; overflow: hidden; opacity: 0; mso-hide: all;">{{ $preheader }}&#8199;&#847;&#8199;&#847;&#8199;&#847;&#8199;&#847;&#8199;&#847;&#8199;&#847;&#8199;&#847;&#8199;&#847;</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="em-page" style="background: #F3F2EA;">
        <tr>
            <td align="center" class="em-outer" style="padding: 36px 16px 48px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; font-family: 'Instrument Sans', -apple-system, 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 15px; line-height: 1.55; color: #12170F;">
                    <tr>
                        <td style="padding: 0 4px 18px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td>
                                        <a href="{{ url('/') }}" style="text-decoration: none; color: #12170F;" class="em-ink"><span style="font-family: 'Bricolage Grotesque', 'Arial Black', Helvetica, Arial, sans-serif; font-weight: 800; font-size: 24px; letter-spacing: -0.04em; line-height: 1; vertical-align: middle;">pickl</span><img src="{{ asset('images/email-mark.png') }}" width="26" height="27" alt="" style="display: inline-block; vertical-align: middle; margin: 0 1px; border: 0;"><span style="font-family: 'Bricolage Grotesque', 'Arial Black', Helvetica, Arial, sans-serif; font-weight: 800; font-size: 24px; letter-spacing: -0.04em; line-height: 1; vertical-align: middle;">x</span></a>
                                    </td>
                                    <td align="right" class="em-ink3" style="font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size-adjust: 0.58; font-variant-numeric: tabular-nums; font-size: 11px; letter-spacing: .12em; text-transform: uppercase; color: #5B6257; white-space: nowrap;">@yield('label')</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="em-card em-pad" style="background: #FFFFFF; border: 1px solid #DEDCD0; border-radius: 24px; padding: 36px 40px 40px;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td align="center" class="em-ink3" style="padding: 24px 12px 0; font-size: 12px; line-height: 1.55; color: #5B6257; text-align: center;">
                            <p style="margin: 0 auto 10px; max-width: 460px;">@yield('why')</p>
                            <p style="margin: 0 0 10px;"><a href="{{ $footerLink[1] }}" class="em-ink2" style="color: #464D42; font-weight: 600; text-decoration: underline;">{{ $footerLink[0] }}</a></p>
                            <p style="margin: 0;">© {{ date('Y') }} PickleX · Made with <span style="color: #EF4452;">&#9829;</span> by <a href="https://creativexph.com/" class="em-ink2" style="color: #464D42; font-weight: 700; text-decoration: underline;">CreativeX Techlabs Inc.</a></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
