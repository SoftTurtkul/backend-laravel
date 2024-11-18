<?php

namespace App\Http\Controllers;
class PaymeController extends Controller
{
    public function index()
    {
        return \request()->toArray();
    }

}
