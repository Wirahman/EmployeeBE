<?php

namespace App\Http\Controllers\CodeSettings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use \Carbon;
use Mail;
use App\Mail\NotifyMail;

// List model
use App\Models\User;
use App\Models\Code_Settings;
use App\Models\Log_Activities;

class CodeSettingsController extends Controller
{
    public function createCodeSettings(Request $request)
    {
        $authorization = $request->header('Authorization');
        $userToken = $request->header('UserToken');
        $headerID = $request->header('HeaderID');
        $token = str_replace('Bearer ', '', $authorization);
        if($token != '4pb4tech'){
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses!',
                'data'    => ''
            ], 402);      
        }

        $periksaHeaderPengguna = user::select("*")
                ->where("id", $headerID)
                ->get()->toArray();
                
        if(count($periksaHeaderPengguna) === 0){
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses!',
                'data'    => ''
            ], 402);      
        }

        if($periksaHeaderPengguna[0]['token'] != $userToken){
            return response()->json([
                'success' => false,
                'message' => 'Token anda sudah habis masa berlakunya!',
                'data'    => ''
            ], 402);      
        }
        
        $table_name = htmlentities($request->input('table_name'));
        $label = htmlentities($request->input('label'));
        $prefix = htmlentities($request->input('prefix'));
        $digit = htmlentities($request->input('digit'));
        $counter = 0;

        $periksaCodeSettings = Code_Settings::select("*")
                            ->where("table_name", $table_name)
                            ->get()->toArray();

        if(count($periksaCodeSettings) != 0){
            return response()->json([
                'success' => false,
                'message' => 'Kode sudah terdaftar!',
                'data'    => ''
            ], 201);
        }

        DB::beginTransaction();
        try{
            Code_Settings::create([
                'table_name'     => $table_name,
                'label'   => $label,
                'prefix'     => $prefix,
                'digit'    => $digit,
                'counter'   => $counter,
                'created_at'    => Carbon\Carbon::now()
            ]);
            
            $codeSettingsBaru =  Code_Settings::select("*")
                                ->where("table_name", $table_name)
                                ->get()->toArray();
            
            $log_activities = Log_Activities::create([
                'user_id'       => $headerID,
                'table_name'    => 'Master Kode Setting',
                'table_id'      => $codeSettingsBaru[0]['id'],
                'action'        => 'add',
                'changes'       => 'code_settings',
                'ip'            => $request->fullUrl(),
                'agent'         => $request->header('user-agent'),
                'created_at'    => Carbon\Carbon::now(),
            ]);

            if(!$log_activities) {
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi!',
                    'data'    => ''
                ], 202);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Kode Setting sudah dibuat'
            ], 200);
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi.!"
            ]);
        }
    }
    
    public function updateCodeSettings(Request $request)
    {
        $authorization = $request->header('Authorization');
        $userToken = $request->header('UserToken');
        $headerID = $request->header('HeaderID');
        $token = str_replace('Bearer ', '', $authorization);
        if($token != '4pb4tech'){
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses!',
                'data'    => ''
            ], 402);      
        }

        $periksaHeaderPengguna = user::select("*")
                ->where("id", $headerID)
                ->get()->toArray();
                
        if(count($periksaHeaderPengguna) === 0){
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses!',
                'data'    => ''
            ], 402);      
        }

        if($periksaHeaderPengguna[0]['token'] != $userToken){
            return response()->json([
                'success' => false,
                'message' => 'Token anda sudah habis masa berlakunya!',
                'data'    => ''
            ], 402);      
        }
        
        $id = htmlentities($request->input('id'));
        $table_name = htmlentities($request->input('table_name'));
        $label = htmlentities($request->input('label'));
        $prefix = htmlentities($request->input('prefix'));
        $digit = htmlentities($request->input('digit'));
        $counter = htmlentities($request->input('counter'));
        $created_at = Carbon\Carbon::now();
        
        $periksaCodeSettings = Code_Settings::select("*")
                ->where("table_name", $table_name)
                ->get()->toArray();

        if(count($periksaCodeSettings) != 0){
            foreach($periksaCodeSettings as $row){
                if($id != $row['id']){
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Kode setting sudah digunakan!',
                        'data'    => ''
                    ], 201);
                }
            }
        } 
        DB::beginTransaction();
        try{
            $editCodeSettings = Code_Settings::where('id', $id)->update([
                'table_name'     => $table_name,
                'label'     => $label,
                'prefix'     => $prefix,
                'digit'     => $digit,
                'counter'     => $counter,
                'updated_at'    => Carbon\Carbon::now()
            ]);
            if($editCodeSettings){
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Code Settings',
                    'table_id'      => $id,
                    'action'        => 'update',
                    'changes'       => 'code_settings',
                    'ip'            => $request->fullUrl(),
                    'agent'         => $request->header('user-agent'),
                    'created_at'    => Carbon\Carbon::now(),
                ]);

                if(!$log_activities) {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi!',
                        'data'    => ''
                    ], 202);
                }
                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Kode Settings sudah diubah'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => true,
                    'message' => 'Kode Settings gagal diubah, silahkan coba beberapa saat lagi'
                ], 200);
            }
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi!"
            ]);
        }
    }
    
    public function deleteCodeSettings(Request $request)
    {
        $authorization = $request->header('Authorization');
        $userToken = $request->header('UserToken');
        $headerID = $request->header('HeaderID');
        $token = str_replace('Bearer ', '', $authorization);
        if($token != '4pb4tech'){
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses!',
                'data'    => ''
            ], 402);      
        }

        $periksaHeaderPengguna = user::select("*")
                ->where("id", $headerID)
                ->get()->toArray();
                
        if(count($periksaHeaderPengguna) === 0){
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses!',
                'data'    => ''
            ], 402);      
        }

        if($periksaHeaderPengguna[0]['token'] != $userToken){
            return response()->json([
                'success' => false,
                'message' => 'Token anda sudah habis masa berlakunya!',
                'data'    => ''
            ], 402);      
        }
        
        $id = htmlentities($request->input('id'));
        
        DB::beginTransaction();
        try{
            $codeSettings = Code_Settings::select("*")
                            ->orderBy('id', 'DESC')
                            ->where("id", $id)
                            ->get()->toArray();
            
            $namaTable = $codeSettings[0]['table_name'];
            $dbHapus = Schema::hasTable($namaTable);

            if(!$dbHapus){
                Code_Settings::find($id)->delete();
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Code',
                    'table_id'      => $id,
                    'action'        => 'delete',
                    'changes'       => 'Code Settings',
                    'ip'            => $request->fullUrl(),
                    'agent'         => $request->header('user-agent'),
                    'created_at'    => Carbon\Carbon::now(),
                ]);

                if(!$log_activities) {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi!',
                        'data'    => ''
                    ], 202);
                }

                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Kode sudah dihapus'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => false,
                    'message' => 'Code settings tidak bisa dihapus, dikarenakan terdapat data didalam tablenya'
                ], 202);
            }
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem mengalami gangguan, silahkan coba beberapa saat lagi.!"
            ]);
        }


    }
    
    public function getAllCodeSettings(Request $request)
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
        
        $codeSettings = Code_Settings::select("*")
                        ->orderBy('id', 'DESC')
                        ->get()->toArray();
                                
        return response([
            'success' => true,
            'message' => 'Daftar Kode',
            'data' => $codeSettings
        ], 200);
    }
    
    public function getCodeSettingsByID(Request $request)
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
        $codeSettings = Code_Settings::select("*")
                        ->orderBy('id', 'DESC')
                        ->where("id", $id)
                        ->get()->toArray();
                                
        return response([
            'success' => true,
            'message' => 'Data Kode',
            'data' => $codeSettings
        ], 200);
    }
    
    public function getCodeSettingsByParams(Request $request)
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
        $params = htmlentities($request->input('params'));

        $codeSettings = Code_Settings::select("*")
                        ->where("table_name", 'like', '%'.$params.'%')
                        ->orWhere("label", 'like', '%'.$params.'%')
                        ->orWhere("prefix", 'like', '%'.$params.'%')
                        ->orWhere("digit", 'like', '%'.$params.'%')
                        ->orWhere("counter", 'like', '%'.$params.'%')
                        ->orderBy('id', 'DESC')
                        ->get()->toArray();
        
        if(count($codeSettings) === 0){
            return response()->json([
                'success' => false,
                'message' => 'Kode tidak ditemukan!',
                'data'    => ''
            ], 201);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Data Kode!',
                'data'    => $codeSettings
            ], 200);
        }
    
    }

    public function getByTableName(Request $request)
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
        
        $table_name = htmlentities($request->input('table_name'));
        $codesettings = Code_Settings::select("*")
                        ->orderBy('id', 'DESC')
                        ->where("table_name", $table_name)
                        ->get()->toArray();
                                
        return response([
            'success' => true,
            'message' => 'Data Code Settings',
            'data' => $codesettings
        ], 200);

    }
    


}
