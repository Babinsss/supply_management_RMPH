<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PettyCashTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'amount',
        'description',
        'receipt_reference',
        'supply_id',
        'processed_by',
    ];

    // Link back to the supply item (if it was an inventory purchase)
    public function supply()
    {
        return $this->belongsTo(Supply::class);
    }

    // Track which user processed the cash
    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}