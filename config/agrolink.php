<?php

return [
    // Única cuenta que ve, agrega y quita moderadores (dueña de la plataforma).
    'owner_email' => strtolower(trim((string) env('OWNER_EMAIL', 'ramirez12354cr@gmail.com'))),

    // Destacados pagados (publicaciones y perfiles). Apagado hasta que se abra a los usuarios:
    // mientras tanto solo moderación puede destacar. Para abrirlo: FEATURED_SALES_ENABLED=true.
    'featured_sales' => (bool) env('FEATURED_SALES_ENABLED', false),

    // Cómo pagar (se muestra al pedir un destacado). Moderación confirma el pago y lo activa.
    'featured_payment_instructions' => env(
        'FEATURED_PAYMENT_INSTRUCTIONS',
        'Un moderador te escribirá con los datos para pagar. Tu destacado se activa en cuanto se confirme el pago.'
    ),
];
