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
            ->assertViewIs('welcome')
            ->assertSeeText('laravel example')
            ->assertViewHas('info', 'Hello World');
    }

    public function testError()
    {
        $response = $this->get('/error');

        $response->assertSuccessful()
            ->assertViewIs('error')
            ->assertSeeText('laravel example')
            ->assertViewHas('info', 'No Message');
    }

    public function testTest()
    {
        $response = $this->get('/test');

        $response->assertForbidden();
    }
}
