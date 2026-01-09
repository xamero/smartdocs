<?php

use App\Models\Document;
use App\Models\DocumentAction;
use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

it('requires authentication to create a document action', function () {
    $document = Document::factory()->create();

    $this->post(route('documents.actions.store', $document), [])
        ->assertRedirect(route('login'));
});

it('can create a document action', function () {
    $user = User::factory()->create();
    $office = Office::factory()->create();
    $user->update(['office_id' => $office->id]);
    $document = Document::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.actions.store', $document), [
            'action_type' => 'note',
            'remarks' => 'This is a test note',
        ])
        ->assertRedirect(route('documents.show', $document))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('document_actions', [
        'document_id' => $document->id,
        'office_id' => $office->id,
        'action_by' => $user->id,
        'action_type' => 'note',
        'remarks' => 'This is a test note',
        'is_office_head_approval' => false,
    ]);
});

it('can create an action with office head approval', function () {
    $user = User::factory()->create();
    $office = Office::factory()->create();
    $user->update(['office_id' => $office->id]);
    $document = Document::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.actions.store', $document), [
            'action_type' => 'approve',
            'remarks' => 'Approved by office head',
            'is_office_head_approval' => true,
        ])
        ->assertRedirect(route('documents.show', $document))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('document_actions', [
        'document_id' => $document->id,
        'action_type' => 'approve',
        'is_office_head_approval' => true,
    ]);
});

it('can upload a memo file with action', function () {
    $user = User::factory()->create();
    $office = Office::factory()->create();
    $user->update(['office_id' => $office->id]);
    $document = Document::factory()->create();
    $file = UploadedFile::fake()->create('memo.pdf', 100);

    $this->actingAs($user)
        ->post(route('documents.actions.store', $document), [
            'action_type' => 'note',
            'remarks' => 'Action with memo',
            'memo_file' => $file,
        ])
        ->assertRedirect(route('documents.show', $document))
        ->assertSessionHas('success');

    $action = DocumentAction::where('document_id', $document->id)->first();
    expect($action->memo_file_path)->not->toBeNull();
    Storage::disk('public')->assertExists($action->memo_file_path);
});

it('updates document status to in_action for approve action', function () {
    $user = User::factory()->create();
    $office = Office::factory()->create();
    $user->update(['office_id' => $office->id]);
    $document = Document::factory()->create(['status' => 'received']);

    $this->actingAs($user)
        ->post(route('documents.actions.store', $document), [
            'action_type' => 'approve',
        ]);

    $document->refresh();
    expect($document->status)->toBe('in_action');
});

it('updates document status to in_action for sign action', function () {
    $user = User::factory()->create();
    $office = Office::factory()->create();
    $user->update(['office_id' => $office->id]);
    $document = Document::factory()->create(['status' => 'received']);

    $this->actingAs($user)
        ->post(route('documents.actions.store', $document), [
            'action_type' => 'sign',
        ]);

    $document->refresh();
    expect($document->status)->toBe('in_action');
});

it('updates document status to in_action for comply action', function () {
    $user = User::factory()->create();
    $office = Office::factory()->create();
    $user->update(['office_id' => $office->id]);
    $document = Document::factory()->create(['status' => 'received']);

    $this->actingAs($user)
        ->post(route('documents.actions.store', $document), [
            'action_type' => 'comply',
        ]);

    $document->refresh();
    expect($document->status)->toBe('in_action');
});

it('updates document status to returned for return action', function () {
    $user = User::factory()->create();
    $office = Office::factory()->create();
    $user->update(['office_id' => $office->id]);
    $document = Document::factory()->create(['status' => 'in_action']);

    $this->actingAs($user)
        ->post(route('documents.actions.store', $document), [
            'action_type' => 'return',
        ]);

    $document->refresh();
    expect($document->status)->toBe('returned');
});

