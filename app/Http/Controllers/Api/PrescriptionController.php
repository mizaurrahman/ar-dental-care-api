<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Prescription;
use App\Support\BdPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PrescriptionController extends Controller
{
    public function index(Request $request)
    {
        $items = Prescription::with(['patient', 'items'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('prescribed_on')
            ->paginate(20);

        return response()->json(['prescriptions' => $items]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => ['nullable', 'integer'],
            'patient' => ['nullable', 'array'],
            'patient.name' => ['required_without:patient_id', 'string', 'max:120'],
            'patient.phone' => ['required_without:patient_id', 'string'],
            'patient.email' => ['nullable', 'email'],
            'patient.age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'appointment_id' => ['nullable', 'integer'],
            'prescribed_on' => ['nullable', 'date'],
            'patient_age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'chief_complaints' => ['nullable', 'string'],
            'on_examination' => ['nullable', 'string'],
            'advice' => ['nullable', 'string'],
            'follow_up_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medicine_id' => ['nullable', 'integer'],
            'items.*.brand_name' => ['required', 'string', 'max:190'],
            'items.*.strength' => ['nullable', 'string', 'max:100'],
            'items.*.form' => ['nullable', 'string', 'max:100'],
            'items.*.dosage' => ['nullable', 'string', 'max:100'],
            'items.*.frequency' => ['nullable', 'string', 'max:100'],
            'items.*.duration' => ['nullable', 'string', 'max:100'],
            'items.*.instructions' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        $prescription = DB::transaction(function () use ($data, $user) {
            if (!empty($data['patient_id'])) {
                $patient = Patient::where('user_id', $user->id)->findOrFail($data['patient_id']);
            } else {
                $phone = BdPhone::normalize($data['patient']['phone'] ?? '');
                if (!$phone) {
                    throw ValidationException::withMessages(['patient.phone' => 'Invalid Bangladesh phone.']);
                }
                $patient = $user->patients()->create([
                    'name' => $data['patient']['name'],
                    'phone' => $phone,
                    'email' => $data['patient']['email'] ?? null,
                    'age' => $data['patient']['age'] ?? ($data['patient_age'] ?? null),
                ]);
            }

            $rx = $user->prescriptions()->create([
                'patient_id' => $patient->id,
                'appointment_id' => $data['appointment_id'] ?? null,
                'prescribed_on' => $data['prescribed_on'] ?? now()->toDateString(),
                'patient_age' => $data['patient_age'] ?? $patient->age,
                'chief_complaints' => $data['chief_complaints'] ?? null,
                'on_examination' => $data['on_examination'] ?? null,
                'advice' => $data['advice'] ?? null,
                'follow_up_days' => $data['follow_up_days'] ?? null,
                'status' => 'final',
                'share_token' => Str::random(40),
            ]);

            foreach ($data['items'] as $i => $item) {
                $rx->items()->create([
                    ...$item,
                    'sort_order' => $i,
                ]);
            }

            return $rx->load(['patient', 'items', 'user']);
        });

        return response()->json(['prescription' => $prescription], 201);
    }

    public function show(Request $request, Prescription $prescription)
    {
        abort_unless($prescription->user_id === $request->user()->id, 403);

        return response()->json([
            'prescription' => $prescription->load(['patient', 'items', 'user']),
        ]);
    }

    public function update(Request $request, Prescription $prescription)
    {
        abort_unless($prescription->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'prescribed_on' => ['sometimes', 'date'],
            'patient_age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'chief_complaints' => ['nullable', 'string'],
            'on_examination' => ['nullable', 'string'],
            'advice' => ['nullable', 'string'],
            'follow_up_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.medicine_id' => ['nullable', 'integer'],
            'items.*.brand_name' => ['required_with:items', 'string', 'max:190'],
            'items.*.strength' => ['nullable', 'string', 'max:100'],
            'items.*.form' => ['nullable', 'string', 'max:100'],
            'items.*.dosage' => ['nullable', 'string', 'max:100'],
            'items.*.frequency' => ['nullable', 'string', 'max:100'],
            'items.*.duration' => ['nullable', 'string', 'max:100'],
            'items.*.instructions' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($prescription, $data) {
            $prescription->update(collect($data)->except('items')->all());
            if (isset($data['items'])) {
                $prescription->items()->delete();
                foreach ($data['items'] as $i => $item) {
                    $prescription->items()->create([...$item, 'sort_order' => $i]);
                }
            }
        });

        return response()->json([
            'prescription' => $prescription->fresh()->load(['patient', 'items', 'user']),
        ]);
    }

    public function shareEmail(Request $request, Prescription $prescription)
    {
        abort_unless($prescription->user_id === $request->user()->id, 403);
        $prescription->load('patient');

        $email = $request->input('email', $prescription->patient->email);
        if (!$email) {
            throw ValidationException::withMessages(['email' => 'Patient email is required.']);
        }

        $url = $prescription->share_url;
        Mail::raw(
            "Your prescription from {$request->user()->clinic_name} is ready.\nView it here: {$url}",
            function ($message) use ($email, $request) {
                $message->to($email)->subject('Your prescription — '.$request->user()->clinic_name);
            }
        );

        return response()->json(['message' => 'Email sent.', 'share_url' => $url]);
    }
}
