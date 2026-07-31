<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class GerenteGeneralController extends Controller
{
    public function index(Request $request): View
    {
        return view('auth.gerente-general.dashboard', [
            'user' => $request->user(),
        ]);

    }
}
