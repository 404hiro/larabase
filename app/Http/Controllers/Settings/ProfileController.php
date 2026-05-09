<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($request->boolean(key: 'remove_avatar')) {
            if ($user->avatar) {
                Storage::disk(name: 'public')->delete($user->avatar);
            }

            $validated['avatar'] = null;
        }

        if ($request->hasFile(key: 'avatar')) {
            if ($user->avatar) {
                Storage::disk(name: 'public')->delete($user->avatar);
            }

            $path = $request->file(key: 'avatar')->store('avatars', 'public');
            $validated['avatar'] = $path;
        }

        $user->fill(attributes: $validated);

        $user->save();

        return to_route(route: 'profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
