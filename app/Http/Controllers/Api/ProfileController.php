<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\BdPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'email' => ['sometimes', 'nullable', 'email', 'max:190', 'unique:users,email,'.$user->id],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'clinic_name' => ['sometimes', 'string', 'max:190'],
            'specialty' => ['sometimes', 'nullable', 'string', 'max:190'],
            'qualifications' => ['sometimes', 'nullable', 'string'],
            'bmdc_reg' => ['sometimes', 'nullable', 'string', 'max:100'],
            'clinic_address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'clinic_phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'visiting_hours' => ['sometimes', 'nullable', 'string', 'max:255'],
            'appointment_slug' => ['sometimes', 'string', 'max:80', 'alpha_dash', 'unique:users,appointment_slug,'.$user->id],
        ]);

        if (array_key_exists('phone', $data) && $data['phone']) {
            $phone = BdPhone::normalize($data['phone']);
            if (!$phone) {
                throw ValidationException::withMessages(['phone' => 'Invalid Bangladesh phone number.']);
            }
            $data['phone'] = $phone;
        }

        if (array_key_exists('clinic_phone', $data) && $data['clinic_phone']) {
            $clinicPhone = BdPhone::normalize($data['clinic_phone']);
            if (!$clinicPhone) {
                throw ValidationException::withMessages(['clinic_phone' => 'Invalid Bangladesh clinic phone.']);
            }
            $data['clinic_phone'] = $clinicPhone;
        }

        if (empty($data['appointment_slug']) && !$user->appointment_slug) {
            $data['appointment_slug'] = Str::slug($user->name).'-'.Str::lower(Str::random(4));
        }

        $user->update($data);

        return response()->json(['user' => $user->fresh()]);
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate(['avatar' => ['required', 'image', 'max:2048']]);
        $user = $request->user();
        $path = $request->file('avatar')->store('avatars', 'public');
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }
        $user->update(['avatar_path' => $path]);

        return response()->json(['user' => $user->fresh()]);
    }

    public function uploadLogo(Request $request)
    {
        $request->validate(['logo' => ['required', 'image', 'max:2048']]);
        $user = $request->user();
        $path = $request->file('logo')->store('logos', 'public');
        if ($user->logo_path) {
            Storage::disk('public')->delete($user->logo_path);
        }
        $user->update(['logo_path' => $path]);

        return response()->json(['user' => $user->fresh()]);
    }
}
