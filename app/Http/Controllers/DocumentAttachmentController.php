<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentAttachmentRequest;
use App\Models\Document;
use App\Models\DocumentAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentAttachmentController extends Controller
{
    public function store(StoreDocumentAttachmentRequest $request, Document $document): RedirectResponse
    {
        $file = $request->file('file');

        $version = (int) $document->attachments()->max('version') + 1;
        $sortOrder = (int) $document->attachments()->max('sort_order') + 1;

        $storedPath = $file->store("attachments/{$document->id}", 'public');

        $attachment = $document->attachments()->create([
            'file_name' => basename($storedPath),
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'file_hash' => hash_file('sha256', $file->getRealPath()),
            'uploaded_by' => $request->user()->id,
            'upload_ip_address' => $request->ip(),
            'upload_user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
            'description' => $request->input('description'),
            'sort_order' => $sortOrder,
            'version' => $version,
            'status' => 'active',
            'uploaded_at' => now(),
        ]);

        return redirect()->route('documents.show', $document)
            ->with('success', 'Attachment uploaded successfully.');
    }

    public function download(Document $document, DocumentAttachment $attachment)
    {
        if ($attachment->document_id !== $document->id) {
            abort(404);
        }

        $path = storage_path('app/public/'.$attachment->file_path);

        if (! file_exists($path)) {
            return redirect()->route('documents.show', $document)
                ->with('error', 'Attachment file not found.');
        }

        return response()->download(
            $path,
            $attachment->original_name,
            $attachment->mime_type ? ['Content-Type' => $attachment->mime_type] : []
        );
    }

    public function destroy(Document $document, DocumentAttachment $attachment): RedirectResponse
    {
        if ($attachment->document_id !== $document->id) {
            abort(404);
        }

        $attachment->update([
            'status' => 'replaced',
            'deleted_by' => request()->user()?->id,
        ]);

        $attachment->delete();

        return redirect()->route('documents.show', $document)
            ->with('success', 'Attachment removed successfully.');
    }
}
