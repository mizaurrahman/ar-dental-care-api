<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use App\Services\SlotService;
use App\Services\SmsService;
use App\Support\BdPhone;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class PublicAppointmentController extends Controller
{
    public function show(string $slug, Request $request, SlotService $slots)
    {
        $doctor = User::where('appointment_slug', $slug)->firstOrFail();
        $from = Carbon::parse($request->query('from', now()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->query('to', now()->addDays(13)->toDateString()))->startOfDay();

        return response()->json([
            'doctor' => [
                'name' => $doctor->name,
                'clinic_name' => $doctor->clinic_name,
                'specialty' => $doctor->specialty,
                'qualifications' => $doctor->qualifications,
                'bmdc_reg' => $doctor->bmdc_reg,
                'clinic_address' => $doctor->clinic_address,
                'clinic_phone' => $doctor->clinic_phone,
                'visiting_hours' => $doctor->visiting_hours,
                'avatar_url' => $doctor->avatar_url,
                'logo_url' => $doctor->logo_url,
            ],
            'slots' => $slots->slotsForRange($doctor, $from, $to),
        ]);
    }

    public function store(string $slug, Request $request, SlotService $slots, SmsService $sms)
    {
        $doctor = User::where('appointment_slug', $slug)->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string'],
            'email' => ['required', 'email'],
            'starts_at' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $phone = BdPhone::normalize($data['phone']);
        if (!$phone) {
            throw ValidationException::withMessages(['phone' => 'Phone must be a valid Bangladesh number.']);
        }

        $starts = Carbon::parse($data['starts_at']);
        $daySlots = $slots->slotsForDate($doctor, $starts->copy()->startOfDay());
        $match = collect($daySlots)->first(
            fn ($s) => Carbon::parse($s['starts_at'])->format('Y-m-d H:i') === $starts->format('Y-m-d H:i')
        );
        if (!$match) {
            throw ValidationException::withMessages(['starts_at' => 'Selected slot is no longer available.']);
        }

        $appointment = DB::transaction(function () use ($doctor, $data, $phone, $match, $slots, $starts) {
            // Re-check under lock so two patients cannot take the same slot
            $stillFree = collect($slots->slotsForDate($doctor, $starts->copy()->startOfDay()))
                ->contains(fn ($s) => Carbon::parse($s['starts_at'])->format('Y-m-d H:i') === $starts->format('Y-m-d H:i'));

            if (!$stillFree) {
                throw ValidationException::withMessages([
                    'starts_at' => 'This time was just taken. Please choose another available slot.',
                ]);
            }

            $taken = Appointment::query()
                ->where('user_id', $doctor->id)
                ->where('status', 'scheduled')
                ->where('starts_at', $match['starts_at'])
                ->lockForUpdate()
                ->exists();

            if ($taken) {
                throw ValidationException::withMessages([
                    'starts_at' => 'This time was just taken. Please choose another available slot.',
                ]);
            }

            $patient = $doctor->patients()->firstOrCreate(
                ['phone' => $phone],
                ['name' => $data['name'], 'email' => $data['email']]
            );
            $patient->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            return Appointment::create([
                'user_id' => $doctor->id,
                'patient_id' => $patient->id,
                'starts_at' => $match['starts_at'],
                'ends_at' => $match['ends_at'],
                'status' => 'scheduled',
                'reason' => $data['reason'] ?? null,
            ])->load('patient');
        });

        $when = Carbon::parse($appointment->starts_at)->format('D, M j Y g:i A');
        $msg = "Appointment confirmed at {$doctor->clinic_name} with {$doctor->name} on {$when}.";

        Mail::raw($msg, function ($message) use ($data, $doctor) {
            $message->to($data['email'])->subject('Appointment confirmed — '.$doctor->clinic_name);
        });

        if ($doctor->email) {
            Mail::raw(
                "New appointment: {$data['name']} ({$phone}) on {$when}",
                function ($message) use ($doctor) {
                    $message->to($doctor->email)->subject('New appointment — '.$doctor->clinic_name);
                }
            );
        }

        $sms->send($phone, $msg);

        return response()->json([
            'message' => 'Appointment confirmed. Confirmation sent by email and SMS.',
            'appointment' => $appointment,
        ], 201);
    }
}
