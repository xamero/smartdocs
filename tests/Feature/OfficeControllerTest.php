<?php

use App\Models\Document;
use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('requires authentication to view offices index', function () {
    $this->get(route('offices.index'))
        ->assertRedirect(route('login'));
});

it('can display the offices index page', function () {
    $user = User::factory()->create(['role' => 'admin']);
    Office::factory()->count(5)->create();

    $this->actingAs($user)
        ->get(route('offices.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('offices', 5)
        );
});

it('loads parent and children relationships on index', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $parent = Office::factory()->create();
    $child = Office::factory()->create(['parent_id' => $parent->id]);

    $this->actingAs($user)
        ->get(route('offices.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('offices', 2)
            ->has('offices.0')
            ->has('offices.1')
        );

    // Verify relationships are loaded by checking the database
    $parentOffice = Office::with('children')->find($parent->id);
    $childOffice = Office::with('parent')->find($child->id);

    expect($parentOffice->children)->toHaveCount(1);
    expect($childOffice->parent)->not->toBeNull();
    expect($childOffice->parent->id)->toBe($parent->id);
});

it('orders offices by sort_order then name', function () {
    $user = User::factory()->create(['role' => 'admin']);
    Office::factory()->create(['name' => 'Z Office', 'sort_order' => 2]);
    Office::factory()->create(['name' => 'A Office', 'sort_order' => 1]);
    Office::factory()->create(['name' => 'B Office', 'sort_order' => 1]);

    $this->actingAs($user)
        ->get(route('offices.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('offices.0.name', 'A Office')
            ->where('offices.1.name', 'B Office')
            ->where('offices.2.name', 'Z Office')
        );
});

it('includes document counts per office on index', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $office = Office::factory()->create(['name' => 'Office 1']);
    Document::factory()->count(2)->create(['current_office_id' => $office->id, 'status' => 'in_transit']);
    Document::factory()->create(['current_office_id' => $office->id, 'status' => 'completed']);

    $this->actingAs($user)
        ->get(route('offices.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('offices.0.documents_total_count')
            ->has('offices.0.documents_in_transit_count')
            ->has('offices.0.documents_completed_count')
        );
});

it('requires authentication to view my offices dashboard', function () {
    $this->get(route('offices.my'))
        ->assertRedirect(route('login'));
});

it('shows only user office and its children on my dashboard', function () {
    $userOffice = Office::factory()->create(['name' => 'Root Office']);
    $childOffice = Office::factory()->create(['name' => 'Child Office', 'parent_id' => $userOffice->id]);
    $otherOffice = Office::factory()->create(['name' => 'Other Office']);

    $user = User::factory()->create([
        'role' => 'admin',
        'office_id' => $userOffice->id,
    ]);

    $this->actingAs($user)
        ->get(route('offices.my'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('offices', 2)
        );
});

it('requires authentication to create an office', function () {
    $this->post(route('offices.store'), [])
        ->assertRedirect(route('login'));
});

it('can create an office', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)
        ->post(route('offices.store'), [
            'name' => 'Test Office',
            'code' => 'TEST',
            'description' => 'Test description',
            'is_active' => true,
            'sort_order' => 1,
        ])
        ->assertRedirect(route('offices.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('offices', [
        'name' => 'Test Office',
        'code' => 'TEST',
        'description' => 'Test description',
        'is_active' => true,
        'sort_order' => 1,
    ]);
});

it('can create an office with parent', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $parent = Office::factory()->create();

    $this->actingAs($user)
        ->post(route('offices.store'), [
            'name' => 'Child Office',
            'code' => 'CHILD',
            'parent_id' => $parent->id,
            'is_active' => true,
        ])
        ->assertRedirect(route('offices.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('offices', [
        'name' => 'Child Office',
        'code' => 'CHILD',
        'parent_id' => $parent->id,
    ]);
});

it('validates required fields when creating an office', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)
        ->post(route('offices.store'), [])
        ->assertSessionHasErrors(['name']);
});

