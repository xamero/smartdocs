<?php

use App\Models\Document;
use App\Models\DocumentRouting;
use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication to route a document', function () {
    $document = Document::factory()->create();

    $this->post(route('documents.route', $document), [])
        ->assertRedirect(route('login'));
});

it('can route a document to another office', function () {
    $user = User::factory()->create();
    $fromOffice = Office::factory()->create();
    $toOffice = Office::factory()->create();
    $document = Document::factory()->create([
        'current_office_id' => $fromOffice->id,
        'status' => 'registered',
    ]);

    $this->actingAs($user)
        ->post(route('documents.route', $document), [
            'to_office_id' => $toOffice->id,
            'remarks' => 'Routing for review',
        ])
        ->assertRedirect(route('documents.show', $document))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('document_routings', [
        'document_id' => $document->id,
        'from_office_id' => $fromOffice->id,
        'to_office_id' => $toOffice->id,
        'routed_by' => $user->id,
        'remarks' => 'Routing for review',
        'status' => 'pending',
    ]);

    $document->refresh();
    expect($document->current_office_id)->toBe($toOffice->id);
    expect($document->status)->toBe('in_transit');
});

it('updates document status to in_transit when routed', function () {
    $user = User::factory()->create();
    $fromOffice = Office::factory()->create();
    $toOffice = Office::factory()->create();
    $document = Document::factory()->create([
        'current_office_id' => $fromOffice->id,
        'status' => 'registered',
    ]);

    $this->actingAs($user)
        ->post(route('documents.route', $document), [
            'to_office_id' => $toOffice->id,
        ]);

    $document->refresh();
    expect($document->status)->toBe('in_transit');
});

it('assigns sequence number when routing a document', function () {
    $user = User::factory()->create();
    $fromOffice = Office::factory()->create();
    $toOffice = Office::factory()->create();
    $document = Document::factory()->create([
        'current_office_id' => $fromOffice->id,
    ]);

    // Create first routing
    DocumentRouting::factory()->create([
        'document_id' => $document->id,
        'sequence' => 1,
    ]);

    $this->actingAs($user)
        ->post(route('documents.route', $document), [
            'to_office_id' => $toOffice->id,
        ]);

    $routing = DocumentRouting::where('document_id', $document->id)
        ->where('to_office_id', $toOffice->id)
        ->first();

    expect($routing->sequence)->toBe(2);
});

it('assigns sequence 1 for first routing', function () {
    $user = User::factory()->create();
    $fromOffice = Office::factory()->create();
    $toOffice = Office::factory()->create();
    $document = Document::factory()->create([
        'current_office_id' => $fromOffice->id,
    ]);

    $this->actingAs($user)
        ->post(route('documents.route', $document), [
            'to_office_id' => $toOffice->id,
        ]);

    $routing = DocumentRouting::where('document_id', $document->id)
        ->where('to_office_id', $toOffice->id)
        ->first();

    expect($routing->sequence)->toBe(1);
});

it('validates to_office_id is required', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.route', $document), [])
        ->assertSessionHasErrors(['to_office_id']);
});

it('validates to_office_id exists', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.route', $document), [
            'to_office_id' => 99999,
        ])
        ->assertSessionHasErrors(['to_office_id']);
});

it('validates remarks is optional', function () {
    $user = User::factory()->create();
    $fromOffice = Office::factory()->create();
    $toOffice = Office::factory()->create();
    $document = Document::factory()->create([
        'current_office_id' => $fromOffice->id,
    ]);

    $this->actingAs($user)
        ->post(route('documents.route', $document), [
            'to_office_id' => $toOffice->id,
        ])
        ->assertRedirect(route('documents.show', $document))
        ->assertSessionHas('success');
});

it('validates remarks max length', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create();
    $office = Office::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.route', $document), [
            'to_office_id' => $office->id,
            'remarks' => str_repeat('a', 1001),
        ])
        ->assertSessionHasErrors(['remarks']);
});

it('requires authentication to receive a document', function () {
    $document = Document::factory()->create();
    $routing = DocumentRouting::factory()->create([
        'document_id' => $document->id,
    ]);

    $this->post(route('documents.routings.receive', [$document, $routing]))
        ->assertRedirect(route('login'));
});

it('can receive a routed document', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create([
        'status' => 'in_transit',
    ]);
    $routing = DocumentRouting::factory()->pending()->create([
        'document_id' => $document->id,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->post(route('documents.routings.receive', [$document, $routing]))
        ->assertRedirect(route('documents.show', $document))
        ->assertSessionHas('success');

    $routing->refresh();
    expect($routing->status)->toBe('received');
    expect($routing->received_by)->toBe($user->id);
    expect($routing->received_at)->not->toBeNull();

    $document->refresh();
    expect($document->status)->toBe('received');
});

it('returns 404 when routing does not belong to document', function () {
    $user = User::factory()->create();
    $document1 = Document::factory()->create();
    $document2 = Document::factory()->create();
    $routing = DocumentRouting::factory()->create([
        'document_id' => $document1->id,
    ]);

    $this->actingAs($user)
        ->post(route('documents.routings.receive', [$document2, $routing]))
        ->assertNotFound();
});

it('updates document status to received when routing is received', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create([
        'status' => 'in_transit',
    ]);
    $routing = DocumentRouting::factory()->pending()->create([
        'document_id' => $document->id,
    ]);

    $this->actingAs($user)
        ->post(route('documents.routings.receive', [$document, $routing]));

    $document->refresh();
    expect($document->status)->toBe('received');
});

it('records received_by and received_at when document is received', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create();
    $routing = DocumentRouting::factory()->pending()->create([
        'document_id' => $document->id,
        'received_by' => null,
        'received_at' => null,
    ]);

    $this->actingAs($user)
        ->post(route('documents.routings.receive', [$document, $routing]));

    $routing->refresh();
    expect($routing->received_by)->toBe($user->id);
    expect($routing->received_at)->not->toBeNull();
    expect($routing->received_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});
