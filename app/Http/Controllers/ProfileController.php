<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $usernameChanged = $request->input('username') !== $user->username;
        $emailChanged = $request->input('email') !== $user->email;
        $requiresPassword = $usernameChanged || $emailChanged;

        $rules = [
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
        ];

        if ($requiresPassword) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $validated = $request->validate($rules);

        $user->update([
            'name'     => $validated['name'],
            'username' => $validated['username'],
            'email'    => $validated['email'],
        ]);

        return response()->json([
            'user' => $this->userPayload($user->fresh()->load('role')),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->update([
            'password' => $validated['password'],
        ]);

        return response()->json(['message' => 'Contraseña actualizada.']);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:800'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $this->deleteAvatarFile($user);

        $path = $request->file('avatar')->store("avatars/{$user->id}", 'public');

        $user->update(['avatar_path' => $path]);

        return response()->json([
            'user' => $this->userPayload($user->fresh()->load('role')),
        ]);
    }

    public function destroyAvatar(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $this->deleteAvatarFile($user);
        $user->update(['avatar_path' => null]);

        return response()->json([
            'user' => $this->userPayload($user->fresh()->load('role')),
        ]);
    }

    private function deleteAvatarFile(User $user): void
    {
        if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
        }
    }

    private function userPayload(User $user): array
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'username'   => $user->username,
            'email'      => $user->email,
            'avatar_url' => $user->avatar_path
                ? Storage::disk('public')->url($user->avatar_path)
                : null,
            'role'       => $user->role ? [
                'id'    => $user->role->id,
                'name'  => $user->role->name,
                'label' => $user->role->label,
            ] : null,
        ];
    }
}
