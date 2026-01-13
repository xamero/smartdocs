<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfficeRequest;
use App\Models\Office;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class OfficeController extends Controller
{
    public function index(): Response
    {
        $offices = Office::with('parent', 'children')
            ->withCount([
                'documents as documents_total_count',
                'documents as documents_in_transit_count' => fn ($query) => $query->where('status', 'in_transit'),
                'documents as documents_in_action_count' => fn ($query) => $query->where('status', 'in_action'),
                'documents as documents_completed_count' => fn ($query) => $query->where('status', 'completed'),
                'documents as documents_archived_count' => fn ($query) => $query->where('status', 'archived'),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Offices/Index', [
            'offices' => $offices,
            'scope' => 'all',
        ]);
    }

    public function my(): Response
    {
        $user = request()->user();

        if (! $user || ! $user->office_id) {
            $emptyDocuments = new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]),
                0,
                15,
                1
            );

            return Inertia::render('Offices/Index', [
                'offices' => [],
                'scope' => 'mine',
                'documents' => $emptyDocuments,
            ]);
        }

        $allOffices = Office::with('parent', 'children')
            ->withCount([
                'documents as documents_total_count',
                'documents as documents_in_transit_count' => fn ($query) => $query->where('status', 'in_transit'),
                'documents as documents_in_action_count' => fn ($query) => $query->where('status', 'in_action'),
                'documents as documents_completed_count' => fn ($query) => $query->where('status', 'completed'),
                'documents as documents_archived_count' => fn ($query) => $query->where('status', 'archived'),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $visibleOffices = $this->filterOfficesByRoot($allOffices, (int) $user->office_id);

        // Fetch documents for the user's office with filters
        $request = request();
        $query = \App\Models\Document::with(['currentOffice', 'creator', 'qrCode'])
            ->where(function ($q) use ($user) {
                $q->where('current_office_id', $user->office_id)
                    ->orWhereHas('creator', function ($creatorQuery) use ($user) {
                        $creatorQuery->where('office_id', $user->office_id);
                    })
                    ->orWhereHas('routings', function ($routingQuery) use ($user) {
                        $routingQuery
                            ->where('to_office_id', $user->office_id)
                            ->whereIn('status', ['in_transit', 'pending']);
                    });
            });

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->has('status') && $request->status === 'archived') {
            $query->where('is_archived', true);
        } elseif ($request->has('status')) {
            $query->where('status', $request->status)
                ->where('is_archived', false);
        } else {
            // By default, hide archived documents
            $query->where('is_archived', false);
        }

        // Document type filter
        if ($request->has('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        // Priority filter
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Overdue filter
        if ($request->has('overdue') && $request->boolean('overdue')) {
            $query->whereNotNull('date_due')
                ->whereDate('date_due', '<', now())
                ->whereNotIn('status', ['completed', 'archived', 'returned']);
        }

        // Due this week filter
        if ($request->has('due_this_week') && $request->boolean('due_this_week')) {
            $query->whereNotNull('date_due')
                ->whereDate('date_due', '>=', now())
                ->whereDate('date_due', '<=', now()->addDays(7))
                ->whereNotIn('status', ['completed', 'archived', 'returned']);
        }

        $documents = $query->latest()->paginate(15)->withQueryString();

        $documents->through(function (\App\Models\Document $document) use ($user) {
            if (! $document->relationLoaded('creator')) {
                $document->load('creator');
            }

            $isOriginatingOffice = $document->creator && $document->creator->office_id === $user->office_id;
            $isIncomingToOffice = $document->routings()
                ->where('to_office_id', $user->office_id)
                ->whereIn('status', ['in_transit', 'pending'])
                ->exists();

            return array_merge($document->toArray(), [
                'is_originating_office' => $isOriginatingOffice,
                'is_incoming_to_office' => $isIncomingToOffice,
            ]);
        });

        return Inertia::render('Offices/Index', [
            'offices' => $visibleOffices->values(),
            'scope' => 'mine',
            'documents' => $documents,
            'filters' => $request->only(['search', 'status', 'document_type', 'priority', 'overdue', 'due_this_week']),
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Office>  $offices
     * @return \Illuminate\Support\Collection<int, \App\Models\Office>
     */
    protected function filterOfficesByRoot(Collection $offices, int $rootId): Collection
    {
        $ids = [];
        $stack = [$rootId];

        while (! empty($stack)) {
            $currentId = array_pop($stack);

            if (in_array($currentId, $ids, true)) {
                continue;
            }

            $ids[] = $currentId;

            foreach ($offices as $office) {
                if ($office->parent_id === $currentId) {
                    $stack[] = $office->id;
                }
            }
        }

        return $offices->whereIn('id', $ids);
    }

    public function store(StoreOfficeRequest $request): RedirectResponse
    {
        Office::create($request->validated());

        return redirect()->route('offices.index')
            ->with('success', 'Office created successfully.');
    }

    public function update(StoreOfficeRequest $request, Office $office): RedirectResponse
    {
        $office->update($request->validated());

        return redirect()->route('offices.index')
            ->with('success', 'Office updated successfully.');
    }

    public function destroy(Office $office): RedirectResponse
    {
        if ($office->users()->exists() || $office->documents()->exists()) {
            return redirect()->route('offices.index')
                ->with('error', 'Cannot delete office with associated users or documents.');
        }

        $office->delete();

        return redirect()->route('offices.index')
            ->with('success', 'Office deleted successfully.');
    }
}
