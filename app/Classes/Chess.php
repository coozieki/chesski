<?php

namespace App\Classes;

use App\Models\Game as ModelsGame;
use App\Models\Piece as ModelsPiece;
use App\Pieces\Piece;
use Illuminate\Http\JsonResponse;

class Chess implements GameInterface {
    private ModelsGame $model;
    private array $users = [];
    private array $gameObjects = [];
    protected GameRules $gameRules;

    public function startGame(int $user_1, int $user_2, GameRules $gameRules): JsonResponse
    {
        $this->users[Piece::COLOR_WHITE] = $user_1;
        $this->users[Piece::COLOR_BLACK] = $user_2;

        $this->gameRules = $gameRules;

        $this->initModel();
        return response()->json($this->initGameObjects());
    }

    public function getMoves(int $objectId, array $data): JsonResponse
    {
        $model = ModelsPiece::find($objectId);

        return response()->json((new ("App\\Pieces\\" . $model->type)($model))->getMoves());
    }

    public function updateObject(int $objectId, array $data): JsonResponse
    {
        $model = ModelsPiece::find($objectId);

        return response()->json((new ("App\\Pieces\\" . $model->type)($objectId))->update($data));
    }

    public function finishGame(): JsonResponse
    {
        return response()->json('finish');
    }

    public function getInitialGameObjectsResponse()
    {
        return response()->json($this->gameObjects);
    }

    private function initModel() : ModelsGame
    {
        $this->model = ModelsGame::create([
            'type' => $this->gameRules->getID()
        ]);

        $this->model->users()->sync($this->users);

        return $this->model;
    }

    private function initGameObjects() : array
    {
        foreach($this->gameRules->getStartPositions() as $color=>$gameObjects) {
            foreach($gameObjects as $gameObject=>$positions) {
                foreach($positions as $position)
                    $this->gameObjects[] = (new $gameObject())->init([
                        'gameId' => $this->model->id,
                        'userId' => $this->users[$color],
                        'color' => $color,
                        'startPosX' => $position[0],
                        'startPosY' => $position[1]
                    ]);
            }
        }

        return $this->gameObjects;
    }
}
