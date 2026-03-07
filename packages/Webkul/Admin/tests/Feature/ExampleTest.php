<?php

declare(strict_types=1);

test('the admin login page returns a successful response', function () {
    $response = $this->get('/admin/login');

    $response->assertStatus(200);
});
