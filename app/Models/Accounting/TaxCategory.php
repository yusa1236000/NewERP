<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaxCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_code', 'category_name', 'description', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function taxCodes()
    {
        return $this->belongsToMany(TaxCode::class, 'tax_category_tax_codes')
                    ->withPivot('is_default', 'sequence')
                    ->orderBy('pivot_sequence');
    }

    public function defaultTaxCodes()
    {
        return $this->belongsToMany(TaxCode::class, 'tax_category_tax_codes')
                    ->wherePivot('is_default', true)
                    ->orderBy('pivot_sequence');
    }

    public function activeTaxCodes()
    {
        return $this->taxCodes()->where('tax_codes.is_active', true);
    }

    public function items()
    {
        return $this->hasMany(\App\Models\Item::class, 'sale_tax_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function calculateTotalTaxRate()
    {
        return $this->activeTaxCodes()
                   ->where('calculation_type', 'percentage')
                   ->sum('tax_rate');
    }

    public function getDefaultTaxesForAmount($amount, $isInclusive = false)
    {
        $taxes = [];
        foreach ($this->defaultTaxCodes as $taxCode) {
            $taxAmount = $taxCode->calculateTax($amount, $isInclusive);
            $taxes[] = [
                'tax_code_id' => $taxCode->id,
                'tax_code' => $taxCode->tax_code,
                'tax_name' => $taxCode->tax_name,
                'tax_rate' => $taxCode->tax_rate,
                'tax_amount' => $taxAmount,
                'base_amount' => $amount
            ];
        }
        return $taxes;
    }
}