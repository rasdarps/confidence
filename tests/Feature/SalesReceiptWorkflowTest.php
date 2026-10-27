<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReceiptEvent;
use App\Models\Unit;
use App\Models\User;
use App\Services\Accounting\AccountingIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Http\Middleware\VerifyCsrfToken;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SalesReceiptWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        Company::create([
            'name' => 'Test Company',
            'address' => 'Test Address',
            'phone' => '555-' . uniqid(),
            'email' => uniqid() . '@example.test',
        ]);
    }

    public function test_original_print_requires_permission_and_only_records_once(): void
    {
        $sale = $this->createSale();
        $operator = $this->createOperator(['sale-print']);
        $url = route('print.sale', Crypt::encrypt($sale->id));

        $this->actingAs($this->createOperator([]))->get($url)->assertForbidden();
        $this->assertNull($sale->fresh()->printed_at);
        $this->assertSame(0, SaleReceiptEvent::count());

        $this->actingAs($operator)->get($url)->assertOk();
        $this->get($url)->assertStatus(409);

        $sale->refresh();
        $this->assertNotNull($sale->printed_at);
        $this->assertSame($operator->id, $sale->printed_by);
        $this->assertSame(1, SaleReceiptEvent::where('event_type', 'original')->count());
    }

    public function test_reprint_requires_reason_and_keeps_original_stamp_unchanged(): void
    {
        $sale = $this->createSale();
        $operator = $this->createOperator(['sale-print', 'sale-receipt-reprint']);
        $this->actingAs($operator)->get(route('print.sale', Crypt::encrypt($sale->id)))->assertOk();
        $sale->refresh();
        $printedAt = $sale->printed_at->toDateTimeString();
        $printedBy = $sale->printed_by;
        $reprintUrl = route('sales.receipt.reprint', Crypt::encrypt($sale->id));

        $this->postJson($reprintUrl, [])->assertStatus(422)->assertJsonValidationErrors('reason');
        $this->assertSame(1, SaleReceiptEvent::where('event_type', 'original')->count());

        $originalPrinter = $this->createOperator(['sale-print']);
        $this->actingAs($originalPrinter)->post($reprintUrl, ['reason' => 'Unauthorized reprint'])->assertForbidden();

        $this->actingAs($operator);
        $this->post($reprintUrl, ['reason' => 'Customer copy requested'])->assertOk()->assertSee('MANAGER REPRINT');
        $sale->refresh();

        $this->assertSame($printedAt, $sale->printed_at->toDateTimeString());
        $this->assertSame($printedBy, $sale->printed_by);
        $this->assertDatabaseHas('sale_receipt_events', [
            'sale_id' => $sale->id,
            'event_type' => 'reprint',
            'operator_id' => $operator->id,
            'reason' => 'Customer copy requested',
        ]);
    }

    public function test_delivery_code_is_unique_and_duplicate_scans_are_rejected(): void
    {
        $sale = $this->createSale();
        $anotherSale = $this->createSale();
        $this->assertNotSame($sale->delivery_code, $anotherSale->delivery_code);
        $this->assertSame(12, strlen($sale->delivery_code));
        $this->assertMatchesRegularExpression('/^D[A-Z0-9]{11}$/', $sale->delivery_code);

        $operator = $this->createOperator(['sale-delivery-verify']);
        $url = route('sales.delivery.verify');
        $this->actingAs($operator)->postJson($url, ['code' => strtolower($sale->delivery_code)])->assertOk()
            ->assertJsonPath('status', 'success');
        $this->postJson($url, ['code' => $sale->delivery_code])->assertStatus(409)
            ->assertJsonPath('status', 'duplicate');

        $this->assertSame(1, SaleReceiptEvent::where('sale_id', $sale->id)->where('event_type', 'delivery_verified')->count());
        $this->assertSame($operator->id, $sale->fresh()->delivered_by);
    }

    public function test_cancelled_sale_and_invalid_delivery_code_cannot_be_verified(): void
    {
        $sale = $this->createSale();
        $legacyDeliveryCode = 'D' . str_repeat('A', 32);
        $sale->forceFill(['cancelled_at' => now(), 'delivery_code' => $legacyDeliveryCode])->save();
        $operator = $this->createOperator(['sale-delivery-verify']);

        $this->actingAs($operator)->postJson(route('sales.delivery.verify'), ['code' => $legacyDeliveryCode])
            ->assertStatus(409)->assertJsonPath('status', 'cancelled');
        $this->postJson(route('sales.delivery.verify'), ['code' => 'not-a-delivery-code'])
            ->assertStatus(422)->assertJsonPath('status', 'invalid');
        $this->assertNull($sale->fresh()->delivered_at);
        $this->assertSame(0, SaleReceiptEvent::where('event_type', 'delivery_verified')->count());
    }

    public function test_receipt_audit_is_permission_protected_and_exportable(): void
    {
        $sale = $this->createSale();
        $printer = $this->createOperator(['sale-print']);
        $this->actingAs($printer)->get(route('print.sale', Crypt::encrypt($sale->id)))->assertOk();

        $this->get(route('reports.sales-receipt-audit'))->assertForbidden();
        $auditor = $this->createOperator(['sale-receipt-audit-view']);
        $this->actingAs($auditor)->get(route('reports.sales-receipt-audit', ['event_type' => 'original']))
            ->assertOk()->assertSee('receipt-audit-table', false);
        $this->get(route('reports.sales-receipt-audit.export', ['event_type' => 'original']))
            ->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_regular_auditors_cannot_see_super_admin_receipt_events(): void
    {
        $regularSale = $this->createSale();
        $superAdminSale = $this->createSale();
        $regularOperator = $this->createOperator([]);
        $superAdmin = $this->createOperator([]);
        $superAdmin->update(['user_type' => 'Super Admin']);
        $superAdminRole = Role::query()->firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin->assignRole($superAdminRole);

        SaleReceiptEvent::create([
            'sale_id' => $regularSale->id,
            'operator_id' => $regularOperator->id,
            'event_type' => 'original',
        ]);
        SaleReceiptEvent::create([
            'sale_id' => $superAdminSale->id,
            'operator_id' => $superAdmin->id,
            'event_type' => 'reprint',
            'reason' => 'Super admin copy',
        ]);

        $auditor = $this->createOperator(['sale-receipt-audit-view']);
        $dataTableRequest = [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'event_type' => 'original',
        ];

        $ajaxHeaders = [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ];

        $this->actingAs($auditor)
            ->get(route('reports.sales-receipt-audit', $dataTableRequest), $ajaxHeaders)
            ->assertOk()
            ->assertJsonFragment(['sale_no' => $regularSale->sale_no])
            ->assertJsonMissing(['sale_no' => $superAdminSale->sale_no]);

        $exportResponse = $this->actingAs($auditor)
            ->get(route('reports.sales-receipt-audit.export'));
        $exportResponse->assertOk();
        $exportContent = $exportResponse->streamedContent();
        $this->assertStringContainsString($regularSale->sale_no, $exportContent);
        $this->assertStringNotContainsString($superAdminSale->sale_no, $exportContent);
    }

    public function test_super_admins_can_see_all_receipt_events(): void
    {
        $regularSale = $this->createSale();
        $superAdminSale = $this->createSale();
        $regularOperator = $this->createOperator([]);
        $superAdmin = $this->createOperator([]);
        $superAdmin->update(['user_type' => 'Super Admin']);
        $superAdminRole = Role::query()->firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin->assignRole($superAdminRole);

        SaleReceiptEvent::create([
            'sale_id' => $regularSale->id,
            'operator_id' => $regularOperator->id,
            'event_type' => 'original',
        ]);
        SaleReceiptEvent::create([
            'sale_id' => $superAdminSale->id,
            'operator_id' => $superAdmin->id,
            'event_type' => 'reprint',
            'reason' => 'Super admin copy',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('reports.sales-receipt-audit', [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]), [
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
            ])
            ->assertOk()
            ->assertJsonFragment(['sale_no' => $regularSale->sale_no])
            ->assertJsonFragment(['sale_no' => $superAdminSale->sale_no]);
    }

    public function test_delivery_verification_page_renders_the_centered_scanner_layout(): void
    {
        $operator = $this->createOperator(['sale-delivery-verify']);

        $this->actingAs($operator)->get(route('sales.delivery.verify.page'))
            ->assertOk()
            ->assertSee('Delivery Verification')
            ->assertSee('Scan the receipt barcode or enter the code manually.')
            ->assertSee('Scan code or enter manually')
            ->assertSee('delivery-camera-button', false)
            ->assertSee('delivery-action-button', false);
    }

    public function test_dashboard_quick_action_renders_for_a_permitted_user_with_mobile_flow_styles(): void
    {
        $operator = $this->createOperator(['sale-delivery-verify', 'dashboard-view']);

        $this->actingAs($operator)->get(route('sales.delivery.verify.page'))
            ->assertOk()
            ->assertSee('ri-home-4-line', false)
            ->assertSee('position: static;', false)
            ->assertSee('display: grid !important;', false)
            ->assertSee('grid-auto-flow: column;', false);
    }

    public function test_sale_and_purchase_scanners_guard_duplicate_changes_and_camera_results(): void
    {
        foreach (['sale', 'purchase'] as $transactionType) {
            $source = file_get_contents(resource_path('views/backend/' . $transactionType . '/create.blade.php'));

            $this->assertStringContainsString('barcodeLookupsInFlight[barcode] || recentBarcodeScans[barcode]', $source);
            $this->assertStringContainsString('}, 1500);', $source);
            $this->assertStringContainsString('delete barcodeLookupsInFlight[barcode];', $source);
            $this->assertStringContainsString("triggerBarcodeLookup(cleanCode);", $source);
        }
    }

    public function test_sale_create_formats_totals_and_starts_with_blank_cash_tendered(): void
    {
        $source = file_get_contents(resource_path('views/backend/sale/create.blade.php'));
        $parseFunctionPosition = strpos($source, 'function parseMoney(value)');
        $submitUsePosition = strpos($source, "var totalAmount = parseMoney($('#estimated_amount').val());");

        $this->assertNotFalse($parseFunctionPosition);
        $this->assertNotFalse($submitUsePosition);
        $this->assertLessThan($submitUsePosition, $parseFunctionPosition);
        $this->assertStringContainsString("replace(/,/g, '')", $source);
        $this->assertStringContainsString("new Intl.NumberFormat('en-US'", $source);
        $this->assertStringContainsString("cashTenderedValue === '' ? 0 : Number(cashTenderedValue)", $source);
        $this->assertStringContainsString('name="cash_tendered" value="" id="cash_tendered"', $source);
    }

    public function test_partial_payment_uses_paid_amount_as_cash_tendered_and_persists_no_change(): void
    {
        $operator = $this->createOperator(['sale-create']);
        $customer = Customer::create([
            'name' => 'Credit Customer',
            'mobile_no' => '055' . random_int(1000000, 9999999),
            'email' => uniqid() . '@example.test',
        ]);
        $unit = Unit::create(['name' => 'Piece']);
        $category = Category::create(['name' => 'General']);
        $product = Product::create([
            'name' => 'Partial Payment Product',
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'quantity' => 10,
            'stock_level' => 1,
            'cost_price' => 50,
            'selling_price' => 100,
        ]);
        $payload = [
            'date' => now()->toDateString(),
            'product_id' => [$product->id],
            'selling_qty' => [1],
            'unit_selling_price' => [100],
            'selling_price' => [100],
            'paid_status' => 'partial_payment',
            'paid_amount' => 40,
            'cash_tendered' => 999,
            'customer_id' => $customer->id,
        ];

        $this->mock(AccountingIntegrationService::class, function ($mock) {
            $mock->shouldReceive('postSaleFromPayment')->once()->andReturnNull();
        });

        $this->actingAs($operator)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->postJson(route('sales.store'), $payload)
            ->assertOk();

        $this->assertDatabaseHas('payments', [
            'customer_id' => $customer->id,
            'paid_status' => 'partial_payment',
            'total_amount' => 100,
            'paid_amount' => 40,
            'due_amount' => 60,
            'cash_tendered' => 40,
            'change_due' => 0,
        ]);
        $this->assertDatabaseHas('payment_details', [
            'current_paid_amount' => 40,
        ]);
    }

    public function test_permission_seeder_creates_sale_print_on_a_fresh_permission_table(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->getPermissions();

        $this->seed(\Database\Seeders\PermissionTableSeeder::class);

        $superAdminRole = Role::findByName('Super Admin', 'web');
        $this->assertDatabaseHas('permissions', [
            'name' => 'sale-print',
            'guard_name' => 'web',
        ]);
        $this->assertSame(
            Permission::query()->where('guard_name', 'web')->count(),
            $superAdminRole->permissions()->count()
        );
        foreach (['sale-print', 'sale-receipt-reprint', 'sale-receipt-audit-view', 'sale-delivery-verify'] as $permission) {
            $this->assertTrue($superAdminRole->hasPermissionTo($permission));
        }
    }

    public function test_permission_seeder_preserves_existing_manager_and_cashier_ui_grants(): void
    {
        $this->seed(\Database\Seeders\PermissionTableSeeder::class);

        $managerRole = Role::findByName('Manager', 'web');
        $cashierRole = Role::findByName('Cashier', 'web');
        $managerRole->revokePermissionTo('product-list');
        $managerRole->givePermissionTo(Permission::query()->where('name', 'unit-edit')->where('guard_name', 'web')->firstOrFail());
        $cashierRole->revokePermissionTo('sale-print');
        $cashierRole->givePermissionTo(Permission::query()->where('name', 'purchase-edit')->where('guard_name', 'web')->firstOrFail());

        $this->seed(\Database\Seeders\PermissionTableSeeder::class);

        $managerRole = Role::findByName('Manager', 'web');
        $cashierRole = Role::findByName('Cashier', 'web');
        $this->assertFalse($managerRole->hasPermissionTo('product-list'));
        $this->assertTrue($managerRole->hasPermissionTo('unit-edit'));
        $this->assertFalse($cashierRole->hasPermissionTo('sale-print'));
        $this->assertTrue($cashierRole->hasPermissionTo('purchase-edit'));
    }

    private function createSale(): Sale
    {
        return Sale::create([
            'sale_no' => 'S-' . uniqid(),
            'date' => now()->toDateString(),
        ]);
    }

    private function createOperator(array $permissionNames): User
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $operator = User::create([
            'name' => 'Test Operator',
            'username' => uniqid('operator-'),
            'email' => uniqid() . '@example.test',
            'phone' => '555-' . uniqid(),
            'password' => Hash::make('password'),
        ]);

        foreach ($permissionNames as $permissionName) {
            $permission = Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
            $operator->givePermissionTo($permission);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $operator;
    }
}