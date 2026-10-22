<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentMasterTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create([
            'email' => 'superadmin@erp.test',
        ]);
        $this->admin->assignRole('Superadmin');
    }

    public function test_can_view_departments_index_and_stats(): void
    {
        Department::create([
            'code' => 'ENG',
            'name' => 'Engineering',
            'description' => 'Rekayasa teknis',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('departments.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Departments/Index')
            ->has('departments.data', 1)
            ->where('departments.data.0.code', 'ENG')
            ->where('departments.data.0.name', 'Engineering')
            ->where('stats.total', 1)
            ->where('stats.active', 1)
        );
    }

    public function test_can_filter_departments_by_search_and_status(): void
    {
        Department::create([
            'code' => 'ENG',
            'name' => 'Engineering',
            'is_active' => true,
        ]);

        Department::create([
            'code' => 'ACC',
            'name' => 'Accounting',
            'is_active' => false,
        ]);

        // Search by keyword
        $searchResponse = $this->actingAs($this->admin)->get(route('departments.index', ['search' => 'Engine']));
        $searchResponse->assertOk();
        $searchResponse->assertInertia(fn ($page) => $page
            ->has('departments.data', 1)
            ->where('departments.data.0.code', 'ENG')
        );

        // Filter by inactive status
        $statusResponse = $this->actingAs($this->admin)->get(route('departments.index', ['status' => '0']));
        $statusResponse->assertOk();
        $statusResponse->assertInertia(fn ($page) => $page
            ->has('departments.data', 1)
            ->where('departments.data.0.code', 'ACC')
        );
    }

    public function test_can_create_department_with_uppercase_code(): void
    {
        $manager = User::factory()->create(['name' => 'John Manager']);

        $payload = [
            'code' => 'ppic',
            'name' => 'Production Planning & Inventory Control',
            'manager_id' => $manager->id,
            'description' => 'Perencanaan produksi dan inventori',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)->post(route('departments.store'), $payload);

        $response->assertRedirect(route('departments.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('departments', [
            'code' => 'PPIC',
            'name' => 'Production Planning & Inventory Control',
            'manager_id' => $manager->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
    }

    public function test_creation_validates_required_fields_and_uniqueness(): void
    {
        Department::create([
            'code' => 'PURCH',
            'name' => 'Purchasing',
        ]);

        // Duplicate code
        $duplicateResponse = $this->actingAs($this->admin)->post(route('departments.store'), [
            'code' => 'purch',
            'name' => 'Purchasing Duplicate',
        ]);
        $duplicateResponse->assertSessionHasErrors(['code']);

        // Missing fields
        $missingResponse = $this->actingAs($this->admin)->post(route('departments.store'), [
            'code' => '',
            'name' => '',
        ]);
        $missingResponse->assertSessionHasErrors(['code', 'name']);
    }

    public function test_can_update_department(): void
    {
        $dept = Department::create([
            'code' => 'HRD',
            'name' => 'Human Resource',
            'is_active' => true,
        ]);

        $manager = User::factory()->create(['name' => 'HR Manager']);

        $response = $this->actingAs($this->admin)->put(route('departments.update', $dept->id), [
            'code' => 'hrdga',
            'name' => 'HR & General Affairs',
            'manager_id' => $manager->id,
            'description' => 'SDM dan umum',
            'is_active' => false,
        ]);

        $response->assertRedirect(route('departments.index'));
        $response->assertSessionHas('success');

        $dept->refresh();
        $this->assertSame('HRDGA', $dept->code);
        $this->assertSame('HR & General Affairs', $dept->name);
        $this->assertSame($manager->id, $dept->manager_id);
        $this->assertFalse($dept->is_active);
        $this->assertSame($this->admin->id, $dept->updated_by);
    }

    public function test_can_soft_delete_and_restore_via_recycle_bin(): void
    {
        $dept = Department::create([
            'code' => 'MTC',
            'name' => 'Maintenance',
        ]);

        // Soft delete
        $deleteResponse = $this->actingAs($this->admin)->delete(route('departments.destroy', $dept->id));
        $deleteResponse->assertRedirect(route('departments.index'));

        $this->assertSoftDeleted('departments', ['id' => $dept->id]);

        // Check Recycle Bin index displays the deleted department
        $recycleResponse = $this->actingAs($this->admin)->get(route('recycle-bin.index', ['type' => 'departments']));
        $recycleResponse->assertOk();
        $recycleResponse->assertInertia(fn ($page) => $page
            ->component('RecycleBin/Index')
            ->where('activeTab', 'departments')
            ->where('counts.departments', 1)
        );

        // Restore
        $restoreResponse = $this->actingAs($this->admin)->post(route('recycle-bin.restore', [
            'type' => 'departments',
            'id' => $dept->id,
        ]));
        $restoreResponse->assertRedirect();

        $this->assertNotSoftDeleted('departments', ['id' => $dept->id]);
    }

    public function test_can_force_delete_via_recycle_bin(): void
    {
        $dept = Department::create([
            'code' => 'TEMP',
            'name' => 'Temporary Dept',
        ]);

        $dept->delete();
        $this->assertSoftDeleted('departments', ['id' => $dept->id]);

        // Force delete
        $forceResponse = $this->actingAs($this->admin)->delete(route('recycle-bin.force-delete', [
            'type' => 'departments',
            'id' => $dept->id,
        ]));
        $forceResponse->assertRedirect();

        $this->assertDatabaseMissing('departments', ['id' => $dept->id]);
    }

    public function test_department_has_relationships_with_users_and_budgets(): void
    {
        $dept = Department::create([
            'code' => 'PRD',
            'name' => 'Production Department',
        ]);

        // User belongs to department
        $employee = User::factory()->create([
            'name' => 'Production Operator',
            'department_id' => $dept->id,
        ]);

        // Budget belongs to department
        $budget = Budget::create([
            'code' => 'BDG-PRD-01',
            'name' => 'Production Line Budget',
            'department_id' => $dept->id,
            'pic_id' => $this->admin->id,
        ]);

        $dept->refresh();

        $this->assertCount(1, $dept->users);
        $this->assertSame($employee->id, $dept->users->first()->id);
        $this->assertSame($dept->id, $employee->department->id);

        $this->assertCount(1, $dept->budgets);
        $this->assertSame($budget->id, $dept->budgets->first()->id);
        $this->assertSame($dept->id, $budget->department->id);
    }

    public function test_cannot_delete_department_with_active_users_or_budgets(): void
    {
        $dept = Department::create([
            'code' => 'PUR',
            'name' => 'Purchasing',
        ]);

        User::factory()->create([
            'name' => 'Purchasing Staff',
            'department_id' => $dept->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('departments.destroy', $dept->id));
        $response->assertRedirect(route('departments.index'));
        $response->assertSessionHas('error');

        $this->assertNotSoftDeleted('departments', ['id' => $dept->id]);
    }

    public function test_can_fetch_department_details_asynchronously(): void
    {
        $dept = Department::create([
            'code' => 'ACC',
            'name' => 'Accounting',
        ]);

        $user = User::factory()->create([
            'name' => 'Accountant',
            'department_id' => $dept->id,
        ]);

        $budget = Budget::create([
            'code' => 'BDG-ACC-01',
            'name' => 'Operational Accounting Budget',
            'department_id' => $dept->id,
            'pic_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('departments.show', $dept->id));

        $response->assertOk();
        $response->assertJsonStructure([
            'users' => [['id', 'name', 'email', 'department_id']],
            'budgets' => [['id', 'code', 'name', 'department_id']],
        ]);
        $response->assertJsonCount(1, 'users');
        $response->assertJsonCount(1, 'budgets');
        $this->assertSame($user->id, $response->json('users.0.id'));
        $this->assertSame($budget->id, $response->json('budgets.0.id'));
    }
}
