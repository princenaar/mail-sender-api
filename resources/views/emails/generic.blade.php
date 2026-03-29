<!DOCTYPE html>
<html lang="fr" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subject }}</title>
    <style>
        body { margin: 0; padding: 0; background-color: #eef2f7; font-family: Arial, Helvetica, sans-serif; }
        table { border-collapse: collapse; }
        img { border: 0; display: block; }
        @media only screen and (max-width: 620px) {
            .wrapper { width: 100% !important; }
            .content-cell { padding: 24px 16px !important; }
            .header-cell { padding: 24px 16px !important; }
            .logo { max-width: 120px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#eef2f7;">

    <!-- Outer wrapper -->
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef2f7;">
        <tr>
            <td align="center" style="padding:32px 16px;">

                <!-- Email card -->
                <table class="wrapper" width="600" cellpadding="0" cellspacing="0" border="0"
                       style="max-width:600px;width:100%;background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.10);">

                    <!-- ===== HEADER ===== -->
                    <tr>
                        <td class="header-cell" align="center"
                            style="background-color:#0d3b6e;padding:32px 40px 24px;">
                            <!-- Logo -->
                            <img class="logo"
                                 src="{{ config('app.url') }}/images/mshp-logo.png"
                                 alt="MSHP Logo"
                                 width="160"
                                 style="display:block;margin:0 auto 16px;max-width:160px;height:auto;">
                            <!-- Divider -->
                            <table width="60" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 16px;">
                                <tr>
                                    <td style="height:3px;background-color:#4a90d9;border-radius:2px;"></td>
                                </tr>
                            </table>
                            <!-- Subtitle -->
                            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:11px;
                                      line-height:1.6;color:#a8c8f0;letter-spacing:0.5px;text-align:center;">
                                Plateforme de Management Intégré des Ressources humaines<br>
                                de la Santé et de l'Action Sociale
                            </p>
                        </td>
                    </tr>

                    <!-- ===== SUBJECT BAR ===== -->
                    <tr>
                        <td align="left"
                            style="background-color:#1565c0;padding:14px 40px;">
                            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:15px;
                                      font-weight:bold;color:#ffffff;letter-spacing:0.3px;">
                                {{ $subject }}
                            </p>
                        </td>
                    </tr>

                    <!-- ===== BODY ===== -->
                    <tr>
                        <td class="content-cell" style="padding:36px 40px;background-color:#ffffff;">
                            <div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;
                                        line-height:1.7;color:#2d3748;">
                                {!! $htmlContent !!}
                            </div>
                        </td>
                    </tr>

                    <!-- ===== FOOTER ===== -->
                    <tr>
                        <td align="center"
                            style="background-color:#f7f9fc;padding:24px 40px;
                                   border-top:1px solid #e2e8f0;">
                            <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                                      font-size:11px;color:#718096;line-height:1.5;">
                                Ce message est généré automatiquement — merci de ne pas y répondre directement.
                            </p>
                            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                                      font-size:11px;color:#a0aec0;">
                                &copy; {{ date('Y') }} Ministère de la Santé et de l'Action Sociale. Tous droits réservés.
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- /Email card -->

            </td>
        </tr>
    </table>
    <!-- /Outer wrapper -->

</body>
</html>
