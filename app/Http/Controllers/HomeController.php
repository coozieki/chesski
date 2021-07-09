<?php

namespace App\Http\Controllers;

use App\Classes\Game;

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

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $game = new Game(1, 2);
        dd($game->getInitialPiecesResponse());
        return view('home');
    }
}
