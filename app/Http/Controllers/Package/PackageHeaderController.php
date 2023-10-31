<?php

namespace App\Http\Controllers\Package;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use \Carbon;
use Mail;
use App\Mail\RegisterNotificationMail;

// List model
use App\Models\User;
use App\Models\Code_Settings;
use App\Models\Log_Activities;
use App\Models\Package_Header;

class PackageHeaderController extends Controller
{
    public function createPackageHeader(Request $request)
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

        $name = htmlentities($request->input('name'));
        $description = htmlentities($request->input('description'));
        $status_awal = htmlentities($request->input('status'));
        if($status_awal == true){
            $status = 'active';
        } else {
            $status = 'inactive';
        }

        $created_at = Carbon\Carbon::now();

        $packageLama = Package_Header::select("*")
                ->where("name", $name)
                ->get()->toArray();

        if(count($packageLama) != 0){
            return response()->json([
                'success' => false,
                'message' => 'Package Header sudah terdaftar!'
            ], 201);  
        }

        $codePackage = Code_Settings::select("*")
                        ->where("table_name", "master_package_header")
                        ->get()->toArray();
        $packageHeader = Package_Header::select("*")->orderBy('id', 'DESC')->first();
        $codeAngka = str_pad($codePackage[0]['counter'] + 1, $codePackage[0]['digit'], '0', STR_PAD_LEFT);
        $code = $codePackage[0]['prefix'] . $codeAngka;
        
        DB::beginTransaction();
        try{
            Package_Header::create([
                'code'          => $code,
                'name'          => $name,
                'description'   => $description,
                'status'     => $status,
                'created_at'     => $created_at
            ]);

            Code_Settings::where('table_name', 'master_package_header')->update([
                'counter'     => $codePackage[0]['counter'] + 1,
                'updated_at'    => Carbon\Carbon::now()
            ]);

            $periksaPackageHeader = Package_Header::select("*")
                            ->where("code", $code)
                            ->get()->toArray();

            $log_activities = Log_Activities::create([
                'user_id'       => $headerID,
                'table_name'    => 'Master Package Header',
                'table_id'      => $periksaPackageHeader[0]['id'],
                'action'        => 'add',
                'changes'       => 'Package Header',
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
                'message' => 'Data Package sudah dibuat.'
            ], 200);
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi."
            ]);
        }

    }
    
    public function updatePackageHeader(Request $request)
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
        $code = htmlentities($request->input('code'));
        $name = htmlentities($request->input('name'));
        $description = htmlentities($request->input('description'));
        $status_awal = htmlentities($request->input('status'));
        if($status_awal == true){
            $status = 'active';
        } else {
            $status = 'inactive';
        }

        $updated_at = Carbon\Carbon::now();
        
        $packageAwal = Package_Header::select("*")
                ->where("name", $name)
                ->get()->toArray();

        if(count($packageAwal) != 0){
            foreach($packageAwal as $row){
                if($id != $row['id']){
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Package Header sudah digunakan!',
                        'data'    => ''
                    ], 201);
                }
            }
        } 
        
        DB::beginTransaction();
        try{
            $editPackageHeader = Package_Header::where('id', $id)->update([
                'code'          => $code,
                'name'          => $name,
                'description'   => $description,
                'status'     => $status,
                'updated_at'     => $updated_at
            ]);
        
            if($editPackageHeader){
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Package Header',
                    'table_id'      => $id,
                    'action'        => 'update',
                    'changes'       => 'Package Header',
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
                    'message' => 'Package Header sudah diubah'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => true,
                    'message' => 'Package Header gagal diubah, silahkan coba beberapa saat lagi'
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
    
    public function deletePackageHeader(Request $request)
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
            $editPackageHeader = Package_Header::where('id', $id)->update([
                'status'     => 'inactive',
                'updated_at'    => Carbon\Carbon::now()
            ]);

            if($editPackageHeader){
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Package Header',
                    'table_id'      => $id,
                    'action'        => 'delete',
                    'changes'       => 'Package Header',
                    'ip'            => $request->fullUrl(),
                    'agent'         => $request->header('user-agent'),
                    'created_at'    => Carbon\Carbon::now(),
                ]);

                if(!$log_activities) {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Sistem mengalami gangguan, silahkan coba beberapa saat lagi.!',
                        'data'    => ''
                    ], 202);
                }

                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Package Header sudah dihapus!'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => false,
                    'message' => 'Package Header gagal dihapus, silahkan coba beberapa saat lagi'
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
    
    public function getAllPackageHeader(Request $request)
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

        try{
            $packageHeader = Package_Header::select("*")
                            ->orderBy('id', 'DESC')
                            ->get()->toArray();

            if(count($packageHeader) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Package Header tidak ditemukan!',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Daftar semua kepala paket',
                    'data' => $packageHeader
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
    
    public function getPackageHeaderByID(Request $request)
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
        
        DB::beginTransaction();
        try{
            $packageHeader = Package_Header::select("*")
                            ->where("id", $id)
                            ->get()->toArray();
            
            if(count($packageHeader) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Package Header tidak ditemukan!',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Data Package Header!',
                    'data'    => $packageHeader
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
    
    public function getPackageHeaderByParams(Request $request)
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
        
        DB::beginTransaction();
        try{
            $packageHeader = Package_Header::select("*")
                            ->where("code", 'like', '%'.$params.'%')
                            ->orWhere("name", 'like', '%'.$params.'%')
                            ->orWhere("status", 'like', '%'.$params.'%')
                            ->orWhere("description", 'like', '%'.$params.'%')
                            ->get()->toArray();
            
            if(count($packageHeader) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Package Header tidak ditemukan!',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Data Package Header!',
                    'data'    => $packageHeader
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
    
}

