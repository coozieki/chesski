<?php

namespace App\Classes;

use App\Models\Game as ModelsGame;
use App\Pieces\Bishop;
use App\Pieces\King;
use App\Pieces\Knight;
use App\Pieces\Pawn;
use App\Pieces\Piece;
use App\Pieces\Queen;
use App\Pieces\Rook;

class Game {
    const ORDINARY_GAME_TYPE = 0;

    private ModelsGame $model;
    private array $users = [];
    private array $pieces = [];

    private array $startPositions = [
        Piece::COLOR_WHITE => [
            Pawn::class => [
                [1, 2], [2, 2], [3, 2], [4, 2], [5, 2], [6, 2], [7, 2], [8, 2]
            ],
            Knight::class => [
                [2, 1], [7, 1]
            ],
            Rook::class => [
                [1, 1], [8, 1]
            ],
            Bishop::class => [
                [3, 1], [6, 1]
            ],
            Queen::class => [
                [4, 1]
            ],
            King::class => [
                [5, 1]
            ]
        ],
        Piece::COLOR_BLACK => [
            Pawn::class => [
                [1, 7], [2, 7], [3, 7], [4, 7], [5, 7], [6, 7], [7, 7], [8, 7]
            ],
            Knight::class => [
                [2, 8], [7, 8]
            ],
            Rook::class => [
                [1, 8], [8, 8]
            ],
            Bishop::class => [
                [3, 8], [6, 8]
            ],
            Queen::class => [
                [4, 8]
            ],
            King::class => [
                [5, 8]
            ]
        ]
    ];

    public function __construct(int $userWhite, int $userBlack, int $type = self::ORDINARY_GAME_TYPE)
    {
        $this->users[Piece::COLOR_WHITE] = $userWhite;
        $this->users[Piece::COLOR_BLACK] = $userBlack;

        $this->initModel($type);
        $this->initPieces();
    }

    public function getInitialPiecesResponse()
    {
        return response()->json($this->pieces);
    }

    private function initModel($type)
    {
        $this->model = ModelsGame::create([
            'type' => $type
        ]);

        $this->model->users()->sync($this->users);
    }

    private function initPieces()
    {
        foreach($this->startPositions as $color=>$pieces) {
            foreach($pieces as $piece=>$positions) {
                foreach($positions as $position)
                    $this->pieces[] = (new $piece())->init($this->model->id, $this->users[$color], $color, $position[0], $position[1]);
            }
        }
    }
}
