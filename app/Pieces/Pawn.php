<?php

namespace App\Pieces;

use App\Models\Piece as ModelPiece;

class Pawn extends Piece {
    protected function getPieceMoves(): array
    {
        $result = [];
        $pieces = ModelPiece::where('game_id', $this->model->game_id)->get();

        $px = $this->model->pos_x;
        $py = $this->model->pos_y;
        $clr = $this->model->color;

        if ($clr == Piece::COLOR_WHITE)
            $incY = 1;
        else
            $incY = -1;

        if (!$pieces->where('pos_x', $px)->where('pos_y', $py + $incY)->count() && !$this->isOutOfField($px, $py + $incY))
            $result[] = ['x' => $px, 'y' => $py + $incY];

        foreach([1, -1] as $incX)
            if ($pieces->where('pos_x', $px + $incX)->where('pos_y', $py + $incY)->where('color', $clr == Piece::COLOR_WHITE ? Piece::COLOR_BLACK : Piece::COLOR_WHITE)->count() && !$this->isOutOfField($px + $incX, $py + $incY))
                $result[] = ['x' => $px + $incX, 'y' => $py + $incY];

        return $result;
    }
}
