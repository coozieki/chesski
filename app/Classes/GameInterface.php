<?php

namespace App\Classes;

use Illuminate\Http\JsonResponse;

interface GameInterface {
    public function startGame(int $user_1, int $user_2, GameRules $gameRules) : JsonResponse;

    public function getMoves(int $objectId, array $data) : JsonResponse;

    public function updateObject(int $objectId, array $data) : JsonResponse;

    public function finishGame() : JsonResponse;
}
