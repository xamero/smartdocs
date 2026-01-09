<?php

use App\Models\Document;
use App\Models\DocumentAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

it('requires authentication to upload an attachment', function () {
    $document = Document::factory()->create();

    $this->post(route('documents.attachments.store', $document), [])
        ->assertRedirect(route('login'));
});

it('can upload an attachment for a document', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create();
    $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

    $this->actingAs($user)
        ->post(route('documents.attachments.store', $document), [
            'file' => $file,
            'description' => 'Test attachment',
        ])
        ->assertRedirect(route('documents.show', $document))
        ->assertSessionHas('success');

    $attachment = DocumentAttachment::where('document_id', $document->id)->first();

    expect($attachment)->not->toBeNull();
    expect($attachment->original_name)->toBe('test.pdf');
    expect($attachment->description)->toBe('Test attachment');
    expect($attachment->uploaded_by)->toBe($user->id);
    expect($attachment->status)->toBe('active');

    Storage::disk('public')->assertExists($attachment->file_path);
});

it('validates file is required when uploading', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.attachments.store', $document), [])
        ->assertSessionHasErrors(['file']);
});

it('can download an attachment', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create();

    $storedPath = "attachments/{$document->id}/download.pdf";

    $realPath = storage_path('app/public/'.$storedPath);
    if (! file_exists(dirname($realPath))) {
        mkdir(dirname($realPath), 0755, true);
    }
    file_put_contents($realPath, 'fake attachment content');

    $attachment = DocumentAttachment::factory()->create([
        'document_id' => $document->id,
        'file_path' => $storedPath,
        'file_name' => basename($storedPath),
        'original_name' => 'download.pdf',
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)
        ->get(route('documents.attachments.download', [$document, $attachment]));

    $response->assertOk();
    $response->assertHeader('Content-Disposition', 'attachment; filename=download.pdf');

    if (file_exists($realPath)) {
        unlink($realPath);
    }
});

it('returns 404 when downloading attachment for another document', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create();
    $otherDocument = Document::factory()->create();

    $attachment = DocumentAttachment::factory()->create([
        'document_id' => $otherDocument->id,
    ]);

    $this->actingAs($user)
        ->get(route('documents.attachments.download', [$document, $attachment]))
        ->assertNotFound();
});

it('marks attachment as replaced when deleting', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create();

    $attachment = DocumentAttachment::factory()->create([
        'document_id' => $document->id,
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->delete(route('documents.attachments.destroy', [$document, $attachment]))
        ->assertRedirect(route('documents.show', $document))
        ->assertSessionHas('success');

    $attachment->refresh();
    expect($attachment->status)->toBe('replaced');
    expect($attachment->deleted_at)->not->toBeNull();
});
