<?php

namespace Tests\Feature;

use Tests\TestCase;

class CorsTest extends TestCase
{
    public function test_la_version_web_puede_llamar_a_la_api(): void
    {
        $this->withHeaders([
            'Origin' => 'https://equipotec1999-cmd.github.io',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'authorization,content-type',
        ])->options('/api/login')
            ->assertSuccessful()
            ->assertHeader('Access-Control-Allow-Origin', '*');
    }
}
