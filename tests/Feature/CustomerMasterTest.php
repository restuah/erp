<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\CustomerBank;
use App\Models\TermOfPayment;
use App\Models\User;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TermOfPaymentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerMasterTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Currency $currency;
    protected TermOfPayment $top;
    protected ChartOfAccount $receivableCoa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CurrencySeeder::class);
        $this->seed(ChartOfAccountSeeder::class);
        $this->seed(TermOfPaymentSeeder::class);

        $this->admin = User::factory()->create([
            'email' => 'superadmin@erp.test',
        ]);
        $this->admin->assignRole('Superadmin');

        $this->currency = Currency::firstOrCreate(['code' => 'IDR'], ['name' => 'Indonesian Rupiah']);
        $this->top = TermOfPayment::firstOrFail();
        $this->receivableCoa = ChartOfAccount::where('account_code', '1121')->firstOrFail();
    }

    public function test_can_view_customers_index_and_stats(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUST-0001',
            'name' => 'PT Astra Daihatsu Motor',
            'alias' => 'ADM',
            'tax_id' => '01.302.839.4-007.000',
            'term_of_payment_id' => $this->top->id,
            'currency_id' => $this->currency->id,
            'coa_receivable_id' => $this->receivableCoa->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('customers.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Customers/Index')
            ->has('customers.data', 1)
            ->where('customers.data.0.customer_code', 'CUST-0001')
            ->where('customers.data.0.name', 'PT Astra Daihatsu Motor')
            ->where('stats.total', 1)
            ->where('stats.active', 1)
            ->where('stats.with_receivable_coa', 1)
        );
    }

    public function test_can_filter_customers_by_search_status_and_currency(): void
    {
        Customer::create([
            'customer_code' => 'CUST-0001',
            'name' => 'PT Astra Daihatsu Motor',
            'alias' => 'ADM',
            'currency_id' => $this->currency->id,
            'is_active' => true,
        ]);

        Customer::create([
            'customer_code' => 'CUST-0002',
            'name' => 'PT Toyota Motor Manufacturing Indonesia',
            'alias' => 'TMMIN',
            'currency_id' => $this->currency->id,
            'is_active' => false,
        ]);

        // Search by keyword
        $searchResponse = $this->actingAs($this->admin)->get(route('customers.index', ['search' => 'Daihatsu']));
        $searchResponse->assertOk();
        $searchResponse->assertInertia(fn ($page) => $page
            ->has('customers.data', 1)
            ->where('customers.data.0.customer_code', 'CUST-0001')
        );

        // Filter by inactive status
        $statusResponse = $this->actingAs($this->admin)->get(route('customers.index', ['status' => '0']));
        $statusResponse->assertOk();
        $statusResponse->assertInertia(fn ($page) => $page
            ->has('customers.data', 1)
            ->where('customers.data.0.customer_code', 'CUST-0002')
        );
    }

    public function test_can_create_customer_with_primary_bank_and_uppercase_code(): void
    {
        $payload = [
            'customer_code' => 'cust-0010',
            'name' => 'PT Honda Prospect Motor',
            'alias' => 'HPM',
            'tax_id' => '01.001.234.5-432.000',
            'term_of_payment_id' => $this->top->id,
            'currency_id' => $this->currency->id,
            'coa_receivable_id' => $this->receivableCoa->id,
            'phone' => '+62 267 440777',
            'email' => 'purchasing@hpm.co.id',
            'contact_person' => 'Eko Prasetyo',
            'contact_person_phone' => '+62 813 5544 3322',
            'billing_address' => 'Jl. Mitra Utara II, Karawang',
            'delivery_lead_time_days' => 2,
            'is_active' => true,
            'bank_name' => 'Bank Central Asia (BCA)',
            'bank_account_number' => '888-01-234567',
            'bank_account_name' => 'PT Honda Prospect Motor',
            'bank_branch' => 'KCU Karawang',
        ];

        $response = $this->actingAs($this->admin)->post(route('customers.store'), $payload);

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('customers', [
            'customer_code' => 'CUST-0010',
            'name' => 'PT Honda Prospect Motor',
            'alias' => 'HPM',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $customer = Customer::where('customer_code', 'CUST-0010')->firstOrFail();
        $this->assertDatabaseHas('customer_banks', [
            'customer_id' => $customer->id,
            'bank_name' => 'Bank Central Asia (BCA)',
            'bank_account_number' => '888-01-234567',
            'is_primary' => true,
        ]);
    }

    public function test_creation_validates_required_fields_and_uniqueness(): void
    {
        Customer::create([
            'customer_code' => 'CUST-0001',
            'name' => 'Customer Existing',
        ]);

        $response = $this->actingAs($this->admin)->post(route('customers.store'), [
            'customer_code' => 'cust-0001', // duplicate
            'name' => '',                   // required
            'email' => 'invalid-email',     // invalid email
        ]);

        $response->assertSessionHasErrors(['customer_code', 'name', 'email']);
    }

    public function test_can_update_customer_and_primary_bank(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUST-0001',
            'name' => 'Original Customer Name',
            'is_active' => true,
        ]);

        CustomerBank::create([
            'customer_id' => $customer->id,
            'bank_name' => 'Bank Mandiri',
            'bank_account_number' => '111-222-333',
            'bank_account_name' => 'Original Customer Name',
            'is_primary' => true,
        ]);

        $updatePayload = [
            'customer_code' => 'CUST-0001',
            'name' => 'Updated Customer Name',
            'alias' => 'UCN',
            'is_active' => false,
            'bank_name' => 'Bank BCA',
            'bank_account_number' => '444-555-666',
            'bank_account_name' => 'Updated Customer Name',
        ];

        $response = $this->actingAs($this->admin)->put(route('customers.update', $customer->id), $updatePayload);

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Customer Name',
            'alias' => 'UCN',
            'is_active' => false,
            'updated_by' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('customer_banks', [
            'customer_id' => $customer->id,
            'bank_name' => 'Bank BCA',
            'bank_account_number' => '444-555-666',
            'is_primary' => true,
        ]);
    }

    public function test_can_soft_delete_and_restore_via_recycle_bin(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUST-0001',
            'name' => 'Customer To Delete',
        ]);

        // 1. Delete
        $deleteResponse = $this->actingAs($this->admin)->delete(route('customers.destroy', $customer->id));
        $deleteResponse->assertRedirect(route('customers.index'));

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);

        // 2. View in Recycle Bin
        $recycleBinResponse = $this->actingAs($this->admin)->get(route('recycle-bin.index', ['type' => 'customers']));
        $recycleBinResponse->assertOk();
        $recycleBinResponse->assertInertia(fn ($page) => $page
            ->where('activeTab', 'customers')
            ->has('items.data', 1)
            ->where('items.data.0.id', $customer->id)
        );

        // 3. Restore
        $restoreResponse = $this->actingAs($this->admin)->post(route('recycle-bin.restore', [
            'type' => 'customers',
            'id' => $customer->id,
        ]));
        $restoreResponse->assertRedirect();

        $this->assertNotSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_can_force_delete_via_recycle_bin(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUST-0001',
            'name' => 'Customer To Force Delete',
        ]);
        $customer->delete();

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);

        $forceDeleteResponse = $this->actingAs($this->admin)->delete(route('recycle-bin.force-delete', [
            'type' => 'customers',
            'id' => $customer->id,
        ]));
        $forceDeleteResponse->assertRedirect();

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_can_generate_next_sequential_code(): void
    {
        Customer::create([
            'customer_code' => 'CUST-0001',
            'name' => 'Customer 1',
        ]);

        Customer::create([
            'customer_code' => 'CUST-0002',
            'name' => 'Customer 2',
        ]);

        $response = $this->actingAs($this->admin)->get(route('customers.generate-code'));
        $response->assertOk();
        $response->assertJson(['code' => 'CUST-0003']);
    }

    public function test_customer_has_relationships(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUST-0001',
            'name' => 'Customer Relational Test',
            'term_of_payment_id' => $this->top->id,
            'currency_id' => $this->currency->id,
            'coa_receivable_id' => $this->receivableCoa->id,
        ]);

        $bank = CustomerBank::create([
            'customer_id' => $customer->id,
            'bank_name' => 'Bank Mandiri',
            'bank_account_number' => '123456789',
            'bank_account_name' => 'Customer Relational Test',
            'is_primary' => true,
        ]);

        $this->assertTrue($customer->termOfPayment->is($this->top));
        $this->assertTrue($customer->currency->is($this->currency));
        $this->assertTrue($customer->receivableAccount->is($this->receivableCoa));
        $this->assertTrue($customer->primaryBank->is($bank));
        $this->assertCount(1, $customer->banks);
    }
}
