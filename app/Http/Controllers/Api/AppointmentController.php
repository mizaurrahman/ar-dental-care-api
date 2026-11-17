<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Appointment::with('patient')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('starts_at');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('date')) {
            $query->whereDate('starts_at', $request->query('date'));
        }

        return response()->json(['appointments' => $query->paginate(30)]);
    }

    public function update(Request $request, Appointment $appointment)
    {
        abort_unless($appointment->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(['scheduled', 'completed', 'cancelled', 'no_show'])],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $appointment->update($data);

        return response()->json(['appointment' => $appointment->fresh('patient')]);
    }
}
