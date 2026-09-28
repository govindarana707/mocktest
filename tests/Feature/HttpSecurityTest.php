<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public and authentication pages respond successfully', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
    $this->get(route('login'))->assertOk()->assertSee('Student portal');
    $this->get(route('register'))->assertOk()->assertSee('Student registration');
    $this->get(route('admin.login'))->assertOk()->assertSee('Administrator sign in');
});

test('state changing forms include csrf tokens', function () {
    $this->get(route('login'))->assertSee('name="_token"', false);
    $this->get(route('register'))->assertSee('name="_token"', false);
    $this->get(route('admin.login'))->assertSee('name="_token"', false);
});
