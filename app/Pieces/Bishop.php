<?php

namespace App\Pieces;

use App\Models\Piece as ModelPiece;

class Bishop extends Piece {
    public function getPieceMoves($pieces = null): array
    {
        $result = [];
        if (!$pieces)
            $pieces = ModelPiece::where('game_id', $this->model->game_id)->get()->all();

        $px = $this->model->pos_x;
        $py = $this->model->pos_y;
        $clr = $this->model->color;

        $check1 = function($i) use ($py, $px, $pieces, $clr, &$result) {
            if ($this->checkHasPiecesAtCell($pieces, $i, $py + $i - $px, [], $clr))
                return false;

            $result[] = ['x' => $i, 'y' => $py + $i - $px];

            if ($this->checkHasPiecesAtCell($pieces, $i, $py + $i - $px, [], $clr == Piece::COLOR_WHITE ? Piece::COLOR_BLACK : Piece::COLOR_WHITE))
                return false;

            return true;
        };

        $check2 = function($i) use ($py, $px, $pieces, $clr, &$result) {
            if ($this->checkHasPiecesAtCell($pieces, $i, $py - $i + $px, [], $clr))
                return false;

            $result[] = ['x' => $i, 'y' => $py - $i + $px];

            if ($this->checkHasPiecesAtCell($pieces, $i, $py - $i + $px, [], $clr == Piece::COLOR_WHITE ? Piece::COLOR_BLACK : Piece::COLOR_WHITE))
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
