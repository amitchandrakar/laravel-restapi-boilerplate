<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\MasterDegreesOccupationsSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CareerEducationDegreeIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_career_education_saves_qualifications_with_degree_id(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(MasterDegreesOccupationsSeeder::class);

        $admin = $this->createUserWithRole('admin', 'admin-career-deg@example.com');
        $candidate = $this->createUserWithRole('candidate', 'candidate-career-deg@example.com');

        $degreeId = (int) DB::table('degrees')->where('name', 'BE/BTech')->where('is_active', true)->value('id');
        $this->assertGreaterThan(0, $degreeId);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/candidates/' . $candidate->uuid . '/sections/career-education', [
                'occupation' => 'Software Engineer',
                'employer' => 'Acme Tech',
                'income' => 1400000,
                'marital_status' => 'Single',
                'qualifications' => [
                    [
                        'degree_id' => $degreeId,
                        'field_of_study' => 'Computer Science',
                        'institution_name' => 'NIT',
                        'year_of_graduation' => 2017,
                    ],
                ],
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $candidate->id,
            'occupation' => 'Software Engineer',
            'employer' => 'Acme Tech',
        ]);

        $row = DB::table('user_education_details')->where('user_id', $candidate->id)->first();
        $this->assertNotNull($row);
        $this->assertSame($degreeId, (int) $row->degree_id);
        $this->assertSame('Computer Science', $row->field_of_study);
        $this->assertSame('NIT', $row->institution_name);
        $this->assertSame(2017, (int) $row->end_year);
    }

    private function createUserWithRole(string $role, string $email): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'first_name' => 'Test',
            'last_name' => ucfirst($role),
            'email' => $email,
            'password' => 'Password@123',
            'status' => 'active',
            'role_id' => (int) Role::query()->where('name', $role)->value('id'),
        ]);
        $user->assignRole($role);

        return $user;
    }
}
