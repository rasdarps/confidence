<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\ConversationParser;
use App\Services\ConversationReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ConversationController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:chat-use');
    }

    public function index(Request $request)
    {
        $conversationId = (string) Str::uuid();
        $examples = [
            'sales' => 'Total sales today',
            'collections' => 'Money collected this week',
            'purchases' => 'Purchases this month',
            'supplier_payments' => 'Supplier payments this month',
            'customer_balance' => 'Customer outstanding balances',
            'supplier_balance' => 'Supplier outstanding balances',
            'low_stock' => 'Show low stock',
            'stock_balance' => 'Show total stock balance',
            'stock_value' => 'Show total stock value',
            'top_products' => 'Best-selling products this month',
            'expenses' => 'Expenses this month by category',
            'bank_balances' => 'Show bank balances',
            'bank_transactions' => 'Bank transactions this week',
        ];
        $examples = array_filter($examples, fn ($intent) => $request->user()->can(ConversationReportService::PERMISSIONS[$intent]), ARRAY_FILTER_USE_KEY);
        return view('backend.conversation.index', compact('conversationId', 'examples'));
    }

    public function ask(Request $request, ConversationParser $parser, ConversationReportService $reports)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:500'],
            'conversation_id' => ['required', 'uuid'],
            'reset' => ['sometimes', 'boolean'],
        ]);
        $key = 'reporting_chat.'.$request->user()->id.'.'.$data['conversation_id'];
        try {
            $context = $request->boolean('reset') ? [] : $request->session()->get($key, []);
            $report = $parser->parse($data['message'], $context);
            $answer = $reports->answer($request->user(), $report);
            $threadsKey = 'reporting_chat.'.$request->user()->id;
            $threads = $request->session()->get($threadsKey, []);
            unset($threads[$data['conversation_id']]);
            $threads[$data['conversation_id']] = $report;
            $request->session()->put($threadsKey, array_slice($threads, -10, null, true));
            Log::info('Reporting chat query', ['user_id' => $request->user()->id, 'report' => $report, 'conversation_id' => $data['conversation_id']]);
            return response()->json(['status' => 'success', 'answer' => $answer]);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['status' => 'clarification', 'message' => $exception->getMessage()]);
        }
    }
}