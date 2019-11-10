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
        $response->assertStatus(302)
            ->assertRedirect(url()->action('IndexController@error'))
            ->assertSessionHasErrors(['count']);

        $string = chr(mt_rand(97, 122));
        $response = $this->get('/queue/create?count=' . $string);
        $response->assertStatus(302)
            ->assertRedirect(url()->action('IndexController@error'))
            ->assertSessionHasErrors(['count']);

        $array = [0, mt_rand(10001, mt_getrandmax())];
        $key = array_rand($array);
        $response = $this->get('/queue/create?count=' . $array[$key]);
        $response->assertStatus(302)
            ->assertRedirect(url()->action('IndexController@error'))
            ->assertSessionHasErrors(['count']);


        $count = mt_rand(1, 10000);
        $response = $this->get('/queue/create?count=' . $count);
        $response->assertSuccessful()
            ->assertViewIs('queue.create')
            ->assertSeeText('laravel example')
            ->assertViewHas('info', 'success');
    }
}
