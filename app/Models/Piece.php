<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Piece extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_x', 'pos_y', 'type', 'color', 'game_id', 'user_id'
    ];

    public function game() {
        return $this->belongsTo(Game::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
