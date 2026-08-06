<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'action_type', 'description'];

    // Relationship: A log belongs to the user who performed the action
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper function to easily create logs from anywhere in your controllers
    public static function log($action_type, $description)
    {
        self::create([
            'user_id' => auth()->id(), // Gets the currently logged-in user's ID
            'action_type' => $action_type,
            'description' => $description,
        ]);
    }
}