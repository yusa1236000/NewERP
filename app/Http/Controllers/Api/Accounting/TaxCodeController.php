<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\TaxCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaxCodeController extends Controller
{
    public function index(Request $request)
    {
        $query = TaxCode::query();

        // Filters
        if ($request->has('scope')) {
            $query->forScope($request->scope);
        }

        if ($request->has('tax_type')) {
            $query->byType($request->tax_type);
        }

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('tax_code', 'like', "%{$search}%")
                  ->orWhere('tax_name', 'like', "%{$search}%");
            });
        }

        $taxCodes = $query->orderBy('tax_code')
                         ->paginate($request->input('per_page', 15));

        return response()->json($taxCodes);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tax_code' => 'required|string|max:20|unique:tax_codes',
            'tax_name' => 'required|string|max:100',
            'tax_rate' => 'required|numeric|min:0|max:9999.9999',
            'tax_type' => 'required|in:vat,gst,sales_tax,service_tax,withholding_tax,excise_tax',
            'calculation_type' => 'required|in:percentage,fixed_amount',
            'scope' => 'required|in:sale,purchase,both',
            'account_id' => 'nullable|exists:ChartOfAccount,account_id',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'is_active' => 'boolean',
            'is_compound' => 'boolean',
            'include_base_amount' => 'boolean',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $taxCode = TaxCode::create($request->all());
        return response()->json([
            'data' => $taxCode->load('taxCategories'), 
            'message' => 'Tax code created successfully'
        ], 201);
    }

    public function show($id)
    {
        $taxCode = TaxCode::with(['taxCategories'])->findOrFail($id);
        return response()->json(['data' => $taxCode]);
    }

    public function update(Request $request, $id)
    {
        $taxCode = TaxCode::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'tax_code' => 'required|string|max:20|unique:tax_codes,tax_code,' . $id,
            'tax_name' => 'required|string|max:100',
            'tax_rate' => 'required|numeric|min:0|max:9999.9999',
            'tax_type' => 'required|in:vat,gst,sales_tax,service_tax,withholding_tax,excise_tax',
            'calculation_type' => 'required|in:percentage,fixed_amount',
            'scope' => 'required|in:sale,purchase,both',
            'account_id' => 'nullable|exists:ChartOfAccount,account_id',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'is_active' => 'boolean',
            'is_compound' => 'boolean',
            'include_base_amount' => 'boolean',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $taxCode->update($request->all());
        return response()->json([
            'data' => $taxCode->load('taxCategories'), 
            'message' => 'Tax code updated successfully'
        ]);
    }

    public function destroy($id)
    {
        $taxCode = TaxCode::findOrFail($id);
        
        // Check if tax code is in use
        if ($taxCode->documentTaxLines()->exists()) {
            return response()->json([
                'message' => 'Cannot delete tax code. It is being used in transactions.'
            ], 422);
        }
        
        $taxCode->delete();
        return response()->json(['message' => 'Tax code deleted successfully']);
    }

    public function getByScope($scope)
    {
        $taxCodes = TaxCode::active()
                          ->forScope($scope)
                          ->orderBy('tax_code')
                          ->get();
        return response()->json(['data' => $taxCodes]);
    }

    public function calculateTax(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tax_code_id' => 'required|exists:tax_codes,id',
            'base_amount' => 'required|numeric|min:0',
            'is_inclusive' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $taxCode = TaxCode::findOrFail($request->tax_code_id);
        $taxAmount = $taxCode->calculateTax(
            $request->base_amount, 
            $request->boolean('is_inclusive', false)
        );

        return response()->json([
            'data' => [
                'tax_code' => $taxCode->tax_code,
                'base_amount' => $request->base_amount,
                'tax_rate' => $taxCode->tax_rate,
                'tax_amount' => round($taxAmount, 2),
                'total_amount' => $request->base_amount + $taxAmount
            ]
        ]);
    }
}