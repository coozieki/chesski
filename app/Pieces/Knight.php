<?php

namespace App\Pieces;

use App\Models\Piece as ModelPiece;
use Illuminate\Support\Facades\DB;

class Knight extends Piece {
    public function getPieceMoves($pieces = null): array
    {
        $result = [];
        if (!$pieces)
            $pieces = DB::table('pieces')->where('game_id', $this->model->game_id)->get()->all();

        $px = $this->model->pos_x;
        $py = $this->model->pos_y;
        $clr = $this->model->color;

        $possible = [
            [$px+1, $py+2], [$px+1, $py-2], [$px+2, $py+1], [$px+2, $py-1],
            [$px-1, $py+2], [$px-1, $py-2], [$px-2, $py+1], [$px-2, $py-1]
        ];

        foreach($possible as $pos) {
            if (!$this->isOutOfField($pos[0], $pos[1]) && !$this->checkHasPiecesAtCell($pieces, $pos[0], $pos[1], [], $clr))
                $result[] = ['x'=>$pos[0], 'y'=>$pos[1]];
        }

        return $result;
    }
}
