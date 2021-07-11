<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'turn'
    ];

    public function users() {
        return $this->belongsToMany(User::class)->withPivot(['color']);
    }
}
