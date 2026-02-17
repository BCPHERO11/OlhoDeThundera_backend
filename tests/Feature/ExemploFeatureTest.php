<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExemploFeatureTest extends TestCase
{
    public function test_aplicacao_retorna_resposta_com_sucesso(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
