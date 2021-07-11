<?php

namespace App\Http\Controllers;

use App\Classes\Chess;
use App\Classes\ExtraordinaryGameRules;
use App\Classes\GameRules;
use App\Classes\OrdinaryGameRules;
use App\Models\Game;
use App\Models\Piece;
use Exception;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GameController extends Controller
{
    protected string $currentGame = Chess::class;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function chess(Request $request, $id = null)
    {
        $game = null;
        if ($id) {
            $game = Game::find($id);
            if (!$game)
                abort(404);
            $gameRules = GameRules::getInstanceByTypeName($game->type);
        }
        else {
            $game = new ($this->currentGame)();
            if ($request->type) {
                try {
                    $gameRules = GameRules::getInstanceByTypeName($request->type);
                } catch (Exception $e) {
                    $gameRules = new OrdinaryGameRules();
                }
            } else
                $gameRules = new OrdinaryGameRules();
            $gameObjects = $game->startGame(1, 4, $gameRules);
            $game = Piece::find($gameObjects[0]['id'])->game;
        }
        $games = Game::whereHas('users', function(EloquentBuilder $query) {
            $query->where('users.id', Auth::id());
        })->get();
        return view('home', compact('game', 'games', 'gameRules'));
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function start()
    {
        $game = new ($this->currentGame)();
        return response()->json($game->startGame(1, 4, new OrdinaryGameRules()));
    }

    public function move(Request $request)
    {
        $game = new ($this->currentGame)();
        return response()->json($game->updateObject($request->id, ['posX' => $request->x, 'posY' => $request->y]));
    }

    public function getMoves(Request $request)
    {
        $game = new ($this->currentGame)();
        return response()->json($game->getMoves($request->id, []));
    }
}
