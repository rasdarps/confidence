<?php

namespace Tests\Unit;

use App\Services\ConversationParser;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Tests\TestCase;

class ConversationParserTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_dates_and_follow_ups_are_deterministic(): void
    {
        CarbonImmutable::setTestNow('2026-10-02 12:00:00');
        $parser = new ConversationParser();
        $report = $parser->parse('Sales for customer "Acme" this week');
        $this->assertSame('sales', $report['intent']);
        $this->assertSame('Acme', $report['customer']);
        $this->assertSame('2026-09-28', $report['from']);
        $this->assertSame('2026-10-02', $report['to']);
        $followUp = $parser->parse('And last month?', $report);
        $this->assertSame('Acme', $followUp['customer']);
        $this->assertSame('2026-09-01', $followUp['from']);
        $this->assertSame('2026-09-30', $followUp['to']);
        $expenses = $parser->parse('Expenses today', $followUp);
        $this->assertArrayNotHasKey('customer', $expenses);
    }

    public function test_explicit_month_and_comparison(): void
    {
        CarbonImmutable::setTestNow('2026-10-02');
        $parser = new ConversationParser();
        $this->assertSame('2026-09-30', $parser->parse('expenses in September 2026')['to']);
        $this->assertSame(['2026-09-01', '2026-09-30'], $parser->parse('Compare sales this month with last month')['comparison']);
    }

    public function test_invalid_dates_are_not_normalized_silently(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ConversationParser())->parse('Sales on 2026-02-30');
    }

    public function test_unsupported_questions_do_not_reuse_previous_report(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ConversationParser())->parse('Tell me a joke', ['intent' => 'sales']);
    }

    public function test_write_requests_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ConversationParser())->parse('Delete expenses today');
    }

    public function test_quoted_names_do_not_change_intent_and_are_case_insensitive(): void
    {
        $report = (new ConversationParser())->parse('Sales for Customer "May Bank" today');
        $this->assertSame('sales', $report['intent']);
        $this->assertSame('May Bank', $report['customer']);
    }

    public function test_compare_follow_up_keeps_the_original_period(): void
    {
        CarbonImmutable::setTestNow('2026-10-02');
        $parser = new ConversationParser();
        $report = $parser->parse('sales this month');
        $comparison = $parser->parse('Compare with last month', $report);
        $this->assertSame('2026-10-01', $comparison['from']);
        $this->assertSame(['2026-09-01', '2026-09-30'], $comparison['comparison']);
    }

    public function test_supplier_payments_and_bank_type_are_not_invoice_totals(): void
    {
        $parser = new ConversationParser();
        $this->assertSame('supplier_payments', $parser->parse('Supplier payments this month')['intent']);
        $this->assertSame(['deposit'], $parser->parse('Bank deposits today')['bank_type']);
        $this->assertSame(['deposit', 'withdrawal'], $parser->parse('Bank deposits and withdrawals today')['bank_type']);
    }

    public function test_unknown_named_filters_do_not_produce_unfiltered_totals(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ConversationParser())->parse('Sales for Acme today');
    }

    public function test_stock_balance_and_value_keep_product_filters_in_follow_ups(): void
    {
        $parser = new ConversationParser();
        $report = $parser->parse('Stock balance for Product "Widget"');
        $this->assertSame('stock_balance', $report['intent']);
        $this->assertSame('Widget', $report['product']);
        $value = $parser->parse('And its value?', $report);
        $this->assertSame('stock_value', $value['intent']);
        $this->assertSame('Widget', $value['product']);
        $all = $parser->parse('Show stock value for all products', $value);
        $this->assertArrayNotHasKey('product', $all);
        $this->assertSame('Widget', $parser->parse('What is the current stock of "Widget"?')['product']);
    }

    public function test_unquoted_stock_product_names_preserve_decimal_sizes_and_dates(): void
    {
        $parser = new ConversationParser();
        foreach (['stock value for Stanley Cup 1.5litres', 'stock value for product Stanley Cup 1.5litres', 'stock balance for Stanley Cup 1.5litres today'] as $question) {
            $report = $parser->parse($question);
            $this->assertSame('Stanley Cup 1.5litres', $report['product']);
        }
        $report = $parser->parse('stock value for Stanley Cup 1.5litres yesterday');
        $this->assertTrue($report['date_requested']);
        $this->assertSame('stock_value', $report['intent']);
        $this->assertArrayNotHasKey('product', $parser->parse('Stock value for all products'));
        $this->assertSame('General', $parser->parse('Stock value for category "General"')['category']);
    }
}