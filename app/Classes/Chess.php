<?php

namespace App\Classes;

use App\Models\Game as ModelsGame;
use App\Models\Piece as ModelsPiece;
use App\Pieces\Piece;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class Chess implements GameInterface {
    private ModelsGame $model;
    private array $users = [];
    private array $gameObjects = [];
    protected GameRules $gameRules;

    public function startGame(int $user_1, int $user_2, GameRules $gameRules): array
    {
        $this->users[Piece::COLOR_WHITE] = $user_1;
        $this->users[Piece::COLOR_BLACK] = $user_2;

        $this->gameRules = $gameRules;

        $this->initModel();
        return $this->initGameObjects();
    }

    public function getMoves(int $objectId, array $data): array
    {
        $model = ModelsPiece::find($objectId);

        if ($model->user_id != Auth::id() || $model->game->turn != $model->game->users->where('id', Auth::id())->first()->pivot->color)
            return [];

        return (new ("App\\Pieces\\" . $model->type)($model))->getMoves();
    }

    public function updateObject(int $objectId, array $data): array
    {
        $model = ModelsPiece::find($objectId);

        if ($model->user_id != Auth::id() || $model->game->turn != $model->game->users->where('id', Auth::id())->first()->pivot->color)
            return [];

        return (new ("App\\Pieces\\" . $model->type)($objectId))->update($data);
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
            'type' => $this->gameRules->getID(),
            'turn' => Piece::COLOR_WHITE
        ]);

        $users = [];
        foreach($this->users as $color=>$user_id) {
            $users[$user_id] = ['color' => $color];
        }

        $this->model->users()->sync($users);

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

    public function getGameObjects($game_id) : array
    {
        $pieces = ModelsPiece::where('game_id', $game_id)->get();
        foreach($pieces as $piece) {
            $this->gameObjects[] = (new ("App\\Pieces\\" . $piece->type)($piece))->getExportData();
        }
        return $this->gameObjects;
    }
}
