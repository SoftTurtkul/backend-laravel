<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Transaction;

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
                $perform = $this->CheckPerformTransaction($data['params']);
                if ($perform === true) {
                    return json_encode([
                        "result" => [
                            "allow" => true
                        ]
                    ]);
                }
                return $perform;
                break;
            case 'CreateTransaction':
                return $this->CreateTransaction($data['params']);
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
        if ($order->total_price * 100 != $params['amount']) {
            return $this->Error(-31001, [
                'en' => "Order price mismatch",
                "ru" => "Order price mismatch",
                "uz" => "Order price mismatch",
            ]);
        }
        return true;
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

    private function CreateTransaction($params)
    {
        if (($perform = $this->CheckPerformTransaction($params)) !== true) {
            return $perform;
        }
        $order = Order::query()->where(['id' => $params['account']['order_id'], 'status' => 0])->get()->first();
        $isExists = Transaction::query()->where(['paycom_transaction_id' => $params['id']])->exists();
        if (!$isExists) {
            //Yo'q bo'lsa\
            if (Transaction::timestamp2milliseconds(1 * $params['time']) - Transaction::timestamp(true) >= Transaction::TIMEOUT) {
                return $this->Error(-31050, [
                    'С даты создания транзакции прошло ' . Transaction::TIMEOUT . 'мс',
                    'Tranzaksiya yaratilgan sanadan ' . Transaction::TIMEOUT . 'ms o`tgan',
                    'Since create time of the transaction passed ' . Transaction::TIMEOUT . 'ms'
                ]);
            }
            $create_time = Transaction::totimestamp(true);
            $transaction = new Transaction();
            $transaction->fill([
                'paycom_transaction_id' => $params['id'],
                'paycom_time' => $params['time'],
                'paycom_time_datetime' => Transaction::timestamp2datetime($params['time']),
                'create_time' => Transaction::timestamp2datetime($create_time),
                'state' => Transaction::STATE_CREATED,
                'amount' => $params['amount'],
                'order_id' => $params['account']['order_id'],
            ]);
            $transaction->save(); // after save $transaction->id will be populated with the newly created transaction's id.

            // send response
            return json_encode([
                "result" => [
                    "allow" => true
                ]
            ]);

        } else {
            //Mavjud bo'lsa
        }


    }

}
