<?php

namespace App\Pieces;

use App\Models\Piece as ModelPiece;

class Pawn extends Piece {
    protected function getPieceMoves(): array
    {
        $result = [];
        $pieces = ModelPiece::where('game_id', $this->model->game_id)->get();
        if ($this->model->color == Piece::COLOR_WHITE) {
            $asd = $pieces->where('pos_x', $this->model->pos_x)->where('pos_y', $this->model->pos_y + 1);
            if (!$pieces->where('pos_x', $this->model->pos_x)->where('pos_y', $this->model->pos_y + 1)->count())
                $result[] = ['x' => $this->model->pos_x, 'y' => $this->model->pos_y + 1];
        }
        return $result;
    }
}
