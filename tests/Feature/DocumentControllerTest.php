<?php

use App\Models\Document;
use App\Models\Office;
use App\Models\QRCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

it('requires authentication to view documents index', function () {
    $this->get(route('documents.index'))
        ->assertRedirect(route('login'));
});

it('can display the documents index page', function () {
    $user = User::factory()->create();
    $office = Office::factory()->create();
    Document::factory()->count(5)->create(['current_office_id' => $office->id]);

    $this->actingAs($user)
        ->get(route('documents.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Index')
            ->has('documents.data', 5)
            ->has('offices')
        );
});

it('can filter documents by status', function () {
    $user = User::factory()->create();
    Document::factory()->create(['status' => 'registered']);
    Document::factory()->create(['status' => 'in_transit']);

    $this->actingAs($user)
        ->get(route('documents.index', ['status' => 'registered']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Index')
            ->has('documents.data', 1)
            ->where('documents.data.0.status', 'registered')
        );
});

it('can filter documents by document type', function () {
    $user = User::factory()->create();
    Document::factory()->incoming()->create();
    Document::factory()->outgoing()->create();

    $this->actingAs($user)
        ->get(route('documents.index', ['document_type' => 'incoming']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Index')
            ->has('documents.data', 1)
            ->where('documents.data.0.document_type', 'incoming')
        );
});

it('can filter documents by priority', function () {
    $user = User::factory()->create();
    Document::factory()->urgent()->create();
    Document::factory()->create(['priority' => 'low']);

    $this->actingAs($user)
        ->get(route('documents.index', ['priority' => 'urgent']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Index')
            ->has('documents.data', 1)
            ->where('documents.data.0.priority', 'urgent')
        );
});

it('can filter documents by office', function () {
    $user = User::factory()->create();
    $office1 = Office::factory()->create();
    $office2 = Office::factory()->create();
    Document::factory()->create(['current_office_id' => $office1->id]);
    Document::factory()->create(['current_office_id' => $office2->id]);

    $this->actingAs($user)
        ->get(route('documents.index', ['office_id' => $office1->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Index')
            ->has('documents.data', 1)
            ->where('documents.data.0.current_office_id', $office1->id)
        );
});

it('can search documents by tracking number', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create(['tracking_number' => 'IN-2024-000001']);
    Document::factory()->create(['tracking_number' => 'OUT-2024-000001']);

    $this->actingAs($user)
        ->get(route('documents.index', ['search' => 'IN-2024']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Index')
            ->has('documents.data', 1)
            ->where('documents.data.0.tracking_number', $document->tracking_number)
        );
});

it('can search documents by title', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create(['title' => 'Important Document']);
    Document::factory()->create(['title' => 'Another Document']);

    $this->actingAs($user)
        ->get(route('documents.index', ['search' => 'Important']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Index')
            ->has('documents.data', 1)
            ->where('documents.data.0.title', 'Important Document')
        );
});

it('can search documents by description', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create(['description' => 'This is a test description']);
    Document::factory()->create(['description' => 'Different description']);

    $this->actingAs($user)
        ->get(route('documents.index', ['search' => 'test']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Index')
            ->has('documents.data', 1)
        );
});

it('requires authentication to view create form', function () {
    $this->get(route('documents.create'))
        ->assertRedirect(route('login'));
});

it('can display the document create form', function () {
    $user = User::factory()->create();
    Office::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('documents.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Create')
            ->has('offices', 3)
        );
});

it('requires authentication to archive a document', function () {
    $document = Document::factory()->create();

    $this->post(route('documents.archive', $document))
        ->assertRedirect(route('login'));
});

it('can archive a document', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create([
        'status' => 'completed',
        'is_archived' => false,
        'archived_at' => null,
    ]);

    $this->actingAs($user)
        ->post(route('documents.archive', $document))
        ->assertRedirect(route('documents.show', $document))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('documents', [
        'id' => $document->id,
        'status' => 'archived',
        'is_archived' => true,
    ]);
});

it('can restore an archived document', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create([
        'status' => 'archived',
        'is_archived' => true,
        'archived_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('documents.restore', $document))
        ->assertRedirect(route('documents.show', $document))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('documents', [
        'id' => $document->id,
        'status' => 'completed',
        'is_archived' => false,
        'archived_at' => null,
    ]);
});

it('requires authentication to create a document', function () {
    $this->post(route('documents.store'), [])
        ->assertRedirect(route('login'));
});

it('can create a document', function () {
    $user = User::factory()->create();
    $office = Office::factory()->create();

    $data = [
        'title' => 'Test Document',
        'description' => 'Test description',
        'document_type' => 'incoming',
        'source' => 'Test Source',
        'priority' => 'high',
        'confidentiality' => 'confidential',
        'receiving_office_id' => $office->id,
        'date_received' => now()->format('Y-m-d'),
        'date_due' => now()->addDays(7)->format('Y-m-d'),
    ];

    $this->actingAs($user)
        ->post(route('documents.store'), $data)
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('documents', [
        'title' => 'Test Document',
        'document_type' => 'incoming',
        'priority' => 'high',
        'confidentiality' => 'confidential',
        'receiving_office_id' => $office->id,
        'created_by' => $user->id,
        'registered_by' => $user->id,
        'status' => 'registered',
    ]);

    $document = Document::where('title', 'Test Document')->first();
    expect($document->tracking_number)->not->toBeEmpty();
    expect($document->qrCode)->not->toBeNull();
});

it('generates a tracking number when creating a document', function () {
    $user = User::factory()->create();
    $office = Office::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.store'), [
            'title' => 'Test Document',
            'document_type' => 'incoming',
            'priority' => 'normal',
            'confidentiality' => 'public',
            'receiving_office_id' => $office->id,
        ]);

    $document = Document::where('title', 'Test Document')->first();
    expect($document->tracking_number)->toMatch('/^IN-\d{4}-\d{6}$/');
});

it('generates a QR code when creating a document', function () {
    $user = User::factory()->create();
    $office = Office::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.store'), [
            'title' => 'Test Document',
            'document_type' => 'incoming',
            'priority' => 'normal',
            'confidentiality' => 'public',
            'receiving_office_id' => $office->id,
        ]);

    $document = Document::where('title', 'Test Document')->first();
    expect($document->qrCode)->not->toBeNull();
    expect($document->qrCode->is_active)->toBeTrue();
    expect($document->qrCode->code)->not->toBeEmpty();
});

