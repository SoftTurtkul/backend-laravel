<?php

namespace App\Http\Controllers;
class PaymeController extends Controller
{
    const test_token="Drb4rksirx6NhWqJa@R%YeSj3jC&aTRKUbQK";
    const user="Paycom";
    public function index()
    {
        return \request()->header('Authorization');
        $data = \request()->toArray();
        switch ($data['method']) {
            case 'CheckPerformTransaction':
                return $this->CheckPerformTransaction($data['params']);
                break;
        }
    }

    private function CheckPerformTransaction(array $params)
    {

    }

}
