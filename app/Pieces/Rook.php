<?php

namespace App\Pieces;

use App\Models\Piece as ModelPiece;

class Rook extends Piece {
    protected function getPieceMoves(): array
    {
        $result = [];
        $pieces = ModelPiece::where('game_id', $this->model->game_id)->get();

        $px = $this->model->pos_x;
        $py = $this->model->pos_y;
        $clr = $this->model->color;

        $checkHor = function($i) use ($pieces, &$result, $py, $clr) {
            if ($pieces->where('pos_x', $i)->where('pos_y', $py)->where('color', $clr)->count())
                return true;

            $result[] = ['x'=>$i, 'y'=>$py];

            if ($pieces->where('pos_x', $i)->where('pos_y', $py)->where('color', $clr == Piece::COLOR_WHITE ? Piece::COLOR_BLACK : Piece::COLOR_WHITE)->count())
                return true;

            return false;
        };

        $checkVert = function($i) use ($pieces, &$result, $px, $clr) {
            if ($pieces->where('pos_x', $px)->where('pos_y', $i)->where('color', $clr)->count())
                return true;

            $result[] = ['x'=>$px, 'y'=>$i];

            if ($pieces->where('pos_x', $px)->where('pos_y', $i)->where('color', $clr == Piece::COLOR_WHITE ? Piece::COLOR_BLACK : Piece::COLOR_WHITE)->count())
                return true;

            return false;
        };

        for($i=$px-1; $i>0; $i--)
            if ($checkHor($i))
                break;

        for($i=$px+1; $i<$this->getGameType()->getFieldLength()+1; $i++)
            if ($checkHor($i))
                break;

        for($i=$py-1; $i>0; $i--)
            if ($checkVert($i))
                break;

        for($i=$py+1; $i<$this->getGameType()->getFieldLength()+1; $i++)
            if ($checkVert($i))
                break;

        return $result;
    }
}
