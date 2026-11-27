<?php

return [
    // Única cuenta que ve, agrega y quita moderadores (dueña de la plataforma).
    'owner_email' => strtolower(trim((string) env('OWNER_EMAIL', 'ramirez12354cr@gmail.com'))),
];
