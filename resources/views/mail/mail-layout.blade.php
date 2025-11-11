<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>@yield('subject', 'EventFlow')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Preheader: hidden preview text in inbox -->
    <style>
        .preheader { display:none !important; visibility:hidden; opacity:0; color:transparent; height:0; width:0; overflow:hidden; mso-hide:all; }
        @media only screen and (max-width: 600px) {
            .container { width: 100% !important; }
            .px-24 { padding-left: 16px !important; padding-right: 16px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background:#f5f7fb;">
  <span class="preheader">
    @yield('preheader', 'EventFlow notification')
  </span>

  <!-- Wrapper -->
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f5f7fb;">
      <tr>
          <td align="center" style="padding:24px;">
              <!-- Container -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" class="container" style="width:600px; max-width:600px; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                  <!-- Header -->
                  <tr>
                      <td style="background:#0f6b3e; padding:20px 24px;">
                          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                              <tr>
                                  <td align="left" style="font-family:Arial, Helvetica, sans-serif; color:#e8f5ed; font-size:18px; font-weight:700;">
                                      {{-- Logo (optional) --}}
                                      @php $logo = $logo ?? null; @endphp
                                      @if($logo)
                                          <img src="{{ $logo }}" alt="{{ config('app.name', 'EventFlow') }} logo" width="140" style="display:block; border:0; outline:none; text-decoration:none;">
                                      @else
                                          {{ config('app.name', 'EventFlow') }}
                                      @endif
                                  </td>
                                  <td align="right" style="font-family:Arial, Helvetica, sans-serif; color:#cde9d8; font-size:12px;">
                                      @yield('subject', 'Notification')
                                  </td>
                              </tr>
                          </table>
                      </td>
                  </tr>

                  <!-- Body -->
                  <tr>
                      <td class="px-24" style="padding:24px; font-family:Arial, Helvetica, sans-serif; color:#111827; font-size:16px; line-height:1.6;">
                          @yield('content')
                      </td>
                  </tr>

                  <!-- Footer -->
                  <tr>
                      <td style="background:#f0f4f8; padding:16px 24px;">
                          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                              <tr>
                                  <td align="right" style="font-family:Arial, Helvetica, sans-serif; color:#6b7280; font-size:12px;">

                                      <a style="color:#6b7280; text-decoration:none;">{{ config('app.name', 'EventFlow') }}</a>

                                  </td>
                              </tr>
                          </table>
                      </td>
                  </tr>

              </table>
              <!-- /Container -->
          </td>
      </tr>
  </table>
  <!-- /Wrapper -->
</body>
</html>

