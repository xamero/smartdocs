<?php

namespace App\Http\Controllers;

use App\Http\Requests\RouteDocumentRequest;
use App\Models\Document;
use App\Models\DocumentRouting;
use App\Models\Office;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;

class DocumentRoutingController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function store(RouteDocumentRequest $request, Document $document): RedirectResponse
    {
        $this->authorize('route', $document);

        // Get the main document (if this is a copy, get the parent)
        $mainDocument = $document->is_copy ? $document->parentDocument : $document;

        // Determine if we're routing to multiple offices
        $officeIds = $request->has('to_office_ids') && count($request->to_office_ids) > 0

            ? $request->to_office_ids
            : ($request->to_office_id ? [$request->to_office_id] : []);

        // Only create copies if:
        // 1. User explicitly checked create_copies, OR
        // 2. Multiple offices are selected
        // AND the document is not already a copy
        $createCopies = ($request->boolean('create_copies', false) || count($officeIds) > 1) && ! $mainDocument->is_copy;

        $routedOffices = [];
        $maxSequence = $mainDocument->routings()->max('sequence') ?? 0;

        foreach ($officeIds as $officeId) {
            $toOffice = Office::findOrFail($officeId);

            if ($createCopies && ! $mainDocument->is_copy) {
                // Create a copy for this office
                $copyNumber = $mainDocument->copies()->count() + 1;

                $copy = Document::create([
                    'tracking_number' => $mainDocument->tracking_number . '-COPY-' . $copyNumber,
                    'title' => $mainDocument->title,
                    'description' => $mainDocument->description,
                    'document_type' => $mainDocument->document_type,
                    'source' => $mainDocument->source,
                    'priority' => $mainDocument->priority,
                    'confidentiality' => $mainDocument->confidentiality,
                    'status' => 'in_transit',
                    'current_office_id' => $mainDocument->current_office_id, // Start from main document's office
                    'receiving_office_id' => $officeId,
                    'created_by' => $mainDocument->created_by,
                    'registered_by' => $mainDocument->registered_by,
                    'date_received' => $mainDocument->date_received,
                    'date_due' => $mainDocument->date_due,
                    'is_merged' => false,
                    'is_archived' => false,
                    'parent_document_id' => $mainDocument->id,
                    'is_copy' => true,
                    'copy_number' => $copyNumber,
                    'metadata' => $mainDocument->metadata,
                ]);

                // Create routing for the copy (copy's own history)
                DocumentRouting::create([
                    'document_id' => $copy->id,
                    'from_office_id' => $mainDocument->current_office_id,
                    'to_office_id' => $officeId,
                    'routed_by' => $request->user()->id,
                    'remarks' => $request->remarks,
                    'status' => 'in_transit',
                    'routed_at' => now(),
                    'sequence' => 1, // First routing for this copy
                ]);

                // Create a summary entry in the main document's history (main document's history)
                DocumentRouting::create([
                    'document_id' => $mainDocument->id,
                    'from_office_id' => $mainDocument->current_office_id,
                    'to_office_id' => $officeId,
                    'routed_by' => $request->user()->id,
                    'remarks' => ($request->remarks ? $request->remarks . ' - ' : '') . 'Copy #' . $copyNumber . ' created and routed',
                    'status' => 'in_transit',
                    'routed_at' => now(),
                    'sequence' => ++$maxSequence,
                ]);

                // Notify users in the receiving office
                $this->notificationService->notifyDocumentRouted($copy, $toOffice, $request->remarks);
            } else {
                // Single routing (existing behavior or routing a copy)
                $targetDocument = $mainDocument->is_copy ? $document : $mainDocument;

                $routing = DocumentRouting::create([
                    'document_id' => $targetDocument->id,
                    'from_office_id' => $targetDocument->current_office_id,
                    'to_office_id' => $officeId,
                    'routed_by' => $request->user()->id,
                    'remarks' => $request->remarks,
                    'status' => 'in_transit',
                    'routed_at' => now(),
                    'sequence' => ++$maxSequence,
                ]);

                $targetDocument->update([
                    'current_office_id' => $officeId,
                    'status' => 'in_transit',
                ]);

                // Notify users in the receiving office
                $this->notificationService->notifyDocumentRouted($targetDocument, $toOffice, $request->remarks);
            }

            $routedOffices[] = $toOffice->name;
        }

        $message = $createCopies && count($officeIds) > 1
            ? 'Document copies created and routed to ' . count($officeIds) . ' offices successfully.'
            : 'Document routed successfully.';

        return redirect()->route('documents.show', $mainDocument)
            ->with('success', $message);
    }

    public function receive(Document $document, DocumentRouting $routing): RedirectResponse
    {
        if ($routing->document_id !== $document->id) {
            abort(404);
        }

        $user = request()->user();
        $this->authorize('view', $document);

        // Verify the user is from the receiving office
        if ($user->office_id !== $routing->to_office_id) {
            abort(403, 'You can only receive documents routed to your office.');
        }

        // Verify the routing is in transit or pending (for backward compatibility)
        if (! in_array($routing->status, ['in_transit', 'pending'])) {
            abort(400, 'This document routing is not in transit and cannot be received.');
        }

        // Actions when document is received:
        // 1. Update routing status to 'received'
        // 2. Record who received it (received_by)
        // 3. Record when it was received (received_at) - routing time is computed automatically
        // 4. Update document status from 'in_transit' to 'received'
        // 5. Ensure document's current_office_id matches the receiving office
        // 6. Send notification to the user who received the document

        $routing->update([
            'status' => 'received',
            'received_by' => $user->id,
            'received_at' => now(),
        ]);

        $document->update([
            'status' => 'received',
            'current_office_id' => $routing->to_office_id, // Ensure current office is set correctly
        ]);

        // Notify the user who received the document
        $this->notificationService->notifyDocumentReceived($document, $user);

        return redirect()->route('documents.show', $document)
            ->with('success', 'Document received successfully.');
    }

    public function cancel(Document $document, DocumentRouting $routing): RedirectResponse
    {
        if ($routing->document_id !== $document->id) {
            abort(404);
        }

        $user = request()->user();
        $this->authorize('view', $document);

        // Only allow canceling if the routing is in transit or pending
        if (! in_array($routing->status, ['in_transit', 'pending'])) {
            abort(400, 'Only in-transit or pending routings can be canceled.');
        }

        // Only allow canceling if the user is from the office that routed it (from_office_id)
        // or if they're an admin
        if ($user->role !== 'admin' && $user->office_id !== $routing->from_office_id) {
            abort(403, 'You can only cancel routings initiated by your office.');
        }

        // Cancel the routing by deleting it
        $routing->delete();

        // If this was the only in-transit routing, update document status back to 'received' or 'registered'
        $hasOtherInTransitRoutings = $document->routings()
            ->whereIn('status', ['in_transit', 'pending'])
            ->exists();

        if (! $hasOtherInTransitRoutings) {
            // Check if document has been received before
            $hasReceivedRoutings = $document->routings()
                ->where('status', 'received')
                ->exists();

            $document->update([
                'status' => $hasReceivedRoutings ? 'received' : 'registered',
                // Keep current_office_id as is - it should remain at the office that had it
            ]);
        }

        return redirect()->route('documents.show', $document)
            ->with('success', 'Document routing canceled successfully.');
    }
}
