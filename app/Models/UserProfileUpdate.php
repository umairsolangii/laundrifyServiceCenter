<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfileUpdate extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'profile_image'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}