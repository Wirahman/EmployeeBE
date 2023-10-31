<?php

namespace App\Http\Controllers\Wilayah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use \Carbon;
use Mail;
use App\Mail\NotifyMail;

// List model
use App\Models\Kecamatan;

class KecamatanController extends Controller
{
    public function getAllKecamatan(Request $request)
    {
        $authorization = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authorization);
        if($token != '4pb4tech'){
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses!',
                'data'    => ''
            ], 402);      
        }
        
        // $page = htmlentities($request->input('page'));
        // $take = 15;
        
        // $offset = htmlentities($request->input('offset'));
        // $limit = htmlentities($request->input('limit'));
        // $skip = ($offset - 1) * $limit;
        $kabupaten_kota_id = htmlentities($request->input('kabupaten_kota_id'));
        try{
            $kecamatan = Kecamatan::select("*")
                            ->where("kabupaten_kota_id", $kabupaten_kota_id)
                            ->orderBy('id', 'DESC')
                            // ->skip($skip)
                            // ->take($limit)
                            ->get()->toArray();
            if(count($kecamatan) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Kecamatan tidak ditemukan',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Daftar semua Kecamatan',
                    'data' => $kecamatan
                ], 200);                
            }
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem mengalami gangguan, silahkan coba beberapa saat lagi.!"
            ]);
        }
    }
    
    public function getAllKecamatanByParams(Request $request)
    {
        $authorization = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authorization);
        if($token != '4pb4tech'){
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses!',
                'data'    => ''
            ], 402);      
        }
        
        // $page = htmlentities($request->input('page'));
        // $take = 15;
        
        // $offset = htmlentities($request->input('offset'));
        // $limit = htmlentities($request->input('limit'));
        // $skip = ($offset - 1) * $limit;
        $kabupaten_kota_id = htmlentities($request->input('kabupaten_kota_id'));
        $params = htmlentities($request->input('params'));
        try{
            $kecamatan = Kecamatan::select("*")
                            ->where("kabupaten_kota_id", $kabupaten_kota_id)
                            ->where("name", 'like', '%'.$params.'%')
                            ->orderBy('id', 'DESC')
                            // ->skip($skip)
                            // ->take($limit)
                            ->get()->toArray();
            if(count($kecamatan) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Kecamatan tidak ditemukan',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Daftar semua Kecamatan',
                    'data' => $kecamatan
                ], 200);                
            }
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem mengalami gangguan, silahkan coba beberapa saat lagi.!"
            ]);
        }
    }
    
    public function getKecamatanByID(Request $request)
    {
        $authorization = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authorization);
        if($token != '4pb4tech'){
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses!',
                'data'    => ''
            ], 402);      
        }
        
        $id = htmlentities($request->input('id'));
        $kecamatan = Kecamatan::select("*")
                        ->orderBy('id', 'DESC')
                        ->where("id", $id)
                        ->get()->toArray();
                                
        return response([
            'success' => true,
            'message' => 'Data Kecamatan',
            'data' => $kecamatan
        ], 200);

    }


}
