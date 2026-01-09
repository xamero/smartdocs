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
        $toOffice = Office::findOrFail($request->to_office_id);

        $routing = DocumentRouting::create([
            'document_id' => $document->id,
            'from_office_id' => $document->current_office_id,
            'to_office_id' => $request->to_office_id,
            'routed_by' => $request->user()->id,
            'remarks' => $request->remarks,
            'status' => 'pending',
            'routed_at' => now(),
            'sequence' => $document->routings()->max('sequence') + 1 ?? 1,
        ]);

        $document->update([
            'current_office_id' => $request->to_office_id,
            'status' => 'in_transit',
        ]);

        // Notify users in the receiving office
        $this->notificationService->notifyDocumentRouted($document, $toOffice, $request->remarks);

        return redirect()->route('documents.show', $document)
            ->with('success', 'Document routed successfully.');
    }

    public function receive(Document $document, DocumentRouting $routing): RedirectResponse
    {
        if ($routing->document_id !== $document->id) {
            abort(404);
        }

        $user = request()->user();

        $routing->update([
            'status' => 'received',
            'received_by' => $user->id,
            'received_at' => now(),
        ]);

        $document->update([
            'status' => 'received',
        ]);

        // Notify the user who received the document
        $this->notificationService->notifyDocumentReceived($document, $user);

        return redirect()->route('documents.show', $document)
            ->with('success', 'Document received successfully.');
    }
}
