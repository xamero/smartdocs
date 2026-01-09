<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateDocumentActionRequest;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;

class DocumentActionController extends Controller
{
    public function store(CreateDocumentActionRequest $request, Document $document): RedirectResponse
    {
        $memoPath = null;

        if ($request->hasFile('memo_file')) {
            $memoPath = $request->file('memo_file')->store('document-memos', 'public');
        }

        $document->actions()->create([
            'office_id' => $request->user()->office_id,
            'action_by' => $request->user()->id,
            'action_type' => $request->action_type,
            'remarks' => $request->remarks,
            'memo_file_path' => $memoPath,
            'is_office_head_approval' => $request->is_office_head_approval ?? false,
            'action_at' => now(),
        ]);

        // Update document status based on action
        if (in_array($request->action_type, ['approve', 'sign', 'comply'])) {
            $document->update(['status' => 'in_action']);
        } elseif ($request->action_type === 'return') {
            $document->update(['status' => 'returned']);
        }

        return redirect()->route('documents.show', $document)
            ->with('success', 'Action recorded successfully.');
    }
}
