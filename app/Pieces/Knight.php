<?php

namespace App\Pieces;

use App\Models\Piece as ModelPiece;

class Knight extends Piece {
    protected function getPieceMoves(): array
    {
        $result = [];
        $pieces = ModelPiece::where('game_id', $this->model->game_id)->get();

        $px = $this->model->pos_x;
        $py = $this->model->pos_y;
        $clr = $this->model->color;

        $possible = [
            [$px+1, $py+2], [$px+1, $py-2], [$px+2, $py+1], [$px+2, $py-1],
            [$px-1, $py+2], [$px-1, $py-2], [$px-2, $py+1], [$px-2, $py-1]
        ];

        foreach($possible as $pos) {
            if (!$this->isOutOfField($pos[0], $pos[1]) && (!$pieces->where('pos_x', $pos[0])->where('pos_y', $pos[1])->where('color', $clr)->count()))
                $result[] = ['x'=>$pos[0], 'y'=>$pos[1]];
        }

        return $result;
    }
}
