<?php

namespace App\Pieces;

abstract class Piece {
    const COLOR_WHITE = 0;
    const COLOR_BLACK = 1;

    protected string $image;
    private int $posX;
    private int $posY;
    private int $color;
    private bool $isAlive = true;

    private $moves;

    public function getPosX() : int {
        return $this->posX;
    }

    public function getPosY() : int {
        return $this->posY;
    }

    public function __construct(int $color)
    {
        $this->color = $color;
    }

    public function getMoves() : array {
        $this->moves = $this->getPieceMoves();

        return $this->moves;
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
    public function move(int $move) : array {
        $this->posX = $this->moves[$move]['x'];
        $this->posY = $this->moves[$move]['y'];

        return [
            'x' => $this->posX,
            'y' => $this->posY
        ];
    }
}
