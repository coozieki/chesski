<?php

namespace App\Pieces;

use App\Classes\GameObjectInterface;
use App\Classes\GameRules;
use App\Models\Piece as ModelsPiece;
use Exception;
use Illuminate\Support\Collection;
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

        return $this->getExportData();
    }

    public function getExportData() : array {
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
        $pieces = DB::table('pieces')->where('game_id', $this->model->game_id)->get()->all();

        $moves = $this->getPieceMoves($pieces);

        foreach($moves as $index=>$move) {
            if ($this->isCheck($pieces, $move))
                unset($moves[$index]);
        }

        return $moves;
    }

    private function isCheck($pieces, array $move) : bool {
        $enemyPieces = [];
        foreach($pieces as $index=>$piece) {
            if ($piece->pos_x == $move['x'] && $piece->pos_y == $move['y']) {
                unset($pieces[$index]);
                continue;
            }
            if ($piece->id == $this->model->id) {
                $pieces[$index]->pos_x = $move['x'];
                $pieces[$index]->pos_y = $move['y'];
            }
            if ($piece->color == ($this->model->color == Piece::COLOR_WHITE ? Piece::COLOR_BLACK : Piece::COLOR_WHITE)) {
                $enemyPieces[] = $piece;
            }
            if ($piece->type == 'King' && $piece->color == $this->model->color) {
                $king = $piece;
            }
        }

        $checkStraightHor = function ($i) use ($king, $enemyPieces, $pieces) {
            if ($this->checkHasPiecesAtCell($enemyPieces, $i, $king->pos_y, ['Queen', 'Rook']))
                return 1;
            if ($this->checkHasPiecesAtCell($pieces, $i, $king->pos_y))
                return 2;
            return 0;
        };
        $checkStraightVert = function ($i) use ($king, $enemyPieces, $pieces) {
            if ($this->checkHasPiecesAtCell($enemyPieces, $king->pos_x, $i, ['Queen', 'Rook']))
                return 1;
            if ($this->checkHasPiecesAtCell($pieces, $king->pos_x, $i))
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
            if ($this->checkHasPiecesAtCell($enemyPieces, $i, $king->pos_y + $i - $king->pos_x, ['Queen', 'Bishop']))
                return 1;

            if ($this->checkHasPiecesAtCell($pieces, $i, $king->pos_y + $i - $king->pos_x))
                return 2;

            return 0;
        };
        $checkDiag2 = function($i) use ($king, $pieces, $enemyPieces) {
            if ($this->checkHasPiecesAtCell($enemyPieces, $i, $king->pos_y - $i + $king->pos_x, ['Queen', 'Bishop']))
                return 1;

            if ($this->checkHasPiecesAtCell($pieces, $i, $king->pos_y - $i + $king->pos_x))
                return 2;

            return 0;
        };
        if ($king->color == Piece::COLOR_WHITE) {
            foreach([1, -1] as $i) {
                if ($this->checkHasPiecesAtCell($enemyPieces, $king->pos_x + $i, $king->pos_y+1, ['Queen', 'King', 'Pawn', 'Bishop']))
                    return true;
            }
        } else {
            foreach([1, -1] as $i) {
                if ($this->checkHasPiecesAtCell($enemyPieces, $king->pos_x + $i, $king->pos_y-1, ['Queen', 'King', 'Pawn', 'Bishop']))
                    return true;
            }
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

    protected function checkHasPiecesAtCell($array, $posX, $posY, $type = [], $color = null) {
        foreach($array as $elem) {
            if ($type != [] && !in_array($elem->type, $type))
                continue;
            if ($color!==null && $elem->color != $color)
                continue;
            if ($elem->pos_x == $posX && $elem->pos_y == $posY)
                return true;
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

        $this->model->game->update([
            'turn' => $this->model->game->turn == Piece::COLOR_WHITE ? Piece::COLOR_BLACK : Piece::COLOR_WHITE
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
