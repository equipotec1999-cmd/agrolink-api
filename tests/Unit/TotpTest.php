<?php

namespace Tests\Unit;

use App\Support\Totp;
use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    // Vectores de prueba oficiales de la RFC 6238 (SHA-1); aquí se comparan los últimos 6 dígitos.
    public function test_vectores_rfc_6238(): void
    {
        $secret = Totp::base32Encode('12345678901234567890');

        foreach ([59 => '287082', 1111111109 => '081804', 1234567890 => '005924', 20000000000 => '353130'] as $time => $esperado) {
            $this->assertSame($esperado, Totp::code($secret, intdiv($time, 30)), "T=$time");
        }
    }

    public function test_acepta_ventana_y_rechaza_reuso_y_fuera_de_ventana(): void
    {
        $secret = Totp::newSecret();
        $now = 1700000000;
        $step = intdiv($now, 30);

        $this->assertSame($step, Totp::verify($secret, Totp::code($secret, $step), null, $now));
        $this->assertSame($step - 1, Totp::verify($secret, Totp::code($secret, $step - 1), null, $now));
        $this->assertNull(Totp::verify($secret, Totp::code($secret, $step), $step, $now), 'el mismo código no sirve dos veces');
        $this->assertNull(Totp::verify($secret, Totp::code($secret, $step + 5), null, $now), 'fuera de la ventana');
        $this->assertNull(Totp::verify($secret, '12345', null, $now), 'formato inválido');
    }
}
