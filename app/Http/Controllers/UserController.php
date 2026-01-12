<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Office;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    protected function authorizeAdmin(Request $request): RedirectResponse|null
    {
        if ($request->user()?->role !== 'admin') {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to access this page.');
        }

        return null;
    }

    public function index(Request $request): Response|RedirectResponse
    {
        if ($redirect = $this->authorizeAdmin($request)) {
            return $redirect;
        }

        $query = User::with('office')->latest();

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role') && $request->role !== '') {
            $query->where('role', $request->role);
        }

        // Filter by office
        if ($request->has('office_id') && $request->office_id !== '') {
            $query->where('office_id', $request->office_id);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $users = $query->paginate(15)->withQueryString();

        $offices = Office::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'offices' => $offices,
            'filters' => $request->only(['search', 'role', 'office_id', 'is_active']),
        ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        if ($redirect = $this->authorizeAdmin($request)) {
            return $redirect;
        }

        $offices = Office::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Users/Create', [
            'offices' => $offices,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        if ($redirect = $this->authorizeAdmin($request)) {
            return $redirect;
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => $request->role,
            'office_id' => $request->office_id,
            'is_active' => $request->boolean('is_active', true),
            'email_verified_at' => now(),
        ]);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(Request $request, User $user): Response|RedirectResponse
    {
        if ($redirect = $this->authorizeAdmin($request)) {
            return $redirect;
        }

        $user->load('office');

        return Inertia::render('Users/Show', [
            'user' => $user,
        ]);
    }

    public function edit(Request $request, User $user): Response|RedirectResponse
    {
        if ($redirect = $this->authorizeAdmin($request)) {
            return $redirect;
        }

        $offices = Office::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Users/Edit', [
            'user' => $user->load('office'),
            'offices' => $offices,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        if ($redirect = $this->authorizeAdmin($request)) {
            return $redirect;
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'office_id' => $request->office_id,
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($redirect = $this->authorizeAdmin($request)) {
            return $redirect;
        }

        // Prevent deleting yourself
        if ($user->id === $request->user()?->id) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}
