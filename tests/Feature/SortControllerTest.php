<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SortControllerTest extends TestCase
{

    public function testBubbleSort()
    {
        $response = $this->get('/sort/bubble');
        $response->assertStatus(302)
            ->assertRedirect(url()->action('IndexController@error'))
            ->assertSessionHasErrors(['count']);

        $string = chr(mt_rand(97, 122));
        $response = $this->get('/sort/bubble?count=' . $string);
        $response->assertStatus(302)
            ->assertRedirect(url()->action('IndexController@error'))
            ->assertSessionHasErrors(['count']);

        $count = 0;
        $response = $this->get('/sort/bubble?count=' . $count);
        $response->assertStatus(302)
            ->assertRedirect(url()->action('IndexController@error'))
            ->assertSessionHasErrors(['count']);

        $count = mt_rand(10001, mt_getrandmax());
        $response = $this->get('/sort/bubble?count=' . $count);
        $response->assertStatus(302)
            ->assertRedirect(url()->action('IndexController@error'))
            ->assertSessionHasErrors(['count']);


        $count = mt_rand(1, 10000);
        $arr = range(1, $count);
        $response = $this->get('/sort/bubble?count=' . $count);
        $response->assertSuccessful()
            ->assertJsonCount($count)
            ->assertExactJson($arr)
            ->assertSeeTextInOrder($arr);
    }

    public function testQuickSort()
    {
        $response = $this->get('/sort/quick');
        $response->assertStatus(302)
            ->assertRedirect(url()->action('IndexController@error'))
            ->assertSessionHasErrors(['count']);

        $string = chr(mt_rand(97, 122));
        $response = $this->get('/sort/quick?count=' . $string);
        $response->assertStatus(302)
            ->assertRedirect(url()->action('IndexController@error'))
            ->assertSessionHasErrors(['count']);

        $count = 0;
        $response = $this->get('/sort/quick?count=' . $count);
        $response->assertStatus(302)
            ->assertRedirect(url()->action('IndexController@error'))
            ->assertSessionHasErrors(['count']);

        $count = mt_rand(10001, mt_getrandmax());
        $response = $this->get('/sort/quick?count=' . $count);
        $response->assertStatus(302)
            ->assertRedirect(url()->action('IndexController@error'))
            ->assertSessionHasErrors(['count']);


        $count = mt_rand(1, 10000);
        $arr = range(1, $count);
        $response = $this->get('/sort/quick?count=' . $count);
        $response->assertSuccessful()
            ->assertJsonCount($count)
            ->assertExactJson($arr)
            ->assertSeeTextInOrder($arr);
    }

    public function testSelectSort()
    {
        $response = $this->get('/sort/select');
        $response->assertStatus(302)
            ->assertRedirect(url()->action('IndexController@error'))
            ->assertSessionHasErrors(['count']);

        $string = chr(mt_rand(97, 122));
        $response = $this->get('/sort/select?count=' . $string);
        $response->assertStatus(302)
            ->assertRedirect(url()->action('IndexController@error'))
            ->assertSessionHasErrors(['count']);

        $count = 0;
        $response = $this->get('/sort/select?count=' . $count);
        $response->assertStatus(302)
            ->assertRedirect(url()->action('IndexController@error'))
            ->assertSessionHasErrors(['count']);

        $count = mt_rand(10001, mt_getrandmax());
        $response = $this->get('/sort/select?count=' . $count);
        $response->assertStatus(302)
            ->assertRedirect(url()->action('IndexController@error'))
            ->assertSessionHasErrors(['count']);


        $count = mt_rand(1, 10000);
        $arr = range(1, $count);
        $response = $this->get('/sort/select?count=' . $count);
        $response->assertSuccessful()
            ->assertJsonCount($count)
            ->assertExactJson($arr)
            ->assertSeeTextInOrder($arr);
    }
}
