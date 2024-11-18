<?php

namespace App\Http\Controllers;

use App\Models\Order;

class PaymeController extends Controller
{
    const test_token = "Drb4rksirx6NhWqJa@R%YeSj3jC&aTRKUbQK";
    const user = "Paycom";

    public function index()
    {
        $auth = \request()->header('Authorization');
        $token = str_replace("Basic ", "", $auth);
        if (base64_encode(self::user . ":" . self::test_token) != $token) {
            return $this->Error(-32504, [
                "ru" => "Not Allowed",
                "en" => "Not Allowed",
                "uz" => "Not Allowed",
            ]);
        }
        $data = \request()->toArray();
        switch ($data['method']) {
            case 'CheckPerformTransaction':
                return $this->CheckPerformTransaction($data['params']);
                break;
        }
    }

    private function CheckPerformTransaction(array $params):string
    {
        $order = Order::query()->where('id', $params['account']['order_id'])->first();
        if (!$order) {
            return $this->Error(-31050, [
                'en' => "Order not found",
                "ru" => "Order not found",
                "uz" => "Order not found",
            ]);
        }
    }

    private function Error($code, $message)
    {
        return json_encode([
            "error" => [
                "code" => $code,
                "message" => $message
            ]
        ]);
    }

}
