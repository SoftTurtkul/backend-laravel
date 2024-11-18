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

    private function CheckPerformTransaction(array $params)
    {
        $order = Order::query()->where(['id' => $params['account']['order_id'], 'status' => 0])->get()->first();
        if (!$order) {
            return $this->Error(-31050, [
                'en' => "Order not found",
                "ru" => "Order not found",
                "uz" => "Order not found",
            ]);
        }
        if ($order->total_price*100 != $params['amount']) {
            return $this->Error(-31001, [
                'en' => "Order price mismatch",
                "ru" => "Order price mismatch",
                "uz" => "Order price mismatch",
            ]);
        }
        return json_encode([
            "result" => [
                "allow" => true
            ]
        ]);
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
