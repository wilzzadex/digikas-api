<?php

namespace App\Http\Controllers;

use App\Helper\FileHelper;
use App\Helper\MessageHelper;
use App\Helper\ResponseFormatter;
use App\Models\MessageLogs;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function test()
    {
        // create txt file with content "Hello World"
        $result = Storage::disk('s3')->put('example1.txt', 'This is a test file.');

        dd($result);
    }

    public function ocrImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'image.required' => 'Image tidak boleh kosong',
            'image.image' => 'File harus berupa gambar',
            'image.mimes' => 'File harus berupa gambar dengan format jpeg, png, jpg',
            'image.max' => 'Ukuran file maksimal 2MB',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(
                ['error' => $validator->errors()],
                'Validation Error',
                422
            );
        }

        $image = $request->file('image');
        $filename = md5(time()) . '.' . $image->getClientOriginalExtension();
        $path = 'ocr/' . date('Y-m-d') . '/' . $filename;
        Storage::disk('s3')->put($path, file_get_contents($image));

        return ResponseFormatter::success(
            ['image_path' => FileHelper::getFullPathUrl($path)],
            'Image berhasil diupload'
        );
    }

    function saveToLogs($data)
    {
        $c = new MessageLogs();
        $c->user_id = $data['user_id'];
        $c->reply_message = $data['reply'];
        $c->message = $data['message'];
        $c->sender_wa = $data['sender_wa'];
        $c->receiver = $data['receiver'];
        $c->message_type = $data['message_type'];
        $c->save();
    }

    function checkMessage($message_type, $message, $receiver, $image)
    {

        $checkUser = User::where('whatsapp_number', MessageHelper::formatWhatsappNumber($receiver))->first();
        if (!$checkUser) {
            // create new user
            $user = new User();
            $user->whatsapp_number = MessageHelper::formatWhatsappNumber($receiver);
            $user->name = MessageHelper::formatWhatsappNumber($receiver);
            $user->password = bcrypt(MessageHelper::formatWhatsappNumber($receiver));
            $user->save();

            $checkUser = $user;
        }

        if ($message_type == 'text') {

            // check message is only 1 character
            if (strlen($message) == 1) {
                $check = MessageLogs::where('receiver', MessageHelper::formatWhatsappNumber($receiver))->orderBy('id', 'desc')->first();
                if (!$check) {
                    return MessageHelper::opening($message);
                } else {
                    if ($message == '1') {
                        return MessageHelper::option1();
                    } elseif ($message == '2') {
                        return MessageHelper::option2($checkUser->id);
                    } elseif ($message == '3') {
                        return MessageHelper::option3($checkUser->id);
                    } else {
                        return MessageHelper::opening($message);
                    }
                }
            } else {
                return $this->processingTransaction($message, $checkUser->id);
            }
        } else {
            // check the image is not empty
            try {
                if ($image) {
                
                    // $fileName = md5(time()) . '.' . $image->getClientOriginalExtension();
                    // $path = 'ocr/' . date('Y-m-d') . '/' . $fileName;
                    // Storage::disk('s3')->put($path, file_get_contents($image));
    
                    // $result = FileHelper::getFullPathUrl($path);
                    // dd($result);
                    $data = [
                        "model" => "gpt-4o",
                        "messages" => [
                            [
                                "role" => "user",
                                "content" => [
                                    [
                                        "type" => "text",
                                        "text" => "read the text that contains the nominal but only item not discount,total etc and provide it in json format with the provisions name, qty, price and total price if has value if not only name and total_price, if unable to read return with json ( status = error )"
                                    ],
                                    [
                                        "type" => "image_url",
                                        "image_url" => [
                                            "url" => $image
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ];
    
                    // send request to api
                    $response = Http::withOptions(['verify' => false])->withHeaders([
                        'Authorization' => 'Bearer ' . env('API_CHATGPT_KEY'),
                    ])->post(env('API_CHATGPT'), $data);
    
                    $response = $response->json()['choices'][0]['message']['content'];
                    $jsonString = str_replace(["```json", "```"], "", $response);
                    $data = json_decode($jsonString, true);
    
                    $replyMessage = '';
                    foreach ($data as $item) {
                        $newTransaction = new Transaction();
                        $newTransaction->user_id = $checkUser->id;
    
                        $name = $item['name'];
                        try {
                            $name .= ' x' . $item['qty'];
                            $name .= ' ' . number_format($item['price']);
                        } catch (\Throwable $th) {
                            //throw $th;
                        }
                        $newTransaction->item = $name;
                        $newTransaction->price = $item['total_price'];
                        $newTransaction->type = 'out';
                        $newTransaction->transaction_date  = date('Y-m-d');
                        $newTransaction->save();
    
                        $replyMessage .= $name . " dengan harga Rp. " . number_format($item['total_price']) . "\n";
                    }
    
                    $replyMessage .= "\n📉 Pengeluaran berhasil di catat ✅";
    
                    return $replyMessage;
                }
            } catch (\Throwable $th) {
                return 'Error Image';
            }
            
        }
    }



    function processingTransaction($message, $user_id)
    {

        // check the message has word 'masukin,Masukin' or not

        DB::beginTransaction();

        try {
            if (strpos($message, 'masukin') !== false || strpos($message, 'Masukin') !== false) {
                $message = explode("\n", $message);
                $result = [];
                foreach ($message as $m) {
                    $m = explode(' ', $m);
                    $name = '';

                    foreach ($m as $key => $value) {
                        if ($key < count($m) - 1) {
                            $name .= $value;

                            if ($key < count($m) - 2) {
                                $name .= ' ';
                            }

                            if (strpos($name, 'masukin') !== false || strpos($name, 'Masukin') !== false) {
                                $name = str_replace('masukin', '', $name);
                                $name = str_replace('Masukin', '', $name);
                                $name = str_replace(' ', '', $name);
                            }
                        }
                    }

                    // get the price from the last array
                    $price = end($m);

                    $result[] = [
                        'item' => $name,
                        'price' => $this->convertPrice($price),
                    ];
                }

                $replyMessage = '';
                foreach ($result as $r) {
                    // save to database
                    $newTransaction = new Transaction();
                    $newTransaction->user_id = $user_id;
                    $newTransaction->item = $r['item'];
                    $newTransaction->price = $r['price'];
                    $newTransaction->type = 'in';
                    $newTransaction->transaction_date  = date('Y-m-d');
                    $newTransaction->save();

                    $replyMessage .= $r['item'] . " dengan nominal Rp. " . number_format($r["price"]) . "\n";
                }

                $replyMessage .= "\n📈 Pemasukan berhasil di catat ✅";

                DB::commit();

                return $replyMessage;
            } else {
                $message = explode("\n", $message);
                $result = [];
                foreach ($message as $m) {
                    $m = explode(' ', $m);
                    $name = '';

                    foreach ($m as $key => $value) {
                        if ($key < count($m) - 1) {
                            $name .= $value;

                            if ($key < count($m) - 2) {
                                $name .= ' ';
                            }
                        }
                    }
                    // get the price from the last array
                    $price = end($m);

                    $result[] = [
                        'item' => $name,
                        'price' => $this->convertPrice($price),
                    ];
                }
                // dd($result);

                $replyMessage = '';
                foreach ($result as $r) {
                    // save to database
                    $newTransaction = new Transaction();
                    $newTransaction->user_id = $user_id;
                    $newTransaction->item = $r['item'];
                    $newTransaction->price = $r['price'];
                    $newTransaction->type = 'out';
                    $newTransaction->transaction_date  = date('Y-m-d');
                    $newTransaction->save();

                    $replyMessage .= $r['item'] . " dengan harga Rp. " . number_format($r["price"]) . "\n";
                }

                $replyMessage .= "\n📉 Pengeluaran berhasil di catat ✅";

                DB::commit();

                return $replyMessage;
            }
        } catch (\Throwable $th) {
            // dd($th);
            DB::rollBack();
            return 'Error';
        }
    }


    function convertPrice($price)
    {
        // convert price to number if the price 10k,10.000,10rb,1jt
        $price = str_replace('k', '000', $price);
        $price = str_replace('rb', '000', $price);
        $price = str_replace('jt', '000000', $price);
        $price = str_replace('.', '', $price);
        return $price;
    }

    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
            'sender_wa' => 'required|string',
            'receiver' => 'required|string',
            'message_type' => 'required|string',
        ], [
            'message.required' => 'Message tidak boleh kosong',
            'sender_wa.required' => 'Sender Whatsapp tidak boleh kosong',
            'receiver.required' => 'Receiver tidak boleh kosong',
            'message_type.required' => 'Message Type tidak boleh kosong',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(
                ['error' => $validator->errors()],
                'Validation Error',
                422
            );
        }

        $userID = User::where('whatsapp_number', MessageHelper::formatWhatsappNumber($request->receiver))->first();

        $result = $this->checkMessage($request->message_type, $request->message, $request->receiver, $request->image);
       

        $reply = '';
        if($result == 'Error'){
            $reply =MessageHelper::opening();
        }else if($result == 'Error Image'){
            $reply = 'Mohon maaf, gambar yang anda kirim tidak bisa dibaca, mohon kirim struk yang jelas';
        }else{
            $reply = $result;
        }

        $data = [
            'user_id' => $userID ? $userID->id : null,
            'reply' => $reply,
            'message' => $request->message,
            'sender_wa' => MessageHelper::formatWhatsappNumber($request->sender_wa),
            'receiver' => MessageHelper::formatWhatsappNumber($request->receiver),
            'message_type' => $request->message_type,
        ];

        $this->saveToLogs($data);

        return ResponseFormatter::success(
            $data,
            'Pesan berhasil dikirim'
        );
    }
}
