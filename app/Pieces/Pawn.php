<?php

namespace App\Pieces;

use App\Models\Piece as ModelPiece;
use Illuminate\Support\Facades\DB;

class Pawn extends Piece {
    public function getPieceMoves($pieces = null): array
    {
        $result = [];
        if (!$pieces)
            $pieces = DB::table('pieces')->where('game_id', $this->model->game_id)->get()->all();

        $px = $this->model->pos_x;
        $py = $this->model->pos_y;
        $clr = $this->model->color;

        if ($clr == Piece::COLOR_WHITE)
            $incY = 1;
        else
            $incY = -1;

        if ((($py == 2 && $clr == Piece::COLOR_WHITE) || ($py == 7 && $clr == Piece::COLOR_BLACK))
            && !count(array_filter($pieces, function($val) use ($px, $py, $incY) {
                return ($val->pos_x == $px && $val->pos_y == $py + 2*$incY)
                        || ($val->pos_x == $px && $val->pos_y == $py + $incY);
            })))
            $result[] = ['x' => $px, 'y' => $py + 2*$incY];

        if (!$this->checkHasPiecesAtCell($pieces, $px, $py+$incY) && !$this->isOutOfField($px, $py + $incY))
            $result[] = ['x' => $px, 'y' => $py + $incY];

        foreach([1, -1] as $incX)
            if ($this->checkHasPiecesAtCell($pieces, $px + $incX, $py+$incY, [], $clr == Piece::COLOR_WHITE ? Piece::COLOR_BLACK : Piece::COLOR_WHITE) && !$this->isOutOfField($px + $incX, $py + $incY))
                $result[] = ['x' => $px + $incX, 'y' => $py + $incY];

        return $result;
    }
}
