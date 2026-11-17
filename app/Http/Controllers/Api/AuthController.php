<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\DoctorSetupService;
use App\Services\SmsService;
use App\Support\BdPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request, SmsService $sms, DoctorSetupService $setup)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'clinic_name' => ['required', 'string', 'max:190'],
            'specialty' => ['nullable', 'string', 'max:190'],
            'clinic_phone' => ['nullable', 'string', 'max:20'],
            'clinic_address' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'email' => ['nullable', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'appointment_slug' => ['nullable', 'string', 'max:80', 'alpha_dash', 'unique:users,appointment_slug'],
        ]);

        if (empty($data['email']) && empty($data['phone'])) {
            throw ValidationException::withMessages([
                'email' => 'Provide either an email or a Bangladeshi phone number.',
            ]);
        }

        $phone = null;
        if (!empty($data['phone'])) {
            $phone = BdPhone::normalize($data['phone']);
            if (!$phone) {
                throw ValidationException::withMessages([
                    'phone' => 'Phone must be a valid Bangladesh number (01XXXXXXXXX).',
                ]);
            }
            if (User::where('phone', $phone)->exists()) {
                throw ValidationException::withMessages([
                    'phone' => 'This phone number is already registered.',
                ]);
            }
        }

        $clinicPhone = null;
        if (!empty($data['clinic_phone'])) {
            $clinicPhone = BdPhone::normalize($data['clinic_phone']);
            if (!$clinicPhone) {
                throw ValidationException::withMessages([
                    'clinic_phone' => 'Clinic phone must be a valid Bangladesh number.',
                ]);
            }
        }

        $slugSource = $data['appointment_slug'] ?? $data['clinic_name'];
        $slug = $setup->uniqueAppointmentSlug($slugSource);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $phone,
            'password' => $data['password'],
            'clinic_name' => $data['clinic_name'],
            'specialty' => $data['specialty'] ?? 'Oral and Dental Surgeon',
            'clinic_phone' => $clinicPhone ?: $phone,
            'clinic_address' => $data['clinic_address'] ?? null,
            'visiting_hours' => '10:00 AM – 2:00 PM & 4:00 PM – 8:00 PM',
            'appointment_slug' => $slug,
        ]);

        $setup->seedDefaultAvailability($user);

        if ($user->email) {
            $token = Str::random(40);
            VerificationCode::create([
                'user_id' => $user->id,
                'channel' => 'email',
                'code' => $token,
                'expires_at' => now()->addDay(),
            ]);
            $link = rtrim(config('app.frontend_url'), '/').'/verify?channel=email&token='.$token;
            Mail::raw("Verify your AR Dental Care account: {$link}", function ($message) use ($user) {
                $message->to($user->email)->subject('Verify your AR Dental Care account');
            });
        }

        if ($user->phone) {
            $otp = (string) random_int(100000, 999999);
            VerificationCode::create([
                'user_id' => $user->id,
                'channel' => 'sms',
                'code' => $otp,
                'expires_at' => now()->addMinutes(15),
            ]);
            $sms->send($user->phone, "AR Dental Care OTP: {$otp}");
        }

        return response()->json([
            'message' => 'Registered. Please verify your account.',
            'user' => $user->fresh(),
            'appointment_url' => $user->appointment_url,
            'verification' => [
                'email' => (bool) $user->email,
                'sms' => (bool) $user->phone,
                // Local/dev convenience: expose OTP when using log SMS driver
                'dev_otp' => config('services.sms.driver') === 'log' && $user->phone
                    ? VerificationCode::where('user_id', $user->id)->where('channel', 'sms')->latest()->value('code')
                    : null,
            ],
        ], 201);
    }

    public function verify(Request $request)
    {
        $data = $request->validate([
            'channel' => ['required', Rule::in(['email', 'sms'])],
            'code' => ['required', 'string'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
        ]);

        $query = VerificationCode::query()
            ->where('channel', $data['channel'])
            ->where('code', $data['code'])
            ->whereNull('used_at')
            ->where('expires_at', '>', now());

        if ($data['channel'] === 'email' && !empty($data['email'])) {
            $user = User::where('email', $data['email'])->first();
            if ($user) {
                $query->where('user_id', $user->id);
            }
        }

        if ($data['channel'] === 'sms' && !empty($data['phone'])) {
            $phone = BdPhone::normalize($data['phone']);
            $user = $phone ? User::where('phone', $phone)->first() : null;
            if ($user) {
                $query->where('user_id', $user->id);
            }
        }

        $record = $query->latest()->first();
        if (!$record) {
            throw ValidationException::withMessages(['code' => 'Invalid or expired verification code.']);
        }

        $user = $record->user;
        $record->update(['used_at' => now()]);

        if ($data['channel'] === 'email') {
            $user->email_verified_at = now();
        } else {
            $user->phone_verified_at = now();
        }
        $user->save();

        $token = $user->createToken('doctor')->plainTextToken;

        return response()->json([
            'message' => 'Verified successfully.',
            'token' => $token,
            'user' => $user->fresh(),
        ]);
    }

    public function resend(Request $request, SmsService $sms)
    {
        $data = $request->validate([
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
        ]);

        $user = null;
        if (!empty($data['email'])) {
            $user = User::where('email', $data['email'])->first();
        } elseif (!empty($data['phone'])) {
            $phone = BdPhone::normalize($data['phone']);
            $user = $phone ? User::where('phone', $phone)->first() : null;
        }

        if (!$user) {
            throw ValidationException::withMessages(['email' => 'Account not found.']);
        }

        $devOtp = null;
        if ($user->email && !$user->email_verified_at) {
            $token = Str::random(40);
            VerificationCode::create([
                'user_id' => $user->id,
                'channel' => 'email',
                'code' => $token,
                'expires_at' => now()->addDay(),
            ]);
            $link = rtrim(config('app.frontend_url'), '/').'/verify?channel=email&token='.$token;
            Mail::raw("Verify your AR Dental Care account: {$link}", function ($message) use ($user) {
                $message->to($user->email)->subject('Verify your AR Dental Care account');
            });
        }

        if ($user->phone && !$user->phone_verified_at) {
            $otp = (string) random_int(100000, 999999);
            VerificationCode::create([
                'user_id' => $user->id,
                'channel' => 'sms',
                'code' => $otp,
                'expires_at' => now()->addMinutes(15),
            ]);
            $sms->send($user->phone, "AR Dental Care OTP: {$otp}");
            $devOtp = config('services.sms.driver') === 'log' ? $otp : null;
        }

        return response()->json(['message' => 'Verification resent.', 'dev_otp' => $devOtp]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = $data['login'];
        $user = User::query()
            ->when(filter_var($login, FILTER_VALIDATE_EMAIL), fn ($q) => $q->where('email', $login))
            ->when(!filter_var($login, FILTER_VALIDATE_EMAIL), function ($q) use ($login) {
                $phone = BdPhone::normalize($login);
                $q->where('phone', $phone ?: $login);
            })
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => 'Invalid credentials.']);
        }

        if (!$user->is_verified) {
            throw ValidationException::withMessages(['login' => 'Please verify your account first.']);
        }

        $token = $user->createToken('doctor')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }

}

