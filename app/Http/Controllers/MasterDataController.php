<?php

namespace App\Http\Controllers;

use App\Helper\ResponseFormatter;
use App\Models\MasterKategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MasterDataController extends Controller
{
    public function getKategori(Request $request)
    {
        $userId = Auth::user()->id;

        $currentCategory = MasterKategori::where('user_id', $userId)
            ->count();
        
        if($currentCategory == 0){
           $master = MasterKategori::whereNull('user_id')->get();
           foreach($master as $m){
               $kategori = new MasterKategori();
               $kategori->name = $m->name;
               $kategori->icon = $m->icon;
               $kategori->type = $m->type;
               $kategori->user_id = $userId;
               $kategori->save();
           }
        }

        $kategori = MasterKategori::where('user_id', $userId)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'icon' => $item->icon,
                    'type' => $item->type,
                ];
            });


        return ResponseFormatter::success($kategori, 'Data kategori berhasil diambil');
    }

    public function createKategori(Request $request)
    {
        $userId = Auth::user()->id;

        $validate = Validator::make($request->all(), [
            'name' => 'required|string|unique:kategori',
            'icon' => 'required|string',
            'type' => 'required|string',
        ]);

        if ($validate->fails()) {
            return ResponseFormatter::error(
                ['error' => $validate->errors()],
                'Validation Error',
                422
            );
        }

        $kategori = new MasterKategori();
        $kategori->name = $request->name;
        $kategori->icon = $request->icon;
        $kategori->type = $request->type;
        $kategori->user_id = $userId;

        try {
            $kategori->save();
            return ResponseFormatter::success($kategori, 'Kategori berhasil dibuat');
        } catch (\Throwable $th) {
            return ResponseFormatter::error($th->getMessage(), 'Kategori gagal dibuat', 500);
        }
    }

    public function updateKategori(Request $request, $id)
    {
        $userId = Auth::user()->id;

        $kategori = MasterKategori::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$kategori) {
            return ResponseFormatter::error(null, 'Kategori tidak ditemukan', 404);
        }

        $validate = Validator::make($request->all(), [
            'name' => 'required|string',
            'icon' => 'required|string',
            'type' => 'required|string',
        ]);

        if ($validate->fails()) {
            return ResponseFormatter::error(
                ['error' => $validate->errors()],
                'Validation Error',
                422
            );
        }

        $kategori->name = $request->name;
        $kategori->icon = $request->icon;
        $kategori->type = $request->type;

        try {
            $kategori->save();
            return ResponseFormatter::success($kategori, 'Kategori berhasil diupdate');
        } catch (\Throwable $th) {
            return ResponseFormatter::error($th->getMessage(), 'Kategori gagal diupdate', 500);
        }
    }

    public function deleteKategori(Request $request, $id)
    {
        $userId = Auth::user()->id;

        $kategori = MasterKategori::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$kategori) {
            return ResponseFormatter::error(null, 'Kategori tidak ditemukan', 404);
        }

        try {
            $kategori->delete();
            return ResponseFormatter::success(null, 'Kategori berhasil dihapus');
        } catch (\Throwable $th) {
            return ResponseFormatter::error($th->getMessage(), 'Kategori gagal dihapus', 500);
        }
    }
}
