<?php

test('the admin panel is accessible', function () {
    $this->get('/admin/login')->assertSuccessful();
});
