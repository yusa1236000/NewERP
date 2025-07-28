<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocumentTaxLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_type', 'document_id', 'line_id', 'tax_code_id',
        'tax_base_amount', 'tax_rate', 'tax_amount', 'base_currency_tax_amount',
        'currency_code', 'exchange_rate', 'sequence', 'is_compound'
    ];

    protected $casts = [
        'tax_base_amount' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'base_currency_tax_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'sequence' => 'integer',
        'is_compound' => 'boolean'
    ];

    public function taxCode()
    {
        return $this->belongsTo(TaxCode::class);
    }

    public function scopeForDocument($query, $documentType, $documentId)
    {
        return $query->where('document_type', $documentType)
                    ->where('document_id', $documentId);
    }

    public function scopeForDocumentLine($query, $documentType, $documentId, $lineId)
    {
        return $query->forDocument($documentType, $documentId)
                    ->where('line_id', $lineId);
    }

    public function scopeHeaderLevel($query)
    {
        return $query->whereNull('line_id');
    }

    public function scopeLineLevel($query)
    {
        return $query->whereNotNull('line_id');
    }
}