<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supply extends Model
{
    use HasFactory;

    protected $table = 'supplies';

    protected $fillable = [
        'name', 'category', 'description', 'quantity', 'unit', 'reorder_level', 'supplier', 'date_delivered'
    ];

    // One supply has many department requests
    public function requests()
    {
        return $this->hasMany(DepartmentRequest::class, 'supply_id');
    }
}