<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SalesListingTotalTest extends TestCase
{
    use RefreshDatabase;

    private function operator(bool $cashier = false): User
    {
        $user = User::factory()->create(['phone' => '024'.random_int(1000000, 9999999), 'user_type' => 'Staff', 'must_change_password' => false]);
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'sale-list', 'guard_name' => 'web']));
        if ($cashier) {
            $user->assignRole(Role::firstOrCreate(['name' => 'Cashier', 'guard_name' => 'web']));
        }
        return $user;
    }

    private function invoice(User $creator, string $date, float $amount, string $status = 'full_payment', ?Customer $customer = null): Sale
    {
        $customer = $customer ?? Customer::firstOrCreate(['email' => 'listing@example.test'], ['name' => 'Listing Customer', 'mobile_no' => '0240000001']);
        $sale = Sale::create(['sale_no' => (string) (Sale::withTrashed()->count() + 1), 'date' => $date, 'created_by' => $creator->id]);
        Payment::create(['sale_id' => $sale->id, 'customer_id' => $customer->id, 'total_amount' => $amount, 'paid_status' => $status,
            'paid_amount' => $status === 'full_payment' ? $amount : 0, 'due_amount' => $status === 'full_payment' ? 0 : $amount]);
        return $sale;
    }

    private function listing(array $filters = [], int $start = 0)
    {
        $this->app->forgetInstance('datatables.request');
        $this->app->forgetInstance('datatables');
        $data = array_merge([
            'draw' => 1, 'start' => $start, 'length' => 1,
            'columns' => [
                ['data' => 'date', 'name' => 'date', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'sale_no', 'name' => 'sale_no', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
            ],
            'order' => [['column' => 0, 'dir' => 'desc']],
            'search' => ['value' => '', 'regex' => 'false'],
        ], $filters);
        return $this->getJson(route('sales.index', $data), ['X-Requested-With' => 'XMLHttpRequest']);
    }

    public function test_default_order_is_newest_first_and_total_covers_all_pages_and_dates(): void
    {
        $user = $this->operator();
        $this->invoice($user, '2025-01-01', 100);
        $this->invoice($user, '2026-10-03', 200);
        $newest = $this->invoice($user, '2026-10-03', 50);
        $this->invoice($user, '2026-09-01', 900)->forceFill(['cancelled_at' => now()])->save();
        $this->invoice($user, '2026-09-01', 800)->delete();
        $this->actingAs($user);
        $this->listing()->assertOk()->assertJsonPath('data.0.id', $newest->id)->assertJsonPath('total_sales', 350)->assertJsonCount(1, 'data');
        $this->listing([], 1)->assertOk()->assertJsonPath('total_sales', 350);
    }

    public function test_filter_boxes_restrict_the_same_total_as_the_sales_table(): void
    {
        $user = $this->operator();
        $other = $this->operator();
        $customer = Customer::create(['name' => 'Second Customer', 'mobile_no' => '0240000002']);
        $sale = $this->invoice($user, '2026-09-10', 120, 'full_credit', $customer);
        $sale->forceFill(['sale_no' => 'SEARCH-TARGET'])->save();
        $this->invoice($user, '2026-08-10', 80);
        $this->invoice($other, '2026-09-10', 200);
        $this->actingAs($user);
        foreach ([
            ['sale_id' => $sale->id], ['customer_id' => $customer->id], ['payment_status' => 'full_credit'],
            ['from_date' => '2026-09-01', 'to_date' => '2026-09-30', 'created_by' => $user->id],
        ] as $filters) {
            $this->listing($filters)->assertOk()->assertJsonPath('total_sales', 120)->assertJsonPath('recordsFiltered', 1);
        }
        $this->listing(['from_date' => '2027-01-01'])->assertJsonPath('total_sales', 0)->assertJsonCount(0, 'data');
        $this->listing(['search' => ['value' => (string) $sale->sale_no, 'regex' => 'false']])->assertJsonPath('total_sales', 120);
        $this->listing()->assertJsonPath('total_sales', 400);
    }

    public function test_cashier_cannot_include_another_cashiers_sales_in_the_total(): void
    {
        $cashier = $this->operator(true);
        $other = $this->operator(true);
        $this->invoice($cashier, '2025-01-01', 100);
        $own = $this->invoice($cashier, '2026-10-03', 50);
        $foreign = $this->invoice($other, '2026-10-03', 900);
        $this->actingAs($cashier);
        $this->listing()->assertJsonPath('total_sales', 150)->assertJsonPath('recordsTotal', 2)->assertJsonPath('data.0.id', $own->id);
        $this->listing(['created_by' => $other->id])->assertJsonPath('total_sales', 0)->assertJsonCount(0, 'data');
        $this->listing(['sale_id' => $foreign->id])->assertJsonPath('total_sales', 0)->assertJsonCount(0, 'data');
    }

    public function test_sales_listing_requires_permission(): void
    {
        $this->listing()->assertUnauthorized();
        $user = User::factory()->create(['phone' => '0241111111', 'user_type' => 'Staff', 'must_change_password' => false]);
        $this->actingAs($user)->listing()->assertForbidden();
    }

    public function test_sales_page_renders_total_card_with_descending_default_order(): void
    {
        $this->actingAs($this->operator(true))->get(route('sales.index'))->assertOk()
            ->assertSee('id="sales-total-card"', false)->assertSee('id="sales-total-amount"', false)
            ->assertSee('All time - Your sales')->assertSee('Cancelled sales excluded')
            ->assertSee('"order":[[0,"desc"]]', false);
    }
}