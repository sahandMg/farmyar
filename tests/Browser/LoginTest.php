<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class LoginTest extends DuskTestCase
{
    /**
     * A Dusk test example.
     *
     * @return void
     */
    public function test_successful_login()
    {
        $this->browse(function (Browser $browser) {
            $browser
            ->visit('https://www.instagram.com/accounts/login/?source=auth_switcher')
                ->pause(5000)
                ->type('username','sahand_smg')
                ->type('password','44644831')
                ->press('Log In')
            ->pause(5000);
        });
    }

    public function test_checkPage(){

        $this->browse(function (Browser $browser){
            $browser->visit('https://www.instagram.com/explore/people/suggested/')
            ->pause(2000)
            ->press('Follow')
            ->pause(2000)
            ->press('Follow')
            ->pause(2000);
        });
    }

//    public function test_unsuccessful_login()
//    {
//        $this->browse(function (Browser $browser) {
//            $browser
//                ->visit('/panel/logout')
//                ->assertPathIs('/')
//                ->visit('/')
//                ->assertDontSee('Dashboard')
//                ->visit('/login')
////                ->assertUrlIs('localhost:7000/login')
//                ->type('email','s23.moghadam@gmail.com')
//                ->type('password','1234567')
//                ->press('LOG IN')
//                ->assertPathIs('/login');
//        });
//    }
}
