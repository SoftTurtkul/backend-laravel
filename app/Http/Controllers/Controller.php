<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use App\Http\Services\Response;
use Illuminate\Support\Facades\File;


class Controller extends BaseController {
    use Response;

    protected function validated(Request $request) {
        return array_filter($request->all(), function($val) {
            return strlen($val);
        });
    }

    protected function saveImage($img, $path) {
        $imageName = time().'.'. $img->extension();
        $img->move(public_path('storage/' . $path), $imageName);
        return $imageName;
    }

    protected function deleteFile($name, $path) {
        File::delete(public_path("storage/$path/" . $name));
    }
}
