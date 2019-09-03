<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class BuyTest extends DuskTestCase
{
    /**
     * A Dusk test example.
     *
     * @return void
     */

    public function test_login(){
        $this->browse(function (Browser $browser) {
            $browser
                ->visit('/login')
                ->assertSee('Log In')
                ->type('email','s23.moghadam@gmail.com')
                ->type('password','123456')
                ->press('LOG IN')
                ->assertPathIs('/panel/dashboard');
        });
    }
    public function test_buy()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/panel/dashboard')
                ->driver->executeScript('window.scrollTo(0, 500);');
            $browser
                ->driver->executeScript(
                    "document.querySelector('#hiddenRange').value = '1'");
            $browser
                ->driver->executeScript(
                    "document.querySelector('#demo').value = '1 Th'");

            $browser->pause(1000)
                ->press('ORDER')
                ->assertSee('You have to pay your invoice with bitcoin. If you do not have , you can purchase it from this list')
                ->press('CONTINUE')
                ->pause(5000)
                ->assertSee('Cancel')
                ->press('Cancel')
                ->press('Yes')
                ->pause(5000);
        });

    }
}
