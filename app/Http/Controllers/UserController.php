<?php

namespace App\Http\Controllers;

use App\Helper\FileHelper;
use App\Helper\ResponseFormatter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function getStatus()
    {
        $user = Auth::user();
        if ($user->name == $user->whatsapp_number) {
            $data['status'] = 'pending-update';
        } else {
            $data['status'] = 'done';
        }
        $data['user'] = $user;
        return ResponseFormatter::success($data, 'Status akun berhasil diambil');
    }

    public function updateProfile(Request $request)
    {
        // dd($request->all());
        $userId = Auth::user()->id;

        $user = User::find($userId);

        if ($request->hasFile('avatar')) {
            $image = $request->file('avatar');
            $fileName = md5(time()) . '.' . $image->getClientOriginalExtension();
            $path = 'ocr/' . date('Y-m-d') . '/' . $fileName;
            Storage::disk('s3')->put($path, file_get_contents($image));

            $result = FileHelper::getFullPathUrl($path);

            $user->avatar = $result;
        }

        $user->name = $request->name;
        $user->whatsapp_number = $request->whatsapp_number;
        $user->email = $request->email;
        $user->age = $request->age;
        $user->gender = $request->gender;
        $user->save();

        return ResponseFormatter::success($user, 'Profil berhasil diupdate');
    }
}
