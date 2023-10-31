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
use App\Models\Desa_Kelurahan;

class DesaKelurahanController extends Controller
{
    public function getAllDesaKelurahan(Request $request)
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
        $kecamatan_id = htmlentities($request->input('kecamatan_id'));
        try{
            $desaKelurahan = Desa_Kelurahan::select("*")
                            ->where("kecamatan_id", $kecamatan_id)
                            ->orderBy('id', 'DESC')
                            // ->skip($skip)
                            // ->take($limit)
                            ->get()->toArray();
            if(count($desaKelurahan) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Desa / Kelurahan tidak ditemukan',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Daftar semua Desa / Kelurahan',
                    'data' => $desaKelurahan
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
    
    public function getAllDesaKelurahanByParams(Request $request)
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
        $kecamatan_id = htmlentities($request->input('kecamatan_id'));
        $params = htmlentities($request->input('params'));
        try{
            $desaKelurahan = Desa_Kelurahan::select("*")
                            ->where("kecamatan_id", $kecamatan_id)
                            ->where("name", 'like', '%'.$params.'%')
                            ->orderBy('id', 'DESC')
                            // ->skip($skip)
                            // ->take($limit)
                            ->get()->toArray();
            if(count($desaKelurahan) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Desa / Kelurahan tidak ditemukan',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Daftar semua Desa / Kelurahan',
                    'data' => $desaKelurahan
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
    
    public function getDesaKelurahanByID(Request $request)
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
        $desaKelurahan = Desa_Kelurahan::select("*")
                        ->orderBy('id', 'DESC')
                        ->where("id", $id)
                        ->get()->toArray();
                                
        return response([
            'success' => true,
            'message' => 'Data Desa / Kelurahan',
            'data' => $desaKelurahan
        ], 200);

    }


}
