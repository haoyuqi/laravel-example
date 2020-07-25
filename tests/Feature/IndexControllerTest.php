<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IndexControllerTest extends TestCase
{
    public function testIndex()
    {
        $response = $this->get('/');

        $response->assertSuccessful()
            ->assertViewIs('index.welcome')
            ->assertSeeText('Laravel example')
            ->assertViewHas('info', 'Hello World');
    }

    public function testError()
    {
        $response = $this->get('/error');

        $response->assertSuccessful()
            ->assertViewIs('index.error')
            ->assertSeeText('Error')
            ->assertViewHas('info', 'No Message');
    }

    public function time()
    {
        $response = $this->get('/time');

        $response->assertSuccessful()
            ->assertViewIs('index.time')
            ->assertSeeText('Time')
            ->assertViewHas('info', now()->toDateTimeString());
    }

    public function testTest()
    {
        $response = $this->get('/test');

        $response->assertForbidden();
    }
}
