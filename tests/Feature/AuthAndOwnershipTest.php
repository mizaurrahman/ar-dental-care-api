<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_doctor_can_login(): void
    {
        User::create([
            'name' => 'Dr. Rahman',
            'email' => 'doctor@ardental.local',
            'phone' => '+8801700000000',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'clinic_name' => 'AR Dental Care',
            'appointment_slug' => 'ar-dental-care',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => 'doctor@ardental.local',
            'password' => 'password',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'email']]);
    }

    public function test_doctor_cannot_view_another_doctors_patient(): void
    {
        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'appointment_slug' => 'owner-clinic',
        ]);

        $intruder = User::create([
            'name' => 'Intruder',
            'email' => 'intruder@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'appointment_slug' => 'intruder-clinic',
        ]);

        $patient = Patient::create([
            'user_id' => $owner->id,
            'name' => 'Secret Patient',
            'phone' => '+8801711111111',
            'email' => 'secret@example.com',
        ]);

        $token = $intruder->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/patients/'.$patient->id)
            ->assertForbidden();
    }

    public function test_rejects_non_bangladesh_phone_on_register(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Dr X',
            'clinic_name' => 'Test Clinic',
            'phone' => '+14155552671',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(422);
    }
}
