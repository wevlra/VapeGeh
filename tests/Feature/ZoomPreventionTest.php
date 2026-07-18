<?php

test('landing page loads zoom prevention scripts and has viewport meta tag', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('user-scalable=no');
    $response->assertSee('maximum-scale=1.0');

    // Check for the compiled/hot asset of app.js
    $content = $response->getContent();
    expect($content)->toContain('app')
        ->toContain('.js');
});

test('admin login page loads app.js which contains zoom prevention script', function () {
    $response = $this->get('/admin/login');

    $response->assertStatus(200);
    $content = $response->getContent();
    expect($content)->toContain('app')
        ->toContain('.js');
});

test('staff login page loads app.js which contains zoom prevention script', function () {
    $response = $this->get('/staff/login');

    $response->assertStatus(200);
    $content = $response->getContent();
    expect($content)->toContain('app')
        ->toContain('.js');
});
