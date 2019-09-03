<?php

namespace Tests\Browser;

use App\User;
use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class SignUpTest extends DuskTestCase
{
    /**
     * A Dusk test example.
     *
     * @return void
     */
    public function test_successful_signup()
    {

        $user  =[  'name'=>str_random(10),
            'email'=>str_random(5).'@gmail.com',
            'password'=>'12345678',
            'confirm_password'=>'12345678'
        ];
        $this->browse(function (Browser $browser) use($user) {
            $browser->visit('/signup')
                    ->assertSee('Sign Up')
                ->type('name',$user['name'])
                ->type('email',$user['email'])
                ->type('password',$user['password'])
                ->type('confirm_password',$user['confirm_password'])
                ->press('SIGN UP')
                ->assertPathIs('/panel/dashboard');

        });
    }

    public function test_unsuccessful_signup(){

        $user  =[  'name'=>str_random(10),
            'email'=>str_random(5).'@gmail.com',
            'password'=>'12345678',
            'confirm_password'=>'12345678'
        ];
        $this->browse(function (Browser $browser) use($user) {
            $browser->visit('/panel/logout')
                ->visit('/signup')
                ->type('name',$user['name'])
                ->type('password',$user['password'])
                ->type('confirm_password',$user['confirm_password'])
                ->press('SIGN UP')
                ->assertPathIs('signup');
        });

    }
}
