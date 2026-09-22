<?php

namespace Tests\Feature;

use App\Models\UnitOfMeasure;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitOfMeasureTest extends TestCase
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

    public function test_can_view_unit_of_measures_index(): void
    {
        UnitOfMeasure::create([
            'code' => 'PCS',
            'name' => 'Pieces',
            'symbol' => 'pcs',
            'category' => 'count',
            'description' => 'Satuan buah',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('unit-of-measures.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('UnitOfMeasures/Index')
            ->has('units.data', 1)
            ->where('units.data.0.code', 'PCS')
            ->where('units.data.0.name', 'Pieces')
            ->where('stats.total', 1)
            ->where('stats.active', 1)
        );
    }

    public function test_can_filter_unit_of_measures_by_search_and_category(): void
    {
        UnitOfMeasure::create([
            'code' => 'KG',
            'name' => 'Kilogram',
            'symbol' => 'kg',
            'category' => 'weight',
            'is_active' => true,
        ]);

        UnitOfMeasure::create([
            'code' => 'M',
            'name' => 'Meter',
            'symbol' => 'm',
            'category' => 'length',
            'is_active' => true,
        ]);

        // Search by KG
        $responseSearch = $this->actingAs($this->admin)->get(route('unit-of-measures.index', ['search' => 'Kilo']));
        $responseSearch->assertOk();
        $responseSearch->assertInertia(fn ($page) => $page
            ->has('units.data', 1)
            ->where('units.data.0.code', 'KG')
        );

        // Filter by category length
        $responseCategory = $this->actingAs($this->admin)->get(route('unit-of-measures.index', ['category' => 'length']));
        $responseCategory->assertOk();
        $responseCategory->assertInertia(fn ($page) => $page
            ->has('units.data', 1)
            ->where('units.data.0.code', 'M')
        );
    }

    public function test_can_create_unit_of_measure_with_uppercase_code(): void
    {
        $payload = [
            'code' => 'box',
            'name' => 'Box Kardus',
            'symbol' => 'bx',
            'category' => 'count',
            'description' => 'Kemasan kardus standar',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)->post(route('unit-of-measures.store'), $payload);

        $response->assertRedirect(route('unit-of-measures.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('unit_of_measures', [
            'code' => 'BOX',
            'name' => 'Box Kardus',
            'symbol' => 'bx',
            'category' => 'count',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
    }

    public function test_creation_validates_required_fields_and_uniqueness(): void
    {
        UnitOfMeasure::create([
            'code' => 'ROLL',
            'name' => 'Roll Gulungan',
            'category' => 'count',
        ]);

        // Duplicate code
        $responseDuplicate = $this->actingAs($this->admin)->post(route('unit-of-measures.store'), [
            'code' => 'roll',
            'name' => 'Roll Lainnya',
            'category' => 'count',
        ]);
        $responseDuplicate->assertSessionHasErrors(['code']);

        // Missing required fields
        $responseMissing = $this->actingAs($this->admin)->post(route('unit-of-measures.store'), [
            'code' => '',
            'name' => '',
            'category' => 'invalid_category',
        ]);
        $responseMissing->assertSessionHasErrors(['code', 'name', 'category']);
    }

    public function test_can_update_unit_of_measure(): void
    {
        $uom = UnitOfMeasure::create([
            'code' => 'LTR',
            'name' => 'Liter',
            'symbol' => 'l',
            'category' => 'volume',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('unit-of-measures.update', $uom->id), [
            'code' => 'ltr',
            'name' => 'Liter Metrik',
            'symbol' => 'L',
            'category' => 'volume',
            'description' => 'Satuan cairan',
            'is_active' => false,
        ]);

        $response->assertRedirect(route('unit-of-measures.index'));
        $response->assertSessionHas('success');

        $uom->refresh();
        $this->assertSame('LTR', $uom->code);
        $this->assertSame('Liter Metrik', $uom->name);
        $this->assertSame('L', $uom->symbol);
        $this->assertFalse($uom->is_active);
        $this->assertSame($this->admin->id, $uom->updated_by);
    }

    public function test_can_soft_delete_and_restore_via_recycle_bin(): void
    {
        $uom = UnitOfMeasure::create([
            'code' => 'TON',
            'name' => 'Ton Metrik',
            'category' => 'weight',
        ]);

        // Delete (move to recycle bin)
        $deleteResponse = $this->actingAs($this->admin)->delete(route('unit-of-measures.destroy', $uom->id));
        $deleteResponse->assertRedirect(route('unit-of-measures.index'));

        $this->assertSoftDeleted('unit_of_measures', ['id' => $uom->id]);

        // Check Recycle Bin index displays the deleted item
        $recycleBinResponse = $this->actingAs($this->admin)->get(route('recycle-bin.index', ['type' => 'unit_of_measures']));
        $recycleBinResponse->assertOk();
        $recycleBinResponse->assertInertia(fn ($page) => $page
            ->component('RecycleBin/Index')
            ->where('activeTab', 'unit_of_measures')
            ->where('counts.unit_of_measures', 1)
        );

        // Restore from recycle bin
        $restoreResponse = $this->actingAs($this->admin)->post(route('recycle-bin.restore', [
            'type' => 'unit_of_measures',
            'id' => $uom->id,
        ]));
        $restoreResponse->assertRedirect();

        $this->assertNotSoftDeleted('unit_of_measures', ['id' => $uom->id]);
    }

    public function test_can_force_delete_unit_of_measure_via_recycle_bin(): void
    {
        $uom = UnitOfMeasure::create([
            'code' => 'TEMP',
            'name' => 'Temporary Unit',
            'category' => 'other',
        ]);

        $uom->delete();
        $this->assertSoftDeleted('unit_of_measures', ['id' => $uom->id]);

        // Force delete from recycle bin
        $forceDeleteResponse = $this->actingAs($this->admin)->delete(route('recycle-bin.force-delete', [
            'type' => 'unit_of_measures',
            'id' => $uom->id,
        ]));
        $forceDeleteResponse->assertRedirect();

        $this->assertDatabaseMissing('unit_of_measures', ['id' => $uom->id]);
    }
}
