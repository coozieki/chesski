<?php

namespace App\Http\Controllers;

use App\Classes\Game;
use App\Classes\GameType;
use App\Classes\OrdinaryGameType;
use App\Models\Piece;
use Illuminate\Http\Request;

class HomeController extends Controller
{
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
        $game = new Game(1, 2, new OrdinaryGameType());
        return $game->getInitialPiecesResponse();
    }

    public function move(Request $request)
    {
        $piece = new ("App\\Pieces\\" . ucfirst($request->type))($request->id);
        return response()->json($piece->move($request->x, $request->y));
    }

    public function getMoves(Request $request)
    {
        $piece = new ("App\\Pieces\\" . ucfirst($request->type))($request->id);
        return response()->json($piece->getMoves());
    }
}
