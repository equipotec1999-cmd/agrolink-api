<?php

namespace Tests\Unit;

use App\Support\NotificationText;
use PHPUnit\Framework\TestCase;

class NotificationTextTest extends TestCase
{
    public function test_oferta_recibida_muestra_monto_cantidad_y_titulo(): void
    {
        [$titulo, $cuerpo] = NotificationText::render('offer_received', [
            'actor_name' => 'Ana', 'amount' => 1400, 'quantity' => 2, 'listing_title' => 'Borregos',
        ]);

        $this->assertSame('Nueva oferta de Ana', $titulo);
        $this->assertSame('$1,400.00 × 2 por «Borregos»', $cuerpo);
    }

    public function test_publicacion_rechazada_incluye_el_motivo(): void
    {
        [, $cuerpo] = NotificationText::render('listing_rejected', ['listing_title' => 'Miel', 'reason' => 'Fotos falsas']);

        $this->assertStringContainsString('Fotos falsas', $cuerpo);
    }

    public function test_tipo_desconocido_no_truena(): void
    {
        $this->assertSame(['Notificación', ''], NotificationText::render('algo_nuevo', []));
    }
}
