<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiDoctorIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_doctor_gets_own_clinic_slug_and_default_hours(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Dr. Smile',
            'clinic_name' => 'Smile Bright Dental',
            'specialty' => 'Orthodontics',
            'clinic_address' => 'Mirpur, Dhaka',
            'email' => 'smile@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.clinic_name', 'Smile Bright Dental')
            ->assertJsonPath('user.appointment_slug', 'smile-bright-dental')
            ->assertJsonStructure(['appointment_url', 'user' => ['id', 'clinic_name', 'appointment_slug']]);

        $doctor = User::where('email', 'smile@example.com')->first();
        $this->assertNotNull($doctor);
        $this->assertSame(14, $doctor->availabilityRules()->count());
        $this->assertStringContainsString('/appointment/smile-bright-dental', $doctor->appointment_url);
    }

    public function test_two_doctors_get_unique_slugs_and_isolated_public_pages(): void
    {
        $a = $this->registerDoctor('Dr Alpha', 'Alpha Dental', 'alpha@example.com');
        $b = $this->registerDoctor('Dr Beta', 'Beta Dental', 'beta@example.com');

        $this->assertNotSame($a['user']['appointment_slug'], $b['user']['appointment_slug']);

        $this->getJson('/api/public/appointment/'.$a['user']['appointment_slug'])
            ->assertOk()
            ->assertJsonPath('doctor.clinic_name', 'Alpha Dental')
            ->assertJsonMissing(['clinic_name' => 'Beta Dental']);

        $this->getJson('/api/public/appointment/'.$b['user']['appointment_slug'])
            ->assertOk()
            ->assertJsonPath('doctor.clinic_name', 'Beta Dental');
    }

    public function test_duplicate_clinic_name_gets_unique_slug_suffix(): void
    {
        $this->registerDoctor('Dr One', 'City Dental', 'one@example.com');
        $second = $this->registerDoctor('Dr Two', 'City Dental', 'two@example.com');

        $this->assertSame('city-dental-1', $second['user']['appointment_slug']);
    }

    public function test_occupied_slot_only_hides_for_that_doctor(): void
    {
        $a = $this->registerDoctor('Dr Alpha', 'Alpha Dental', 'alpha2@example.com');
        $b = $this->registerDoctor('Dr Beta', 'Beta Dental', 'beta2@example.com');

        $doctorA = User::find($a['user']['id']);
        $doctorB = User::find($b['user']['id']);

        $date = now()->next('Monday')->toDateString();
        $starts = $date.' 10:00:00';
        $ends = $date.' 10:30:00';

        $patient = Patient::create([
            'user_id' => $doctorA->id,
            'name' => 'Patient A',
            'phone' => '+8801711111111',
            'email' => 'patient-a@example.com',
        ]);

        Appointment::create([
            'user_id' => $doctorA->id,
            'patient_id' => $patient->id,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'status' => 'scheduled',
            'reason' => 'Checkup',
        ]);

        $slotsA = $this->getJson('/api/public/appointment/'.$doctorA->appointment_slug.'?from='.$date.'&to='.$date)
            ->assertOk()
            ->json('slots.'.$date);
        $slotsB = $this->getJson('/api/public/appointment/'.$doctorB->appointment_slug.'?from='.$date.'&to='.$date)
            ->assertOk()
            ->json('slots.'.$date);

        $labelsA = collect($slotsA)->pluck('label');
        $labelsB = collect($slotsB)->pluck('label');

        $this->assertFalse($labelsA->contains('10:00 AM'));
        $this->assertTrue($labelsB->contains('10:00 AM'));
    }

    public function test_dashboard_returns_own_appointment_url_per_doctor(): void
    {
        $a = $this->registerDoctor('Dr Alpha', 'Alpha Dental', 'alpha3@example.com');
        $doctor = User::find($a['user']['id']);
        $doctor->forceFill(['email_verified_at' => now()])->save();

        $token = $doctor->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('appointment_url', $doctor->appointment_url);
    }

    public function test_public_appointment_includes_clinic_and_profile_images(): void
    {
        $registered = $this->registerDoctor('Dr Photo', 'Photo Dental', 'photo@example.com');
        $doctor = User::find($registered['user']['id']);
        $doctor->forceFill([
            'avatar_path' => 'avatars/demo-doctor.jpg',
            'logo_path' => 'logos/demo-clinic.png',
            'qualifications' => 'B.D.S',
            'bmdc_reg' => 'BMDC-9',
        ])->save();

        $this->getJson('/api/public/appointment/'.$doctor->appointment_slug)
            ->assertOk()
            ->assertJsonPath('doctor.clinic_name', 'Photo Dental')
            ->assertJsonPath('doctor.qualifications', 'B.D.S')
            ->assertJsonPath('doctor.bmdc_reg', 'BMDC-9')
            ->assertJsonPath('doctor.avatar_url', $doctor->fresh()->avatar_url)
            ->assertJsonPath('doctor.logo_url', $doctor->fresh()->logo_url);
    }

    /**
     * @return array{user: array, appointment_url: string}
     */
    private function registerDoctor(string $name, string $clinic, string $email): array
    {
        return $this->postJson('/api/auth/register', [
            'name' => $name,
            'clinic_name' => $clinic,
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertCreated()->json();
    }
}
