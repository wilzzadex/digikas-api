<?php

namespace App\Http\Controllers;

use App\Helper\ResponseFormatter;
use App\Models\OTP;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'whatsapp_number' => 'required|string',
        ], [
            'whatsapp_number.required' => 'Nomor Whatsapp tidak boleh kosong',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(
                ['error' => $validator->errors()],
                'Validation Error',
                422
            );
        }


        $checkUser = User::where('whatsapp_number', $request->whatsapp_number)->first();
        if (!$checkUser) {
            // create new user
            $user = new User();
            $user->whatsapp_number = $request->whatsapp_number;
            $user->name = $request->whatsapp_number;
            $user->password = bcrypt($request->whatsapp_number);
            $user->save();

            $userId = $user->id;
        } else {
            $userId = $checkUser->id;
        }

        // create new OTP 6 digit
        $otp = mt_rand(100000, 999999);
        $userOtp = new OTP();
        $userOtp->user_id = $userId;
        $userOtp->otp = $otp;
        $userOtp->expired_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        $userOtp->save();

        return ResponseFormatter::success(
            ['otp' => $otp],
            'OTP berhasil dikirim'
        );
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required',
        ], [
            'otp.required' => 'OTP tidak boleh kosong',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(
                ['error' => $validator->errors()],
                'Validation Error',
                422
            );
        }

        $otp = OTP::where('otp', $request->otp)->where('expired_at', '>', date('Y-m-d H:i:s'))->first();
        if (!$otp) {
            return ResponseFormatter::error(
                ['error' => 'OTP tidak valid atau sudah kadaluarsa'],
                'OTP tidak valid atau sudah kadaluarsa',
                400
            );
        }

        $user = User::find($otp->user_id);
        $token = Auth::attempt(['whatsapp_number' => $user->whatsapp_number, 'password' => $user->whatsapp_number]);

        // delete otp
        $otp->delete();


        return ResponseFormatter::success(
            [
                'token' => $token,
                'user' => $user
            ],
            'OTP berhasil diverifikasi'
        );
    }
}
