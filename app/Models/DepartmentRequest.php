<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentRequest extends Model
{
    use HasFactory;

    protected $table = 'department_requests';

    protected $fillable = [
        'batch_id', 'department_name', 'requested_by', 'supply_id', 'quantity', 'purpose', 'status'
    ];

    // Inverse of relationship
    public function supply()
    {
        return $this->belongsTo(Supply::class, 'supply_id');
    }
}