<?php

/*
 * Textos legales (Aviso de privacidad y Términos de uso). El contenido vive en
 * resources/legal/*.md y se sirve a la app (/api/legal/{doc}) y a la web
 * (/privacidad, /terminos — la liga que pide Google Play).
 *
 * Al cambiar un texto de forma importante, sube `version`: la app pedirá a cada
 * usuario aceptarlo de nuevo.
 */
return [
    'version' => env('LEGAL_VERSION', '2026-11-27'),
    'responsable' => env('LEGAL_RESPONSABLE', 'EquipoTec, equipo desarrollador de AgroLink (proyecto del Instituto Tecnológico de Tizimín)'),
    'domicilio' => env('LEGAL_DOMICILIO', 'Tizimín, Yucatán, México'),
    'contacto' => env('LEGAL_CONTACT_EMAIL', env('BREVO_FROM_EMAIL', env('MAIL_FROM_ADDRESS'))),
];
