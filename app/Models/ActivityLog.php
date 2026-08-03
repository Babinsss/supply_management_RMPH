<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'action', 'details', 'ip_address'];

    // Relationship to see WHICH user did the action
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // MAGIC HELPER: Call this anywhere in your controllers to log an action!
    public static function log($action, $details)
    {
        self::create([
            'user_id' => auth()->check() ? auth()->id() : null,
            'action' => $action,
            'details' => $details,
            'ip_address' => request()->ip(),
        ]);
    }
}