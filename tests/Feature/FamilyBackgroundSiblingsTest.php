<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class FamilyBackgroundSiblingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_family_background_saves_parents_and_siblings(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-fam-bg@example.com');
        $candidate = $this->createUserWithRole('candidate', 'candidate-fam-bg@example.com');

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/candidates/' . $candidate->uuid . '/sections/family-background', [
                'father_name' => 'Rajesh',
                'father_occupation' => 'Business',
                'father_gotra' => 'Kashyap',
                'father_native_place' => 'Bilaspur',
                'mother_name' => 'Sunita',
                'mother_occupation' => 'Homemaker',
                'mother_gotra' => 'Kaushik',
                'mother_native_place' => 'Durg',
                'brothers_count' => 1,
                'sisters_count' => 1,
                'family_type' => 'Nuclear',
                'family_status' => 'Middle Class',
                'siblings' => [
                    [
                        'name' => 'Ravi',
                        'age' => 40,
                        'occupation' => 'Business',
                        'marital_status' => 'Married',
                        'relation_type' => 'brother',
                    ],
                ],
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $candidate->id,
            'father_name' => 'Rajesh',
            'mother_name' => 'Sunita',
            'brothers_count' => 1,
        ]);

        $sib = DB::table('user_siblings_details')->where('user_id', $candidate->id)->first();
        $this->assertNotNull($sib);
        $this->assertSame('Ravi', $sib->name);
        $this->assertSame(40, (int) $sib->age);
        $this->assertSame('brother', $sib->relation_type);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/candidates/' . $candidate->uuid)
            ->assertStatus(200)
            ->assertJsonPath('data.sections.familyBackground.siblings.0.name', 'Ravi')
            ->assertJsonPath('data.sections.familyBackground.siblings.0.age', 40);
    }

    public function test_family_background_clear_siblings_with_empty_array(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = $this->createUserWithRole('admin', 'admin-fam-clear@example.com');
        $candidate = $this->createUserWithRole('candidate', 'candidate-fam-clear@example.com');

        DB::table('user_siblings_details')->insert([
            'uuid' => (string) Str::uuid(),
            'user_id' => $candidate->id,
            'name' => 'Temp',
            'relation_type' => 'sister',
            'sort_order' => 0,
            'is_elder' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/candidates/' . $candidate->uuid . '/sections/family-background', [
                'siblings' => [],
            ])
            ->assertStatus(200);

        $this->assertSame(0, DB::table('user_siblings_details')->where('user_id', $candidate->id)->count());
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
