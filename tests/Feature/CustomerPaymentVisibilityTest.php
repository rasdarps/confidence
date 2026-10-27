<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerPaymentVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function operator(bool $cashier = true, array $permissions = []): User
    {
        $user = User::factory()->create(['phone' => '024'.random_int(1000000, 9999999), 'user_type' => 'Staff', 'must_change_password' => false]);
        foreach ($permissions as $name) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }
        if ($cashier) $user->assignRole(Role::firstOrCreate(['name' => 'Cashier', 'guard_name' => 'web']));
        return $user;
    }

    private function invoice(User $creator, bool $credit, string $name): Payment
    {
        $customer = Customer::create(['name' => $name, 'mobile_no' => '024'.random_int(1000000, 9999999)]);
        $sale = Sale::create(['sale_no' => 'TEST-'.(Sale::count() + 1), 'date' => '2026-10-01', 'created_by' => $creator->id]);
        return Payment::create(['sale_id' => $sale->id, 'customer_id' => $customer->id, 'total_amount' => 100,
            'paid_amount' => $credit ? 40 : 100, 'due_amount' => $credit ? 60 : 0,
            'paid_status' => $credit ? 'partial_payment' : 'full_payment']);
    }

    private function listing(string $route, array $filters = [])
    {
        $this->app->forgetInstance('datatables.request');
        $this->app->forgetInstance('datatables');
        return $this->getJson(route($route, array_merge([
            'draw' => 1, 'start' => 0, 'length' => 10,
            'columns' => [['data' => 'sale_no', 'name' => 'sale_no', 'searchable' => 'false', 'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false']]],
            'search' => ['value' => '', 'regex' => 'false'],
        ], $filters)), ['X-Requested-With' => 'XMLHttpRequest']);
    }

    public function test_cashier_paid_and_credit_rows_and_filters_are_own_sales_only(): void
    {
        $cashier = $this->operator(true, ['payment-sales-cash-list', 'payment-sales-credit-list']);
        $other = $this->operator();
        foreach (['paid.customer' => false, 'credit.customer' => true] as $route => $credit) {
            $own = $this->invoice($cashier, $credit, 'Own '.($credit ? 'Credit' : 'Paid'));
            $foreign = $this->invoice($other, $credit, 'Foreign '.($credit ? 'Credit' : 'Paid'));
            $this->actingAs($cashier);
            $this->listing($route)->assertOk()->assertJsonPath('recordsTotal', 1)->assertJsonPath('data.0.id', $own->id);
            $this->listing($route, ['sale_id' => $foreign->sale_id])->assertJsonPath('recordsFiltered', 0)->assertJsonCount(0, 'data');
            $this->listing($route, ['customer_id' => $foreign->customer_id])->assertJsonCount(0, 'data');
            $this->listing($route, ['from_date' => '2026-10-01', 'to_date' => '2026-10-01'])->assertJsonPath('recordsFiltered', 1);
            $this->app->forgetInstance('datatables.request');
            $this->app->forgetInstance('datatables');
            $this->get(route($route))->assertOk()->assertSee('Showing payments for your sales only.')->assertSee($own->customer->name)->assertDontSee($foreign->customer->name)
                ->assertSee('<option value="'.$own->sale_id.'">', false)->assertDontSee('<option value="'.$foreign->sale_id.'">', false);
        }
    }

    public function test_other_roles_keep_access_to_all_paid_and_credit_records(): void
    {
        $first = $this->operator();
        $second = $this->operator();
        $manager = $this->operator(false, ['payment-sales-cash-list', 'payment-sales-credit-list']);
        foreach (['paid.customer' => false, 'credit.customer' => true] as $route => $credit) {
            $this->invoice($first, $credit, 'First '.($credit ? 'Credit' : 'Paid'));
            $foreign = $this->invoice($second, $credit, 'Second '.($credit ? 'Credit' : 'Paid'));
            $this->actingAs($manager);
            $this->listing($route)->assertOk()->assertJsonPath('recordsTotal', 2)->assertJsonCount(2, 'data');
            $this->listing($route, ['sale_id' => $foreign->sale_id])->assertJsonPath('data.0.id', $foreign->id);
        }
    }

    public function test_cashier_cannot_view_or_pay_another_cashiers_credit_invoice_directly(): void
    {
        $cashier = $this->operator(true, ['payment-sales-credit-pay']);
        $foreign = $this->invoice($this->operator(), true, 'Foreign Credit');
        $own = $this->invoice($cashier, true, 'Own Credit');
        $this->actingAs($cashier);
        $this->get(route('customer.edit.sale', $foreign->sale_id))->assertNotFound();
        $this->post(route('customer.update.sale', $foreign->sale_id), ['date' => '2026-10-03', 'paid_status' => 'full_payment'])->assertNotFound();
        $this->assertSame(60.0, (float) $foreign->fresh()->due_amount);
        $this->assertDatabaseCount('payment_details', 0);
        $this->get(route('customer.edit.sale', $own->sale_id))->assertOk();
        $manager = $this->operator(false, ['payment-sales-credit-pay']);
        $this->actingAs($manager)->get(route('customer.edit.sale', $foreign->sale_id))->assertOk();
    }

    public function test_payment_listing_permissions_remain_required(): void
    {
        foreach (['paid.customer', 'credit.customer'] as $route) {
            Auth::forgetGuards();
            $this->listing($route)->assertUnauthorized();
            $this->actingAs($this->operator());
            $this->listing($route)->assertForbidden();
        }
    }
}