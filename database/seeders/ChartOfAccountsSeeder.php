<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('ChartOfAccount')->truncate();

        // Accounts data from Excel file
        $accounts = [
            // EQUITY ACCOUNTS
            [
                'account_code' => '400-0000',
                'name' => 'SHARE CAPITAL',
                'account_type' => 'Equity',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '410-0000',
                'name' => 'RETAINED EARNING B/F',
                'account_type' => 'Equity',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '420-0000',
                'name' => 'ACCUMULATED PROFIT/LOSS',
                'account_type' => 'Equity',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],

            // FIXED ASSETS
            [
                'account_code' => '200-0000',
                'name' => 'FIXED ASSETS NETT',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '200-0010',
                'name' => 'LAND & LEGAL',
                'account_type' => 'Asset',
                'parent_account_code' => '200-0000',
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 3
            ],
            [
                'account_code' => '200-0020',
                'name' => 'BUILDINGS',
                'account_type' => 'Asset',
                'parent_account_code' => '200-0000',
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 3
            ],
            [
                'account_code' => '200-0025',
                'name' => 'ACC BUILDINGS',
                'account_type' => 'Asset',
                'parent_account_code' => '200-0000',
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 3
            ],
            [
                'account_code' => '200-0030',
                'name' => 'ELECTRICAL & INSTALLATION',
                'account_type' => 'Asset',
                'parent_account_code' => '200-0000',
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 3
            ],
            [
                'account_code' => '200-0035',
                'name' => 'ACC ELECTRICAL & INSTALLATION',
                'account_type' => 'Asset',
                'parent_account_code' => '200-0000',
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 3
            ],
            [
                'account_code' => '200-0040',
                'name' => 'PLANT & MACHINERY',
                'account_type' => 'Asset',
                'parent_account_code' => '200-0000',
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 3
            ],
            [
                'account_code' => '200-0045',
                'name' => 'ACC PLANT & MACHINERY',
                'account_type' => 'Asset',
                'parent_account_code' => '200-0000',
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 3
            ],
            [
                'account_code' => '200-0050',
                'name' => 'BUSINESS COMPUTER',
                'account_type' => 'Asset',
                'parent_account_code' => '200-0000',
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 3
            ],
            [
                'account_code' => '200-0055',
                'name' => 'ACC BUSINESS COMPUTER',
                'account_type' => 'Asset',
                'parent_account_code' => '200-0000',
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 3
            ],
            [
                'account_code' => '200-0060',
                'name' => 'FURNITURE & FITTINGS',
                'account_type' => 'Asset',
                'parent_account_code' => '200-0000',
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 3
            ],
            [
                'account_code' => '200-0065',
                'name' => 'ACC FURNITURE & FITTINGS',
                'account_type' => 'Asset',
                'parent_account_code' => '200-0000',
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 3
            ],
            [
                'account_code' => '200-0070',
                'name' => 'VEHICLES',
                'account_type' => 'Asset',
                'parent_account_code' => '200-0000',
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 3
            ],
            [
                'account_code' => '200-0075',
                'name' => 'ACC VEHICLES',
                'account_type' => 'Asset',
                'parent_account_code' => '200-0000',
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 3
            ],
            [
                'account_code' => '200-0080',
                'name' => 'OFFICE EQUIPMENT',
                'account_type' => 'Asset',
                'parent_account_code' => '200-0000',
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 3
            ],
            [
                'account_code' => '200-0085',
                'name' => 'ACC OFFICE EQUIPMENT',
                'account_type' => 'Asset',
                'parent_account_code' => '200-0000',
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 3
            ],
            [
                'account_code' => '200-0090',
                'name' => 'TOOLS & EQUIPMENT',
                'account_type' => 'Asset',
                'parent_account_code' => '200-0000',
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 3
            ],
            [
                'account_code' => '200-0095',
                'name' => 'ACC TOOLS & EQUIPMENT',
                'account_type' => 'Asset',
                'parent_account_code' => '200-0000',
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 3
            ],
            [
                'account_code' => '200-0100',
                'name' => 'LEASEHOLD IMPROVEMENT',
                'account_type' => 'Asset',
                'parent_account_code' => '200-0000',
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 3
            ],
            [
                'account_code' => '200-0105',
                'name' => 'ACC LEASEHOLD IMPROVEMENT',
                'account_type' => 'Asset',
                'parent_account_code' => '200-0000',
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 3
            ],

            // OTHER ASSETS
            [
                'account_code' => '210-0000',
                'name' => 'OTHER ASSETS',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '220-0000',
                'name' => 'GOODWILL',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '230-0000',
                'name' => 'INVESTMENT',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],

            // CURRENT ASSETS
            [
                'account_code' => '100-0010',
                'name' => 'CASH IN HAND',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '100-0020',
                'name' => 'PETTY CASH',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '100-0030',
                'name' => 'CASH IN TRANSIT',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],

            // BANK ACCOUNTS
            [
                'account_code' => '110-0010',
                'name' => 'BANK BCA',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '110-0020',
                'name' => 'BANK MANDIRI',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '110-0030',
                'name' => 'BANK BNI',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '110-0040',
                'name' => 'BANK BRI',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '110-0050',
                'name' => 'BANK MAYBANK',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '110-0060',
                'name' => 'BANK PERMATA',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '110-0070',
                'name' => 'BANK PANIN',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '110-0080',
                'name' => 'BANK OCBC NISP',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '110-0090',
                'name' => 'BANK CIMB NIAGA',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '110-0100',
                'name' => 'BANK UOB',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '110-0110',
                'name' => 'BANK HSBC',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '110-0120',
                'name' => 'BANK DANAMON',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],

            // RECEIVABLES
            [
                'account_code' => '120-0010',
                'name' => 'ACCOUNTS RECEIVABLE',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '120-0020',
                'name' => 'ALLOWANCE FOR DOUBTFUL ACCOUNTS',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '120-0030',
                'name' => 'NOTES RECEIVABLE',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '120-0040',
                'name' => 'ADVANCE TO SUPPLIERS',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '120-0050',
                'name' => 'ADVANCE TO EMPLOYEES',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '120-0060',
                'name' => 'OTHER RECEIVABLES',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],

            // INVENTORY
            [
                'account_code' => '130-0010',
                'name' => 'INVENTORY',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],

            // PREPAID EXPENSES
            [
                'account_code' => '140-0010',
                'name' => 'PREPAID EXPENSES',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],

            // TAX ASSETS
            [
                'account_code' => '150-0010',
                'name' => 'VAT IN',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '150-0020',
                'name' => 'INCOME TAX PREPAID',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '150-0030',
                'name' => 'TAX ARTICLE 21',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '150-0040',
                'name' => 'TAX ARTICLE 22',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '150-0050',
                'name' => 'TAX ARTICLE 23',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '150-0060',
                'name' => 'TAX ARTICLE 25',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '150-0070',
                'name' => 'TAX ARTICLE 26',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '150-0080',
                'name' => 'TAX ARTICLE 29',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '150-0090',
                'name' => 'TAX ARTICLE 4(2)',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '150-0100',
                'name' => 'TAX ARTICLE 15',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '150-0110',
                'name' => 'FINAL TAX',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '150-0120',
                'name' => 'OTHER TAX',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '150-0130',
                'name' => 'RESTITUTION TAX',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '150-0140',
                'name' => 'DEPOSIT',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '150-0150',
                'name' => 'CLAIM RECEIVABLE',
                'account_type' => 'Asset',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],

            // CURRENT LIABILITIES
            [
                'account_code' => '300-0010',
                'name' => 'ACCOUNTS PAYABLE',
                'account_type' => 'Liability',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '300-0020',
                'name' => 'NOTES PAYABLE',
                'account_type' => 'Liability',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '300-0030',
                'name' => 'ACCRUED EXPENSES',
                'account_type' => 'Liability',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '300-0040',
                'name' => 'CUSTOMER DEPOSITS',
                'account_type' => 'Liability',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '300-0050',
                'name' => 'ADVANCES FROM CUSTOMERS',
                'account_type' => 'Liability',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '300-0060',
                'name' => 'OTHER PAYABLES',
                'account_type' => 'Liability',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '300-0070',
                'name' => 'SALARIES PAYABLE',
                'account_type' => 'Liability',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '300-0080',
                'name' => 'COMMISSIONS PAYABLE',
                'account_type' => 'Liability',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '300-0090',
                'name' => 'EMPLOYEE BENEFITS PAYABLE',
                'account_type' => 'Liability',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '300-0100',
                'name' => 'INTEREST PAYABLE',
                'account_type' => 'Liability',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '300-0110',
                'name' => 'BANK OVERDRAFT',
                'account_type' => 'Liability',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '300-0120',
                'name' => 'SHORT TERM LOANS',
                'account_type' => 'Liability',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '300-0130',
                'name' => 'BANK LOANS',
                'account_type' => 'Liability',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '300-0140',
                'name' => 'LEASING PAYABLE',
                'account_type' => 'Liability',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],

            // TAX LIABILITIES  
            [
                'account_code' => '310-0010',
                'name' => 'VAT OUT',
                'account_type' => 'Liability',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '310-0020',
                'name' => 'INCOME TAX PAYABLE',
                'account_type' => 'Liability',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '310-0030',
                'name' => 'TAX ARTICLE 21 PAYABLE',
                'account_type' => 'Liability',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '310-0040',
                'name' => 'TAX ARTICLE 23 PAYABLE',
                'account_type' => 'Liability',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '310-0050',
                'name' => 'TAX ARTICLE 25 PAYABLE',
                'account_type' => 'Liability',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '310-0060',
                'name' => 'TAX ARTICLE 26 PAYABLE',
                'account_type' => 'Liability',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '310-0070',
                'name' => 'TAX ARTICLE 29 PAYABLE',
                'account_type' => 'Liability',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '310-0080',
                'name' => 'FINAL TAX PAYABLE',
                'account_type' => 'Liability',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],

            // LONG TERM LIABILITIES
            [
                'account_code' => '320-0010',
                'name' => 'LONG TERM LOANS',
                'account_type' => 'Liability',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '320-0020',
                'name' => 'BONDS PAYABLE',
                'account_type' => 'Liability',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],

            // SALES REVENUE
            [
                'account_code' => '500-0010',
                'name' => 'SALES PUMPS',
                'account_type' => 'Revenue',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '500-0020',
                'name' => 'SALES VALVES',
                'account_type' => 'Revenue',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '500-0030',
                'name' => 'SALES SERVICES',
                'account_type' => 'Revenue',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],
            [
                'account_code' => '500-0040',
                'name' => 'SALES SPARE PARTS',
                'account_type' => 'Revenue',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],

            // SALES ADJUSTMENTS
            [
                'account_code' => '600-0000',
                'name' => 'SALES ADJUSTMENTS',
                'account_type' => 'Revenue',
                'parent_account_code' => null,
                'default_currency' => 'IDR',
                'is_active' => true,
                'allow_multi_currency' => false,
                'level' => 2
            ],

            // COMPLETE 182 ACCOUNTS from PT. Armstrong Industri Indonesia Excel File
            
            // Replace the above sample accounts with the complete array from "Complete Accounts Array Data - 182 Accounts" artifact
            // Copy the entire $accounts array from
        ];

        // Insert accounts in the correct order (parents first)
        $this->insertAccountsWithParentLookup($accounts);
    }

    /**
     * Insert accounts with proper parent-child relationships
     */
    private function insertAccountsWithParentLookup(array $accounts): void
    {
        $parentAccountMap = [];
        
        // First pass: Insert all accounts without parent relationships
        foreach ($accounts as $account) {
            $accountData = [
                'account_code' => $account['account_code'],
                'name' => $account['name'],
                'account_type' => $account['account_type'],
                'parent_account_id' => null, // Will be updated in second pass
                'default_currency' => $account['default_currency'],
                'is_active' => $account['is_active'],
                'allow_multi_currency' => $account['allow_multi_currency'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];

$accountId = DB::table('ChartOfAccount')->insertGetId($accountData, 'account_id');
            $parentAccountMap[$account['account_code']] = $accountId;
        }

        // Second pass: Update parent relationships
        foreach ($accounts as $account) {
            if (!empty($account['parent_account_code'])) {
                $parentId = $parentAccountMap[$account['parent_account_code']] ?? null;
                
                if ($parentId) {
                    DB::table('ChartOfAccount')
                        ->where('account_code', $account['account_code'])
                        ->update(['parent_account_id' => $parentId]);
                }
            }
        }
    }
}