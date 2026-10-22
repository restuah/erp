<?php

namespace Tests\Feature;

use App\Models\TermOfPayment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TermOfPaymentTest extends TestCase
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

    public function test_can_view_term_of_payments_index(): void
    {
        TermOfPayment::create([
            'payment' => '30_A_INVOICE',
            'name' => '30 Days After Invoice',
            'days' => 30,
            'description' => 'Net 30 days',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('term-of-payments.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('TermOfPayments/Index')
            ->has('terms.data', 1)
            ->where('terms.data.0.payment', '30_A_INVOICE')
            ->where('stats.total', 1)
            ->where('stats.active', 1)
        );
    }

    public function test_can_search_and_filter_term_of_payments(): void
    {
        TermOfPayment::create([
            'payment' => 'COD',
            'name' => 'Cash On Delivery',
            'days' => 0,
            'is_active' => true,
        ]);

        TermOfPayment::create([
            'payment' => '60_A_INVOICE',
            'name' => '60 Days After Invoice',
            'days' => 60,
            'is_active' => false,
        ]);

        // Search COD
        $searchRes = $this->actingAs($this->admin)->get(route('term-of-payments.index', ['search' => 'Delivery']));
        $searchRes->assertOk();
        $searchRes->assertInertia(fn ($page) => $page
            ->has('terms.data', 1)
            ->where('terms.data.0.payment', 'COD')
        );

        // Filter status inactive
        $statusRes = $this->actingAs($this->admin)->get(route('term-of-payments.index', ['status' => '0']));
        $statusRes->assertOk();
        $statusRes->assertInertia(fn ($page) => $page
            ->has('terms.data', 1)
            ->where('terms.data.0.payment', '60_A_INVOICE')
        );
    }

    public function test_can_create_term_of_payment(): void
    {
        $payload = [
            'payment' => '14_a_invoice',
            'name' => '14 Days After Invoice',
            'days' => 14,
            'description' => '2 Minggu setelah invoice',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)->post(route('term-of-payments.store'), $payload);

        $response->assertRedirect(route('term-of-payments.index'));
        $this->assertDatabaseHas('term_of_payments', [
            'payment' => '14_A_INVOICE',
            'days' => 14,
        ]);
    }

    public function test_term_of_payment_validates_required_fields_and_uniqueness(): void
    {
        TermOfPayment::create([
            'payment' => 'EXISTING_TERM',
            'name' => 'Existing Term',
            'days' => 10,
        ]);

        $response = $this->actingAs($this->admin)->post(route('term-of-payments.store'), [
            'payment' => 'existing_term',
            'name' => '',
            'days' => -5,
        ]);

        $response->assertSessionHasErrors(['payment', 'name', 'days']);
    }

    public function test_can_update_term_of_payment(): void
    {
        $term = TermOfPayment::create([
            'payment' => 'OLD_CODE',
            'name' => 'Old Name',
            'days' => 15,
        ]);

        $response = $this->actingAs($this->admin)->put(route('term-of-payments.update', $term->id), [
            'payment' => 'NEW_CODE',
            'name' => 'Updated Name',
            'days' => 20,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('term-of-payments.index'));
        $this->assertDatabaseHas('term_of_payments', [
            'id' => $term->id,
            'payment' => 'NEW_CODE',
            'name' => 'Updated Name',
            'days' => 20,
        ]);
    }

    public function test_can_soft_delete_and_restore_term_of_payment_via_recycle_bin(): void
    {
        $term = TermOfPayment::create([
            'payment' => 'TO_DELETE',
            'name' => 'To Delete',
            'days' => 5,
        ]);

        $delResponse = $this->actingAs($this->admin)->delete(route('term-of-payments.destroy', $term->id));
        $delResponse->assertRedirect(route('term-of-payments.index'));

        $this->assertSoftDeleted('term_of_payments', ['id' => $term->id]);

        // Restore via recycle bin
        $restoreResponse = $this->actingAs($this->admin)->post(route('recycle-bin.restore', [
            'type' => 'term_of_payments',
            'id' => $term->id,
        ]));
        $restoreResponse->assertRedirect();
        $this->assertNotSoftDeleted('term_of_payments', ['id' => $term->id]);
    }
}
