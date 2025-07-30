<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\AccountingPeriod;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\JournalEntryLine;
use App\Models\Accounting\CustomerReceivable;
use App\Models\Accounting\VendorPayable;
use App\Models\Accounting\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinancialReportController extends Controller
{
    /**
     * Generate trial balance report with multi-currency support.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function trialBalance(Request $request)
    {
        // Validate request parameters
        $request->validate([
            'period_id' => 'required|exists:AccountingPeriod,period_id',
            'include_zero_balances' => 'boolean',
            'currency' => 'nullable|string|size:3',
            'show_foreign_currency' => 'boolean',
            'as_of_date' => 'nullable|date',
            'show_original_amounts' => 'boolean'
        ]);
        
        $period = AccountingPeriod::findOrFail($request->period_id);
        $baseCurrency = config('app.base_currency', 'USD');
        $reportCurrency = $request->input('currency', $baseCurrency);
        $asOfDate = $request->input('as_of_date', $period->end_date);
        $showForeignCurrency = $request->boolean('show_foreign_currency', false);
        $showOriginalAmounts = $request->boolean('show_original_amounts', false);
        
        // Get all journal entries for the period that are posted
        $journalEntries = JournalEntry::where('period_id', $period->period_id)
            ->where('status', 'Posted')
            ->where('entry_date', '<=', $asOfDate)
            ->pluck('journal_id');
        
        // Calculate debits and credits for each account
        $accounts = DB::table('ChartOfAccount')
            ->leftJoin('JournalEntryLine', 'ChartOfAccount.account_id', '=', 'JournalEntryLine.account_id')
            ->leftJoin('JournalEntry', 'JournalEntryLine.journal_id', '=', 'JournalEntry.journal_id')
            ->select(
                'ChartOfAccount.account_id',
                'ChartOfAccount.account_code',
                'ChartOfAccount.name',
                'ChartOfAccount.account_type',
                DB::raw('SUM(IFNULL(JournalEntryLine.debit_amount, 0)) as total_debit'),
                DB::raw('SUM(IFNULL(JournalEntryLine.credit_amount, 0)) as total_credit'),
                DB::raw('GROUP_CONCAT(DISTINCT JournalEntryLine.currency ORDER BY JournalEntryLine.currency) as currencies_used')
            )
            ->whereIn('JournalEntry.journal_id', $journalEntries)
            ->where('JournalEntry.status', 'Posted')
            ->groupBy(
                'ChartOfAccount.account_id',
                'ChartOfAccount.account_code',
                'ChartOfAccount.name',
                'ChartOfAccount.account_type'
            );
        
        // Exclude accounts with zero balances if requested
        if (!$request->input('include_zero_balances', true)) {
            $accounts->havingRaw('SUM(IFNULL(JournalEntryLine.debit_amount, 0)) > 0 OR SUM(IFNULL(JournalEntryLine.credit_amount, 0)) > 0');
        }
        
        $accounts = $accounts->orderBy('ChartOfAccount.account_code')->get();
        
        // Convert to report currency if different from base currency
        $exchangeRate = $this->getExchangeRate($baseCurrency, $reportCurrency, $asOfDate);
        
        $trialBalanceData = $accounts->map(function($account) use ($exchangeRate, $reportCurrency, $baseCurrency, $showForeignCurrency, $showOriginalAmounts, $period, $asOfDate) {
            $debitBalance = $account->total_debit;
            $creditBalance = $account->total_credit;
            $netBalance = $debitBalance - $creditBalance;
            
            // Convert to report currency
            $convertedDebit = $debitBalance * $exchangeRate;
            $convertedCredit = $creditBalance * $exchangeRate;
            $convertedNet = $netBalance * $exchangeRate;
            
            $result = [
                'account_id' => $account->account_id,
                'account_code' => $account->account_code,
                'account_name' => $account->name,
                'account_type' => $account->account_type,
                'debit_balance' => $convertedDebit,
                'credit_balance' => $convertedCredit,
                'net_balance' => $convertedNet,
                'currency' => $reportCurrency,
                'currencies_used' => $account->currencies_used ? explode(',', $account->currencies_used) : [$baseCurrency]
            ];
            
            // Add original amounts if requested
            if ($showOriginalAmounts && $reportCurrency !== $baseCurrency) {
                $result['original_amounts'] = [
                    'currency' => $baseCurrency,
                    'debit_balance' => $debitBalance,
                    'credit_balance' => $creditBalance,
                    'net_balance' => $netBalance,
                    'exchange_rate' => $exchangeRate
                ];
            }
            
            // Add foreign currency breakdown
            if ($showForeignCurrency) {
                $result['foreign_currency_breakdown'] = $this->getForeignCurrencyBreakdown($account->account_id, $period->period_id, $asOfDate);
            }
            
            return $result;
        });
        
        // Calculate totals
        $totalDebits = $trialBalanceData->sum('debit_balance');
        $totalCredits = $trialBalanceData->sum('credit_balance');
        
        return response()->json([
            'data' => $trialBalanceData,
            'summary' => [
                'period' => $period,
                'as_of_date' => $asOfDate,
                'currency' => $reportCurrency,
                'base_currency' => $baseCurrency,
                'exchange_rate' => $exchangeRate,
                'total_debits' => $totalDebits,
                'total_credits' => $totalCredits,
                'difference' => $totalDebits - $totalCredits,
                'is_balanced' => abs($totalDebits - $totalCredits) < 0.01,
                'available_currencies' => $this->getAvailableCurrencies($period->period_id)
            ]
        ], 200);
    }

    /**
     * Generate income statement with multi-currency support.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function incomeStatement(Request $request)
    {
        $request->validate([
            'period_id' => 'required|exists:AccountingPeriod,period_id',
            'currency' => 'nullable|string|size:3',
            'compare_previous_period' => 'boolean',
            'show_foreign_currency' => 'boolean',
            'consolidate_currencies' => 'boolean'
        ]);
        
        $period = AccountingPeriod::findOrFail($request->period_id);
        $baseCurrency = config('app.base_currency', 'USD');
        $reportCurrency = $request->input('currency', $baseCurrency);
        $showForeignCurrency = $request->boolean('show_foreign_currency', false);
        $consolidateCurrencies = $request->boolean('consolidate_currencies', true);
        $exchangeRate = $this->getExchangeRate($baseCurrency, $reportCurrency, $period->end_date);
        
        // Get revenue accounts (Income type)
        $revenueAccounts = $this->getAccountBalances(['Income'], $period->period_id, $exchangeRate, $reportCurrency, $showForeignCurrency);
        
        // Get expense accounts (Expense type)
        $expenseAccounts = $this->getAccountBalances(['Expense'], $period->period_id, $exchangeRate, $reportCurrency, $showForeignCurrency);
        
        // Calculate totals
        $totalRevenue = $revenueAccounts->sum('credit_balance');
        $totalExpenses = $expenseAccounts->sum('debit_balance');
        $netIncome = $totalRevenue - $totalExpenses;
        
        $incomeStatement = [
            'revenue' => [
                'accounts' => $revenueAccounts,
                'total' => $totalRevenue
            ],
            'expenses' => [
                'accounts' => $expenseAccounts,
                'total' => $totalExpenses
            ],
            'net_income' => $netIncome,
            'gross_margin' => $totalRevenue > 0 ? (($totalRevenue - $totalExpenses) / $totalRevenue) * 100 : 0
        ];
        
        // Add previous period comparison if requested
        if ($request->boolean('compare_previous_period')) {
            $previousPeriod = $this->getPreviousPeriod($period);
            if ($previousPeriod) {
                $prevExchangeRate = $this->getExchangeRate($baseCurrency, $reportCurrency, $previousPeriod->end_date);
                
                $prevRevenueAccounts = $this->getAccountBalances(['Income'], $previousPeriod->period_id, $prevExchangeRate, $reportCurrency, false);
                $prevExpenseAccounts = $this->getAccountBalances(['Expense'], $previousPeriod->period_id, $prevExchangeRate, $reportCurrency, false);
                
                $prevTotalRevenue = $prevRevenueAccounts->sum('credit_balance');
                $prevTotalExpenses = $prevExpenseAccounts->sum('debit_balance');
                $prevNetIncome = $prevTotalRevenue - $prevTotalExpenses;
                
                $incomeStatement['comparison'] = [
                    'previous_period' => $previousPeriod,
                    'previous_revenue' => $prevTotalRevenue,
                    'previous_expenses' => $prevTotalExpenses,
                    'previous_net_income' => $prevNetIncome,
                    'revenue_variance' => $totalRevenue - $prevTotalRevenue,
                    'expense_variance' => $totalExpenses - $prevTotalExpenses,
                    'net_income_variance' => $netIncome - $prevNetIncome,
                    'revenue_variance_percent' => $prevTotalRevenue != 0 ? (($totalRevenue - $prevTotalRevenue) / abs($prevTotalRevenue)) * 100 : 0,
                    'expense_variance_percent' => $prevTotalExpenses != 0 ? (($totalExpenses - $prevTotalExpenses) / abs($prevTotalExpenses)) * 100 : 0
                ];
            }
        }
        
        // Add currency breakdown if requested
        if ($showForeignCurrency && !$consolidateCurrencies) {
            $incomeStatement['currency_breakdown'] = $this->getCurrencyBreakdown($period->period_id, ['Income', 'Expense'], $reportCurrency);
        }
        
        return response()->json([
            'data' => $incomeStatement,
            'summary' => [
                'period' => $period,
                'currency' => $reportCurrency,
                'base_currency' => $baseCurrency,
                'exchange_rate' => $exchangeRate,
                'total_revenue' => $totalRevenue,
                'total_expenses' => $totalExpenses,
                'net_income' => $netIncome,
                'profit_margin' => $totalRevenue != 0 ? ($netIncome / $totalRevenue) * 100 : 0,
                'available_currencies' => $this->getAvailableCurrencies($period->period_id)
            ]
        ], 200);
    }

    /**
     * Generate balance sheet with multi-currency support.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function balanceSheet(Request $request)
    {
        $request->validate([
            'period_id' => 'required|exists:AccountingPeriod,period_id',
            'currency' => 'nullable|string|size:3',
            'as_of_date' => 'nullable|date',
            'show_foreign_currency' => 'boolean',
            'consolidate_currencies' => 'boolean'
        ]);
        
        $period = AccountingPeriod::findOrFail($request->period_id);
        $baseCurrency = config('app.base_currency', 'USD');
        $reportCurrency = $request->input('currency', $baseCurrency);
        $asOfDate = $request->input('as_of_date', $period->end_date);
        $showForeignCurrency = $request->boolean('show_foreign_currency', false);
        $exchangeRate = $this->getExchangeRate($baseCurrency, $reportCurrency, $asOfDate);
        
        // Get assets
        $currentAssets = $this->getAccountBalances(['Current Asset'], $period->period_id, $exchangeRate, $reportCurrency, $showForeignCurrency, $asOfDate);
        $nonCurrentAssets = $this->getAccountBalances(['Non-current Asset', 'Fixed Asset'], $period->period_id, $exchangeRate, $reportCurrency, $showForeignCurrency, $asOfDate);
        $totalAssets = $currentAssets->sum('debit_balance') + $nonCurrentAssets->sum('debit_balance');
        
        // Get liabilities
        $currentLiabilities = $this->getAccountBalances(['Current Liability'], $period->period_id, $exchangeRate, $reportCurrency, $showForeignCurrency, $asOfDate);
        $nonCurrentLiabilities = $this->getAccountBalances(['Non-current Liability'], $period->period_id, $exchangeRate, $reportCurrency, $showForeignCurrency, $asOfDate);
        $totalLiabilities = $currentLiabilities->sum('credit_balance') + $nonCurrentLiabilities->sum('credit_balance');
        
        // Get equity
        $equity = $this->getAccountBalances(['Equity'], $period->period_id, $exchangeRate, $reportCurrency, $showForeignCurrency, $asOfDate);
        $retainedEarnings = $this->calculateRetainedEarnings($period->period_id, $exchangeRate, $asOfDate);
        $totalEquity = $equity->sum('credit_balance') + $retainedEarnings;
        
        $balanceSheet = [
            'assets' => [
                'current_assets' => [
                    'accounts' => $currentAssets,
                    'total' => $currentAssets->sum('debit_balance')
                ],
                'non_current_assets' => [
                    'accounts' => $nonCurrentAssets,
                    'total' => $nonCurrentAssets->sum('debit_balance')
                ],
                'total' => $totalAssets
            ],
            'liabilities' => [
                'current_liabilities' => [
                    'accounts' => $currentLiabilities,
                    'total' => $currentLiabilities->sum('credit_balance')
                ],
                'non_current_liabilities' => [
                    'accounts' => $nonCurrentLiabilities,
                    'total' => $nonCurrentLiabilities->sum('credit_balance')
                ],
                'total' => $totalLiabilities
            ],
            'equity' => [
                'accounts' => $equity,
                'retained_earnings' => $retainedEarnings,
                'total' => $totalEquity
            ],
            'total_liabilities_equity' => $totalLiabilities + $totalEquity
        ];
        
        return response()->json([
            'data' => $balanceSheet,
            'summary' => [
                'as_of_date' => $asOfDate,
                'currency' => $reportCurrency,
                'base_currency' => $baseCurrency,
                'exchange_rate' => $exchangeRate,
                'total_assets' => $totalAssets,
                'total_liabilities' => $totalLiabilities,
                'total_equity' => $totalEquity,
                'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
                'difference' => $totalAssets - ($totalLiabilities + $totalEquity),
                'available_currencies' => $this->getAvailableCurrencies($period->period_id)
            ]
        ], 200);
    }

    /**
     * Generate cash flow statement with multi-currency support.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function cashFlow(Request $request)
    {
        $request->validate([
            'period_id' => 'required|exists:AccountingPeriod,period_id',
            'currency' => 'nullable|string|size:3',
            'method' => 'nullable|in:direct,indirect',
            'show_foreign_currency' => 'boolean'
        ]);
        
        $period = AccountingPeriod::findOrFail($request->period_id);
        $baseCurrency = config('app.base_currency', 'USD');
        $reportCurrency = $request->input('currency', $baseCurrency);
        $method = $request->input('method', 'indirect');
        $showForeignCurrency = $request->boolean('show_foreign_currency', false);
        $exchangeRate = $this->getExchangeRate($baseCurrency, $reportCurrency, $period->end_date);
        
        if ($method === 'indirect') {
            return $this->generateIndirectCashFlow($period, $reportCurrency, $exchangeRate, $showForeignCurrency);
        } else {
            return $this->generateDirectCashFlow($period, $reportCurrency, $exchangeRate, $showForeignCurrency);
        }
    }

    /**
     * Generate accounts receivable report with multi-currency support.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function accountsReceivable(Request $request)
    {
        $request->validate([
            'as_of_date' => 'nullable|date',
            'currency' => 'nullable|string|size:3',
            'aging_periods' => 'nullable|array',
            'aging_periods.*' => 'integer|min:1',
            'show_foreign_currency' => 'boolean',
            'consolidate_currencies' => 'boolean'
        ]);
        
        $asOfDate = $request->input('as_of_date', now()->toDateString());
        $baseCurrency = config('app.base_currency', 'USD');
        $reportCurrency = $request->input('currency', $baseCurrency);
        $showForeignCurrency = $request->boolean('show_foreign_currency', false);
        $consolidateCurrencies = $request->boolean('consolidate_currencies', true);
        $agingPeriods = $request->input('aging_periods', [30, 60, 90, 120]);
        $exchangeRate = $this->getExchangeRate($baseCurrency, $reportCurrency, $asOfDate);
        
        $receivables = $this->getAccountsReceivableData($asOfDate, $reportCurrency, $exchangeRate, $agingPeriods, $showForeignCurrency, $consolidateCurrencies);
        
        return response()->json([
            'data' => $receivables,
            'summary' => [
                'as_of_date' => $asOfDate,
                'currency' => $reportCurrency,
                'base_currency' => $baseCurrency,
                'exchange_rate' => $exchangeRate,
                'aging_periods' => $agingPeriods,
                'total_receivables' => $receivables->sum('total_amount'),
                'available_currencies' => $this->getReceivableCurrencies($asOfDate)
            ]
        ], 200);
    }

    /**
     * Generate accounts payable report with multi-currency support.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function accountsPayable(Request $request)
    {
        $request->validate([
            'as_of_date' => 'nullable|date',
            'currency' => 'nullable|string|size:3',
            'aging_periods' => 'nullable|array',
            'aging_periods.*' => 'integer|min:1',
            'show_foreign_currency' => 'boolean',
            'consolidate_currencies' => 'boolean'
        ]);
        
        $asOfDate = $request->input('as_of_date', now()->toDateString());
        $baseCurrency = config('app.base_currency', 'USD');
        $reportCurrency = $request->input('currency', $baseCurrency);
        $showForeignCurrency = $request->boolean('show_foreign_currency', false);
        $consolidateCurrencies = $request->boolean('consolidate_currencies', true);
        $agingPeriods = $request->input('aging_periods', [30, 60, 90, 120]);
        $exchangeRate = $this->getExchangeRate($baseCurrency, $reportCurrency, $asOfDate);
        
        $payables = $this->getAccountsPayableData($asOfDate, $reportCurrency, $exchangeRate, $agingPeriods, $showForeignCurrency, $consolidateCurrencies);
        
        return response()->json([
            'data' => $payables,
            'summary' => [
                'as_of_date' => $asOfDate,
                'currency' => $reportCurrency,
                'base_currency' => $baseCurrency,
                'exchange_rate' => $exchangeRate,
                'aging_periods' => $agingPeriods,
                'total_payables' => $payables->sum('total_amount'),
                'available_currencies' => $this->getPayableCurrencies($asOfDate)
            ]
        ], 200);
    }

    /**
     * Get exchange rate between two currencies on a specific date.
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
        
        $exchangeRate = ExchangeRate::where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->first();
        
        if ($exchangeRate) {
            return $exchangeRate->rate;
        }
        
        // Try reverse rate
        $reverseRate = ExchangeRate::where('from_currency', $toCurrency)
            ->where('to_currency', $fromCurrency)
            ->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->first();
        
        if ($reverseRate && $reverseRate->rate > 0) {
            return 1 / $reverseRate->rate;
        }
        
        // Default to 1.0 if no rate found
        return 1.0;
    }

    /**
     * Get account balances for specific account types with currency conversion.
     *
     * @param array $accountTypes
     * @param int $periodId
     * @param float $exchangeRate
     * @param string $reportCurrency
     * @param bool $showForeignCurrency
     * @param string|null $asOfDate
     * @return \Illuminate\Support\Collection
     */
    private function getAccountBalances($accountTypes, $periodId, $exchangeRate, $reportCurrency, $showForeignCurrency = false, $asOfDate = null)
    {
        $query = DB::table('ChartOfAccount')
            ->leftJoin('JournalEntryLine', 'ChartOfAccount.account_id', '=', 'JournalEntryLine.account_id')
            ->leftJoin('JournalEntry', 'JournalEntryLine.journal_id', '=', 'JournalEntry.journal_id')
            ->select(
                'ChartOfAccount.account_id',
                'ChartOfAccount.account_code',
                'ChartOfAccount.name',
                'ChartOfAccount.account_type',
                DB::raw('SUM(IFNULL(JournalEntryLine.debit_amount, 0)) as total_debit'),
                DB::raw('SUM(IFNULL(JournalEntryLine.credit_amount, 0)) as total_credit')
            )
            ->where('JournalEntry.period_id', $periodId)
            ->where('JournalEntry.status', 'Posted')
            ->whereIn('ChartOfAccount.account_type', $accountTypes);
        
        if ($asOfDate) {
            $query->where('JournalEntry.entry_date', '<=', $asOfDate);
        }
        
        $accounts = $query->groupBy(
                'ChartOfAccount.account_id',
                'ChartOfAccount.account_code',
                'ChartOfAccount.name',
                'ChartOfAccount.account_type'
            )
            ->orderBy('ChartOfAccount.account_code')
            ->get();
        
        return $accounts->map(function($account) use ($exchangeRate, $reportCurrency, $showForeignCurrency, $periodId, $asOfDate) {
            $debitBalance = $account->total_debit * $exchangeRate;
            $creditBalance = $account->total_credit * $exchangeRate;
            
            $result = [
                'account_id' => $account->account_id,
                'account_code' => $account->account_code,
                'account_name' => $account->name,
                'account_type' => $account->account_type,
                'debit_balance' => $debitBalance,
                'credit_balance' => $creditBalance,
                'net_balance' => $debitBalance - $creditBalance,
                'currency' => $reportCurrency
            ];
            
            if ($showForeignCurrency) {
                $result['foreign_currency_breakdown'] = $this->getForeignCurrencyBreakdown($account->account_id, $periodId, $asOfDate);
            }
            
            return $result;
        });
    }

    /**
     * Get foreign currency breakdown for an account.
     *
     * @param int $accountId
     * @param int $periodId
     * @param string|null $asOfDate
     * @return array
     */
    private function getForeignCurrencyBreakdown($accountId, $periodId, $asOfDate = null)
    {
        $query = DB::table('JournalEntryLine')
            ->join('JournalEntry', 'JournalEntryLine.journal_id', '=', 'JournalEntry.journal_id')
            ->select(
                'JournalEntryLine.currency',
                DB::raw('SUM(IFNULL(JournalEntryLine.debit_amount, 0)) as total_debit'),
                DB::raw('SUM(IFNULL(JournalEntryLine.credit_amount, 0)) as total_credit'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->where('JournalEntryLine.account_id', $accountId)
            ->where('JournalEntry.period_id', $periodId)
            ->where('JournalEntry.status', 'Posted');
        
        if ($asOfDate) {
            $query->where('JournalEntry.entry_date', '<=', $asOfDate);
        }
        
        return $query->groupBy('JournalEntryLine.currency')
            ->orderBy('JournalEntryLine.currency')
            ->get()
            ->map(function($item) {
                return [
                    'currency' => $item->currency,
                    'debit_amount' => $item->total_debit,
                    'credit_amount' => $item->total_credit,
                    'net_amount' => $item->total_debit - $item->total_credit,
                    'transaction_count' => $item->transaction_count
                ];
            })
            ->toArray();
    }

    /**
     * Get available currencies used in a period.
     *
     * @param int $periodId
     * @return array
     */
    private function getAvailableCurrencies($periodId)
    {
        return DB::table('JournalEntryLine')
            ->join('JournalEntry', 'JournalEntryLine.journal_id', '=', 'JournalEntry.journal_id')
            ->where('JournalEntry.period_id', $periodId)
            ->where('JournalEntry.status', 'Posted')
            ->distinct()
            ->pluck('JournalEntryLine.currency')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Calculate retained earnings with currency conversion.
     *
     * @param int $periodId
     * @param float $exchangeRate
     * @param string|null $asOfDate
     * @return float
     */
    private function calculateRetainedEarnings($periodId, $exchangeRate, $asOfDate = null)
    {
        // This should calculate retained earnings from previous periods
        // Implementation depends on your specific business logic
        $retainedEarningsAccountId = ChartOfAccount::where('account_code', 'LIKE', '%retained%')
            ->orWhere('name', 'LIKE', '%retained%')
            ->first()?->account_id;
        
        if (!$retainedEarningsAccountId) {
            return 0;
        }
        
        $query = DB::table('JournalEntryLine')
            ->join('JournalEntry', 'JournalEntryLine.journal_id', '=', 'JournalEntry.journal_id')
            ->where('JournalEntryLine.account_id', $retainedEarningsAccountId)
            ->where('JournalEntry.period_id', '<=', $periodId)
            ->where('JournalEntry.status', 'Posted');
        
        if ($asOfDate) {
            $query->where('JournalEntry.entry_date', '<=', $asOfDate);
        }
        
        $result = $query->select(
                DB::raw('SUM(IFNULL(JournalEntryLine.credit_amount, 0)) - SUM(IFNULL(JournalEntryLine.debit_amount, 0)) as retained_earnings')
            )
            ->first();
        
        return ($result->retained_earnings ?? 0) * $exchangeRate;
    }

    /**
     * Get previous accounting period.
     *
     * @param AccountingPeriod $period
     * @return AccountingPeriod|null
     */
    private function getPreviousPeriod($period)
    {
        return AccountingPeriod::where('start_date', '<', $period->start_date)
            ->orderBy('start_date', 'desc')
            ->first();
    }

    /**
     * Generate indirect cash flow statement.
     *
     * @param AccountingPeriod $period
     * @param string $reportCurrency
     * @param float $exchangeRate
     * @param bool $showForeignCurrency
     * @return \Illuminate\Http\Response
     */
    private function generateIndirectCashFlow($period, $reportCurrency, $exchangeRate, $showForeignCurrency)
    {
        // Implementation for indirect cash flow method
        // This is a simplified version - you should implement according to your needs
        
        $netIncome = $this->getNetIncome($period->period_id, $exchangeRate);
        $depreciation = $this->getDepreciation($period->period_id, $exchangeRate);
        $workingCapitalChanges = $this->getWorkingCapitalChanges($period->period_id, $exchangeRate);
        
        $operatingCashFlow = $netIncome + $depreciation + $workingCapitalChanges;
        
        $cashFlow = [
            'operating_activities' => [
                'net_income' => $netIncome,
                'depreciation' => $depreciation,
                'working_capital_changes' => $workingCapitalChanges,
                'total' => $operatingCashFlow
            ],
            'investing_activities' => [
                'total' => 0 // Implement based on your needs
            ],
            'financing_activities' => [
                'total' => 0 // Implement based on your needs
            ]
        ];
        
        return response()->json([
            'data' => $cashFlow,
            'summary' => [
                'period' => $period,
                'currency' => $reportCurrency,
                'exchange_rate' => $exchangeRate,
                'method' => 'indirect',
                'net_cash_flow' => $operatingCashFlow
            ]
        ]);
    }

    /**
     * Generate direct cash flow statement.
     *
     * @param AccountingPeriod $period
     * @param string $reportCurrency
     * @param float $exchangeRate
     * @param bool $showForeignCurrency
     * @return \Illuminate\Http\Response
     */
    private function generateDirectCashFlow($period, $reportCurrency, $exchangeRate, $showForeignCurrency)
    {
        // Implementation for direct cash flow method
        // This is a simplified version - you should implement according to your needs
        
        $cashFromCustomers = $this->getCashFromCustomers($period->period_id, $exchangeRate);
        $cashToSuppliers = $this->getCashToSuppliers($period->period_id, $exchangeRate);
        
        $operatingCashFlow = $cashFromCustomers - $cashToSuppliers;
        
        $cashFlow = [
            'operating_activities' => [
                'cash_from_customers' => $cashFromCustomers,
                'cash_to_suppliers' => $cashToSuppliers,
                'total' => $operatingCashFlow
            ],
            'investing_activities' => [
                'total' => 0 // Implement based on your needs
            ],
            'financing_activities' => [
                'total' => 0 // Implement based on your needs
            ]
        ];
        
        return response()->json([
            'data' => $cashFlow,
            'summary' => [
                'period' => $period,
                'currency' => $reportCurrency,
                'exchange_rate' => $exchangeRate,
                'method' => 'direct',
                'net_cash_flow' => $operatingCashFlow
            ]
        ]);
    }

    // Additional helper methods for cash flow calculations
    private function getNetIncome($periodId, $exchangeRate) 
    {
        $income = DB::table('chart_of_accounts')
            ->leftJoin('journal_entry_lines', 'chart_of_accounts.account_id', '=', 'journal_entry_lines.account_id')
            ->leftJoin('journal_entries', 'journal_entry_lines.journal_id', '=', 'journal_entries.journal_id')
            ->where('chart_of_accounts.account_type', 'Income')
            ->where('journal_entries.period_id', $periodId)
            ->where('journal_entries.status', 'Posted')
            ->sum('journal_entry_lines.credit_amount');
            
        $expenses = DB::table('chart_of_accounts')
            ->leftJoin('journal_entry_lines', 'chart_of_accounts.account_id', '=', 'journal_entry_lines.account_id')
            ->leftJoin('journal_entries', 'journal_entry_lines.journal_id', '=', 'journal_entries.journal_id')
            ->where('chart_of_accounts.account_type', 'Expense')
            ->where('journal_entries.period_id', $periodId)
            ->where('journal_entries.status', 'Posted')
            ->sum('journal_entry_lines.debit_amount');
            
        return ($income - $expenses) * $exchangeRate;
    }

    private function getDepreciation($periodId, $exchangeRate) 
    {
        $depreciation = DB::table('chart_of_accounts')
            ->leftJoin('journal_entry_lines', 'chart_of_accounts.account_id', '=', 'journal_entry_lines.account_id')
            ->leftJoin('journal_entries', 'journal_entry_lines.journal_id', '=', 'journal_entries.journal_id')
            ->where('chart_of_accounts.name', 'LIKE', '%depreciation%')
            ->where('journal_entries.period_id', $periodId)
            ->where('journal_entries.status', 'Posted')
            ->sum('journal_entry_lines.debit_amount');
            
        return $depreciation * $exchangeRate;
    }

    private function getWorkingCapitalChanges($periodId, $exchangeRate) 
    {
        // Calculate changes in working capital accounts
        $currentAssets = DB::table('chart_of_accounts')
            ->leftJoin('journal_entry_lines', 'chart_of_accounts.account_id', '=', 'journal_entry_lines.account_id')
            ->leftJoin('journal_entries', 'journal_entry_lines.journal_id', '=', 'journal_entries.journal_id')
            ->where('chart_of_accounts.account_type', 'Current Asset')
            ->where('journal_entries.period_id', $periodId)
            ->where('journal_entries.status', 'Posted')
            ->sum('journal_entry_lines.debit_amount');
            
        $currentLiabilities = DB::table('chart_of_accounts')
            ->leftJoin('journal_entry_lines', 'chart_of_accounts.account_id', '=', 'journal_entry_lines.account_id')
            ->leftJoin('journal_entries', 'journal_entry_lines.journal_id', '=', 'journal_entries.journal_id')
            ->where('chart_of_accounts.account_type', 'Current Liability')
            ->where('journal_entries.period_id', $periodId)
            ->where('journal_entries.status', 'Posted')
            ->sum('journal_entry_lines.credit_amount');
            
        return ($currentLiabilities - $currentAssets) * $exchangeRate;
    }

    private function getCashFromCustomers($periodId, $exchangeRate) 
    {
        $cashReceived = DB::table('receivable_payments')
            ->join('customer_receivables', 'receivable_payments.receivable_id', '=', 'customer_receivables.receivable_id')
            ->join('accounting_periods', function($join) use ($periodId) {
                $join->on('receivable_payments.payment_date', '>=', 'accounting_periods.start_date')
                     ->on('receivable_payments.payment_date', '<=', 'accounting_periods.end_date')
                     ->where('accounting_periods.period_id', $periodId);
            })
            ->sum('receivable_payments.amount');
            
        return $cashReceived * $exchangeRate;
    }

    private function getCashToSuppliers($periodId, $exchangeRate) 
    {
        $cashPaid = DB::table('payable_payments')
            ->join('vendor_payables', 'payable_payments.payable_id', '=', 'vendor_payables.payable_id')
            ->join('accounting_periods', function($join) use ($periodId) {
                $join->on('payable_payments.payment_date', '>=', 'accounting_periods.start_date')
                     ->on('payable_payments.payment_date', '<=', 'accounting_periods.end_date')
                     ->where('accounting_periods.period_id', $periodId);
            })
            ->sum('payable_payments.amount');
            
        return $cashPaid * $exchangeRate;
    }

    // Additional helper methods for receivables and payables
    private function getAccountsReceivableData($asOfDate, $reportCurrency, $exchangeRate, $agingPeriods, $showForeignCurrency, $consolidateCurrencies) 
    {
        $query = CustomerReceivable::with('customer')
            ->where('status', '!=', 'Paid')
            ->where('due_date', '<=', $asOfDate);
            
        $receivables = $query->get();
        
        return $receivables->map(function($receivable) use ($reportCurrency, $exchangeRate, $asOfDate, $agingPeriods, $showForeignCurrency) {
            $convertedAmount = $receivable->balance * $exchangeRate;
            $daysPastDue = now()->parse($asOfDate)->diffInDays($receivable->due_date, false);
            
            $result = [
                'receivable_id' => $receivable->receivable_id,
                'customer_name' => $receivable->customer->name ?? 'Unknown',
                'invoice_number' => $receivable->invoice_id,
                'due_date' => $receivable->due_date,
                'original_amount' => $receivable->amount,
                'balance' => $receivable->balance,
                'total_amount' => $convertedAmount,
                'currency' => $reportCurrency,
                'days_past_due' => $daysPastDue,
                'aging_bucket' => $this->getAgingBucket($daysPastDue, $agingPeriods)
            ];
            
            if ($showForeignCurrency) {
                $result['original_currency'] = $receivable->currency_code ?? config('app.base_currency');
                $result['exchange_rate'] = $exchangeRate;
            }
            
            return $result;
        });
    }

    private function getAccountsPayableData($asOfDate, $reportCurrency, $exchangeRate, $agingPeriods, $showForeignCurrency, $consolidateCurrencies) 
    {
        $query = VendorPayable::with('vendor')
            ->where('status', '!=', 'Paid')
            ->where('due_date', '<=', $asOfDate);
            
        $payables = $query->get();
        
        return $payables->map(function($payable) use ($reportCurrency, $exchangeRate, $asOfDate, $agingPeriods, $showForeignCurrency) {
            $convertedAmount = $payable->balance * $exchangeRate;
            $daysPastDue = now()->parse($asOfDate)->diffInDays($payable->due_date, false);
            
            $result = [
                'payable_id' => $payable->payable_id,
                'vendor_name' => $payable->vendor->name ?? 'Unknown',
                'invoice_number' => $payable->invoice_id,
                'due_date' => $payable->due_date,
                'original_amount' => $payable->amount,
                'balance' => $payable->balance,
                'total_amount' => $convertedAmount,
                'currency' => $reportCurrency,
                'days_past_due' => $daysPastDue,
                'aging_bucket' => $this->getAgingBucket($daysPastDue, $agingPeriods),
                'is_overdue' => $daysPastDue < 0
            ];
            
            if ($showForeignCurrency) {
                $result['original_currency'] = $payable->currency_code ?? config('app.base_currency');
                $result['exchange_rate'] = $exchangeRate;
            }
            
            return $result;
        });
    }

    private function getReceivableCurrencies($asOfDate) 
    {
        return CustomerReceivable::where('status', '!=', 'Paid')
            ->where('due_date', '<=', $asOfDate)
            ->distinct()
            ->pluck('currency_code')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    private function getPayableCurrencies($asOfDate) 
    {
        return VendorPayable::where('status', '!=', 'Paid')
            ->where('due_date', '<=', $asOfDate)
            ->distinct()
            ->pluck('currency_code')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    private function getCurrencyBreakdown($periodId, $accountTypes, $reportCurrency) 
    {
        return DB::table('chart_of_accounts')
            ->join('journal_entry_lines', 'chart_of_accounts.account_id', '=', 'journal_entry_lines.account_id')
            ->join('journal_entries', 'journal_entry_lines.journal_id', '=', 'journal_entries.journal_id')
            ->whereIn('chart_of_accounts.account_type', $accountTypes)
            ->where('journal_entries.period_id', $periodId)
            ->where('journal_entries.status', 'Posted')
            ->groupBy('journal_entry_lines.currency')
            ->select(
                'journal_entry_lines.currency',
                DB::raw('SUM(journal_entry_lines.debit_amount) as total_debit'),
                DB::raw('SUM(journal_entry_lines.credit_amount) as total_credit'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->get()
            ->map(function($item) use ($reportCurrency) {
                $exchangeRate = $this->getExchangeRate($item->currency, $reportCurrency, now()->toDateString());
                return [
                    'currency' => $item->currency,
                    'debit_amount' => $item->total_debit,
                    'credit_amount' => $item->total_credit,
                    'converted_debit' => $item->total_debit * $exchangeRate,
                    'converted_credit' => $item->total_credit * $exchangeRate,
                    'transaction_count' => $item->transaction_count,
                    'exchange_rate' => $exchangeRate
                ];
            })
            ->toArray();
    }

    private function getAgingBucket($daysPastDue, $agingPeriods)
    {
        if ($daysPastDue >= 0) {
            return 'current';
        }
        
        $absDays = abs($daysPastDue);
        foreach ($agingPeriods as $i => $period) {
            if ($absDays <= $period) {
                return $i === 0 ? "1-{$period}_days" : ($agingPeriods[$i-1] + 1) . "-{$period}_days";
            }
        }
        
        return 'over_' . end($agingPeriods) . '_days';
    }

    /**
     * Get account balances by specific currency.
     *
     * @param array $accountTypes
     * @param int $periodId
     * @param string $currency
     * @param float $exchangeRate
     * @param string|null $asOfDate
     * @return array
     */
    private function getAccountBalancesByCurrency($accountTypes, $periodId, $currency, $exchangeRate, $asOfDate = null)
    {
        $query = DB::table('chart_of_accounts')
            ->join('journal_entry_lines', 'chart_of_accounts.account_id', '=', 'journal_entry_lines.account_id')
            ->join('journal_entries', 'journal_entry_lines.journal_id', '=', 'journal_entries.journal_id')
            ->where('journal_entries.period_id', $periodId)
            ->where('journal_entries.status', 'Posted')
            ->where('journal_entry_lines.currency', $currency)
            ->whereIn('chart_of_accounts.account_type', $accountTypes);
            
        if ($asOfDate) {
            $query->where('journal_entries.entry_date', '<=', $asOfDate);
        }
        
        $result = $query->select(
                DB::raw('SUM(IFNULL(journal_entry_lines.debit_amount, 0)) as total_debit'),
                DB::raw('SUM(IFNULL(journal_entry_lines.credit_amount, 0)) as total_credit'),
                DB::raw('SUM(IFNULL(journal_entry_lines.foreign_amount, 0)) as total_foreign'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->first();
            
        return [
            'debit_amount' => $result->total_debit ?? 0,
            'credit_amount' => $result->total_credit ?? 0,
            'foreign_amount' => $result->total_foreign ?? 0,
            'converted_debit' => ($result->total_debit ?? 0) * $exchangeRate,
            'converted_credit' => ($result->total_credit ?? 0) * $exchangeRate,
            'transaction_count' => $result->transaction_count ?? 0,
            'exchange_rate' => $exchangeRate
        ];
    }

    /**
     * Calculate unrealized gains/losses from currency fluctuation.
     *
     * @param array $currencies
     * @param array $exchangeRates
     * @param string $asOfDate
     * @return array
     */
    private function calculateUnrealizedGains($currencies, $exchangeRates, $asOfDate)
    {
        $baseCurrency = config('app.base_currency', 'USD');
        $unrealizedGains = [];
        
        foreach ($currencies as $currency) {
            if ($currency === $baseCurrency) continue;
            
            // Get historical rate (e.g., from beginning of year)
            $yearStart = date('Y-01-01', strtotime($asOfDate));
            $historicalRate = $this->getExchangeRate($currency, $baseCurrency, $yearStart);
            $currentRate = $exchangeRates[$currency];
            
            // Get foreign currency balances
            $foreignBalances = DB::table('journal_entry_lines')
                ->join('journal_entries', 'journal_entry_lines.journal_id', '=', 'journal_entries.journal_id')
                ->where('journal_entry_lines.currency', $currency)
                ->where('journal_entries.status', 'Posted')
                ->where('journal_entries.entry_date', '<=', $asOfDate)
                ->select(
                    DB::raw('SUM(IFNULL(journal_entry_lines.foreign_amount, 0)) as total_foreign_balance')
                )
                ->first();
                
            $foreignBalance = $foreignBalances->total_foreign_balance ?? 0;
            
            if ($foreignBalance != 0) {
                $historicalValue = $foreignBalance * $historicalRate;
                $currentValue = $foreignBalance * $currentRate;
                $unrealizedGain = $currentValue - $historicalValue;
                
                $unrealizedGains[$currency] = [
                    'foreign_balance' => $foreignBalance,
                    'historical_rate' => $historicalRate,
                    'current_rate' => $currentRate,
                    'historical_value' => $historicalValue,
                    'current_value' => $currentValue,
                    'unrealized_gain' => $unrealizedGain,
                    'gain_percent' => $historicalValue != 0 ? ($unrealizedGain / abs($historicalValue)) * 100 : 0
                ];
            }
        }
        
        return $unrealizedGains;
    }

    /**
     * Assess impact of exchange rate variance.
     *
     * @param float $variancePercent
     * @return string
     */
    private function assessImpact($variancePercent)
    {
        $absVariance = abs($variancePercent);
        
        if ($absVariance >= 20) {
            return 'very_high';
        } elseif ($absVariance >= 10) {
            return 'high';
        } elseif ($absVariance >= 5) {
            return 'moderate';
        } elseif ($absVariance >= 2) {
            return 'low';
        } else {
            return 'minimal';
        }
    }
}