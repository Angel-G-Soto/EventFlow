@extends('mail.mail-layout')

@section('subject', 'Event Cancelled')
@section('preheader', 'Event has been cancelled.')

@section('content')
    <!-- Email-safe, inline-styled list -->
    <h3>The following event request has been cancelled</h3>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;mso-table-lspace:0;mso-table-rspace:0;">
        <tr>
            <td align="center" style="padding:16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border:1px solid #e6e6e6;border-radius:8px;overflow:hidden;">
                    <!-- Header (optional) -->
                    <tr>
                        <td
                            style="padding:16px 20px;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;font-size:16px;line-height:24px;color:#212529;border-bottom:1px solid #e6e6e6;mso-line-height-rule:exactly;">
                            <strong style="font-size:17px;display:block;">Event Title: {{ $event['title'] ?? '' }}</strong>
                            <div>Organization: {{ $event['organization_name'] ?? '—' }}</div>
                            <div>Advisor: {{ $event['organization_advisor_name'] ?? '—' }}
                                ({{ $event['organization_advisor_email'] ?? 'N/A' }})</div>
                            <div>Starts: {{ $event['start_time'] ?? '' }}</div>
                            <div>Ends: {{ $event['end_time'] ?? '' }}</div>
                            <div>Venue: {{ $event['venue_code'] ?? '' }} ({{ $event['venue_name'] ?? '' }})</div>
                            <div>Justification: {{ $justification ?? '—' }}</div>
                        </td>

                    </tr>

                    {{--                    <!-- Example of a “subtle” item without a button -->--}}
                    {{--                    <tr>--}}
                    {{--                        <td style="padding:14px 16px;border-top:1px solid #e6e6e6;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;font-size:15px;line-height:22px;color:#212529;mso-line-height-rule:exactly;">--}}
                    {{--                            Floor Map.png--}}
                    {{--                            <div style="font-size:12px;color:#6c757d;margin-top:2px;">PNG • 512 KB</div>--}}
                    {{--                        </td>--}}
                    {{--                    </tr>--}}


                </table>
                <br>
                <div style="width: 100%">
                    <a href="{{ $route }}" target="_blank"
                       style="font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;font-size:14px;text-decoration:none;background:#0d6efd;color:#ffffff;padding:8px 12px;border-radius:6px;display:inline-block;">
                        View in EventFlow UPRM
                    </a>
                    <p style="margin:12px 0 0;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;font-size:12px;line-height:18px;color:#6c757d;">
                        If the button does not work, copy and paste this link into your browser:
                        <br>
                        <a href="{{ $route }}" style="color:#0d6efd;word-break:break-all;">{{ $route }}</a>
                    </p>
                </div>

            </td>
        </tr>
    </table>
@endsection
