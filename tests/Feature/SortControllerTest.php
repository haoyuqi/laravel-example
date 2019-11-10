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

        $array = [0, mt_rand(10001, mt_getrandmax())];
        $key = array_rand($array);
        $response = $this->get('/sort/bubble?count=' . $array[$key]);
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
}
