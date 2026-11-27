<?php

return [


    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Push (FCM). Credenciales de la cuenta de servicio en base64, en UNA línea.
    'firebase' => [
        'credentials_base64' => env('FIREBASE_CREDENTIALS_BASE64'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
        // Sin dominio verificado Resend solo acepta este remitente y solo envía al dueño de la cuenta.
        'from' => env('RESEND_FROM', 'AgroLink <onboarding@resend.dev>'),
    ],

    // Brevo (gratis, 300 correos/día): basta verificar UN correo remitente (p. ej. un Gmail) y envía
    // a cualquier destinatario. Va por API HTTPS porque Render gratis bloquea los puertos SMTP.
    // Emblema que aparece en los correos (opcional; por defecto se sirve desde la propia API).
    'mail_logo_url' => env('MAIL_LOGO_URL'),

    'brevo' => [
        'key' => env('BREVO_API_KEY'),
        'from_email' => env('BREVO_FROM_EMAIL', env('MAIL_FROM_ADDRESS')),
        'from_name' => env('BREVO_FROM_NAME', 'AgroLink'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
