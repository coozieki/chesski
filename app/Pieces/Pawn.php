<?php

namespace App\Pieces;

use App\Models\Piece as ModelPiece;
use Illuminate\Support\Facades\DB;

class Pawn extends Piece {
    public function getPieceMoves($pieces = null): array
    {
        $result = [];
        if (!$pieces)
            $pieces = DB::table('pieces')->where('game_id', $this->model->game_id)->get();

        $px = $this->model->pos_x;
        $py = $this->model->pos_y;
        $clr = $this->model->color;

        if ($clr == Piece::COLOR_WHITE)
            $incY = 1;
        else
            $incY = -1;

        if ((($py == 2 && $clr == Piece::COLOR_WHITE) || ($py == 7 && $clr == Piece::COLOR_BLACK))
            && !$pieces->filter(function($val) use ($px, $py, $incY) {
                return ($val->pos_x == $px && $val->pos_y == $py + 2*$incY)
                        || ($val->pos_x == $px && $val->pos_y == $py + $incY);
            })->count())
            $result[] = ['x' => $px, 'y' => $py + 2*$incY];

        if (!$pieces->where('pos_x', $px)->where('pos_y', $py + $incY)->count() && !$this->isOutOfField($px, $py + $incY))
            $result[] = ['x' => $px, 'y' => $py + $incY];

        foreach([1, -1] as $incX)
            if ($pieces->where('pos_x', $px + $incX)->where('pos_y', $py + $incY)->where('color', $clr == Piece::COLOR_WHITE ? Piece::COLOR_BLACK : Piece::COLOR_WHITE)->count() && !$this->isOutOfField($px + $incX, $py + $incY))
                $result[] = ['x' => $px + $incX, 'y' => $py + $incY];

        return $result;
    }
}
