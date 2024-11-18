<?php

namespace App\Http\Controllers;
class PaymeController extends Controller
{
    public function index()
    {
        dd(\request()->toArray());
    }

}
