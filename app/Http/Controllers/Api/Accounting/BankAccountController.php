<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\BankAccount;
use App\Models\Accounting\ChartOfAccount;
use App\Models\SystemCurrency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BankAccountController extends Controller
{
    /**
     * Display a listing of bank accounts.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $bankAccounts = BankAccount::with([
            'chartOfAccount',
            'currency:code,name,symbol',
            'baseCurrency:code,name,symbol'
        ])
            ->orderBy('bank_name')
            ->orderBy('account_name')
            ->get();
        
        // Add formatted balance information
        $bankAccounts->each(function ($account) {
            $account->formatted_balance = number_format($account->current_balance, 2);
            $account->formatted_base_balance = number_format($account->base_currency_balance, 2);
            $account->currency_display = $account->currency ? $account->currency->symbol . ' ' . $account->currency->code : 'USD';
        });
        
        return response()->json(['data' => $bankAccounts], 200);
    }

    /**
     * Store a newly created bank account in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:100',
            'current_balance' => 'required|numeric',
            'currency_code' => 'required|string|size:3|exists:system_currencies,code',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'gl_account_id' => 'required|exists:ChartOfAccount,account_id'
        ], [
            'currency_code.required' => 'Currency code is required',
            'currency_code.size' => 'Currency code must be exactly 3 characters',
            'currency_code.exists' => 'The selected currency is not supported',
            'exchange_rate.min' => 'Exchange rate must be greater than 0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Verify that the GL account is of type Asset
        $glAccount = ChartOfAccount::findOrFail($request->gl_account_id);
        if ($glAccount->account_type !== 'Asset') {
            return response()->json([
                'message' => 'The selected GL account must be of type Asset'
            ], 422);
        }

        // Validate currency compatibility with GL account
        $currencyValidation = $this->validateCurrencyWithGLAccount($request->currency_code, $glAccount);
        if (!$currencyValidation['valid']) {
            return response()->json([
                'message' => $currencyValidation['message']
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Get or calculate exchange rate
            $exchangeRate = $this->getExchangeRate($request->currency_code, $request->exchange_rate);
            
            // Calculate base currency balance
            $baseCurrencyBalance = $request->current_balance * $exchangeRate;
            
            $bankAccount = BankAccount::create([
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'account_name' => $request->account_name,
                'current_balance' => $request->current_balance,
                'currency_code' => $request->currency_code,
                'exchange_rate' => $exchangeRate,
                'base_currency' => config('app.base_currency', 'USD'),
                'base_currency_balance' => $baseCurrencyBalance,
                'gl_account_id' => $request->gl_account_id
            ]);

            DB::commit();

            // Load relationships for response
            $bankAccount->load([
                'chartOfAccount',
                'currency:code,name,symbol',
                'baseCurrency:code,name,symbol'
            ]);

            return response()->json([
                'data' => $bankAccount, 
                'message' => 'Bank account created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create bank account: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified bank account.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $bankAccount = BankAccount::with([
            'chartOfAccount',
            'currency:code,name,symbol',
            'baseCurrency:code,name,symbol',
            'bankReconciliations' => function($query) {
                $query->orderBy('statement_date', 'desc')->limit(5);
            }
        ])->findOrFail($id);
        
        // Add balance information in both currencies
        $bankAccount->balance_info = [
            'current_balance' => [
                'amount' => $bankAccount->current_balance,
                'currency' => $bankAccount->currency_code,
                'formatted' => number_format($bankAccount->current_balance, 2) . ' ' . $bankAccount->currency_code
            ],
            'base_currency_balance' => [
                'amount' => $bankAccount->base_currency_balance,
                'currency' => $bankAccount->base_currency,
                'formatted' => number_format($bankAccount->base_currency_balance, 2) . ' ' . $bankAccount->base_currency
            ],
            'exchange_rate' => $bankAccount->exchange_rate,
            'last_updated' => $bankAccount->updated_at
        ];
        
        return response()->json(['data' => $bankAccount], 200);
    }

    /**
     * Update the specified bank account in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $bankAccount = BankAccount::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'bank_name' => 'string|max:100',
            'account_number' => 'string|max:50',
            'account_name' => 'string|max:100',
            'current_balance' => 'numeric',
            'currency_code' => 'string|size:3|exists:system_currencies,code',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'gl_account_id' => 'exists:ChartOfAccount,account_id'
        ], [
            'currency_code.size' => 'Currency code must be exactly 3 characters',
            'currency_code.exists' => 'The selected currency is not supported',
            'exchange_rate.min' => 'Exchange rate must be greater than 0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        DB::beginTransaction();
        try {
            // Verify that the GL account is of type Asset if provided
            if ($request->has('gl_account_id')) {
                $glAccount = ChartOfAccount::findOrFail($request->gl_account_id);
                if ($glAccount->account_type !== 'Asset') {
                    return response()->json([
                        'message' => 'The selected GL account must be of type Asset'
                    ], 422);
                }
                
                // Validate currency with new GL account if currency is being changed
                $currencyCode = $request->currency_code ?? $bankAccount->currency_code;
                $currencyValidation = $this->validateCurrencyWithGLAccount($currencyCode, $glAccount);
                if (!$currencyValidation['valid']) {
                    return response()->json([
                        'message' => $currencyValidation['message']
                    ], 422);
                }
            }

            // Handle currency or balance changes
            $updateData = $request->only(['bank_name', 'account_number', 'account_name', 'gl_account_id']);
            
            if ($request->has('currency_code') || $request->has('current_balance') || $request->has('exchange_rate')) {
                // Recalculate base currency balance if needed
                $currencyCode = $request->currency_code ?? $bankAccount->currency_code;
                $currentBalance = $request->current_balance ?? $bankAccount->current_balance;
                $exchangeRate = $this->getExchangeRate($currencyCode, $request->exchange_rate);
                
                $updateData = array_merge($updateData, [
                    'current_balance' => $currentBalance,
                    'currency_code' => $currencyCode,
                    'exchange_rate' => $exchangeRate,
                    'base_currency_balance' => $currentBalance * $exchangeRate
                ]);
            }
            
            $bankAccount->update($updateData);
            
            DB::commit();

            // Load relationships for response
            $bankAccount->load([
                'chartOfAccount',
                'currency:code,name,symbol',
                'baseCurrency:code,name,symbol'
            ]);

            return response()->json([
                'data' => $bankAccount, 
                'message' => 'Bank account updated successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update bank account: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified bank account from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $bankAccount = BankAccount::findOrFail($id);
        
        // Check if there are reconciliations
        if ($bankAccount->bankReconciliations()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete bank account with associated reconciliations'
            ], 422);
        }
        
        $bankAccount->delete();

        return response()->json(['message' => 'Bank account deleted successfully'], 200);
    }

    /**
     * Get available currencies for bank accounts.
     *
     * @return \Illuminate\Http\Response
     */
    public function getCurrencies()
    {
        $currencies = SystemCurrency::where('is_active', true)
            ->orderBy('code')
            ->get(['code', 'name', 'symbol']);
            
        return response()->json(['data' => $currencies], 200);
    }

    /**
     * Get current exchange rate for a currency pair.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getExchangeRateApi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3',
            'date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $rate = $this->getExchangeRateFromDatabase(
            $request->from_currency,
            $request->to_currency,
            $request->date ?? now()->toDateString()
        );

        if (!$rate) {
            return response()->json([
                'message' => 'Exchange rate not found for the specified currency pair'
            ], 404);
        }

        return response()->json([
            'data' => [
                'from_currency' => $request->from_currency,
                'to_currency' => $request->to_currency,
                'rate' => $rate,
                'date' => $request->date ?? now()->toDateString()
            ]
        ], 200);
    }

    /**
     * Validate currency compatibility with GL account.
     *
     * @param string $currencyCode
     * @param ChartOfAccount $glAccount
     * @return array
     */
    private function validateCurrencyWithGLAccount($currencyCode, $glAccount)
    {
        // If GL account allows multi-currency, any currency is valid
        if ($glAccount->allow_multi_currency) {
            return ['valid' => true];
        }

        // If single currency account, must match default currency
        if ($glAccount->default_currency !== $currencyCode) {
            return [
                'valid' => false,
                'message' => "The selected GL account only supports {$glAccount->default_currency} currency. Please select a multi-currency GL account or change the currency to {$glAccount->default_currency}."
            ];
        }

        return ['valid' => true];
    }

    /**
     * Get exchange rate for currency conversion.
     *
     * @param string $currencyCode
     * @param float|null $manualRate
     * @return float
     */
    private function getExchangeRate($currencyCode, $manualRate = null)
    {
        $baseCurrency = config('app.base_currency', 'USD');
        
        // If currency is same as base currency, rate is 1
        if ($currencyCode === $baseCurrency) {
            return 1.0;
        }

        // Use manual rate if provided
        if ($manualRate) {
            return $manualRate;
        }

        // Get rate from database
        $rate = $this->getExchangeRateFromDatabase($currencyCode, $baseCurrency);
        
        return $rate ?: 1.0; // Default to 1 if no rate found
    }

    /**
     * Get exchange rate from database.
     *
     * @param string $fromCurrency
     * @param string $toCurrency
     * @param string|null $date
     * @return float|null
     */
    private function getExchangeRateFromDatabase($fromCurrency, $toCurrency, $date = null)
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $date = $date ?: now()->toDateString();

        $rate = DB::table('currency_rates')
            ->where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->where('effective_date', '<=', $date)
            ->where('is_active', true)
            ->whereNull('end_date')
            ->orWhere('end_date', '>', $date)
            ->orderBy('effective_date', 'desc')
            ->value('rate');

        return $rate;
    }
}