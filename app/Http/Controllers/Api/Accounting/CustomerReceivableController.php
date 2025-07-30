<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\CustomerReceivable;
use App\Models\CurrencyRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomerReceivableController extends Controller
{
    /**
     * Display a listing of customer receivables.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|exists:Customer,customer_id',
            'status' => 'nullable|string',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'currency_code' => 'nullable|string|size:3',
            'display_currency' => 'nullable|string|size:3',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = CustomerReceivable::with(['customer', 'salesInvoice']);
        
        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by currency
        if ($request->filled('currency_code')) {
            $query->where('currency_code', $request->currency_code);
        }
        
        // Filter by due date range
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('due_date', [$request->from_date, $request->to_date]);
        }
        
        $receivables = $query->orderBy('due_date')
            ->paginate($request->input('per_page', 15));

        // Convert amounts if display currency is specified
        $displayCurrency = $request->input('display_currency');
        if ($displayCurrency) {
            $receivables->getCollection()->transform(function ($receivable) use ($displayCurrency) {
                $amounts = $receivable->getAmountsInCurrency($displayCurrency);
                $receivable->display_currency = $displayCurrency;
                $receivable->display_amount = $amounts['amount'];
                $receivable->display_paid_amount = $amounts['paid_amount'];
                $receivable->display_balance = $amounts['balance'];
                return $receivable;
            });
        }
        
        return response()->json($receivables, 200);
    }

    /**
     * Display the statement for a specific receivable.
     *
     * @param int $id
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function statement($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'display_currency' => 'nullable|string|size:3'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $receivable = CustomerReceivable::with(['salesInvoice', 'receivablePayments'])
            ->findOrFail($id);

        // Validate customer_id
        if (empty($receivable->customer_id) || !is_numeric($receivable->customer_id)) {
            return response()->json([
                'error' => 'Invalid or missing customer_id for this receivable.'
            ], 400);
        }

        // Load customer relationship separately with validation
        $customer = $receivable->customer()->first();
        if (!$customer) {
            return response()->json([
                'error' => 'Customer not found for this receivable.'
            ], 404);
        }

        $displayCurrency = $request->input('display_currency', $receivable->currency_code);
        
        // Convert receivable amounts if needed
        if ($displayCurrency !== $receivable->currency_code) {
            $amounts = $receivable->getAmountsInCurrency($displayCurrency);
            $receivable->display_currency = $displayCurrency;
            $receivable->display_amount = $amounts['amount'];
            $receivable->display_paid_amount = $amounts['paid_amount'];
            $receivable->display_balance = $amounts['balance'];
        }

        // Convert payment amounts if needed
        $payments = $receivable->receivablePayments()->orderBy('payment_date')->get();
        if ($displayCurrency !== $receivable->currency_code) {
            $payments->transform(function ($payment) use ($displayCurrency) {
                $payment->display_currency = $displayCurrency;
                $payment->display_amount = $payment->getAmountInCurrency($displayCurrency);
                return $payment;
            });
        }

        // Prepare statement data
        $statement = [
            'receivable' => $receivable,
            'customer' => $customer,
            'invoice' => $receivable->salesInvoice,
            'payments' => $payments,
            'display_currency' => $displayCurrency,
            'original_currency' => $receivable->currency_code,
        ];

        return response()->json(['data' => $statement], 200);
    }

    /**
     * Store a newly created customer receivable in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:Customer,customer_id',
            'invoice_id' => 'required|exists:SalesInvoice,invoice_id',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'status' => 'required|string|max:50',
            'currency_code' => 'required|string|size:3',
            'exchange_rate' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Check if a receivable already exists for this invoice
        $exists = CustomerReceivable::where('invoice_id', $request->invoice_id)->exists();
        if ($exists) {
            return response()->json([
                'message' => 'A receivable already exists for this invoice'
            ], 422);
        }

        $baseCurrency = config('app.base_currency', 'USD');
        $exchangeRate = $request->exchange_rate;

        // Get exchange rate if not provided and currency is not base currency
        if (!$exchangeRate && $request->currency_code !== $baseCurrency) {
            $rateRecord = CurrencyRate::getCurrentRate($request->currency_code, $baseCurrency);
            if ($rateRecord) {
                $exchangeRate = $rateRecord;
            } else {
                return response()->json([
                    'message' => 'Exchange rate not provided and no current rate found for ' . $request->currency_code
                ], 422);
            }
        }

        // Set default exchange rate for base currency
        if ($request->currency_code === $baseCurrency) {
            $exchangeRate = 1;
        }

        // Calculate base currency amounts
        $baseCurrencyAmount = $request->amount * $exchangeRate;

        $receivable = CustomerReceivable::create([
            'customer_id' => $request->customer_id,
            'invoice_id' => $request->invoice_id,
            'amount' => $request->amount,
            'due_date' => $request->due_date,
            'paid_amount' => 0,
            'balance' => $request->amount,
            'status' => $request->status,
            'currency_code' => $request->currency_code,
            'exchange_rate' => $exchangeRate,
            'base_currency' => $baseCurrency,
            'base_currency_amount' => $baseCurrencyAmount,
            'base_currency_balance' => $baseCurrencyAmount
        ]);

        return response()->json([
            'data' => $receivable, 
            'message' => 'Customer receivable created successfully'
        ], 201);
    }

    /**
     * Display the specified customer receivable.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'display_currency' => 'nullable|string|size:3'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $receivable = CustomerReceivable::with([
            'customer', 
            'salesInvoice',
            'receivablePayments'
        ])->findOrFail($id);

        $displayCurrency = $request->input('display_currency');
        
        // Convert amounts if display currency is specified and different
        if ($displayCurrency && $displayCurrency !== $receivable->currency_code) {
            $amounts = $receivable->getAmountsInCurrency($displayCurrency);
            $receivable->display_currency = $displayCurrency;
            $receivable->display_amount = $amounts['amount'];
            $receivable->display_paid_amount = $amounts['paid_amount'];
            $receivable->display_balance = $amounts['balance'];
            
            // Convert payment amounts too
            $receivable->receivablePayments->transform(function ($payment) use ($displayCurrency) {
                $payment->display_currency = $displayCurrency;
                $payment->display_amount = $payment->getAmountInCurrency($displayCurrency);
                return $payment;
            });
        }
        
        return response()->json(['data' => $receivable], 200);
    }

    /**
     * Update the specified customer receivable in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $receivable = CustomerReceivable::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'due_date' => 'date',
            'status' => 'string|max:50',
            'exchange_rate' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $updateData = $request->only(['due_date', 'status']);
        
        // Update exchange rate and recalculate base currency amounts if provided
        if ($request->filled('exchange_rate') && $receivable->currency_code !== $receivable->base_currency) {
            $updateData['exchange_rate'] = $request->exchange_rate;
            $updateData['base_currency_amount'] = $receivable->amount * $request->exchange_rate;
            
            // Recalculate base currency balance
            $paidInBaseCurrency = $updateData['base_currency_amount'] - $receivable->balance * $request->exchange_rate;
            $updateData['base_currency_balance'] = $updateData['base_currency_amount'] - $paidInBaseCurrency;
        }
        
        $receivable->update($updateData);

        return response()->json([
            'data' => $receivable, 
            'message' => 'Customer receivable updated successfully'
        ], 200);
    }

    /**
     * Remove the specified customer receivable from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $receivable = CustomerReceivable::findOrFail($id);
        
        // Check if there are payments
        if ($receivable->receivablePayments()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete receivable with associated payments'
            ], 422);
        }
        
        $receivable->delete();

        return response()->json(['message' => 'Customer receivable deleted successfully'], 200);
    }
    
    /**
     * Generate aging report for customer receivables.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function aging(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'display_currency' => 'nullable|string|size:3',
            'base_currency_only' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $displayCurrency = $request->input('display_currency', config('app.base_currency', 'USD'));
        $baseCurrencyOnly = $request->input('base_currency_only', false);

        // If using base currency only, use optimized query
        if ($baseCurrencyOnly || $displayCurrency === config('app.base_currency', 'USD')) {
            $aging = DB::table('CustomerReceivable')
                ->join('Customer', 'CustomerReceivable.customer_id', '=', 'Customer.customer_id')
                ->select(
                    'CustomerReceivable.customer_id',
                    'Customer.name as customer_name',
                    DB::raw('SUM(CASE WHEN (current_date - due_date) <= 0 THEN base_currency_balance ELSE 0 END) as current_amount'),
                    DB::raw('SUM(CASE WHEN (current_date - due_date) BETWEEN 1 AND 30 THEN base_currency_balance ELSE 0 END) as days_1_30'),
                    DB::raw('SUM(CASE WHEN (current_date - due_date) BETWEEN 31 AND 60 THEN base_currency_balance ELSE 0 END) as days_31_60'),
                    DB::raw('SUM(CASE WHEN (current_date - due_date) BETWEEN 61 AND 90 THEN base_currency_balance ELSE 0 END) as days_61_90'),
                    DB::raw('SUM(CASE WHEN (current_date - due_date) > 90 THEN base_currency_balance ELSE 0 END) as days_over_90'),
                    DB::raw('SUM(base_currency_balance) as total_balance')
                )
                ->where('CustomerReceivable.status', '!=', 'Paid')
                ->groupBy('CustomerReceivable.customer_id', 'Customer.name')
                ->orderBy('Customer.name')
                ->get();
        } else {
            // For other currencies, we need to convert each receivable
            $receivables = CustomerReceivable::with('customer')
                ->where('status', '!=', 'Paid')
                ->get();

            $agingData = [];
            
            foreach ($receivables as $receivable) {
                $customerId = $receivable->customer_id;
                $customerName = $receivable->customer->name;
                
                // Convert balance to display currency
                $amounts = $receivable->getAmountsInCurrency($displayCurrency);
                $balance = $amounts['balance'];
                
                // Calculate days overdue
                $daysOverdue = now()->diffInDays($receivable->due_date, false);
                
                // Initialize customer data if not exists
                if (!isset($agingData[$customerId])) {
                    $agingData[$customerId] = [
                        'customer_id' => $customerId,
                        'customer_name' => $customerName,
                        'current_amount' => 0,
                        'days_1_30' => 0,
                        'days_31_60' => 0,
                        'days_61_90' => 0,
                        'days_over_90' => 0,
                        'total_balance' => 0
                    ];
                }
                
                // Add to appropriate aging bucket
                if ($daysOverdue <= 0) {
                    $agingData[$customerId]['current_amount'] += $balance;
                } elseif ($daysOverdue <= 30) {
                    $agingData[$customerId]['days_1_30'] += $balance;
                } elseif ($daysOverdue <= 60) {
                    $agingData[$customerId]['days_31_60'] += $balance;
                } elseif ($daysOverdue <= 90) {
                    $agingData[$customerId]['days_61_90'] += $balance;
                } else {
                    $agingData[$customerId]['days_over_90'] += $balance;
                }
                
                $agingData[$customerId]['total_balance'] += $balance;
            }
            
            $aging = collect(array_values($agingData))->sortBy('customer_name');
        }
        
        $totals = [
            'current_amount' => $aging->sum('current_amount'),
            'days_1_30' => $aging->sum('days_1_30'),
            'days_31_60' => $aging->sum('days_31_60'),
            'days_61_90' => $aging->sum('days_61_90'),
            'days_over_90' => $aging->sum('days_over_90'),
            'total_balance' => $aging->sum('total_balance')
        ];
        
        return response()->json([
            'data' => $aging,
            'totals' => $totals,
            'display_currency' => $displayCurrency,
            'report_date' => now()->toDateString()
        ], 200);
    }

    /**
     * Get customer transactions including receivables, invoices, and payments.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function customerTransactions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:Customer,customer_id',
            'currency_code' => 'string|nullable',
            'display_currency' => 'string|nullable',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $customerId = $request->customer_id;
        $currencyCode = $request->currency_code; // Filter by specific currency
        $displayCurrency = $request->display_currency; // Convert all amounts to this currency

        // Build base query for receivables
        $receivablesQuery = CustomerReceivable::with(['salesInvoice'])
            ->where('customer_id', $customerId);
            
        if ($currencyCode) {
            $receivablesQuery->where('currency_code', $currencyCode);
        }

        // Fetch receivables
        $receivables = $receivablesQuery->get()
            ->map(function ($receivable) use ($displayCurrency) {
                $amounts = $displayCurrency ? $receivable->getAmountsInCurrency($displayCurrency) : [
                    'amount' => $receivable->amount,
                    'paid_amount' => $receivable->paid_amount,
                    'balance' => $receivable->balance,
                ];
                return [
                    'type' => 'receivable',
                    'id' => $receivable->receivable_id,
                    'invoice_id' => $receivable->invoice_id,
                    'invoice_number' => $receivable->salesInvoice->invoice_number ?? null,
                    'due_date' => $receivable->due_date,
                    'amount' => $amounts['amount'],
                    'paid_amount' => $amounts['paid_amount'],
                    'balance' => $amounts['balance'],
                    'status' => $receivable->status,
                    'currency_code' => $receivable->currency_code,
                    'display_currency' => $displayCurrency,
                ];
            });

        // Build query for payments
        $paymentsQuery = \App\Models\Accounting\ReceivablePayment::whereHas('customerReceivable', function ($query) use ($customerId, $currencyCode) {
            $query->where('customer_id', $customerId);
            if ($currencyCode) {
                $query->where('currency_code', $currencyCode);
            }
        });

        // Add date filter for payments
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $paymentsQuery->whereBetween('payment_date', [$request->from_date, $request->to_date]);
        }

        // Fetch payments
        $payments = $paymentsQuery->get()
            ->map(function ($payment) use ($displayCurrency) {
                $amount = $displayCurrency ? $payment->getAmountInCurrency($displayCurrency) : $payment->amount;
                return [
                    'type' => 'payment',
                    'id' => $payment->payment_id,
                    'receivable_id' => $payment->receivable_id,
                    'payment_date' => $payment->payment_date,
                    'amount' => $amount,
                    'payment_method' => $payment->payment_method,
                    'reference_number' => $payment->reference_number,
                    'payment_currency' => $payment->payment_currency ?? null,
                    'display_currency' => $displayCurrency,
                ];
            });

        // Build query for invoices
        $invoicesQuery = \App\Models\Sales\SalesInvoice::where('customer_id', $customerId);
        
        if ($currencyCode) {
            $invoicesQuery->where('currency_code', $currencyCode);
        }

        // Add date filter for invoices
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $invoicesQuery->whereBetween('invoice_date', [$request->from_date, $request->to_date]);
        }

        // Fetch invoices
        $invoices = $invoicesQuery->get()
            ->map(function ($invoice) use ($displayCurrency) {
                $amounts = $displayCurrency ? $invoice->getAmountsInCurrency($displayCurrency) : [
                    'total_amount' => $invoice->total_amount,
                    'tax_amount' => $invoice->tax_amount,
                ];
                return [
                    'type' => 'invoice',
                    'id' => $invoice->invoice_id,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_date' => $invoice->invoice_date,
                    'total_amount' => $amounts['total_amount'],
                    'tax_amount' => $amounts['tax_amount'],
                    'status' => $invoice->status,
                    'currency_code' => $invoice->currency_code ?? null,
                    'display_currency' => $displayCurrency,
                ];
            });

        // Combine all transactions and sort by date descending
        $transactions = $receivables->concat($payments)->concat($invoices)->sortByDesc(function ($item) {
            if ($item['type'] === 'payment') {
                return $item['payment_date'];
            } elseif ($item['type'] === 'invoice') {
                return $item['invoice_date'];
            } elseif ($item['type'] === 'receivable') {
                return $item['due_date'];
            }
            return null;
        })->values();

        return response()->json([
            'data' => $transactions,
            'filters' => [
                'customer_id' => $customerId,
                'currency_code' => $currencyCode,
                'display_currency' => $displayCurrency,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
            ]
        ], 200);
    }

    /**
     * Get currency summary for customer receivables.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function currencySummary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|exists:Customer,customer_id',
            'display_currency' => 'nullable|string|size:3'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = CustomerReceivable::where('status', '!=', 'Paid');
        
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $receivables = $query->get();
        $displayCurrency = $request->input('display_currency');
        
        // Group by currency
        $currencySummary = [];
        
        foreach ($receivables as $receivable) {
            $currency = $receivable->currency_code;
            
            if (!isset($currencySummary[$currency])) {
                $currencySummary[$currency] = [
                    'currency_code' => $currency,
                    'count' => 0,
                    'total_amount' => 0,
                    'total_balance' => 0,
                ];
            }
            
            $currencySummary[$currency]['count']++;
            $currencySummary[$currency]['total_amount'] += $receivable->amount;
            $currencySummary[$currency]['total_balance'] += $receivable->balance;
            
            // Add converted amounts if display currency is specified
            if ($displayCurrency && $displayCurrency !== $currency) {
                $amounts = $receivable->getAmountsInCurrency($displayCurrency);
                
                if (!isset($currencySummary[$currency]['display_currency'])) {
                    $currencySummary[$currency]['display_currency'] = $displayCurrency;
                    $currencySummary[$currency]['display_total_amount'] = 0;
                    $currencySummary[$currency]['display_total_balance'] = 0;
                }
                
                $currencySummary[$currency]['display_total_amount'] += $amounts['amount'];
                $currencySummary[$currency]['display_total_balance'] += $amounts['balance'];
            }
        }

        return response()->json([
            'data' => array_values($currencySummary),
            'display_currency' => $displayCurrency,
            'total_currencies' => count($currencySummary)
        ], 200);
    }
}