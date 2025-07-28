<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\TaxConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaxConfigurationController extends Controller
{
    public function index()
    {
        $configuration = TaxConfiguration::with([
            'defaultSaleTaxCategory.taxCodes', 
            'defaultPurchaseTaxCategory.taxCodes'
        ])->active()->first();
        
        return response()->json(['data' => $configuration]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:100',
            'tax_jurisdiction' => 'required|string|max:100',
            'tax_authority' => 'required|string|max:100',
            'tax_registration_number' => 'nullable|string|max:50',
            'default_sale_tax_category_id' => 'nullable|exists:tax_categories,id',
            'default_purchase_tax_category_id' => 'nullable|exists:tax_categories,id',
            'tax_inclusive_pricing' => 'boolean',
            'rounding_method' => 'required|in:round,round_up,round_down',
            'rounding_precision' => 'required|integer|min:0|max:6',
            'compound_tax_calculation' => 'required|in:sequential,parallel'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Deactivate existing configurations
        TaxConfiguration::where('is_active', true)->update(['is_active' => false]);

        $configuration = TaxConfiguration::create(array_merge(
            $request->all(),
            ['is_active' => true]
        ));

        return response()->json([
            'data' => $configuration->load(['defaultSaleTaxCategory', 'defaultPurchaseTaxCategory']), 
            'message' => 'Tax configuration created successfully'
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $configuration = TaxConfiguration::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:100',
            'tax_jurisdiction' => 'required|string|max:100',
            'tax_authority' => 'required|string|max:100',
            'tax_registration_number' => 'nullable|string|max:50',
            'default_sale_tax_category_id' => 'nullable|exists:tax_categories,id',
            'default_purchase_tax_category_id' => 'nullable|exists:tax_categories,id',
            'tax_inclusive_pricing' => 'boolean',
            'rounding_method' => 'required|in:round,round_up,round_down',
            'rounding_precision' => 'required|integer|min:0|max:6',
            'compound_tax_calculation' => 'required|in:sequential,parallel'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $configuration->update($request->all());
        
        return response()->json([
            'data' => $configuration->load(['defaultSaleTaxCategory', 'defaultPurchaseTaxCategory']), 
            'message' => 'Tax configuration updated successfully'
        ]);
    }

    public function testRounding(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'rounding_method' => 'required|in:round,round_up,round_down',
            'rounding_precision' => 'required|integer|min:0|max:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $config = new TaxConfiguration();
        $config->rounding_method = $request->rounding_method;
        $config->rounding_precision = $request->rounding_precision;
        
        $roundedAmount = $config->roundTaxAmount($request->amount);

        return response()->json([
            'data' => [
                'original_amount' => $request->amount,
                'rounded_amount' => $roundedAmount,
                'rounding_method' => $request->rounding_method,
                'rounding_precision' => $request->rounding_precision
            ]
        ]);
    }
}