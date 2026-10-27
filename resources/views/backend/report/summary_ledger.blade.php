@extends('layout.main')
@section('title') {{ 'Summary Ledger' }} @endsection

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Summary Ledger</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Accounting Ledger Summary</h4>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th class="text-center" colspan="2">Ledger Book</th>
                                    </tr>
                                    <tr>
                                        <th class="text-center text-danger">Debit (Left Side)</th>
                                        <th class="text-center text-success">Credit (Right Side)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div class="d-flex justify-content-between">
                                                <span>All Total Purchases</span>
                                                <strong>{{ number_format((float) $totalPurchases, 2) }}</strong>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-between">
                                                <span>All Total Sales</span>
                                                <strong>{{ number_format((float) $totalSales, 2) }}</strong>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="d-flex justify-content-between">
                                                <span>Current Total Stock Value</span>
                                                <strong>{{ number_format((float) $currentStockValue, 2) }}</strong>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-between">
                                                <span>Gross Profit</span>
                                                <strong>{{ number_format((float) $grossProfit, 2) }}</strong>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="d-flex justify-content-between">
                                                <span>All Expenses</span>
                                                <strong>{{ number_format((float) $totalExpenses, 2) }}</strong>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-between">
                                                <span>Total Net Profit</span>
                                                <strong>{{ number_format((float) $netProfit, 2) }}</strong>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="d-flex justify-content-between">
                                                <span>Cash Shortage Loss</span>
                                                <strong>{{ number_format((float) $cashShortageLoss, 2) }}</strong>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-between">
                                                <span>Cash Overage Gain</span>
                                                <strong>{{ number_format((float) $cashOverageGain, 2) }}</strong>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <div class="d-flex justify-content-between">
                                                <span>Cash Net Adjustment Impact</span>
                                                <strong>{{ number_format((float) $cashNetAdjustment, 2) }}</strong>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="d-flex justify-content-between">
                                                <span>Stock Shortage Loss</span>
                                                <strong>{{ number_format((float) $stockShortageLoss, 2) }}</strong>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-between">
                                                <span>Stock Overage Gain</span>
                                                <strong>{{ number_format((float) $stockOverageGain, 2) }}</strong>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <div class="d-flex justify-content-between">
                                                <span>Stock Net Adjustment Impact</span>
                                                <strong>{{ number_format((float) $stockNetAdjustment, 2) }}</strong>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-lg-4 col-md-6">
                                <div class="card border h-100 mb-0">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3">Sales Breakdown</h5>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Cash Sales</span>
                                            <strong>{{ number_format((float) $cashSalesTotal, 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Credit Sales</span>
                                            <strong>{{ number_format((float) $creditSalesTotal, 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-0">
                                            <span>Partial Sales</span>
                                            <strong>{{ number_format((float) $partialSalesTotal, 2) }}</strong>
                                        </div>
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between mb-0">
                                            <span>Total Sales</span>
                                            <strong>{{ number_format((float) $cashSalesTotal + (float) $creditSalesTotal + (float) $partialSalesTotal, 2) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <div class="card border h-100 mb-0">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3">Cash Received</h5>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>From Full Sales</span>
                                            <strong>{{ number_format((float) $cashCollectedFromFullSales, 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-0">
                                            <span>From Partial Sales</span>
                                            <strong>{{ number_format((float) $cashCollectedFromPartialSales, 2) }}</strong>
                                        </div>
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between mb-0">
                                            <span>Total Cash Collections</span>
                                            <strong>{{ number_format((float) $cashCollectedFromFullSales + (float) $cashCollectedFromPartialSales, 2) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-12">
                                <div class="card border h-100 mb-0">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3">Receivables</h5>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>From Credit Sales</span>
                                            <strong>{{ number_format((float) $receivablesFromCreditSales, 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-0">
                                            <span>From Partial Sales</span>
                                            <strong>{{ number_format((float) $receivablesFromPartialSales, 2) }}</strong>
                                        </div>
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between mb-0">
                                            <span>Total Receivables</span>
                                            <strong>{{ number_format((float) $receivablesFromCreditSales + (float) $receivablesFromPartialSales, 2) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-lg-4 col-md-6">
                                <div class="card border h-100 mb-0">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3">Purchases Breakdown</h5>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Cash Purchases</span>
                                            <strong>{{ number_format((float) $cashPaidToFullPurchases, 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Credit Purchases</span>
                                            <strong>{{ number_format((float) $payablesFromCreditPurchases, 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-0">
                                            <span>Partial Purchases</span>
                                            <strong>{{ number_format((float) $payablesFromPartialPurchases, 2) }}</strong>
                                        </div>
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between mb-0">
                                            <span>Total Payables</span>
                                            <strong>{{ number_format((float) $payablesFromCreditPurchases + (float) $payablesFromPartialPurchases, 2) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <div class="card border h-100 mb-0">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3">Cash Paid</h5>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>For Full Purchases</span>
                                            <strong>{{ number_format((float) $cashPaidToFullPurchases, 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-0">
                                            <span>For Partial Purchases</span>
                                            <strong>{{ number_format((float) $cashPaidToPartialPurchases, 2) }}</strong>
                                        </div>
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between mb-0">
                                            <span>Total Cash Paid</span>
                                            <strong>{{ number_format((float) $cashPaidToFullPurchases + (float) $cashPaidToPartialPurchases, 2) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-12">
                                <div class="card border h-100 mb-0">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3">Payables</h5>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>From Credit Purchases</span>
                                            <strong>{{ number_format((float) $payablesFromCreditPurchases, 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-0">
                                            <span>From Partial Purchases</span>
                                            <strong>{{ number_format((float) $payablesFromPartialPurchases, 2) }}</strong>
                                        </div>
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between mb-0">
                                            <span>Total Payables</span>
                                            <strong>{{ number_format((float) $payablesFromCreditPurchases + (float) $payablesFromPartialPurchases, 2) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-light border mt-3 mb-0">
                            <strong>Formula:</strong> Net Profit = Gross Profit - All Expenses - Cash Shortage Loss + Cash Overage Gain - Stock Shortage Loss + Stock Overage Gain
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
