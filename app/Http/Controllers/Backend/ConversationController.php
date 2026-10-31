<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
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
        $user = $request->user();
        $canFilterOutlets = $user && ($user->can('dashboard-outlet-filter') || $user->hasRole('Super Admin') || $user->isCentral());
        $outlets = Outlet::where('is_active', true)->orderByDesc('is_central')->orderBy('name')->get();

        if ($canFilterOutlets) {
            if ($request->has('outlet_id')) {
                $selectedOutletId = $request->filled('outlet_id') ? (int) $request->outlet_id : null;
                $request->session()->put('reporting_chat_outlet_id.'.$user->id, $selectedOutletId);
            } else {
                $selectedOutletId = $request->session()->get('reporting_chat_outlet_id.'.$user->id);
            }
        } else {
            $selectedOutletId = $user->assignedOutletId() ?? ($user->outlet_id ? (int) $user->outlet_id : null);
        }

        $selectedOutlet = $selectedOutletId ? $outlets->firstWhere('id', $selectedOutletId) : null;

        $examples = [
            'sales' => 'Total sales today',
            'collections' => 'Money collected this week',
            'purchases' => 'Purchases this month',
            'supplier_payments' => 'Supplier payments this month',
            'customer_balance' => 'Customer outstanding balances',
            'supplier_balance' => 'Supplier outstanding balances',
            'low_stock' => $selectedOutlet ? 'Low stock at this outlet' : 'Show low stock',
            'stock_balance' => $selectedOutlet ? 'Stock balance in bags/boxes & pieces' : 'Show stock balance in bags/boxes & pieces',
            'stock_value' => $selectedOutlet ? 'Stock value for this outlet' : 'Show total stock value',
            'top_products' => 'Best-selling products this month',
            'expenses' => 'Expenses this month by category',
            'bank_balances' => 'Show bank balances',
            'bank_transactions' => 'Bank transactions this week',
        ];
        $examples = array_filter($examples, fn ($intent) => $user->can(ConversationReportService::PERMISSIONS[$intent]), ARRAY_FILTER_USE_KEY);

        return view('backend.conversation.index', compact(
            'conversationId',
            'examples',
            'canFilterOutlets',
            'outlets',
            'selectedOutletId',
            'selectedOutlet'
        ));
    }

    public function ask(Request $request, ConversationParser $parser, ConversationReportService $reports)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:500'],
            'conversation_id' => ['required', 'uuid'],
            'reset' => ['sometimes', 'boolean'],
            'outlet_id' => ['sometimes', 'nullable', 'integer', 'exists:outlets,id'],
        ]);

        $user = $request->user();
        $canFilterOutlets = $user && ($user->can('dashboard-outlet-filter') || $user->hasRole('Super Admin') || $user->isCentral());

        if ($canFilterOutlets) {
            $outletId = array_key_exists('outlet_id', $data)
                ? ($data['outlet_id'] ? (int) $data['outlet_id'] : null)
                : $request->session()->get('reporting_chat_outlet_id.'.$user->id);
        } else {
            $outletId = $user->assignedOutletId() ?? ($user->outlet_id ? (int) $user->outlet_id : null);
        }

        $key = 'reporting_chat.'.$user->id.'.'.$data['conversation_id'];
        try {
            $context = $request->boolean('reset') ? [] : $request->session()->get($key, []);
            $report = $parser->parse($data['message'], $context);
            $answer = $reports->answer($user, $report, $outletId);
            $threadsKey = 'reporting_chat.'.$user->id;
            $threads = $request->session()->get($threadsKey, []);
            unset($threads[$data['conversation_id']]);
            $threads[$data['conversation_id']] = $report;
            $request->session()->put($threadsKey, array_slice($threads, -10, null, true));
            Log::info('Reporting chat query', [
                'user_id' => $user->id,
                'outlet_id' => $outletId,
                'report' => $report,
                'conversation_id' => $data['conversation_id']
            ]);
            return response()->json(['status' => 'success', 'answer' => $answer]);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['status' => 'clarification', 'message' => $exception->getMessage()]);
        }
    }
}
