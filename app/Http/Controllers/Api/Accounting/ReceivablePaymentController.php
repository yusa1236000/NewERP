<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\CustomerReceivable;
use App\Models\Accounting\ReceivablePayment;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\JournalEntryLine;
use App\Models\Accounting\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReceivablePaymentController extends Controller
{
    /**
     * Display a listing of receivable payments.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = ReceivablePayment::with('customerReceivable.customer');
        
        // Filter by receivable
        if ($request->has('receivable_id')) {
            $query->where('receivable_id', $request->receivable_id);
        }
        
        // Filter by payment date range
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('payment_date', [$request->from_date, $request->to_date]);
        }
        
        // Filter by payment currency
        if ($request->has('payment_currency')) {
            $query->where('payment_currency', $request->payment_currency);
        }
        
        // Filter by customer
        if ($request->has('customer_id')) {
            $query->whereHas('customerReceivable', function($q) use ($request) {
                $q->where('customer_id', $request->customer_id);
            });
        }
        
        $payments = $query->orderBy('payment_date', 'desc')
            ->paginate($request->input('per_page', 15));
        
        return response()->json($payments, 200);
    }

    /**
     * Store a newly created receivable payment in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receivable_id' => 'required|exists:CustomerReceivable,receivable_id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_currency' => 'required|string|size:3',
            'exchange_rate' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string|max:50',
            'reference_number' => 'required|string|max:50',
            'create_journal_entry' => 'boolean',
            'cash_account_id' => 'required_if:create_journal_entry,true|exists:ChartOfAccount,account_id',
            'receivable_account_id' => 'required_if:create_journal_entry,true|exists:ChartOfAccount,account_id',
            'exchange_gain_loss_account_id' => 'nullable|exists:ChartOfAccount,account_id',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Get the receivable
        $receivable = CustomerReceivable::findOrFail($request->receivable_id);
        
        // Check if receivable is already paid
        if ($receivable->status === 'Paid') {
            return response()->json([
                'message' => 'This receivable has already been fully paid'
            ], 422);
        }
        
        try {
            DB::beginTransaction();
            
            // Get base currency
            $baseCurrency = config('app.base_currency', 'USD');
            $paymentCurrency = $request->payment_currency;
            $receivableCurrency = $receivable->currency_code ?? $baseCurrency;
            
            // Get exchange rate for payment currency to base currency
            $exchangeRateToBase = $this->getExchangeRate($paymentCurrency, $baseCurrency, $request->payment_date);
            if ($request->has('exchange_rate') && $request->exchange_rate > 0) {
                $exchangeRateToBase = $request->exchange_rate;
            }
            
            // Calculate receivable currency amount (amount being applied to the receivable)
            $receivableAmount = $request->amount;
            if ($paymentCurrency !== $receivableCurrency) {
                // Convert payment currency to receivable currency
                if ($receivableCurrency === $baseCurrency) {
                    $receivableAmount = $request->amount * $exchangeRateToBase;
                } else {
                    // Convert via base currency: payment -> base -> receivable
                    $baseAmount = $request->amount * $exchangeRateToBase;
                    $receivableToBaseRate = $this->getExchangeRate($receivableCurrency, $baseCurrency, $request->payment_date);
                    $receivableAmount = $baseAmount / $receivableToBaseRate;
                }
            }
            
            // Calculate exchange difference
            $exchangeDifference = 0;
            if ($paymentCurrency !== $receivableCurrency) {
                // Original receivable amount at current exchange rate
                $currentReceivableRate = $this->getExchangeRate($receivableCurrency, $baseCurrency, $request->payment_date);
                $expectedBaseAmount = $receivableAmount * $currentReceivableRate;
                $actualBaseAmount = $request->amount * $exchangeRateToBase;
                $exchangeDifference = $actualBaseAmount - $expectedBaseAmount;
            }
            
            // Check if payment amount doesn't exceed receivable balance
            if ($receivableAmount > $receivable->balance) {
                return response()->json([
                    'message' => 'Payment amount cannot exceed the remaining balance of ' . number_format($receivable->balance, 2) . ' ' . $receivableCurrency
                ], 422);
            }
            
            // Create payment record
            $payment = ReceivablePayment::create([
                'receivable_id' => $request->receivable_id,
                'payment_date' => $request->payment_date,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'payment_currency' => $paymentCurrency,
                'exchange_rate' => $exchangeRateToBase,
                'receivable_amount' => $receivableAmount,
                'exchange_difference' => $exchangeDifference
            ]);
            
            // Update receivable balance (in receivable currency)
            $receivable->paid_amount += $receivableAmount;
            $receivable->balance -= $receivableAmount;
            
            // Update status if fully paid (with small tolerance for rounding)
            if ($receivable->balance <= 0.01) {
                $receivable->status = 'Paid';
                $receivable->balance = 0; // Clean up any rounding issues
            }
            
            $receivable->save();
            
            // Create journal entry if requested
            if ($request->input('create_journal_entry', false)) {
                $this->createJournalEntry($payment, $receivable, $request);
            }
            
            DB::commit();
            
            // Load relationships for response
            $payment->load('customerReceivable.customer');
            
            return response()->json([
                'data' => $payment, 
                'message' => 'Receivable payment created successfully'
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create receivable payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified receivable payment.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $payment = ReceivablePayment::with([
            'customerReceivable.customer', 
            'customerReceivable.salesInvoice'
        ])->findOrFail($id);
        
        // Add currency conversion info
        $baseCurrency = config('app.base_currency', 'USD');
        $payment->base_currency_amount = $payment->amount * $payment->exchange_rate;
        $payment->conversion_info = [
            'base_currency' => $baseCurrency,
            'payment_currency' => $payment->payment_currency,
            'receivable_currency' => $payment->customerReceivable->currency_code ?? $baseCurrency,
            'exchange_rate_used' => $payment->exchange_rate,
            'has_exchange_difference' => abs($payment->exchange_difference) > 0.01
        ];
        
        return response()->json(['data' => $payment], 200);
    }

    /**
     * Remove the specified receivable payment from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $payment = ReceivablePayment::findOrFail($id);
        $receivable = CustomerReceivable::findOrFail($payment->receivable_id);
        
        try {
            DB::beginTransaction();
            
            // Update receivable (reverse the payment)
            $receivable->paid_amount -= $payment->receivable_amount;
            $receivable->balance += $payment->receivable_amount;
            
            // Update status
            if ($receivable->balance > 0) {
                $receivable->status = 'Open';
            }
            
            $receivable->save();
            
            // Find and delete any related journal entry
            $journalEntry = JournalEntry::where('reference_type', 'ReceivablePayment')
                ->where('reference_id', $payment->payment_id)
                ->first();
            
            if ($journalEntry) {
                // Delete journal entry lines first
                JournalEntryLine::where('journal_id', $journalEntry->journal_id)->delete();
                // Delete journal entry
                $journalEntry->delete();
            }
            
            // Delete payment
            $payment->delete();
            
            DB::commit();
            
            return response()->json(['message' => 'Receivable payment deleted successfully'], 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete receivable payment: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get currency exchange rates for a specific date.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getExchangeRates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'nullable|string|size:3'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $baseCurrency = config('app.base_currency', 'USD');
        $toCurrency = $request->to_currency ?? $baseCurrency;
        
        // If requesting same currency, rate is always 1
        if ($request->from_currency === $toCurrency) {
            return response()->json([
                'data' => [
                    'from_currency' => $request->from_currency,
                    'to_currency' => $toCurrency,
                    'date' => $request->date,
                    'rate' => 1
                ]
            ]);
        }
        
        $rate = $this->getExchangeRate($request->from_currency, $toCurrency, $request->date);
        
        if (!$rate) {
            return response()->json([
                'message' => 'No exchange rate found for ' . $request->from_currency . ' to ' . $toCurrency . ' on or before ' . $request->date
            ], 404);
        }
        
        return response()->json([
            'data' => [
                'from_currency' => $request->from_currency,
                'to_currency' => $toCurrency,
                'date' => $request->date,
                'rate' => $rate
            ]
        ]);
    }

    /**
     * Get payment summary by currency.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getCurrencySummary(Request $request)
    {
        $query = ReceivablePayment::with('customerReceivable');
        
        // Apply filters
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('payment_date', [$request->from_date, $request->to_date]);
        }
        
        if ($request->has('customer_id')) {
            $query->whereHas('customerReceivable', function($q) use ($request) {
                $q->where('customer_id', $request->customer_id);
            });
        }
        
        $payments = $query->get();
        
        // Group by currency
        $summary = $payments->groupBy('payment_currency')->map(function ($currencyPayments, $currency) {
            $baseCurrency = config('app.base_currency', 'USD');
            $totalAmount = $currencyPayments->sum('amount');
            $totalReceivableAmount = $currencyPayments->sum('receivable_amount');
            $totalExchangeDifference = $currencyPayments->sum('exchange_difference');
            $count = $currencyPayments->count();
            
            // Calculate base currency equivalent
            $baseCurrencyTotal = $currencyPayments->sum(function($payment) {
                return $payment->amount * $payment->exchange_rate;
            });
            
            return [
                'currency' => $currency,
                'count' => $count,
                'total_amount' => $totalAmount,
                'total_receivable_amount' => $totalReceivableAmount,
                'total_exchange_difference' => $totalExchangeDifference,
                'base_currency_total' => $baseCurrencyTotal,
                'base_currency' => $baseCurrency
            ];
        });
        
        return response()->json([
            'data' => $summary->values(),
            'summary_total' => [
                'total_currencies' => $summary->count(),
                'base_currency_grand_total' => $summary->sum('base_currency_total'),
                'base_currency' => config('app.base_currency', 'USD')
            ]
        ]);
    }
    
    /**
     * Helper method to get exchange rate.
     *
     * @param string $fromCurrency
     * @param string $toCurrency  
     * @param string $date
     * @return float
     */
    private function getExchangeRate($fromCurrency, $toCurrency, $date)
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }
        
        $rate = ExchangeRate::where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->where('rate_date', '<=', $date)
            ->orderBy('rate_date', 'desc')
            ->value('rate');
        
        if (!$rate) {
            // Try reverse rate
            $reverseRate = ExchangeRate::where('from_currency', $toCurrency)
                ->where('to_currency', $fromCurrency)
                ->where('rate_date', '<=', $date)
                ->orderBy('rate_date', 'desc')
                ->value('rate');
                
            if ($reverseRate && $reverseRate > 0) {
                return 1 / $reverseRate;
            }
        }
        
        return $rate ?? 1.0;
    }
    
    /**
     * Create journal entry for the payment.
     *
     * @param ReceivablePayment $payment
     * @param CustomerReceivable $receivable
     * @param Request $request
     * @return void
     */
    private function createJournalEntry($payment, $receivable, $request)
    {
        $baseCurrency = config('app.base_currency', 'USD');
        $baseAmount = $payment->amount * $payment->exchange_rate;
        
        // Create journal entry
        $journalEntry = JournalEntry::create([
            'journal_number' => 'RECPMT-' . date('YmdHis'),
            'entry_date' => $payment->payment_date,
            'reference_type' => 'ReceivablePayment',
            'reference_id' => $payment->payment_id,
            'description' => 'Payment from ' . $receivable->customer->name . ' - ' . $payment->reference_number,
            'period_id' => $this->getCurrentPeriodId(),
            'status' => 'Posted'
        ]);
        
        // Debit Cash/Bank Account
        JournalEntryLine::create([
            'journal_id' => $journalEntry->journal_id,
            'account_id' => $request->cash_account_id,
            'debit_amount' => $baseAmount,
            'credit_amount' => 0,
            'description' => 'Payment received from ' . $receivable->customer->name,
            'currency' => $payment->payment_currency,
            'foreign_amount' => $payment->payment_currency !== $baseCurrency ? $payment->amount : null
        ]);
        
        // Credit Accounts Receivable
        $receivableCurrency = $receivable->currency_code ?? $baseCurrency;
        $receivableBaseAmount = $payment->receivable_amount;
        if ($receivableCurrency !== $baseCurrency) {
            $receivableRate = $this->getExchangeRate($receivableCurrency, $baseCurrency, $payment->payment_date);
            $receivableBaseAmount = $payment->receivable_amount * $receivableRate;
        }
        
        JournalEntryLine::create([
            'journal_id' => $journalEntry->journal_id,
            'account_id' => $request->receivable_account_id,
            'debit_amount' => 0,
            'credit_amount' => $receivableBaseAmount,
            'description' => 'Payment applied to receivable',
            'currency' => $receivableCurrency,
            'foreign_amount' => $receivableCurrency !== $baseCurrency ? $payment->receivable_amount : null
        ]);
        
        // Record exchange gain/loss if applicable
        if (abs($payment->exchange_difference) > 0.01 && $request->has('exchange_gain_loss_account_id')) {
            if ($payment->exchange_difference > 0) {
                // Exchange gain (credit)
                JournalEntryLine::create([
                    'journal_id' => $journalEntry->journal_id,
                    'account_id' => $request->exchange_gain_loss_account_id,
                    'debit_amount' => 0,
                    'credit_amount' => abs($payment->exchange_difference),
                    'description' => 'Exchange gain on payment'
                ]);
            } else {
                // Exchange loss (debit)
                JournalEntryLine::create([
                    'journal_id' => $journalEntry->journal_id,
                    'account_id' => $request->exchange_gain_loss_account_id,
                    'debit_amount' => abs($payment->exchange_difference),
                    'credit_amount' => 0,
                    'description' => 'Exchange loss on payment'
                ]);
            }
        }
    }
    
    /**
     * Helper method to get the current accounting period ID.
     *
     * @return int
     */
    private function getCurrentPeriodId()
    {
        $currentPeriod = DB::table('AccountingPeriod')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where('status', 'Open')
            ->first();
        
        if (!$currentPeriod) {
            throw new \Exception('No active accounting period found for the current date');
        }
        
        return $currentPeriod->period_id;
    }
}