it('validates required fields when creating a document', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.store'), [])
        ->assertSessionHasErrors(['title', 'document_type', 'priority', 'confidentiality']);
});

it('validates document type is valid when creating', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.store'), [
            'title' => 'Test',
            'document_type' => 'invalid',
            'priority' => 'normal',
            'confidentiality' => 'public',
        ])
        ->assertSessionHasErrors(['document_type']);
});

it('validates priority is valid when creating', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.store'), [
            'title' => 'Test',
            'document_type' => 'incoming',
            'priority' => 'invalid',
            'confidentiality' => 'public',
        ])
        ->assertSessionHasErrors(['priority']);
});

it('validates confidentiality is valid when creating', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.store'), [
            'title' => 'Test',
            'document_type' => 'incoming',
            'priority' => 'normal',
            'confidentiality' => 'invalid',
        ])
        ->assertSessionHasErrors(['confidentiality']);
});

it('validates receiving office exists when creating', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.store'), [
            'title' => 'Test',
            'document_type' => 'incoming',
            'priority' => 'normal',
            'confidentiality' => 'public',
            'receiving_office_id' => 99999,
        ])
        ->assertSessionHasErrors(['receiving_office_id']);
});

it('validates date due is after or equal to date received', function () {
    $user = User::factory()->create();
    $office = Office::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.store'), [
            'title' => 'Test',
            'document_type' => 'incoming',
            'priority' => 'normal',
            'confidentiality' => 'public',
            'receiving_office_id' => $office->id,
            'date_received' => now()->format('Y-m-d'),
            'date_due' => now()->subDay()->format('Y-m-d'),
        ])
        ->assertSessionHasErrors(['date_due']);
});

it('requires authentication to view a document', function () {
    $document = Document::factory()->create();

    $this->get(route('documents.show', $document))
        ->assertRedirect(route('login'));
});

it('can display a document', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create();

    $this->actingAs($user)
        ->get(route('documents.show', $document))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Show')
            ->has('document')
            ->where('document.id', $document->id)
            ->has('offices')
        );
});

it('loads relationships when displaying a document', function () {
    $user = User::factory()->create();
    $office = Office::factory()->create();
    $document = Document::factory()->create([
        'current_office_id' => $office->id,
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('documents.show', $document))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Show')
            ->has('document.current_office')
            ->has('document.creator')
        );
});

it('requires authentication to view edit form', function () {
    $document = Document::factory()->create();

    $this->get(route('documents.edit', $document))
        ->assertRedirect(route('login'));
});

