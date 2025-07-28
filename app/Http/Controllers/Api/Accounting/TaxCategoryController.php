<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\TaxCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TaxCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = TaxCategory::with('taxCodes');

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('category_code', 'like', "%{$search}%")
                  ->orWhere('category_name', 'like', "%{$search}%");
            });
        }

        $categories = $query->orderBy('category_code')
                           ->paginate($request->input('per_page', 15));

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_code' => 'required|string|max:20|unique:tax_categories',
            'category_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'tax_codes' => 'array',
            'tax_codes.*.tax_code_id' => 'required|exists:tax_codes,id',
            'tax_codes.*.is_default' => 'boolean',
            'tax_codes.*.sequence' => 'integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $category = TaxCategory::create($request->only([
                'category_code', 'category_name', 'description', 'is_active'
            ]));

            if ($request->has('tax_codes')) {
                $taxCodes = collect($request->tax_codes)->mapWithKeys(function ($item) {
                    return [$item['tax_code_id'] => [
                        'is_default' => $item['is_default'] ?? false,
                        'sequence' => $item['sequence'] ?? 0
                    ]];
                });
                $category->taxCodes()->sync($taxCodes);
            }

            DB::commit();
            return response()->json([
                'data' => $category->load('taxCodes'), 
                'message' => 'Tax category created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Failed to create tax category'], 500);
        }
    }

    public function show($id)
    {
        $category = TaxCategory::with('taxCodes')->findOrFail($id);
        return response()->json(['data' => $category]);
    }

    public function update(Request $request, $id)
    {
        $category = TaxCategory::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'category_code' => 'required|string|max:20|unique:tax_categories,category_code,' . $id,
            'category_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'tax_codes' => 'array',
            'tax_codes.*.tax_code_id' => 'required|exists:tax_codes,id',
            'tax_codes.*.is_default' => 'boolean',
            'tax_codes.*.sequence' => 'integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $category->update($request->only([
                'category_code', 'category_name', 'description', 'is_active'
            ]));

            if ($request->has('tax_codes')) {
                $taxCodes = collect($request->tax_codes)->mapWithKeys(function ($item) {
                    return [$item['tax_code_id'] => [
                        'is_default' => $item['is_default'] ?? false,
                        'sequence' => $item['sequence'] ?? 0
                    ]];
                });
                $category->taxCodes()->sync($taxCodes);
            }

            DB::commit();
            return response()->json([
                'data' => $category->load('taxCodes'), 
                'message' => 'Tax category updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Failed to update tax category'], 500);
        }
    }

    public function destroy($id)
    {
        $category = TaxCategory::findOrFail($id);
        
        // Check if category is in use
        if ($category->items()->exists()) {
            return response()->json([
                'message' => 'Cannot delete tax category. It is being used by items.'
            ], 422);
        }
        
        $category->delete();
        return response()->json(['message' => 'Tax category deleted successfully']);
    }

    public function getDefaultTaxCodes($id)
    {
        $category = TaxCategory::findOrFail($id);
        $defaultTaxCodes = $category->defaultTaxCodes;
        return response()->json(['data' => $defaultTaxCodes]);
    }

    public function calculateCategoryTax(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'base_amount' => 'required|numeric|min:0',
            'is_inclusive' => 'boolean',
            'use_default_only' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $category = TaxCategory::findOrFail($id);
        $useDefaultOnly = $request->boolean('use_default_only', true);
        $isInclusive = $request->boolean('is_inclusive', false);
        
        $taxCodes = $useDefaultOnly ? $category->defaultTaxCodes : $category->activeTaxCodes;
        $taxes = $category->getDefaultTaxesForAmount($request->base_amount, $isInclusive);

        return response()->json(['data' => $taxes]);
    }
}