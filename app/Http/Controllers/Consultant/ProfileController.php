<?php

namespace App\Http\Controllers\Consultant;

use App\Http\Controllers\Controller;
use App\Models\ConsultantProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        $profile = $user->consultantProfile ?? new ConsultantProfile();
        return view('consultant.profile.edit', compact('user', 'profile'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'specialty' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'experience_years' => ['required', 'integer', 'min:0'],
            'qualifications' => ['nullable', 'string'],
            'languages' => ['nullable', 'string'],
            'meeting_url' => ['nullable', 'url', 'max:500'],
            'chatwork_account_id' => ['nullable', 'string', 'max:50'],
            'booking_acceptance_enabled' => ['boolean'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);

        $user = auth()->user();

        $profileData = [
            'specialty' => $validated['specialty'],
            'bio' => $validated['bio'],
            'experience_years' => $validated['experience_years'],
            'qualifications' => $validated['qualifications'] ? array_map('trim', explode(',', $validated['qualifications'])) : [],
            'languages' => $validated['languages'] ? array_map('trim', explode(',', $validated['languages'])) : [],
            'meeting_url' => $validated['meeting_url'],
            'chatwork_account_id' => $validated['chatwork_account_id'],
            'booking_acceptance_enabled' => $request->boolean('booking_acceptance_enabled'),
        ];

        if ($request->hasFile('photo')) {
            $existingProfile = $user->consultantProfile;
            if ($existingProfile && $existingProfile->photo) {
                Storage::disk('public')->delete($existingProfile->photo);
            }
            $path = $request->file('photo')->store('consultant_photos', 'public');
            $profileData['photo'] = $path;
        }

        $user->consultantProfile()->updateOrCreate(
            ['user_id' => $user->id],
            $profileData
        );

        // Also update user info
        $user->update([
            'name' => $request->input('name'),
            'phone' => $request->input('phone'),
            'notify_email' => $request->boolean('notify_email'),
            'notify_line' => $request->boolean('notify_line'),
        ]);

        return back()->with('success', 'プロフィールを更新しました。');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = auth()->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => '現在のパスワードが正しくありません。']);
        }

        $user->update(['password' => Hash::make($validated['password'])]);

        return back()->with('success', 'パスワードを変更しました。');
    }

    public function deletePhoto()
    {
        $profile = auth()->user()->consultantProfile;

        if ($profile && $profile->photo) {
            Storage::disk('public')->delete($profile->photo);
            $profile->update(['photo' => null]);
        }

        return back()->with('success', 'プロフィール写真を削除しました。');
    }
}