it('does not update document status for note action', function () {
    $user = User::factory()->create();
    $office = Office::factory()->create();
    $user->update(['office_id' => $office->id]);
    $document = Document::factory()->create(['status' => 'received']);

    $this->actingAs($user)
        ->post(route('documents.actions.store', $document), [
            'action_type' => 'note',
        ]);

    $document->refresh();
    expect($document->status)->toBe('received');
});

it('does not update document status for forward action', function () {
    $user = User::factory()->create();
    $office = Office::factory()->create();
    $user->update(['office_id' => $office->id]);
    $document = Document::factory()->create(['status' => 'received']);

    $this->actingAs($user)
        ->post(route('documents.actions.store', $document), [
            'action_type' => 'forward',
        ]);

    $document->refresh();
    expect($document->status)->toBe('received');
});

it('validates action_type is required', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.actions.store', $document), [])
        ->assertSessionHasErrors(['action_type']);
});

it('validates action_type is valid', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.actions.store', $document), [
            'action_type' => 'invalid',
        ])
        ->assertSessionHasErrors(['action_type']);
});

it('validates remarks max length', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.actions.store', $document), [
            'action_type' => 'note',
            'remarks' => str_repeat('a', 5001),
        ])
        ->assertSessionHasErrors(['remarks']);
});

it('validates memo_file is a valid file type', function () {
    $user = User::factory()->create();
    $office = Office::factory()->create();
    $user->update(['office_id' => $office->id]);
    $document = Document::factory()->create();
    $file = UploadedFile::fake()->create('memo.txt', 100);

    $this->actingAs($user)
        ->post(route('documents.actions.store', $document), [
            'action_type' => 'note',
            'memo_file' => $file,
        ])
        ->assertSessionHasErrors(['memo_file']);
});

it('validates memo_file size limit', function () {
    $user = User::factory()->create();
    $office = Office::factory()->create();
    $user->update(['office_id' => $office->id]);
    $document = Document::factory()->create();
    $file = UploadedFile::fake()->create('memo.pdf', 10241); // 10MB + 1KB

    $this->actingAs($user)
        ->post(route('documents.actions.store', $document), [
            'action_type' => 'note',
            'memo_file' => $file,
        ])
        ->assertSessionHasErrors(['memo_file']);
});

it('accepts valid memo file types', function () {
    $user = User::factory()->create();
    $office = Office::factory()->create();
    $user->update(['office_id' => $office->id]);
    $document = Document::factory()->create();

    $validTypes = ['pdf', 'doc', 'docx'];

    foreach ($validTypes as $type) {
        $file = UploadedFile::fake()->create("memo.{$type}", 100);

        $this->actingAs($user)
            ->post(route('documents.actions.store', $document), [
                'action_type' => 'note',
                'memo_file' => $file,
            ])
            ->assertRedirect(route('documents.show', $document))
            ->assertSessionHas('success');
    }
});

it('records action_at timestamp', function () {
    $user = User::factory()->create();
    $office = Office::factory()->create();
    $user->update(['office_id' => $office->id]);
    $document = Document::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.actions.store', $document), [
            'action_type' => 'note',
        ]);

    $action = DocumentAction::where('document_id', $document->id)->first();
    expect($action->action_at)->not->toBeNull();
    expect($action->action_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('uses user office_id for action', function () {
    $user = User::factory()->create();
    $office = Office::factory()->create();
    $user->update(['office_id' => $office->id]);
    $document = Document::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.actions.store', $document), [
            'action_type' => 'note',
        ]);

    $action = DocumentAction::where('document_id', $document->id)->first();
    expect($action->office_id)->toBe($office->id);
});

it('uses authenticated user as action_by', function () {
    $user = User::factory()->create();
    $office = Office::factory()->create();
    $user->update(['office_id' => $office->id]);
    $document = Document::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.actions.store', $document), [
            'action_type' => 'note',
        ]);

    $action = DocumentAction::where('document_id', $document->id)->first();
    expect($action->action_by)->toBe($user->id);
});
