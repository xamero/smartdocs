<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Office;
use App\Models\SmartdocNotification;
use App\Models\User;

class NotificationService
{
    public function notifyDocumentRouted(Document $document, Office $toOffice, ?string $remarks = null): void
    {
        $users = $toOffice->users()->where('is_active', true)->get();

        foreach ($users as $user) {
            SmartdocNotification::create([
                'user_id' => $user->id,
                'office_id' => $toOffice->id,
                'document_id' => $document->id,
                'type' => 'routing',
                'title' => 'New Document Routed',
                'message' => "Document {$document->tracking_number} has been routed to your office.",
                'data' => [
                    'document_id' => $document->id,
                    'document_title' => $document->title,
                    'tracking_number' => $document->tracking_number,
                    'from_office_id' => $document->current_office_id,
                    'to_office_id' => $toOffice->id,
                    'remarks' => $remarks,
                ],
            ]);
        }
    }

    public function notifyDocumentReceived(Document $document, User $receivedBy): void
    {
        SmartdocNotification::create([
            'user_id' => $receivedBy->id,
            'office_id' => $document->current_office_id,
            'document_id' => $document->id,
            'type' => 'routing',
            'title' => 'Document Received',
            'message' => "Document {$document->tracking_number} has been received in your office.",
            'data' => [
                'document_id' => $document->id,
                'document_title' => $document->title,
                'tracking_number' => $document->tracking_number,
            ],
        ]);
    }

    public function notifyActionRequired(Document $document, Office $office, string $actionType): void
    {
        $users = $office->users()->where('is_active', true)->get();

        foreach ($users as $user) {
            SmartdocNotification::create([
                'user_id' => $user->id,
                'office_id' => $office->id,
                'document_id' => $document->id,
                'type' => 'action_required',
                'title' => 'Action Required',
                'message' => "Document {$document->tracking_number} requires your attention.",
                'data' => [
                    'document_id' => $document->id,
                    'document_title' => $document->title,
                    'tracking_number' => $document->tracking_number,
                    'action_type' => $actionType,
                ],
            ]);
        }
    }

    public function notifyOverdue(Document $document): void
    {
        if (! $document->current_office_id) {
            return;
        }

        $office = Office::find($document->current_office_id);
        if (! $office) {
            return;
        }

        $users = $office->users()->where('is_active', true)->get();

        foreach ($users as $user) {
            SmartdocNotification::create([
                'user_id' => $user->id,
                'office_id' => $office->id,
                'document_id' => $document->id,
                'type' => 'overdue',
                'title' => 'Overdue Document',
                'message' => "Document {$document->tracking_number} is overdue.",
                'data' => [
                    'document_id' => $document->id,
                    'document_title' => $document->title,
                    'tracking_number' => $document->tracking_number,
                    'date_due' => $document->date_due?->toDateString(),
                ],
            ]);
        }
    }

    public function notifyPriorityEscalation(Document $document): void
    {
        if (! $document->current_office_id) {
            return;
        }

        $office = Office::find($document->current_office_id);
        if (! $office) {
            return;
        }

        $users = $office->users()->where('is_active', true)->get();

        foreach ($users as $user) {
            SmartdocNotification::create([
                'user_id' => $user->id,
                'office_id' => $office->id,
                'document_id' => $document->id,
                'type' => 'priority_escalation',
                'title' => 'Priority Document',
                'message' => "High priority document {$document->tracking_number} requires immediate attention.",
                'data' => [
                    'document_id' => $document->id,
                    'document_title' => $document->title,
                    'tracking_number' => $document->tracking_number,
                    'priority' => $document->priority,
                ],
            ]);
        }
    }
}
