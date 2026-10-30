<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SortsQueries;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    use SortsQueries;

    public function index(Request $request)
    {
        $search = $request->string('search')->trim()->toString();

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            });

        $sorting = $this->applySort($users, $request, ['name', 'email', 'role', 'is_active', 'created_at'], 'created_at');

        return Inertia::render('Users/Index', [
            'title' => 'Data Pengguna',
            'subtitle' => 'Kelola akun yang dapat mengakses aplikasi',
            'users' => $this->paginateQuery($users, $request),
            'filters' => ['search' => $search, ...$sorting],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return back()->with('success', 'Data pengguna berhasil ditambahkan.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if ($request->user()->is($user)) {
            $data['role'] = 'superadmin';
            $data['is_active'] = true;
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return back()->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return back()->with('success', 'Data pengguna berhasil dihapus.');
    }
}
