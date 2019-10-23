<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IndexTest extends TestCase
{
    public function testIndex()
    {
        $response = $this->get('/');

        $response->assertSuccessful()
            ->assertViewIs('welcome')
            ->assertSeeText('laravel example')
            ->assertViewHas('info', 'Hello World');
    }


    public function testTest()
    {
        $response = $this->get('/test');

        $response->assertForbidden();
    }
}
