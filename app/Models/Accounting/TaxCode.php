<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaxCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'tax_code', 'tax_name', 'tax_rate', 'tax_type', 'calculation_type',
        'scope', 'is_active', 'is_compound', 'include_base_amount', 'account_id',
        'effective_from', 'effective_to', 'description'
    ];

    protected $casts = [
        'tax_rate' => 'decimal:4',
        'is_active' => 'boolean',
        'is_compound' => 'boolean',
        'include_base_amount' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function taxCategories()
    {
        return $this->belongsToMany(TaxCategory::class, 'tax_category_tax_codes')
                    ->withPivot('is_default', 'sequence')
                    ->orderBy('pivot_sequence');
    }

    public function documentTaxLines()
    {
        return $this->hasMany(DocumentTaxLine::class);
    }

    public function calculateTax($baseAmount, $isInclusive = false)
    {
        if ($this->calculation_type === 'percentage') {
            if ($isInclusive) {
                return $baseAmount * ($this->tax_rate / (100 + $this->tax_rate));
            }
            return $baseAmount * ($this->tax_rate / 100);
        }
        return $this->tax_rate; // Fixed amount
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('effective_from', '<=', now())
                    ->where(function($q) {
                        $q->whereNull('effective_to')
                          ->orWhere('effective_to', '>=', now());
                    });
    }

    public function scopeForScope($query, $scope)
    {
        return $query->whereIn('scope', [$scope, 'both']);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('tax_type', $type);
    }

    public function isValidForDate($date = null)
    {
        $checkDate = $date ? Carbon::parse($date) : now();
        
        if ($checkDate->lt($this->effective_from)) {
            return false;
        }
        
        if ($this->effective_to && $checkDate->gt($this->effective_to)) {
            return false;
        }
        
        return true;
    }
}