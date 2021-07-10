<?php

namespace App\Pieces;

use App\Models\Piece as ModelPiece;

class Rook extends Piece {
    public function getPieceMoves($pieces = null): array
    {
        $result = [];
        if (!$pieces)
            $pieces = ModelPiece::where('game_id', $this->model->game_id)->get()->all();

        $px = $this->model->pos_x;
        $py = $this->model->pos_y;
        $clr = $this->model->color;

        $checkHor = function($i) use ($pieces, &$result, $py, $clr) {
            if ($this->checkHasPiecesAtCell($pieces, $i, $py, [], $clr))
                return true;

            $result[] = ['x'=>$i, 'y'=>$py];

            if ($this->checkHasPiecesAtCell($pieces, $i, $py, [], $clr == Piece::COLOR_WHITE ? Piece::COLOR_BLACK : Piece::COLOR_WHITE))
                return true;

            return false;
        };

        $checkVert = function($i) use ($pieces, &$result, $px, $clr) {
            if ($this->checkHasPiecesAtCell($pieces, $px, $i, [], $clr))
                return true;

            $result[] = ['x'=>$px, 'y'=>$i];

            if ($this->checkHasPiecesAtCell($pieces, $px, $i, [], $clr == Piece::COLOR_WHITE ? Piece::COLOR_BLACK : Piece::COLOR_WHITE))
                return true;

            return false;
        };

        for($i=$px-1; $i>0; $i--)
            if ($checkHor($i))
                break;

        for($i=$px+1; $i<$this->getGameRules()->getFieldLength()+1; $i++)
            if ($checkHor($i))
                break;

        for($i=$py-1; $i>0; $i--)
            if ($checkVert($i))
                break;

        for($i=$py+1; $i<$this->getGameRules()->getFieldLength()+1; $i++)
            if ($checkVert($i))
                break;

        return $result;
    }
}
