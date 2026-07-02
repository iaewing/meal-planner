<?php

use App\Models\User;

it('shares flash messages with inertia responses', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['success' => 'Recipe imported successfully.'])
        ->get(route('recipes.index'));

    $response->assertOk();

    expect($response->viewData('page')['props']['flash']['success'])
        ->toBe('Recipe imported successfully.');
});
