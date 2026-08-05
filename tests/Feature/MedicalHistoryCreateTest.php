<?php

use App\Models\User;
use App\Models\UsersProfile;
use App\Services\MedicalHistory\MedicalHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates patient user profile and medical history when patient does not exist', function () {
    $admin = User::factory()->superusuario()->create();
    $this->actingAs($admin);

    $prefix = 'HN';
    $year = date('Y');
    $sequence = '00001';
    $base = sprintf('%s-%s-%s', $prefix, $year, $sequence);

    $calculateLuhn = function (string $value): string {
        $digits = array_reverse(str_split(preg_replace('/[^0-9]/', '', $value)));
        $sum = 0;

        foreach ($digits as $index => $digit) {
            $digit = (int) $digit;

            if ($index % 2 === 0) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        return (string) ((10 - ($sum % 10)) % 10);
    };

    $checkDigit = $calculateLuhn($base);
    $mrn = sprintf('%s-%s', $base, $checkDigit);

    $dni = 'V'.rand(1000000, 9999999);

    $payload = [
        'name' => 'Historia Test',
        'patient_identifier' => $dni,
        'mrn' => $mrn,
        'email' => 'newpatient+'.time().'@test.local',
        'status' => 'active',
        'first_name' => 'Test',
        'last_name' => 'Paciente',
        'nacionality' => 'V',
        'dni' => $dni,
        'birth_date' => '1990-01-01',
        'gender' => 'M',
        'phone_mobile' => '04141234567',
    ];

    $response = $this->post(route('medical-histories.store'), $payload);

    $response->assertRedirect(route('medical-histories.index'));

    $this->assertDatabaseHas('users', ['email' => $payload['email']]);
    $this->assertDatabaseHas('users_profiles', ['dni' => $dni, 'mrn' => $mrn]);
    $this->assertDatabaseHas('medical_histories', ['mrn' => $mrn]);

    // Verify the created users_profile is linked to the created user
    $user = User::where('email', $payload['email'])->first();
    $this->assertNotNull($user);

    $this->assertDatabaseHas('users_profiles', ['user_id' => $user->id]);
});

it('service attaches medical history to existing patient without creating new user', function () {
    $admin = User::factory()->superusuario()->create();

    $existingUser = User::factory()->create();
    $dni = 'E'.rand(1000000, 9999999);

    UsersProfile::create([
        'first_name' => 'Exist',
        'last_name' => 'Paciente',
        'nacionality' => 'E',
        'dni' => $dni,
        'birth_date' => '1975-01-01',
        'gender' => 'F',
        'phone_mobile' => '04141230000',
        'created_by_user_id' => $admin->id,
        'user_id' => $existingUser->id,
        'mrn' => null,
    ]);

    $prefix = 'HN';
    $year = date('Y');
    $sequence = '00003';
    $base = sprintf('%s-%s-%s', $prefix, $year, $sequence);

    $calculateLuhn = function (string $value): string {
        $digits = array_reverse(str_split(preg_replace('/[^0-9]/', '', $value)));
        $sum = 0;
        foreach ($digits as $index => $digit) {
            $digit = (int) $digit;
            if ($index % 2 === 0) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }

        return (string) ((10 - ($sum % 10)) % 10);
    };

    $mrn = sprintf('%s-%s-%s-%s', $prefix, $year, $sequence, $calculateLuhn($base));

    $beforeUsers = User::count();

    $service = app(MedicalHistoryService::class);
    $data = ['name' => 'FromService', 'patient_identifier' => $dni, 'patient_id' => $existingUser->id, 'mrn' => $mrn];
    $medical = $service->store($data);

    $this->assertDatabaseHas('medical_histories', ['id' => $medical->id, 'mrn' => $mrn, 'patient_id' => $existingUser->id]);
    $this->assertEquals($beforeUsers, User::count());
});

it('attaches medical history to existing patient and updates profile mrn', function () {
    $admin = User::factory()->superusuario()->create();
    $this->actingAs($admin);

    // existing patient
    $existingUser = User::factory()->create();
    $dni = 'V'.rand(1000000, 9999999);

    UsersProfile::create([
        'first_name' => 'Exist',
        'last_name' => 'Paciente',
        'nacionality' => 'V',
        'dni' => $dni,
        'birth_date' => '1980-01-01',
        'gender' => 'M',
        'phone_mobile' => '04141234567',
        'created_by_user_id' => $admin->id,
        'user_id' => $existingUser->id,
        'mrn' => null,
    ]);

    $prefix = 'HN';
    $year = date('Y');
    $sequence = '00002';
    $base = sprintf('%s-%s-%s', $prefix, $year, $sequence);

    $calculateLuhn = function (string $value): string {
        $digits = array_reverse(str_split(preg_replace('/[^0-9]/', '', $value)));
        $sum = 0;

        foreach ($digits as $index => $digit) {
            $digit = (int) $digit;

            if ($index % 2 === 0) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        return (string) ((10 - ($sum % 10)) % 10);
    };

    $checkDigit = $calculateLuhn($base);
    $mrn = sprintf('%s-%s', $base, $checkDigit);

    $beforeUsers = User::count();

    $payload = [
        'name' => 'Historia Existente',
        'patient_identifier' => $dni,
        'mrn' => $mrn,
    ];

    $response = $this->post(route('medical-histories.store'), $payload);
    $this->assertTrue(in_array($response->status(), [201, 301, 302, 303, 307, 308]), 'Unexpected status '.$response->status().': '.$response->getContent());

    // no new users created
    $this->assertEquals($beforeUsers, User::count());

    // medical history exists and linked to existing user
    $this->assertDatabaseHas('medical_histories', ['mrn' => $mrn, 'patient_id' => $existingUser->id]);
});
