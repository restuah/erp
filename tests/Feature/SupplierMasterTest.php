<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\TermOfPayment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierMasterTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected TermOfPayment $top30;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create([
            'email' => 'superadmin@erp.test',
        ]);
        $this->admin->assignRole('Superadmin');

        $this->top30 = TermOfPayment::create([
            'payment' => '30_A_INVOICE',
            'name' => '30 Days After Invoice',
            'days' => 30,
            'is_active' => true,
        ]);
    }

    public function test_can_view_suppliers_index_and_stats(): void
    {
        Supplier::create([
            'supplier_type' => 'direct',
            'supplier_code' => 'SUP-DIR-0001',
            'supplier_name' => 'PT Baja Unggul',
            'status' => 'active',
            'term_of_payment_id' => $this->top30->id,
            'default_currency' => 'IDR',
        ]);

        Supplier::create([
            'supplier_type' => 'indirect',
            'supplier_code' => 'SUP-IND-0001',
            'supplier_name' => 'CV Alat Tulis Jaya',
            'status' => 'nonactive',
            'default_currency' => 'IDR',
        ]);

        $response = $this->actingAs($this->admin)->get(route('suppliers.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Suppliers/Index')
            ->has('suppliers.data', 2)
            ->where('stats.total', 2)
            ->where('stats.direct', 1)
            ->where('stats.indirect', 1)
            ->where('stats.active', 1)
            ->where('stats.nonactive', 1)
        );
    }

    public function test_can_filter_suppliers_by_type_direct_and_indirect(): void
    {
        Supplier::create([
            'supplier_type' => 'direct',
            'supplier_code' => 'SUP-DIR-0001',
            'supplier_name' => 'PT Direct Material',
            'status' => 'active',
        ]);

        Supplier::create([
            'supplier_type' => 'indirect',
            'supplier_code' => 'SUP-IND-0001',
            'supplier_name' => 'PT Indirect Services',
            'status' => 'active',
        ]);

        // Filter direct
        $dirRes = $this->actingAs($this->admin)->get(route('suppliers.index', ['type' => 'direct']));
        $dirRes->assertOk();
        $dirRes->assertInertia(fn ($page) => $page
            ->has('suppliers.data', 1)
            ->where('suppliers.data.0.supplier_code', 'SUP-DIR-0001')
        );

        // Filter indirect
        $indRes = $this->actingAs($this->admin)->get(route('suppliers.index', ['type' => 'indirect']));
        $indRes->assertOk();
        $indRes->assertInertia(fn ($page) => $page
            ->has('suppliers.data', 1)
            ->where('suppliers.data.0.supplier_code', 'SUP-IND-0001')
        );
    }

    public function test_can_filter_suppliers_by_status_and_term_of_payment(): void
    {
        $topCash = TermOfPayment::create([
            'payment' => 'COD',
            'name' => 'Cash On Delivery',
            'days' => 0,
            'is_active' => true,
        ]);

        Supplier::create([
            'supplier_type' => 'direct',
            'supplier_code' => 'SUP-DIR-0001',
            'supplier_name' => 'Supplier Active Top 30',
            'status' => 'active',
            'term_of_payment_id' => $this->top30->id,
        ]);

        Supplier::create([
            'supplier_type' => 'direct',
            'supplier_code' => 'SUP-DIR-0002',
            'supplier_name' => 'Supplier Nonactive Top Cash',
            'status' => 'nonactive',
            'term_of_payment_id' => $topCash->id,
        ]);

        // Filter status nonactive
        $statusRes = $this->actingAs($this->admin)->get(route('suppliers.index', ['status' => 'nonactive']));
        $statusRes->assertOk();
        $statusRes->assertInertia(fn ($page) => $page
            ->has('suppliers.data', 1)
            ->where('suppliers.data.0.supplier_code', 'SUP-DIR-0002')
        );

        // Filter term of payment COD
        $termRes = $this->actingAs($this->admin)->get(route('suppliers.index', ['term_of_payment_id' => $topCash->id]));
        $termRes->assertOk();
        $termRes->assertInertia(fn ($page) => $page
            ->has('suppliers.data', 1)
            ->where('suppliers.data.0.supplier_code', 'SUP-DIR-0002')
        );
    }

    public function test_can_search_suppliers_by_keyword(): void
    {
        Supplier::create([
            'supplier_type' => 'direct',
            'supplier_code' => 'SUP-DIR-9999',
            'supplier_name' => 'PT Indocement Makmur',
            'marketing_name' => 'Agus Gunawan',
            'marketing_email' => 'agus@indocement.test',
            'status' => 'active',
        ]);

        // Search by marketing name
        $searchRes = $this->actingAs($this->admin)->get(route('suppliers.index', ['search' => 'Gunawan']));
        $searchRes->assertOk();
        $searchRes->assertInertia(fn ($page) => $page
            ->has('suppliers.data', 1)
            ->where('suppliers.data.0.supplier_code', 'SUP-DIR-9999')
        );
    }

    public function test_can_generate_next_sequential_supplier_code(): void
    {
        // Initial codes should be 0001
        $resDirect = $this->actingAs($this->admin)->getJson(route('suppliers.generate-code', ['type' => 'direct']));
        $resDirect->assertOk();
        $resDirect->assertJson(['code' => 'SUP-DIR-0001']);

        $resIndirect = $this->actingAs($this->admin)->getJson(route('suppliers.generate-code', ['type' => 'indirect']));
        $resIndirect->assertOk();
        $resIndirect->assertJson(['code' => 'SUP-IND-0001']);

        // Create one direct supplier
        Supplier::create([
            'supplier_type' => 'direct',
            'supplier_code' => 'SUP-DIR-0001',
            'supplier_name' => 'Direct 1',
            'status' => 'active',
        ]);

        // Next direct code should be 0002
        $resDirectNext = $this->actingAs($this->admin)->getJson(route('suppliers.generate-code', ['type' => 'direct']));
        $resDirectNext->assertOk();
        $resDirectNext->assertJson(['code' => 'SUP-DIR-0002']);

        // Indirect should still be 0001
        $resIndirectStill = $this->actingAs($this->admin)->getJson(route('suppliers.generate-code', ['type' => 'indirect']));
        $resIndirectStill->assertOk();
        $resIndirectStill->assertJson(['code' => 'SUP-IND-0001']);
    }

    public function test_can_create_supplier_with_all_columns(): void
    {
        $payload = [
            'supplier_type' => 'direct',
            'supplier_code' => 'sup-dir-0010',
            'supplier_name' => 'PT Logam Perkasa',
            'status' => 'active',
            'term_of_payment_id' => $this->top30->id,
            'address' => 'Jl. Industri Raya No. 45, Kawasan MM2100',
            'zipcode' => '17520',
            'npwp' => '01.555.666.7-888.000',
            'office_phone' => '021-8987654',
            'fax' => '021-8987655',
            'marketing_name' => 'Bambang Wijaya',
            'marketing_phone' => '081122334455',
            'marketing_email' => 'bambang@logamperkasa.test',
            'accounting_name' => 'Dewi Lestari',
            'accounting_phone' => '081299887766',
            'accounting_email' => 'finance@logamperkasa.test',
            'bank_name' => 'BCA',
            'bank_account_name' => 'PT Logam Perkasa',
            'bank_account_number' => '8880012345',
            'default_currency' => 'idr',
            'country_code' => 'id',
            'delivery_period' => '3 Hari Kerja',
            'delivery_time' => '08:00',
            'delivery_end_time' => '16:00',
        ];

        $response = $this->actingAs($this->admin)->post(route('suppliers.store'), $payload);

        $response->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', [
            'supplier_type' => 'direct',
            'supplier_code' => 'SUP-DIR-0010',
            'supplier_name' => 'PT Logam Perkasa',
            'status' => 'active',
            'term_of_payment_id' => $this->top30->id,
            'marketing_name' => 'Bambang Wijaya',
            'marketing_email' => 'bambang@logamperkasa.test',
            'accounting_name' => 'Dewi Lestari',
            'bank_name' => 'BCA',
            'default_currency' => 'IDR',
            'country_code' => 'ID',
            'delivery_period' => '3 Hari Kerja',
            'delivery_time' => '08:00',
            'delivery_end_time' => '16:00',
        ]);
    }

    public function test_can_create_supplier_with_only_required_fields(): void
    {
        $payload = [
            'supplier_type' => 'indirect',
            'supplier_code' => 'SUP-IND-0005',
            'supplier_name' => 'CV Minimalis Sukses',
            'status' => 'active',
            'term_of_payment_id' => null,
            'address' => null,
            'zipcode' => null,
            'npwp' => null,
            'office_phone' => null,
            'fax' => null,
            'marketing_name' => null,
            'marketing_phone' => null,
            'marketing_email' => null,
            'accounting_name' => null,
            'accounting_phone' => null,
            'accounting_email' => null,
            'bank_name' => null,
            'bank_account_name' => null,
            'bank_account_number' => null,
            'default_currency' => null,
            'country_code' => null,
            'delivery_period' => null,
            'delivery_time' => null,
            'delivery_end_time' => null,
        ];

        $response = $this->actingAs($this->admin)->post(route('suppliers.store'), $payload);

        $response->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', [
            'supplier_type' => 'indirect',
            'supplier_code' => 'SUP-IND-0005',
            'supplier_name' => 'CV Minimalis Sukses',
            'status' => 'active',
            'marketing_name' => null,
            'bank_name' => null,
        ]);
    }

    public function test_creation_validates_required_fields_and_uniqueness(): void
    {
        Supplier::create([
            'supplier_type' => 'direct',
            'supplier_code' => 'EXISTING_SUP',
            'supplier_name' => 'Existing Supplier',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->post(route('suppliers.store'), [
            'supplier_type' => 'invalid_type',
            'supplier_code' => 'existing_sup',
            'supplier_name' => '',
            'status' => 'invalid_status',
            'marketing_email' => 'not-an-email',
            'term_of_payment_id' => '00000000-0000-0000-0000-000000000000',
        ]);

        $response->assertSessionHasErrors([
            'supplier_type',
            'supplier_code',
            'supplier_name',
            'status',
            'marketing_email',
            'term_of_payment_id',
        ]);
    }

    public function test_can_update_supplier(): void
    {
        $supplier = Supplier::create([
            'supplier_type' => 'direct',
            'supplier_code' => 'SUP-DIR-0001',
            'supplier_name' => 'Nama Awal',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->put(route('suppliers.update', $supplier->id), [
            'supplier_type' => 'indirect',
            'supplier_code' => 'SUP-DIR-0001', // Keeps same code
            'supplier_name' => 'Nama Diperbarui',
            'status' => 'nonactive',
            'term_of_payment_id' => $this->top30->id,
            'address' => 'Alamat baru',
        ]);

        $response->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'supplier_type' => 'indirect',
            'supplier_name' => 'Nama Diperbarui',
            'status' => 'nonactive',
            'address' => 'Alamat baru',
        ]);
    }

    public function test_can_soft_delete_and_restore_supplier_via_recycle_bin(): void
    {
        $supplier = Supplier::create([
            'supplier_type' => 'direct',
            'supplier_code' => 'SUP-DEL-0001',
            'supplier_name' => 'Supplier To Delete',
            'status' => 'active',
        ]);

        // Soft delete
        $delRes = $this->actingAs($this->admin)->delete(route('suppliers.destroy', $supplier->id));
        $delRes->assertRedirect(route('suppliers.index'));
        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);

        // Restore via recycle bin
        $restoreRes = $this->actingAs($this->admin)->post(route('recycle-bin.restore', [
            'type' => 'suppliers',
            'id' => $supplier->id,
        ]));
        $restoreRes->assertRedirect();
        $this->assertNotSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    public function test_can_force_delete_supplier_via_recycle_bin(): void
    {
        $supplier = Supplier::create([
            'supplier_type' => 'indirect',
            'supplier_code' => 'SUP-FORCE-0001',
            'supplier_name' => 'Supplier Force Delete',
            'status' => 'active',
        ]);

        $supplier->delete();
        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);

        // Force delete
        $forceRes = $this->actingAs($this->admin)->delete(route('recycle-bin.force-delete', [
            'type' => 'suppliers',
            'id' => $supplier->id,
        ]));
        $forceRes->assertRedirect();
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }
}
