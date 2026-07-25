<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects root of staff subdomain to staff panel', function () {
    $response = $this->get('http://staff.localhost/');

    $response->assertRedirect('/staff');
});

it('renders PWA manifest and meta tags on staff panel pages', function () {
    $response = $this->get('http://staff.localhost/staff/login');

    $response->assertOk()
        ->assertSee('<link rel="manifest" href="/manifest.json">', false)
        ->assertSee('<meta name="apple-mobile-web-app-capable" content="yes">', false);
});

it('allows manifest.json request on staff subdomain', function () {
    $response = $this->get('http://staff.localhost/manifest.json');

    $response->assertOk();
});
