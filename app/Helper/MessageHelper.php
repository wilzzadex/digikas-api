<?php

namespace App\Helper;

use App\Models\Transaction;
use Illuminate\Http\JsonResponse;

class MessageHelper
{

    public static function formatWhatsappNumber($number)
    {
        $number = preg_replace("/[^0-9]/", "", $number);

        if (substr($number, 0, 1) == "0") {
            $number = "62" . substr($number, 1);
        }

        return $number;
    }

    public static function opening()
    {
        $message = "Halo Sahabat *DigiKas*!\nSaya *Babang Ai*, asisten keuangan pribadi kamu 😊\n\nPilih opsi yang kamu butuhkan dengan mengetik angka:\n1️⃣ Cara Mencatat di DigiKas\n2️⃣ Laporan Keuangan\n3️⃣ Laporan Keuangan Bulan Ini\n4️⃣ Download Aplikasi DigiKas\n5️⃣ Lapor Kendala\n\n✨ Tips:\n- Ketik angkanya saja. Contoh: 1 untuk Cara Mencatat di DigiKas.\n- Punya foto struk? Kirim langsung ke sini, dan saya bantu catat!";

        return $message;
    }

    public static function option1()
    {
        $message = "1️⃣Cara Mencatat di DigiKas\n\n💰 Untuk Catat Pengeluaran:\n•⁠  ⁠Ketik format berikut:\n[Nama Pengeluaran] [Harga]\nContoh: Bakso 15000\n——————————————————\n💵 Untuk Catat Pemasukan:\n•⁠  ⁠Ketik format berikut:\masukin [Nama Pemasukan] [Jumlah]\nContoh: masukin Gajian 5000000\n——————————————————\n📋 Untuk Catat Banyak Sekaligus:\n•⁠  ⁠Ketik seperti format di atas lalu tambahkan enter untuk setiap item:\nContoh:\nBakso 5000\nEs teh 5000\n——————————————————\n💡 Tips:\n•⁠  ⁠Angka bisa ditulis dengan format: 10rb, 10k, 10.000, 10000, 10jt.\n•⁠  ⁠Selalu gunakan format ini agar data tercatat dengan benar!\n";

        return $message;
    }

    public static function option2($user_id)
    {
        $currentDate = date('Y-m-d');
        $transaction = Transaction::where('user_id', $user_id)->where('transaction_date', $currentDate)->get();
        $totalSaldo = Transaction::where('user_id', $user_id)->where('type', 'in')->sum('price') - Transaction::where('user_id', $user_id)->where('type', 'out')->sum('price');

        $totalIncome = 0;
        $totalExpense = 0;

        foreach ($transaction as $t) {
            if ($t->type == 'in') {
                $totalIncome += $t->price;
            } else {
                $totalExpense += $t->price;
            }
        }

        $message = "2️⃣ Laporan Keuangan Hari Ini \n\n💰 Laporan Keuangan kamu hari ini:\n\n📉 Pengeluaran:\n";
        foreach ($transaction as $t) {
            if ($t->type == 'out') {
                $message .= "- " . $t->item . ": Rp. " . number_format($t->price) . "\n";
            }
        }

        $message .= "\n📈 Pemasukan:\n";
        foreach ($transaction as $t) {
            if ($t->type == 'in') {
                $message .= "- " . $t->item . ": Rp. " . number_format($t->price) . "\n";
            }
        }

        $message .= "\n📉 Total Pengeluaran: Rp. " . number_format($totalExpense) . "\n📈 Total Pemasukan: Rp. " . number_format($totalIncome) . "\n💸 Total Saldo: Rp. " . number_format($totalSaldo);
        return $message;
    }

    public static function option3($user_id)
    {
        $transaction = Transaction::where('user_id', $user_id)->where('transaction_date', 'like', '%' . date('Y-m') . '%')->get();
        $totalSaldo = Transaction::where('user_id', $user_id)->where('type', 'in')->sum('price') - Transaction::where('user_id', $user_id)->where('type', 'out')->sum('price');

        $totalIncome = 0;
        $totalExpense = 0;

        foreach ($transaction as $t) {
            if ($t->type == 'in') {
                $totalIncome += $t->price;
            } else {
                $totalExpense += $t->price;
            }
        }

        $message = "3️⃣ Laporan Keuangan Bulan Ini \n\n💰 Laporan Keuangan kamu bulan ini:\n\n📉 Pengeluaran:\n";
        foreach ($transaction as $t) {
            if ($t->type == 'out') {
                $message .= "- " . $t->item . ": Rp. " . number_format($t->price) . "\n";
            }
        }

        $message .= "\n📈 Pemasukan:\n";
        foreach ($transaction as $t) {
            if ($t->type == 'in') {
                $message .= "- " . $t->item . ": Rp. " . number_format($t->price) . "\n";
            }
        }

        $message .= "\n📉 Total Pengeluaran: Rp. " . number_format($totalExpense) . "\n📈 Total Pemasukan: Rp. " . number_format($totalIncome) . "\n💸 Total Saldo: Rp. " . number_format($totalSaldo);
        return $message;
    }

    public static function error($message = null, $data = null, $code = 400): JsonResponse
    {
        return response()->json([
            "status" => "error",
            "message" => $message,
            "data" => $data
        ], $code);
    }
}
