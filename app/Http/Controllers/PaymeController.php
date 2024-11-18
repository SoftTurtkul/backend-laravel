<?php

namespace App\Http\Controllers;
class PaymeController extends Controller
{
    const test_token = "Drb4rksirx6NhWqJa@R%YeSj3jC&aTRKUbQK";
    const user = "Paycom";

    public function index()
    {
        $auth = \request()->header('Authorization');
        $token = str_replace("Bearer ", "", $auth);
        if (!base64_encode(self::user . ":" . self::test_token) == $token) {
            return json_encode([
                "error" => [
                    "code" => -31050,
                ]
            ]);
        }
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
