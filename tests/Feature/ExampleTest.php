<?php

test('home route redirects guests', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect();
});
