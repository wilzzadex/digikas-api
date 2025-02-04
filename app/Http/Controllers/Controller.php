<?php

namespace App\Http\Controllers;

use App\Models\OTP;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;


    public function __construct()
    {
        // remove otp if expired > 5 minutes from current time
        $currentDate = date('Y-m-d H:i:s');
        $otp = OTP::where('expired_at', '<', $currentDate)->get();
        foreach ($otp as $item) {
            $item->delete();
        }

    }

}
