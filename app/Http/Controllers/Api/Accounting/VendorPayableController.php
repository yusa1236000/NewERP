<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\VendorPayable;
use App\Models\CurrencyRate;
use App\Models\SystemCurrency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VendorPayableController extends Controller
{
    /**
     * Display a listing of vendor payables with multi-currency support.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = VendorPayable::with(['vendor', 'vendorInvoice', 'payablePayments']);
        
        // Filter by vendor
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $statuses = explode(',', $request->status);
            if (count($statuses) > 1) {
                $query->whereIn('status', $statuses);
            } else {
                $query->where('status', $request->status);
            }
        }
        
        // Filter by currency
        if ($request->filled('currency_code')) {
            $query->where('currency_code', $request->currency_code);
        }
        
        // Filter by due date range
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('due_date', [$request->from_date, $request->to_date]);
        }
        
        $payables = $query->orderBy('due_date')
            ->paginate($request->input('per_page', 15));
        
        // Add currency conversion if requested
        if ($request->filled('convert_to_currency')) {
            $targetCurrency = $request->convert_to_currency;
            $conversionDate = $request->input('conversion_date', now()->toDateString());
            
            $payables->getCollection()->transform(function ($payable) use ($targetCurrency, $conversionDate) {
                $payable->converted_amounts = $this->convertPayableAmounts($payable, $targetCurrency, $conversionDate);
                return $payable;
            });
        }
        
        return response()->json($payables, 200);
    }

    /**
     * Store a newly created vendor payable with multi-currency support.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vendor_id' => 'required|exists:vendors,vendor_id',
            'invoice_id' => 'required|exists:VendorInvoice,invoice_id',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'status' => 'required|string|max:50',
            'currency_code' => 'required|string|size:3|exists:system_currencies,code',
            'exchange_rate' => 'nullable|numeric|min:0',
            'base_currency' => 'nullable|string|size:3|exists:system_currencies,code'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Check if a payable already exists for this invoice
        $exists = VendorPayable::where('invoice_id', $request->invoice_id)->exists();
        if ($exists) {
            return response()->json([
                'message' => 'A payable already exists for this invoice'
            ], 422);
        }

        // Get base currency from system settings
        $baseCurrency = $request->input('base_currency', 'USD');
        
        // Get or calculate exchange rate
        $exchangeRate = $this->getExchangeRate(
            $request->currency_code, 
            $baseCurrency, 
            $request->due_date,
            $request->exchange_rate
        );
        
        if (!$exchangeRate) {
            return response()->json([
                'message' => "Exchange rate not found for {$request->currency_code} to {$baseCurrency}"
            ], 422);
        }

        // Calculate base currency amounts
        $baseCurrencyAmount = $request->amount * $exchangeRate;

        $payable = VendorPayable::create([
            'vendor_id' => $request->vendor_id,
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

        $payable->load(['vendor', 'vendorInvoice']);

        return response()->json([
            'data' => $payable, 
            'message' => 'Vendor payable created successfully'
        ], 201);
    }

    /**
     * Display the specified vendor payable with multi-currency details.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $payable = VendorPayable::with([
            'vendor', 
            'vendorInvoice',
            'payablePayments'
        ])->findOrFail($id);

        // Recalculate exchange rate to ensure it is current
        $currentExchangeRate = $this->getExchangeRate(
            $payable->currency_code,
            $payable->base_currency,
            $payable->due_date,
            null
        );

        // Update exchange rate and base currency amounts
        $payable->exchange_rate = $currentExchangeRate;
        $payable->base_currency_amount = $payable->amount * $currentExchangeRate;
        $payable->base_currency_balance = $payable->balance * $currentExchangeRate;

        // Add currency conversion summary
        $payable->currency_summary = [
            'original_currency' => $payable->currency_code,
            'base_currency' => $payable->base_currency,
            'exchange_rate' => $payable->exchange_rate,
            'amounts' => [
                'original' => [
                    'amount' => $payable->amount,
                    'balance' => $payable->balance,
                    'paid_amount' => $payable->paid_amount,
                    'currency' => $payable->currency_code
                ],
                'base_currency' => [
                    'amount' => $payable->base_currency_amount,
                    'balance' => $payable->base_currency_balance,
                    'paid_amount' => $payable->base_currency_amount - $payable->base_currency_balance,
                    'currency' => $payable->base_currency
                ]
            ]
        ];
        
        return response()->json(['data' => $payable], 200);
    }

    /**
     * Update the specified vendor payable with multi-currency support.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $payable = VendorPayable::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'due_date' => 'date',
            'status' => 'string|max:50',
            'exchange_rate' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $updateData = $request->only(['due_date', 'status']);
        
        // Update exchange rate if provided and recalculate base currency amounts
        if ($request->filled('exchange_rate')) {
            $updateData['exchange_rate'] = $request->exchange_rate;
            $updateData['base_currency_amount'] = $payable->amount * $request->exchange_rate;
            $updateData['base_currency_balance'] = $payable->balance * $request->exchange_rate;
        }
        
        $payable->update($updateData);

        return response()->json([
            'data' => $payable, 
            'message' => 'Vendor payable updated successfully'
        ], 200);
    }

    /**
     * Remove the specified vendor payable from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $payable = VendorPayable::findOrFail($id);
        
        // Check if there are payments
        if ($payable->payablePayments()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete payable with associated payments'
            ], 422);
        }
        
        $payable->delete();

        return response()->json(['message' => 'Vendor payable deleted successfully'], 200);
    }
    
    /**
     * Generate multi-currency aging report for vendor payables.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function aging(Request $request)
    {
        $reportCurrency = $request->input('currency', 'USD');
        $reportDate = $request->input('as_of_date', now()->toDateString());
        
        // Get payables with vendor information
        $payables = VendorPayable::with('vendor')
            ->where('status', '!=', 'Paid')
            ->get();
        
        $aging = collect();
        
        // Group by vendor and calculate aging buckets
        $vendorGroups = $payables->groupBy('vendor_id');
        
        foreach ($vendorGroups as $vendorId => $vendorPayables) {
            $vendor = $vendorPayables->first()->vendor;
            
            $vendorAging = [
                'vendor_id' => $vendorId,
                'vendor_name' => $vendor->name,
                'vendor_currency' => $vendor->preferred_currency ?? 'USD',
                'current_amount' => 0,
                'days_1_30' => 0,
                'days_31_60' => 0,
                'days_61_90' => 0,
                'days_over_90' => 0,
                'total_balance' => 0,
                'currency_breakdown' => []
            ];
            
            foreach ($vendorPayables as $payable) {
                $daysOverdue = now()->parse($reportDate)->diffInDays($payable->due_date, false);
                
                // Convert amount to report currency
                $convertedBalance = $this->convertAmount(
                    $payable->balance,
                    $payable->currency_code,
                    $reportCurrency,
                    $reportDate
                );
                
                // Add to currency breakdown
                if (!isset($vendorAging['currency_breakdown'][$payable->currency_code])) {
                    $vendorAging['currency_breakdown'][$payable->currency_code] = [
                        'original_amount' => 0,
                        'converted_amount' => 0,
                        'exchange_rate' => $this->getExchangeRate($payable->currency_code, $reportCurrency, $reportDate) ?? 1
                    ];
                }
                
                $vendorAging['currency_breakdown'][$payable->currency_code]['original_amount'] += $payable->balance;
                $vendorAging['currency_breakdown'][$payable->currency_code]['converted_amount'] += $convertedBalance;
                
                // Categorize by aging bucket
                if ($daysOverdue <= 0) {
                    $vendorAging['current_amount'] += $convertedBalance;
                } elseif ($daysOverdue <= 30) {
                    $vendorAging['days_1_30'] += $convertedBalance;
                } elseif ($daysOverdue <= 60) {
                    $vendorAging['days_31_60'] += $convertedBalance;
                } elseif ($daysOverdue <= 90) {
                    $vendorAging['days_61_90'] += $convertedBalance;
                } else {
                    $vendorAging['days_over_90'] += $convertedBalance;
                }
                
                $vendorAging['total_balance'] += $convertedBalance;
            }
            
            $aging->push($vendorAging);
        }
        
        // Calculate totals
        $totals = [
            'current_amount' => $aging->sum('current_amount'),
            'days_1_30' => $aging->sum('days_1_30'),
            'days_31_60' => $aging->sum('days_31_60'),
            'days_61_90' => $aging->sum('days_61_90'),
            'days_over_90' => $aging->sum('days_over_90'),
            'total_balance' => $aging->sum('total_balance'),
            'report_currency' => $reportCurrency,
            'report_date' => $reportDate
        ];
        
        return response()->json([
            'data' => $aging->sortBy('vendor_name')->values(),
            'totals' => $totals,
            'metadata' => [
                'report_currency' => $reportCurrency,
                'report_date' => $reportDate,
                'total_vendors' => $aging->count(),
                'available_currencies' => $payables->pluck('currency_code')->unique()->sort()->values()
            ]
        ], 200);
    }
    
    /**
     * Get currency summary for all payables.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function currencySummary(Request $request)
    {
        $targetCurrency = $request->input('target_currency', 'USD');
        $asOfDate = $request->input('as_of_date', now()->toDateString());
        
        $summary = VendorPayable::select('currency_code')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(amount) as total_amount')
            ->selectRaw('SUM(balance) as total_balance')
            ->selectRaw('SUM(paid_amount) as total_paid')
            ->where('status', '!=', 'Paid')
            ->groupBy('currency_code')
            ->get()
            ->map(function ($item) use ($targetCurrency, $asOfDate) {
                $exchangeRate = $this->getExchangeRate($item->currency_code, $targetCurrency, $asOfDate);
                
                return [
                    'currency_code' => $item->currency_code,
                    'count' => $item->count,
                    'amounts' => [
                        'original' => [
                            'total_amount' => $item->total_amount,
                            'total_balance' => $item->total_balance,
                            'total_paid' => $item->total_paid,
                        ],
                        'converted' => [
                            'total_amount' => $item->total_amount * $exchangeRate,
                            'total_balance' => $item->total_balance * $exchangeRate,
                            'total_paid' => $item->total_paid * $exchangeRate,
                            'exchange_rate' => $exchangeRate,
                            'target_currency' => $targetCurrency
                        ]
                    ]
                ];
            });
        
        $grandTotal = [
            'target_currency' => $targetCurrency,
            'total_amount' => $summary->sum('amounts.converted.total_amount'),
            'total_balance' => $summary->sum('amounts.converted.total_balance'),
            'total_paid' => $summary->sum('amounts.converted.total_paid'),
            'as_of_date' => $asOfDate
        ];
        
        return response()->json([
            'data' => $summary,
            'grand_total' => $grandTotal
        ], 200);
    }
    
    /**
     * Convert payable amounts to target currency.
     *
     * @param VendorPayable $payable
     * @param string $targetCurrency
     * @param string $date
     * @return array
     */
    private function convertPayableAmounts($payable, $targetCurrency, $date)
    {
        $exchangeRate = $this->getExchangeRate($payable->currency_code, $targetCurrency, $date);
        
        return [
            'target_currency' => $targetCurrency,
            'exchange_rate' => $exchangeRate,
            'conversion_date' => $date,
            'converted_amount' => $payable->amount * $exchangeRate,
            'converted_balance' => $payable->balance * $exchangeRate,
            'converted_paid_amount' => $payable->paid_amount * $exchangeRate
        ];
    }
    
    /**
     * Convert amount from one currency to another.
     *
     * @param float $amount
     * @param string $fromCurrency
     * @param string $toCurrency
     * @param string $date
     * @return float
     */
    private function convertAmount($amount, $fromCurrency, $toCurrency, $date)
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }
        
        $exchangeRate = $this->getExchangeRate($fromCurrency, $toCurrency, $date);
        return $amount * $exchangeRate;
    }
    
    /**
     * Get exchange rate between currencies.
     *
     * @param string $fromCurrency
     * @param string $toCurrency
     * @param string $date
     * @param float|null $manualRate
     * @return float|null
     */
    private function getExchangeRate($fromCurrency, $toCurrency, $date, $manualRate = null)
    {
        // Return manual rate if provided
        if ($manualRate !== null) {
            return $manualRate;
        }
        
        // Same currency
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }
        
        // Use CurrencyRate model's getCurrentRate method
        $rate = CurrencyRate::getCurrentRate($fromCurrency, $toCurrency, $date);
        
        if ($rate !== null) {
            return $rate;
        }
        
        // Log warning for missing exchange rate
        \Log::warning("Exchange rate not found for {$fromCurrency} to {$toCurrency} on {$date}. Falling back to 1.0");
        
        // Throw exception or return null to notify caller
        // For now, return null to indicate missing rate
        return null;
    }
}