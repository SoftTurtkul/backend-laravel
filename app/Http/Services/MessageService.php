<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MessageService
{
    public string $email = 'roma_2020@mail.ru';
    public string $password = '2gJ2pSmo0Bgr6OAjAlQzFpsWGx2mjDV3BZhtkSdW';

    public function refreshToken()
    {
        $response = Http::patch("notify.eskiz.uz/api/auth/refresh")->json();
        return $response['data']['token'];
    }

    public function sendMessage($phone, $message)
    {
        $token = $this->getToken();
        $res = Http::withToken($token)->post("notify.eskiz.uz/api/message/sms/send", [
            'mobile_phone' => "998$phone",
            'message' => $message,
            'from' => '4546',
            'callback_url' => route('receive_status')
        ]);
        return $res;
    }


    public function getToken()
    {
        $response = Http::post("notify.eskiz.uz/api/auth/login", [
            'email' => $this->email,
            'password' => $this->password,
        ])->json();

        return $response['data']['token'];
    }
}
