<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->with('role')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => $this->userPayload($user));

        $roles = Role::query()
            ->orderBy('name')
            ->get(['id', 'name', 'label']);

        return response()->json([
            'data'  => $users,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,username'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
            'role_id'  => ['required', 'exists:roles,id'],
        ]);

        $user = User::query()->create($validated);

        return response()->json(
            $this->userPayload($user->load('role')),
            201,
        );
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role_id'  => ['required', 'exists:roles,id'],
            'password' => ['nullable', Password::min(8)],
        ]);

        if ($user->id === $request->user()->id) {
            $sysAdminRole = Role::query()->where('name', Role::SYS_ADMIN)->firstOrFail();
            if ((int) $validated['role_id'] !== $sysAdminRole->id) {
                return response()->json([
                    'message' => 'No puedes cambiar tu propio rol.',
                ], 422);
            }
        }

        $password = $validated['password'] ?? null;
        unset($validated['password']);

        $user->update($validated);

        if ($password) {
            $user->update(['password' => $password]);
        }

        return response()->json(
            $this->userPayload($user->fresh()->load('role')),
        );
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'No puedes eliminar tu propia cuenta.',
            ], 422);
        }

        if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->delete();

        return response()->json(['message' => 'Usuario eliminado.']);
    }

    private function userPayload(User $user): array
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'username'   => $user->username,
            'email'      => $user->email,
            'role'       => $user->role ? [
                'id'    => $user->role->id,
                'name'  => $user->role->name,
                'label' => $user->role->label,
            ] : null,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}
