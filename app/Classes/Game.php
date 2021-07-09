<?php

namespace App\Classes;

use App\Models\Game as ModelsGame;
use App\Pieces\Piece;

class Game {
    private ModelsGame $model;
    private array $users = [];
    private array $pieces = [];
    protected GameType $gameType;

    public function __construct(int $userWhite, int $userBlack, GameType $gameType)
    {
        $this->users[Piece::COLOR_WHITE] = $userWhite;
        $this->users[Piece::COLOR_BLACK] = $userBlack;

        $this->gameType = $gameType;

        $this->initModel();
        $this->initPieces();
    }

    public function getInitialPiecesResponse()
    {
        return response()->json($this->pieces);
    }

    private function initModel()
    {
        $this->model = ModelsGame::create([
            'type' => $this->gameType->getID()
        ]);

        $this->model->users()->sync($this->users);
    }

    private function initPieces()
    {
        foreach($this->gameType->getStartPositions() as $color=>$pieces) {
            foreach($pieces as $piece=>$positions) {
                foreach($positions as $position)
                    $this->pieces[] = (new $piece())->init($this->model->id, $this->users[$color], $color, $position[0], $position[1]);
            }
        }
    }
}
