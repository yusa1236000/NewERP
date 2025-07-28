<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaxConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name', 'tax_jurisdiction', 'tax_authority', 'tax_registration_number',
        'default_sale_tax_category_id', 'default_purchase_tax_category_id',
        'tax_inclusive_pricing', 'rounding_method', 'rounding_precision',
        'compound_tax_calculation', 'is_active'
    ];

    protected $casts = [
        'tax_inclusive_pricing' => 'boolean',
        'rounding_precision' => 'integer',
        'is_active' => 'boolean'
    ];

    public function defaultSaleTaxCategory()
    {
        return $this->belongsTo(TaxCategory::class, 'default_sale_tax_category_id');
    }

    public function defaultPurchaseTaxCategory()
    {
        return $this->belongsTo(TaxCategory::class, 'default_purchase_tax_category_id');
    }

    public function roundTaxAmount($amount)
    {
        $precision = $this->rounding_precision;
        $multiplier = pow(10, $precision);
        
        switch ($this->rounding_method) {
            case 'round_up':
                return ceil($amount * $multiplier) / $multiplier;
            case 'round_down':
                return floor($amount * $multiplier) / $multiplier;
            default:
                return round($amount, $precision);
        }
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function getActive()
    {
        return static::active()->first();
    }
}