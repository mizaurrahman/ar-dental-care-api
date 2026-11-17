<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;

class PublicPrescriptionController extends Controller
{
    public function show(string $token)
    {
        $prescription = Prescription::with(['items', 'patient', 'user'])
            ->where('share_token', $token)
            ->firstOrFail();

        return response()->json([
            'prescription' => [
                'prescribed_on' => $prescription->prescribed_on,
                'patient_age' => $prescription->patient_age,
                'chief_complaints' => $prescription->chief_complaints,
                'on_examination' => $prescription->on_examination,
                'advice' => $prescription->advice,
                'follow_up_days' => $prescription->follow_up_days,
                'patient' => [
                    'name' => $prescription->patient->name,
                ],
                'items' => $prescription->items,
                'doctor' => [
                    'name' => $prescription->user->name,
                    'clinic_name' => $prescription->user->clinic_name,
                    'specialty' => $prescription->user->specialty,
                    'qualifications' => $prescription->user->qualifications,
                    'bmdc_reg' => $prescription->user->bmdc_reg,
                    'clinic_address' => $prescription->user->clinic_address,
                    'clinic_phone' => $prescription->user->clinic_phone,
                    'visiting_hours' => $prescription->user->visiting_hours,
                    'logo_url' => $prescription->user->logo_url,
                ],
            ],
        ]);
    }
}
