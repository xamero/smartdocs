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
            return Inertia::render('Offices/Index', [
                'offices' => [],
                'scope' => 'mine',
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

        return Inertia::render('Offices/Index', [
            'offices' => $visibleOffices->values(),
            'scope' => 'mine',
        ]);
    }

    /**
     * @param \Illuminate\Support\Collection<int, \App\Models\Office> $offices
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
