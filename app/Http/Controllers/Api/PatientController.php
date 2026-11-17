<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Support\BdPhone;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $patients = Patient::query()
            ->where('user_id', $request->user()->id)
            ->when($q, fn ($query) => $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            }))
            ->orderBy('name')
            ->limit(50)
            ->get();

        return response()->json(['patients' => $patients]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'notes' => ['nullable', 'string'],
        ]);

        $phone = BdPhone::normalize($data['phone']);
        if (!$phone) {
            throw ValidationException::withMessages(['phone' => 'Invalid Bangladesh phone number.']);
        }

        $patient = $request->user()->patients()->create([
            ...$data,
            'phone' => $phone,
        ]);

        return response()->json(['patient' => $patient], 201);
    }

    public function show(Request $request, Patient $patient)
    {
        abort_unless($patient->user_id === $request->user()->id, 403);

        $patient->load([
            'appointments' => fn ($q) => $q->orderByDesc('starts_at')->limit(20),
            'prescriptions' => fn ($q) => $q->with('items')->orderByDesc('prescribed_on')->limit(20),
        ]);

        return response()->json(['patient' => $patient]);
    }
}
