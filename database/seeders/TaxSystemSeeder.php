<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Accounting\TaxCode;
use App\Models\Accounting\TaxCategory;
use App\Models\Accounting\TaxConfiguration;

class TaxSystemSeeder extends Seeder
{
    public function run(): void
    {
        // Create Tax Codes
        $vatTax = TaxCode::create([
            'tax_code' => 'VAT10',
            'tax_name' => 'Value Added Tax 10%',
            'tax_rate' => 10.0000,
            'tax_type' => 'vat',
            'calculation_type' => 'percentage',
            'scope' => 'both',
            'is_active' => true,
            'effective_from' => now()->startOfYear(),
            'description' => 'Standard VAT rate'
        ]);

        $salesTax = TaxCode::create([
            'tax_code' => 'ST5',
            'tax_name' => 'Sales Tax 5%',
            'tax_rate' => 5.0000,
            'tax_type' => 'sales_tax',
            'calculation_type' => 'percentage',
            'scope' => 'sale',
            'is_active' => true,
            'effective_from' => now()->startOfYear(),
            'description' => 'State sales tax'
        ]);

        $serviceTax = TaxCode::create([
            'tax_code' => 'SVC8',
            'tax_name' => 'Service Tax 8%',
            'tax_rate' => 8.0000,
            'tax_type' => 'service_tax',
            'calculation_type' => 'percentage',
            'scope' => 'both',
            'is_active' => true,
            'effective_from' => now()->startOfYear(),
            'description' => 'Service tax for professional services'
        ]);

        $gstTax = TaxCode::create([
            'tax_code' => 'GST12',
            'tax_name' => 'Goods & Services Tax 12%',
            'tax_rate' => 12.0000,
            'tax_type' => 'gst',
            'calculation_type' => 'percentage',
            'scope' => 'both',
            'is_active' => true,
            'effective_from' => now()->startOfYear(),
            'description' => 'Standard GST rate'
        ]);

        // Create Tax Categories
        $standardCategory = TaxCategory::create([
            'category_code' => 'STANDARD',
            'category_name' => 'Standard Tax Category',
            'description' => 'Standard tax rates for most products',
            'is_active' => true
        ]);

        $serviceCategory = TaxCategory::create([
            'category_code' => 'SERVICE',
            'category_name' => 'Service Tax Category',
            'description' => 'Tax rates for services',
            'is_active' => true
        ]);

        $exemptCategory = TaxCategory::create([
            'category_code' => 'EXEMPT',
            'category_name' => 'Tax Exempt Category',
            'description' => 'No taxes applied',
            'is_active' => true
        ]);

        // Attach Tax Codes to Categories
        $standardCategory->taxCodes()->attach([
            $vatTax->id => ['is_default' => true, 'sequence' => 1],
            $salesTax->id => ['is_default' => false, 'sequence' => 2]
        ]);

        $serviceCategory->taxCodes()->attach([
            $serviceTax->id => ['is_default' => true, 'sequence' => 1],
            $vatTax->id => ['is_default' => false, 'sequence' => 2]
        ]);

        // Create Tax Configuration
        TaxConfiguration::create([
            'company_name' => 'Demo Company Ltd.',
            'tax_jurisdiction' => 'United States',
            'tax_authority' => 'Internal Revenue Service',
            'tax_registration_number' => 'US-TAX-123456789',
            'default_sale_tax_category_id' => $standardCategory->id,
            'default_purchase_tax_category_id' => $standardCategory->id,
            'tax_inclusive_pricing' => false,
            'rounding_method' => 'round',
            'rounding_precision' => 2,
            'compound_tax_calculation' => 'sequential',
            'is_active' => true
        ]);
    }
}