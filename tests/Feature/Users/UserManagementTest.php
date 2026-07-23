<?php

use App\Notifications\UserCreatedTemporaryPassword;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

test('user edit works without password fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('users.update', $user), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ])
        ->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);
});

test('admin can reset user password and send temporary password notification', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('users.reset-password', $user))
        ->assertRedirect(route('users.index'));

    Notification::assertSentTo($user, UserCreatedTemporaryPassword::class);
    $this->assertFalse($user->refresh()->password_updated);
});
