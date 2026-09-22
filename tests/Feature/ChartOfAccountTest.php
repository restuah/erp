<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChartOfAccountTest extends TestCase
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

    public function test_can_view_chart_of_accounts_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('chart-of-accounts.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('ChartOfAccounts/Index'));
    }

    public function test_can_create_root_account_with_level_one_and_postable(): void
    {
        $payload = [
            'account_code' => '9000',
            'account_name' => 'Akun Uji Coba Root',
            'parent_code' => null,
            'jenis' => 'debit',
            'kategori' => 'bs',
            'postable' => true,
            'description' => 'Test root account',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)->post(route('chart-of-accounts.store'), $payload);

        $response->assertRedirect(route('chart-of-accounts.index'));

        $this->assertDatabaseHas('chart_of_accounts', [
            'account_code' => '9000',
            'account_name' => 'Akun Uji Coba Root',
            'level' => 1,
            'parent_code' => null,
            'parent_id' => null,
            'postable' => true,
        ]);
    }

    public function test_creating_child_account_sets_parent_postable_to_false_and_computes_level(): void
    {
        // 1. Create parent account
        $parent = ChartOfAccount::create([
            'account_code' => '1000',
            'account_name' => 'Aset',
            'level' => 1,
            'parent_code' => null,
            'jenis' => 'debit',
            'kategori' => 'bs',
            'postable' => true,
        ]);

        $this->assertTrue($parent->postable);

        // 2. Create child under this parent
        $payload = [
            'account_code' => '1100',
            'account_name' => 'Aset Lancar',
            'parent_code' => '1000',
            'jenis' => 'debit',
            'kategori' => 'bs',
            'postable' => true,
        ];

        $response = $this->actingAs($this->admin)->post(route('chart-of-accounts.store'), $payload);
        $response->assertRedirect(route('chart-of-accounts.index'));

        // Verify child has level 2 and parent_id
        $this->assertDatabaseHas('chart_of_accounts', [
            'account_code' => '1100',
            'level' => 2,
            'parent_code' => '1000',
            'parent_id' => $parent->id,
            'postable' => true,
        ]);

        // CRITICAL: Parent MUST automatically become postable = false!
        $parent->refresh();
        $this->assertFalse($parent->postable, 'Parent account must automatically become postable = false when a child is created.');
    }

    public function test_cannot_select_self_or_descendant_as_parent(): void
    {
        $root = ChartOfAccount::create([
            'account_code' => '1000',
            'account_name' => 'Aset',
            'level' => 1,
            'jenis' => 'debit',
            'kategori' => 'bs',
            'postable' => false,
        ]);

        $child = ChartOfAccount::create([
            'account_code' => '1100',
            'account_name' => 'Aset Lancar',
            'level' => 2,
            'parent_code' => '1000',
            'parent_id' => $root->id,
            'jenis' => 'debit',
            'kategori' => 'bs',
            'postable' => true,
        ]);

        // 1. Attempt to set root's parent to itself
        $response = $this->actingAs($this->admin)->put(route('chart-of-accounts.update', $root->id), [
            'account_code' => '1000',
            'account_name' => 'Aset',
            'parent_code' => '1000',
            'jenis' => 'debit',
            'kategori' => 'bs',
            'postable' => false,
        ]);
        $response->assertSessionHasErrors('parent_code');

        // 2. Attempt to set root's parent to its child (circular)
        $response2 = $this->actingAs($this->admin)->put(route('chart-of-accounts.update', $root->id), [
            'account_code' => '1000',
            'account_name' => 'Aset',
            'parent_code' => '1100',
            'jenis' => 'debit',
            'kategori' => 'bs',
            'postable' => false,
        ]);
        $response2->assertSessionHasErrors('parent_code');
    }

    public function test_cannot_delete_account_that_has_children(): void
    {
        $parent = ChartOfAccount::create([
            'account_code' => '1000',
            'account_name' => 'Aset',
            'level' => 1,
            'jenis' => 'debit',
            'kategori' => 'bs',
            'postable' => false,
        ]);

        $child = ChartOfAccount::create([
            'account_code' => '1100',
            'account_name' => 'Aset Lancar',
            'level' => 2,
            'parent_code' => '1000',
            'parent_id' => $parent->id,
            'jenis' => 'debit',
            'kategori' => 'bs',
            'postable' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('chart-of-accounts.destroy', $parent->id));
        $response->assertSessionHas('error');

        // Parent should still exist in database
        $this->assertDatabaseHas('chart_of_accounts', [
            'id' => $parent->id,
            'deleted_at' => null,
        ]);
    }

    public function test_deleting_leaf_child_restores_parent_postable_if_no_children_remain(): void
    {
        $parent = ChartOfAccount::create([
            'account_code' => '1000',
            'account_name' => 'Aset',
            'level' => 1,
            'jenis' => 'debit',
            'kategori' => 'bs',
            'postable' => false,
        ]);

        $child = ChartOfAccount::create([
            'account_code' => '1100',
            'account_name' => 'Aset Lancar',
            'level' => 2,
            'parent_code' => '1000',
            'parent_id' => $parent->id,
            'jenis' => 'debit',
            'kategori' => 'bs',
            'postable' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('chart-of-accounts.destroy', $child->id));
        $response->assertRedirect(route('chart-of-accounts.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('chart_of_accounts', [
            'id' => $child->id,
        ]);

        // Parent should now have postable = true since it has 0 active children left
        $parent->refresh();
        $this->assertTrue($parent->postable);
    }

    public function test_can_restore_soft_deleted_coa_from_recycle_bin(): void
    {
        $account = ChartOfAccount::create([
            'account_code' => '9999',
            'account_name' => 'Akun Dihapus',
            'level' => 1,
            'jenis' => 'debit',
            'kategori' => 'bs',
            'postable' => true,
        ]);

        $account->delete();
        $this->assertSoftDeleted('chart_of_accounts', ['id' => $account->id]);

        $response = $this->actingAs($this->admin)->post(route('recycle-bin.restore', [
            'type' => 'chart_of_accounts',
            'id' => $account->id,
        ]));

        $response->assertRedirect();
        $this->assertDatabaseHas('chart_of_accounts', [
            'id' => $account->id,
            'deleted_at' => null,
        ]);
    }
}
