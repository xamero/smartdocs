<?php

use App\Models\Document;
use App\Models\Office;
use App\Models\SmartdocNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('requires authentication to view notifications', function () {
    $this->get(route('notifications.index'))
        ->assertRedirect(route('login'));
});

it('can view notifications index', function () {
    $user = User::factory()->create();
    SmartdocNotification::factory()->count(5)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Notifications/Index')
            ->has('notifications.data', 5)
        );
});

it('returns unread count', function () {
    $user = User::factory()->create();
    SmartdocNotification::factory()->count(3)->create([
        'user_id' => $user->id,
        'is_read' => false,
    ]);
    SmartdocNotification::factory()->count(2)->create([
        'user_id' => $user->id,
        'is_read' => true,
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('notifications.unread-count'));

    $response->assertOk()
        ->assertJson(['count' => 3]);
});

it('returns recent notifications', function () {
    $user = User::factory()->create();
    SmartdocNotification::factory()->count(15)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->getJson(route('notifications.recent'));

    $response->assertOk();
    expect($response->json())->toHaveCount(10);
});

it('can mark notification as read', function () {
    $user = User::factory()->create();
    $notification = SmartdocNotification::factory()->create([
        'user_id' => $user->id,
        'is_read' => false,
    ]);

    $this->actingAs($user)
        ->post(route('notifications.read', $notification))
        ->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('smartdoc_notifications', [
        'id' => $notification->id,
        'is_read' => true,
    ]);
});

it('cannot mark another users notification as read', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $notification = SmartdocNotification::factory()->create([
        'user_id' => $user1->id,
        'is_read' => false,
    ]);

    $this->actingAs($user2)
        ->post(route('notifications.read', $notification))
        ->assertForbidden();
});

it('can mark all notifications as read', function () {
    $user = User::factory()->create();
    SmartdocNotification::factory()->count(5)->create([
        'user_id' => $user->id,
        'is_read' => false,
    ]);

    $this->actingAs($user)
        ->post(route('notifications.read-all'))
        ->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseMissing('smartdoc_notifications', [
        'user_id' => $user->id,
        'is_read' => false,
    ]);
});
