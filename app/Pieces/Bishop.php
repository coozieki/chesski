<?php

namespace App\Pieces;

use App\Models\Piece as ModelPiece;

class Bishop extends Piece {
    protected function getPieceMoves(): array
    {
        $result = [];
        $pieces = ModelPiece::where('game_id', $this->model->game_id)->get();

        $px = $this->model->pos_x;
        $py = $this->model->pos_y;
        $clr = $this->model->color;

        return [];
    }
}
