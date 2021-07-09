<?php

namespace App\Pieces;

use App\Classes\GameType;
use App\Models\Piece as ModelsPiece;
use ReflectionClass;

abstract class Piece {
    const COLOR_WHITE = 0;
    const COLOR_BLACK = 1;

    protected ModelsPiece $model;
    protected GameType $gameType;
    protected string $image = '1';

    public function __construct(int $id = null)
    {
        if ($id)
            $this->model = ModelsPiece::find($id);
    }

    public function init(int $gameId, int $userId, int $color, int $startPosX, int $startPosY) : array
    {
        $this->model = ModelsPiece::create([
            'pos_x' => $startPosX,
            'pos_y' => $startPosY,
            'color' => $color,
            'game_id' => $gameId,
            'user_id' => $userId,
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
        return $this->getPieceMoves();
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
    abstract protected function getPieceMoves() : array;

    /**
     * @param int $move Index of last moves from getMoves()
     *
     * @return array Piece position after move
     */
    public function move(int $posX, int $posY) : array {
        if (!$this->canMove($posX, $posY))
            return [];

        $piece = ModelsPiece::where('pos_x', $posX)->where('pos_y', $posY)->where('game_id', $this->model->game_id)->first();
        if ($piece)
            $piece->delete();

        $this->model->update([
            'pos_x' => $posX,
            'pos_y' => $posY
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

    protected function getGameType() : GameType {
        if (!$this->gameType) {
            $this->model->load('game');
            $this->gameType = GameType::getInstanceByTypeName($this->model->game->type);
        }
        return $this->gameType;
    }

    protected function isOutOfField(int $posX, int $posY) : bool {
        return !(($posX >= 1) && ($posX <= $this->getGameType()->getFieldLength()) && ($posY >= 1) && ($posY <= $this->getGameType()->getFieldLength()));
    }
}
