<?php

namespace App\Pieces;

use App\Models\Piece as ModelPiece;

class King extends Piece {
    public function getPieceMoves($pieces = null): array
    {
        $result = [];
        if (!$pieces)
            $pieces = ModelPiece::where('game_id', $this->model->game_id)->get();

        $px = $this->model->pos_x;
        $py = $this->model->pos_y;
        $clr = $this->model->color;

        foreach([1, -1] as $j) {
            for($i=-1;$i<=1;$i++) {
                if (!$pieces->where('pos_x', $px+$i)->where('pos_y', $py+$j)->where('color', $clr)->count() && !$this->isOutOfField($px+$i, $py+$j))
                    $result[] = ['x' => $px+$i, 'y' => $py+$j];
            }
        }

        foreach([-1, 1] as $i) {
            if (!$pieces->where('pos_x', $px+$i)->where('pos_y', $py)->where('color', $clr)->count() && !$this->isOutOfField($px+$i, $py))
                $result[] = ['x' => $px+$i, 'y' => $py];
        }

        return $result;
    }
}
