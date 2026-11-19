<?php

test('the public home shows the church name', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Welcome')
        ->where('church', 'Iglesia Cristiana Tabernáculo de Adoración'));
});
