<?php

namespace App\Http\Controllers;

use App\Classes\Chess;
use App\Classes\OrdinaryGameRules;
use Illuminate\Http\Request;

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

    public function home()
    {
        return view('home');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function start()
    {
        $game = new ($this->currentGame)();
        return $game->startGame(1, 2, new OrdinaryGameRules());
    }

    public function move(Request $request)
    {
        $game = new ($this->currentGame)();
        return $game->updateObject($request->id, ['posX' => $request->x, 'posY' => $request->y]);
    }

    public function getMoves(Request $request)
    {
        $game = new ($this->currentGame)();
        return $game->getMoves($request->id, []);
    }
}
