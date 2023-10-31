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
use App\Models\Kabupaten_Kota;

class KabupatenKotaController extends Controller
{
    public function getAllKabupatenKota(Request $request)
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
        $id_provinsi = htmlentities($request->input('id_provinsi'));
        try{
            $kabupatenKota = Kabupaten_Kota::select("*")
                            ->where("provinsi_id", $id_provinsi)
                            ->orderBy('id', 'DESC')
                            // ->skip($skip)
                            // ->take($limit)
                            ->get()->toArray();
            if(count($kabupatenKota) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Kabupaten Kota tidak ditemukan',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Daftar semua Kabupaten Kota',
                    'data' => $kabupatenKota
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
    
    public function getAllKabupatenKotaByParams(Request $request)
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
        $id_provinsi = htmlentities($request->input('id_provinsi'));
        $params = htmlentities($request->input('params'));
        try{
            $kabupatenKota = Kabupaten_Kota::select("*")
                            ->where("provinsi_id", $id_provinsi)
                            ->where("name", 'like', '%'.$params.'%')
                            ->orderBy('id', 'DESC')
                            // ->skip($skip)
                            // ->take($limit)
                            ->get()->toArray();
            if(count($kabupatenKota) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Kabupaten Kota tidak ditemukan',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Daftar semua Kabupaten Kota',
                    'data' => $kabupatenKota
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
    
    public function getKabupatenKotaByID(Request $request)
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
        $kabupaten_kota = Kabupaten_Kota::select("*")
                        ->orderBy('id', 'DESC')
                        ->where("id", $id)
                        ->get()->toArray();
                                
        return response([
            'success' => true,
            'message' => 'Data Kabupaten / Kota',
            'data' => $kabupaten_kota
        ], 200);

    }



}