it('can display the document edit form', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create();
    Office::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('documents.edit', $document))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Edit')
            ->has('document')
            ->where('document.id', $document->id)
            ->has('offices')
        );
});

it('requires authentication to update a document', function () {
    $document = Document::factory()->create();

    $this->put(route('documents.update', $document), [])
        ->assertRedirect(route('login'));
});

it('can update a document', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create(['title' => 'Old Title']);

    $this->actingAs($user)
        ->put(route('documents.update', $document), [
            'title' => 'New Title',
            'description' => 'Updated description',
            'priority' => 'urgent',
        ])
        ->assertRedirect(route('documents.show', $document))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('documents', [
        'id' => $document->id,
        'title' => 'New Title',
        'description' => 'Updated description',
        'priority' => 'urgent',
    ]);
});

it('validates required fields when updating a document', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create();

    $this->actingAs($user)
        ->put(route('documents.update', $document), [
            'title' => '',
        ])
        ->assertSessionHasErrors(['title']);
});

it('validates status is valid when updating', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create();

    $this->actingAs($user)
        ->put(route('documents.update', $document), [
            'status' => 'invalid_status',
        ])
        ->assertSessionHasErrors(['status']);
});

it('requires authentication to delete a document', function () {
    $document = Document::factory()->create();

    $this->delete(route('documents.destroy', $document))
        ->assertRedirect(route('login'));
});

it('can delete a document', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create();

    $this->actingAs($user)
        ->delete(route('documents.destroy', $document))
        ->assertRedirect(route('documents.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted('documents', [
        'id' => $document->id,
    ]);
});

it('requires authentication to download QR code', function () {
    $document = Document::factory()->create();

    $this->get(route('documents.qr-code.download', $document))
        ->assertRedirect(route('login'));
});

it('can download QR code', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create();
    $qrCode = QRCode::factory()->create([
        'document_id' => $document->id,
    ]);

    // Update with the correct image path after creation
    $imagePath = 'qr-codes/'.$qrCode->id.'.png';
    $qrCode->update(['image_path' => $imagePath]);

    // Create the file in the real storage path since the controller uses file_exists()
    $realPath = storage_path('app/public/'.$imagePath);
    if (! file_exists(dirname($realPath))) {
        mkdir(dirname($realPath), 0755, true);
    }
    file_put_contents($realPath, 'fake qr code content');

    $response = $this->actingAs($user)
        ->get(route('documents.qr-code.download', $document));

    $response->assertOk();
    $response->assertHeader('Content-Disposition', 'attachment; filename=qr-code-'.$document->tracking_number.'.png');

    // Clean up
    if (file_exists($realPath)) {
        unlink($realPath);
    }
});

it('redirects when QR code image not found', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create();
    QRCode::factory()->create([
        'document_id' => $document->id,
        'image_path' => null,
    ]);

    $this->actingAs($user)
        ->get(route('documents.qr-code.download', $document))
        ->assertRedirect(route('documents.show', $document))
        ->assertSessionHas('error');
});

it('redirects when QR code file does not exist', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create();
    QRCode::factory()->create([
        'document_id' => $document->id,
        'image_path' => 'qr-codes/nonexistent.png',
    ]);

    $this->actingAs($user)
        ->get(route('documents.qr-code.download', $document))
        ->assertRedirect(route('documents.show', $document))
        ->assertSessionHas('error');
});

it('requires authentication to regenerate QR code', function () {
    $document = Document::factory()->create();

    $this->post(route('documents.qr-code.regenerate', $document))
        ->assertRedirect(route('login'));
});

it('can regenerate QR code', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create();
    $oldQrCode = QRCode::factory()->create([
        'document_id' => $document->id,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->post(route('documents.qr-code.regenerate', $document))
        ->assertRedirect(route('documents.show', $document))
        ->assertSessionHas('success');

    $oldQrCode->refresh();
    expect($oldQrCode->is_active)->toBeFalse();
    expect($oldQrCode->is_regenerated)->toBeTrue();

    // Get the new QR code by querying for active QR codes for this document
    $newQrCode = \App\Models\QRCode::where('document_id', $document->id)
        ->where('is_active', true)
        ->first();

    expect($newQrCode)->not->toBeNull();
    expect($newQrCode->id)->not->toBe($oldQrCode->id);
    expect($newQrCode->is_active)->toBeTrue();
});

it('redirects when no QR code exists to regenerate', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.qr-code.regenerate', $document))
        ->assertRedirect(route('documents.show', $document))
        ->assertSessionHas('error');
});
