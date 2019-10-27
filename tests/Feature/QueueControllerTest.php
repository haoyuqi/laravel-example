<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class QueueControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreate()
    {
        $response = $this->get('/queue/create');
        $response->assertSuccessful()
            ->assertViewIs('queue.create')
            ->assertSeeText('laravel example')
            ->assertViewHas('info', 'count 不能为空。');

        $string = chr(mt_rand(97, 122));
        $response = $this->get('/queue/create?count=' . $string);
        $response->assertSuccessful()
            ->assertViewIs('queue.create')
            ->assertSeeText('laravel example')
            ->assertViewHas('info', 'count 必须是整数。');

        $array = [0, mt_rand(10001, mt_getrandmax())];
        $key = array_rand($array);
        $response = $this->get('/queue/create?count=' . $array[$key]);
        $response->assertSuccessful()
            ->assertViewIs('queue.create')
            ->assertSeeText('laravel example')
            ->assertViewHas('info', 'count 必须介于 1 - 10000 之间。');

        $count = mt_rand(1, 10000);
        $response = $this->get('/queue/create?count=' . $count);
        $response->assertSuccessful()
            ->assertViewIs('queue.create')
            ->assertSeeText('laravel example')
            ->assertViewHas('info', 'success');
    }
}
