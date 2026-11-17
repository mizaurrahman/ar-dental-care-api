<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Prescription;
use Carbon\Carbon;
use Illuminate\Http\Request;
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $date = $request->query('date', now()->toDateString());
        $day = Carbon::parse($date)->startOfDay();

        $totalAppointments = Appointment::where('user_id', $user->id)->count();
        $todayAppointments = Appointment::where('user_id', $user->id)
            ->whereDate('starts_at', $day)->count();
        $prescriptions = Prescription::where('user_id', $user->id)->count();
        $newPatients = Patient::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))->count();

        $upcoming = Appointment::with('patient')
            ->where('user_id', $user->id)
            ->where('starts_at', '>=', now())
            ->where('status', 'scheduled')
            ->orderBy('starts_at')
            ->limit(8)
            ->get();

        $patientsToday = Appointment::with('patient')
            ->where('user_id', $user->id)
            ->whereDate('starts_at', $day)
            ->orderBy('starts_at')
            ->get();

        $lastVisited = Appointment::with('patient')
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->orderByDesc('starts_at')
            ->limit(6)
            ->get();

        $weekStart = now()->startOfWeek(Carbon::SUNDAY);
        $weekEnd = $weekStart->copy()->addWeek();
        $appointments = Appointment::query()
            ->where('user_id', $user->id)
            ->where('starts_at', '>=', $weekStart)
            ->where('starts_at', '<', $weekEnd)
            ->get(['starts_at']);

        $weekly = [];
        foreach ($appointments as $appointment) {
            $dow = (string) Carbon::parse($appointment->starts_at)->dayOfWeek;
            $weekly[$dow] = ($weekly[$dow] ?? 0) + 1;
        }

        $chart = [];
        for ($i = 0; $i < 7; $i++) {
            $chart[] = [
                'day' => $weekStart->copy()->addDays($i)->format('D'),
                'total' => (int) ($weekly[(string) $i] ?? 0),
            ];
        }

        return response()->json([
            'stats' => [
                'total_appointments' => $totalAppointments,
                'today_appointments' => $todayAppointments,
                'prescriptions' => $prescriptions,
                'new_patients' => $newPatients,
            ],
            'upcoming' => $upcoming,
            'patients_today' => $patientsToday,
            'last_visited' => $lastVisited,
            'weekly_chart' => $chart,
            'clinic_name' => $user->clinic_name,
            'appointment_url' => $user->appointment_url,
        ]);
    }
}
