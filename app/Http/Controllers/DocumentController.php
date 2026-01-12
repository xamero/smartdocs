<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Models\Document;
use App\Models\Office;
use App\Services\QRCodeService;
use App\Services\TrackingNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    public function __construct(
        protected TrackingNumberService $trackingNumberService,
        protected QRCodeService $qrCodeService
    ) {}

    public function index(Request $request): Response
    {
        $query = Document::with(['currentOffice', 'creator', 'qrCode'])
            ->latest();

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($request->has('status') && $request->status === 'archived') {
            // When filtering for archived, use is_archived flag
            $query->where('is_archived', true);
        } elseif ($request->has('status')) {
            // For other status filters, exclude archived documents
            $query->where('status', $request->status)
                ->where('is_archived', false);
        } else {
            // By default, hide archived documents from the main list
            $query->where('is_archived', false);
        }

        if ($request->has('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('office_id')) {
            $query->where('current_office_id', $request->office_id);
        }

        // Overdue filter: documents with date_due < today and not completed/archived/returned
        if ($request->has('overdue') && $request->boolean('overdue')) {
            $query->whereNotNull('date_due')
                ->whereDate('date_due', '<', now())
                ->whereNotIn('status', ['completed', 'archived', 'returned']);
        }

        // Due this week filter: documents with date_due within the next 7 days
        if ($request->has('due_this_week') && $request->boolean('due_this_week')) {
            $query->whereNotNull('date_due')
                ->whereDate('date_due', '>=', now())
                ->whereDate('date_due', '<=', now()->addDays(7))
                ->whereNotIn('status', ['completed', 'archived', 'returned']);
        }

        $documents = $query->paginate(15)->withQueryString();

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
            'filters' => $request->only(['search', 'status', 'document_type', 'priority', 'office_id']),
            'offices' => Office::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Documents/Create', [
            'offices' => Office::where('is_active', true)->orderBy('name')->get(),
           
        ]);
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $trackingNumber = $this->trackingNumberService->generate(
            $request->document_type,
            $request->prefix
        );

        $document = Document::create([
            'tracking_number' => $trackingNumber,
            'title' => $request->title,
            'description' => $request->description,
            'document_type' => $request->document_type,
            'source' => $request->source,
            'priority' => $request->priority,
            'confidentiality' => $request->confidentiality,
            'receiving_office_id' => $request->receiving_office_id,
            'current_office_id' => $request->receiving_office_id ?? $request->user()->office_id,
            'date_received' => $request->date_received,
            'date_due' => $request->date_due,
            'created_by' => $request->user()->id,
            'registered_by' => $request->user()->id,
            'status' => 'registered',
            'metadata' => $request->metadata,
        ]);

        // Generate QR code
        $this->qrCodeService->generateForDocument($document);

        return redirect()->route('documents.show', $document)
            ->with('success', 'Document created successfully.');
    }

    public function show(Document $document): Response
    {
        $document->load([
            'currentOffice',
            'receivingOffice',
            'creator',
            'registrar',
            'routings.fromOffice',
            'routings.toOffice',
            'routings.routedBy',
            'routings.receivedBy',
            'actions.office',
            'actions.actionBy',
            'attachments.uploadedBy',
            'qrCode',
        ]);

        return Inertia::render('Documents/Show', [
            'document' => $document,
            'offices' => Office::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function edit(Document $document): Response
    {
        return Inertia::render('Documents/Edit', [
            'document' => $document,
            'offices' => Office::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateDocumentRequest $request, Document $document): RedirectResponse
    {
        $document->update($request->validated());

        return redirect()->route('documents.show', $document)
            ->with('success', 'Document updated successfully.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $document->delete();

        return redirect()->route('documents.index')
            ->with('success', 'Document deleted successfully.');
    }

    public function downloadQRCode(Document $document): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        $qrCode = $document->qrCode;

        if (! $qrCode || ! $qrCode->image_path) {
            return redirect()->route('documents.show', $document)
                ->with('error', 'QR code image not found.');
        }

        $path = storage_path('app/public/'.$qrCode->image_path);

        if (! file_exists($path)) {
            return redirect()->route('documents.show', $document)
                ->with('error', 'QR code file not found.');
        }

        return response()->download($path, 'qr-code-'.$document->tracking_number.'.png');
    }

    public function regenerateQRCode(Document $document): RedirectResponse
    {
        if (! $document->qrCode) {
            return redirect()->route('documents.show', $document)
                ->with('error', 'No QR code found to regenerate.');
        }

        $this->qrCodeService->regenerate($document->qrCode);

        return redirect()->route('documents.show', $document)
            ->with('success', 'QR code regenerated successfully.');
    }

    public function archive(Document $document): RedirectResponse
    {
        $document->update([
            'status' => 'archived',
            'is_archived' => true,
            'archived_at' => now(),
        ]);

        return redirect()->route('documents.show', $document)
            ->with('success', 'Document archived successfully.');
    }

    public function restore(Document $document): RedirectResponse
    {
        $document->update([
            'status' => 'completed',
            'is_archived' => false,
            'archived_at' => null,
        ]);

        return redirect()->route('documents.show', $document)
            ->with('success', 'Document restored successfully.');
    }

    public function import(\App\Http\Requests\ImportDocumentsRequest $request): RedirectResponse
    {
        $file = $request->file('file');

        if (! $file) {
            return redirect()->route('documents.index')
                ->with('error', 'No file uploaded.');
        }

        $handle = fopen($file->getRealPath(), 'rb');

        if (! $handle) {
            return redirect()->route('documents.index')
                ->with('error', 'Unable to read import file.');
        }

        $header = fgetcsv($handle);

        if (! $header) {
            fclose($handle);

            return redirect()->route('documents.index')
                ->with('error', 'Import file is empty.');
        }

        $header = array_map('trim', $header);

        $created = 0;
        $failed = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === 1 && trim((string) $row[0]) === '') {
                continue;
            }

            $data = array_combine($header, $row);

            if (! $data) {
                $failed++;
                continue;
            }

            try {
                $documentType = $data['document_type'] ?? 'incoming';
                $priority = $data['priority'] ?? 'normal';
                $confidentiality = $data['confidentiality'] ?? 'public';

                $trackingNumber = $this->trackingNumberService->generate($documentType);

                Document::create([
                    'tracking_number' => $trackingNumber,
                    'title' => $data['title'] ?? 'Untitled',
                    'description' => $data['description'] ?? null,
                    'document_type' => $documentType,
                    'source' => $data['source'] ?? null,
                    'priority' => $priority,
                    'confidentiality' => $confidentiality,
                    'receiving_office_id' => $data['receiving_office_id'] ?? null,
                    'current_office_id' => $data['receiving_office_id'] ?? $request->user()->office_id,
                    'date_received' => $data['date_received'] ?? null,
                    'date_due' => $data['date_due'] ?? null,
                    'created_by' => $request->user()->id,
                    'registered_by' => $request->user()->id,
                    'status' => 'registered',
                    'metadata' => null,
                ]);

                $created++;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Document import row failed', [
                    'error' => $e->getMessage(),
                    'row' => $data,
                ]);
            }
        }

        fclose($handle);

        return redirect()->route('documents.index')
            ->with('success', "Imported {$created} documents. {$failed} rows failed.");
    }
}
