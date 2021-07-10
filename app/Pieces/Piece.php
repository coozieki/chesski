<?php

namespace App\Pieces;

use App\Classes\GameObjectInterface;
use App\Classes\GameRules;
use App\Models\Piece as ModelsPiece;
use Exception;
use Illuminate\Support\Facades\DB;
use ReflectionClass;

abstract class Piece implements GameObjectInterface {
    const COLOR_WHITE = 0;
    const COLOR_BLACK = 1;

    protected ModelsPiece|null $model = null;
    protected GameRules|null $gameRules = null;
    protected string $image = '1';

    public function __construct(ModelsPiece|int|null $model = null)
    {
        if (is_int($model))
            $this->model = ModelsPiece::find($model);
        else if ($model)
            $this->model = $model;
    }

    public function init(array $data) : array
    {
        if ($this->model)
            throw new Exception('This piece is already initialized.');

        $this->model = ModelsPiece::create([
            'pos_x' => $data['startPosX'],
            'pos_y' => $data['startPosY'],
            'color' => $data['color'],
            'game_id' => $data['gameId'],
            'user_id' => $data['userId'],
            'type' => (new ReflectionClass($this))->getShortName()
        ]);

        $pieceExportData = array_intersect_key($this->model->toArray(), array_flip(['id', 'pos_x', 'pos_y', 'color', 'type']));
        $pieceExportData['image'] = $this->getImage();

        return $pieceExportData;
    }

    public function getPosX() : int {
        return $this->model->pos_x;
    }

    public function getPosY() : int {
        return $this->model->pos_y;
    }

    public function getMoves() : array {
        $pieces = DB::table('pieces')->where('game_id', $this->model->game_id)->get();

        $moves = $this->getPieceMoves();

        foreach($moves as $index=>$move) {
            if ($this->isCheck($pieces, $move))
                unset($moves[$index]);
        }

        return $moves;
    }

    private function isCheck($pieces, array $move) : bool {
        $pieces->where('id', $this->model->id)->first()->pos_x = $move['x'];
        $pieces->where('id', $this->model->id)->first()->pos_y = $move['y'];
        $enemyPieces = $pieces->where('color', $this->model->color == Piece::COLOR_WHITE ? Piece::COLOR_BLACK : Piece::COLOR_WHITE);
        $king = $pieces->where('type', 'King')->where('color', $this->model->color)->first();

        $checkStraightHor = function ($i) use ($king, $enemyPieces, $pieces) {
            if ($enemyPieces->where('pos_x', $i)->where('pos_y', $king->pos_y)->whereIn('type', ['Queen', 'Rook'])->count())
                return 1;
            if ($pieces->where('pos_x', $i)->where('pos_y', $king->pos_y)->count())
                return 2;
            return 0;
        };
        $checkStraightVert = function ($i) use ($king, $enemyPieces, $pieces) {
            if ($enemyPieces->where('pos_x', $king->pos_x)->where('pos_y', $i)->whereIn('type', ['Queen', 'Rook'])->count())
                return 1;
            if ($pieces->where('pos_x', $king->pos_x)->where('pos_y', $i)->count())
                return 2;
            return 0;
        };
        for($i=$king->pos_x+1;$i<$this->getGameRules()->getFieldLength()+1;$i++) {
            $check = $checkStraightHor($i);
            if ($check == 1)
                return true;
            else if ($check == 2)
                break;
        }
        for($i=$king->pos_x-1;$i>0;$i--) {
            $check = $checkStraightHor($i);
            if ($check == 1)
                return true;
            else if ($check == 2)
                break;
        }
        for($i=$king->pos_y+1;$i<$this->getGameRules()->getFieldLength()+1;$i++) {
            $check = $checkStraightVert($i);
            if ($check == 1)
                return true;
            else if ($check == 2)
                break;
        }
        for($i=$king->pos_y-1;$i>0;$i--) {
            $check = $checkStraightVert($i);
            if ($check == 1)
                return true;
            else if ($check == 2)
                break;
        }
        $checkDiag1 = function($i) use ($king, $pieces, $enemyPieces) {
            if ($enemyPieces->where('pos_x', $i)->where('pos_y', $king->pos_y + $i - $king->pos_x)->whereIn('type', ['Queen', 'Bishop'])->count())
                return 1;

            if ($pieces->where('pos_x', $i)->where('pos_y', $king->pos_y + $i - $king->pos_x)->count())
                return 2;

            return 0;
        };
        $checkDiag2 = function($i) use ($king, $pieces, $enemyPieces) {
            if ($enemyPieces->where('pos_x', $i)->where('pos_y', $king->pos_y - $i + $king->pos_x)->whereIn('type', ['Queen', 'Bishop'])->count())
                return 1;

            if ($pieces->where('pos_x', $i)->where('pos_y', $king->pos_y - $i + $king->pos_x)->count())
                return 2;

            return 0;
        };

        foreach([1, -1] as $i) {
            if ($enemyPieces->where('pos_x', $king->pos_x + $i)->where('pos_y', $king->pos_y+1)->whereIn('type', ['Queen', 'Rook', 'Pawn', 'Bishop'])->count())
                return true;
        }

        for($i=$king->pos_x+1;$i<$this->getGameRules()->getFieldLength()+1;$i++) {
            $check = $checkDiag1($i);
            if ($check == 1)
                return true;
            else if ($check == 2)
                break;
        }
        for($i=$king->pos_x-1;$i>0;$i--) {
            $check = $checkDiag1($i);
            if ($check == 1)
                return true;
            else if ($check == 2)
                break;
        }
        for($i=$king->pos_x+1;$i<$this->getGameRules()->getFieldLength()+1;$i++) {
            $check = $checkDiag2($i);
            if ($check == 1)
                return true;
            else if ($check == 2)
                break;
        }
        for($i=$king->pos_x-1;$i>0;$i--) {
            $check = $checkDiag2($i);
            if ($check == 1)
                return true;
            else if ($check == 2)
                break;
        }

        return false;
    }

    public function canMove(int $posX, int $posY) : bool {
        foreach($this->getMoves() as $move) {
            if ($posX === $move['x'] && $posY === $move['y'])
                return true;
        }

        return false;
    }

    /**
     * Get concrete piece moveset
     */
    abstract public function getPieceMoves($pieces = null) : array;

    /**
     *
     * @return array Piece position after move
     */
    public function update(array $data) : array {
        if (!$this->canMove($data['posX'], $data['posY']))
            return [];

        $piece = ModelsPiece::where('pos_x', $data['posX'])->where('pos_y', $data['posY'])->where('game_id', $this->model->game_id)->first();
        if ($piece)
            $piece->delete();

        $this->model->update([
            'pos_x' => $data['posX'],
            'pos_y' => $data['posY']
        ]);


        $this->model->fresh();

        return [
            'x' => $this->model->pos_x,
            'y' => $this->model->pos_y
        ];
    }

    private function getImage() : string {
        return "/img/" . strtolower((new ReflectionClass($this))->getShortName()) . "_{$this->model->color}.png";
    }

    protected function getGameRules() : GameRules {
        if (!$this->gameRules) {
            $this->model->load('game');
            $this->gameRules = GameRules::getInstanceByTypeName($this->model->game->type);
        }
        return $this->gameRules;
    }

    protected function isOutOfField(int $posX, int $posY) : bool {
        return !(($posX >= 1) && ($posX <= $this->getGameRules()->getFieldLength()) && ($posY >= 1) && ($posY <= $this->getGameRules()->getFieldLength()));
    }
}
