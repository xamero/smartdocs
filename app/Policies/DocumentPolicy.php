<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view the document list
        // Filtering is handled in the controller
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Document $document): bool
    {
        // Admin can view all documents
        if ($user->role === 'admin') {
            return true;
        }

        // User must have an office
        if (! $user->office_id) {
            return false;
        }

        // User can view documents:
        // 1. Routed to their office (current_office_id)
        // 2. Created by someone in their office (creator's office_id)
        $canView = $document->current_office_id === $user->office_id;

        // Check if creator is in the same office (load if not already loaded)
        if (! $canView && $document->created_by) {
            if (! $document->relationLoaded('creator')) {
                $document->load('creator');
            }
            $canView = $document->creator && $document->creator->office_id === $user->office_id;
        }

        // 3. Documents currently in transit/pending to their office (receiving)
        if (! $canView) {
            $canView = $document->routings()
                ->where('to_office_id', $user->office_id)
                ->whereIn('status', ['in_transit', 'pending'])
                ->exists();
        }

        return $canView;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // All authenticated users can create documents
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Document $document): bool
    {
        // Admin can update all documents
        if ($user->role === 'admin') {
            return true;
        }

        // User must have an office
        if (! $user->office_id) {
            return false;
        }

        // User can update documents:
        // 1. Routed to their office (current_office_id)
        // 2. Created by someone in their office (creator's office_id)
        $canUpdate = $document->current_office_id === $user->office_id;

        // Check if creator is in the same office (load if not already loaded)
        if (! $canUpdate && $document->created_by) {
            if (! $document->relationLoaded('creator')) {
                $document->load('creator');
            }
            $canUpdate = $document->creator && $document->creator->office_id === $user->office_id;
        }

        return $canUpdate;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Document $document): bool
    {
        // Only admin can delete documents
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Document $document): bool
    {
        // Admin can restore all documents
        if ($user->role === 'admin') {
            return true;
        }

        // User must have an office
        if (! $user->office_id) {
            return false;
        }

        // User can restore documents they can view
        return $this->view($user, $document);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Document $document): bool
    {
        // Only admin can permanently delete documents
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can route the document.
     */
    public function route(User $user, Document $document): bool
    {
        return $this->update($user, $document);
    }

    /**
     * Determine whether the user can take action on the document.
     */
    public function takeAction(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }

    /**
     * Determine whether the user can download attachments.
     */
    public function downloadAttachment(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }

    /**
     * Determine whether the user can download QR code.
     */
    public function downloadQRCode(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }

    /**
     * Determine whether the user can regenerate QR code.
     */
    public function regenerateQRCode(User $user, Document $document): bool
    {
        return $this->update($user, $document);
    }

    /**
     * Determine whether the user can archive the document.
     * Only the creating office can archive documents.
     */
    public function archive(User $user, Document $document): bool
    {
        // Admin can archive all documents
        if ($user->role === 'admin') {
            return true;
        }

        // User must have an office
        if (! $user->office_id) {
            return false;
        }

        // Only the creating office can archive
        if (! $document->relationLoaded('creator')) {
            $document->load('creator');
        }

        return $document->creator && $document->creator->office_id === $user->office_id;
    }

    /**
     * Determine whether the user can edit the document.
     * Only the creating office can edit documents.
     */
    public function canEdit(User $user, Document $document): bool
    {
        // Admin can edit all documents
        if ($user->role === 'admin') {
            return true;
        }

        // User must have an office
        if (! $user->office_id) {
            return false;
        }

        // Only the creating office can edit
        if (! $document->relationLoaded('creator')) {
            $document->load('creator');
        }

        return $document->creator && $document->creator->office_id === $user->office_id;
    }
}
