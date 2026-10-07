<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Seguro contra accidentes: RefreshDatabase borra todas las tablas, así que solo se
     * permite correr contra una base cuyo nombre termine en `_test`.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $config = $app['config'];
        $database = (string) $config->get('database.connections.'.$config->get('database.default').'.database');

        if (! str_ends_with($database, '_test')) {
            throw new \RuntimeException("Las pruebas solo corren en una base *_test (actual: '$database').");
        }

        return $app;
    }
}
