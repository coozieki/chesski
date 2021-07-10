<?php

namespace App\Pieces;

use App\Models\Piece as ModelPiece;

class Bishop extends Piece {
    public function getPieceMoves($pieces = null): array
    {
        $result = [];
        if (!$pieces)
            $pieces = ModelPiece::where('game_id', $this->model->game_id)->get();

        $px = $this->model->pos_x;
        $py = $this->model->pos_y;
        $clr = $this->model->color;

        $check1 = function($i) use ($py, $px, $pieces, $clr, &$result) {
            if ($pieces->where('pos_x', $i)->where('pos_y', $py + $i - $px)->where('color', $clr)->count())
                return false;

            $result[] = ['x' => $i, 'y' => $py + $i - $px];

            if ($pieces->where('pos_x', $i)->where('pos_y', $py + $i - $px)->where('color', $clr == Piece::COLOR_WHITE ? Piece::COLOR_BLACK : Piece::COLOR_WHITE)->count())
                return false;

            return true;
        };

        $check2 = function($i) use ($py, $px, $pieces, $clr, &$result) {
            if ($pieces->where('pos_x', $i)->where('pos_y', $py - $i + $px)->where('color', $clr)->count())
                return false;

            $result[] = ['x' => $i, 'y' => $py - $i + $px];

            if ($pieces->where('pos_x', $i)->where('pos_y', $py - $i + $px)->where('color', $clr == Piece::COLOR_WHITE ? Piece::COLOR_BLACK : Piece::COLOR_WHITE)->count())
                return false;

            return true;
        };

        for($i=$px+1;$i<$this->getGameRules()->getFieldLength()+1;$i++) {
            if (!$check1($i))
                break;
        }

        for($i=$px-1;$i>0;$i--) {
            if (!$check1($i))
                break;
        }

        for($i=$px+1;$i<$this->getGameRules()->getFieldLength()+1;$i++) {
            if (!$check2($i))
                break;
        }

        for($i=$px-1;$i>0;$i--) {
            if (!$check2($i))
                break;
        }


        return $result;
    }
}
