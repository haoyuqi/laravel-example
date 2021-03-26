<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TimeTest extends DuskTestCase
{
    /**
     * A Dusk test example.
     *
     * @return void
     */
    public function testPage()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/time')
                    ->assertSee(now()->format('Y-m-d H:i'));
        });
    }
}