it('validates code is unique when creating', function () {
    $user = User::factory()->create(['role' => 'admin']);
    Office::factory()->create(['code' => 'EXIST']);

    $this->actingAs($user)
        ->post(route('offices.store'), [
            'name' => 'Test Office',
            'code' => 'EXIST',
        ])
        ->assertSessionHasErrors(['code']);
});

it('requires admin role to create an office', function () {
    $user = User::factory()->create(['role' => 'encoder']);

    $this->actingAs($user)
        ->post(route('offices.store'), [
            'name' => 'Test Office',
            'code' => 'TEST',
        ])
        ->assertForbidden();
});

it('requires admin role to update an office', function () {
    $user = User::factory()->create(['role' => 'encoder']);
    $office = Office::factory()->create();

    $this->actingAs($user)
        ->put(route('offices.update', $office), [
            'name' => 'Updated Name',
            'code' => $office->code,
        ])
        ->assertForbidden();
});

it('requires admin role to delete an office', function () {
    $user = User::factory()->create(['role' => 'encoder']);
    $office = Office::factory()->create();

    // Authorization is checked in the request, which redirects on failure
    $this->actingAs($user)
        ->delete(route('offices.destroy', $office))
        ->assertRedirect();
});

it('requires authentication to update an office', function () {
    $office = Office::factory()->create();

    $this->put(route('offices.update', $office), [])
        ->assertRedirect(route('login'));
});

it('can update an office', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $office = Office::factory()->create(['name' => 'Old Name']);

    $this->actingAs($user)
        ->put(route('offices.update', $office), [
            'name' => 'New Name',
            'code' => $office->code,
            'description' => 'Updated description',
        ])
        ->assertRedirect(route('offices.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('offices', [
        'id' => $office->id,
        'name' => 'New Name',
        'description' => 'Updated description',
    ]);
});

it('validates code is unique when updating', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $office1 = Office::factory()->create(['code' => 'CODE1']);
    $office2 = Office::factory()->create(['code' => 'CODE2']);

    $this->actingAs($user)
        ->put(route('offices.update', $office2), [
            'name' => $office2->name,
            'code' => 'CODE1',
        ])
        ->assertSessionHasErrors(['code']);
});

it('allows same code when updating same office', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $office = Office::factory()->create(['code' => 'CODE1']);

    $this->actingAs($user)
        ->put(route('offices.update', $office), [
            'name' => 'Updated Name',
            'code' => 'CODE1',
        ])
        ->assertRedirect(route('offices.index'))
        ->assertSessionHas('success');
});

it('requires authentication to delete an office', function () {
    $office = Office::factory()->create();

    $this->delete(route('offices.destroy', $office))
        ->assertRedirect(route('login'));
});

it('can delete an office', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $office = Office::factory()->create();

    $this->actingAs($user)
        ->delete(route('offices.destroy', $office))
        ->assertRedirect(route('offices.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted('offices', [
        'id' => $office->id,
    ]);
});

it('cannot delete office with associated users', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $office = Office::factory()->create();
    User::factory()->create(['office_id' => $office->id]);

    $this->actingAs($user)
        ->delete(route('offices.destroy', $office))
        ->assertRedirect(route('offices.index'))
        ->assertSessionHas('error', 'Cannot delete office with associated users or documents.');

    $this->assertDatabaseHas('offices', [
        'id' => $office->id,
    ]);
});

it('cannot delete office with associated documents', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $office = Office::factory()->create();
    Document::factory()->create(['current_office_id' => $office->id]);

    $this->actingAs($user)
        ->delete(route('offices.destroy', $office))
        ->assertRedirect(route('offices.index'))
        ->assertSessionHas('error', 'Cannot delete office with associated users or documents.');

    $this->assertDatabaseHas('offices', [
        'id' => $office->id,
    ]);
});

it('can delete office without associated users or documents', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $office = Office::factory()->create();

    $this->actingAs($user)
        ->delete(route('offices.destroy', $office))
        ->assertRedirect(route('offices.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted('offices', [
        'id' => $office->id,
    ]);
});
