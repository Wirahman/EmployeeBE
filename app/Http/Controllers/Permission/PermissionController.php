<?php

namespace App\Http\Controllers\Permission;

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
use App\Models\User;
use App\Models\Code_Settings;
use App\Models\Permission;
use App\Models\Log_Activities;


class PermissionController extends Controller
{
    public function createPermission(Request $request)
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
        $status_awal = htmlentities($request->input('status'));
        if($status_awal == true){
            $status = 'active';
        } else {
            $status = 'inactive';
        }
        $description = htmlentities($request->input('description'));
        
        $codePermission = Code_Settings::select("*")
        ->where("table_name", "master_permission")
        ->get()->toArray();

        $permission = Permission::select("*")->orderBy('id', 'DESC')->first();
        $codeAngka = str_pad($codePermission[0]['counter'] + 1, $codePermission[0]['digit'], '0', STR_PAD_LEFT);
        $code = $codePermission[0]['prefix'] . $codeAngka;

        DB::beginTransaction();
        try{
            $permission = Permission::select("*")
                    ->where("code", $code)
                    ->orWhere("name", $name)
                    ->get()->toArray();

            if(count($permission) === 0){
                Permission::create([
                    'code'     => $code,
                    'name'   => $name,
                    'status'     => $status,
                    'description'     => $description,
                    'created_at'    => Carbon\Carbon::now()
                ]);

                $periksaPermission = Permission::select("*")
                                ->where("code", $code)
                                ->get()->toArray();

                Code_Settings::where('table_name', 'master_permission')->update([
                    'counter'     => $codePermission[0]['counter'] + 1,
                    'updated_at'    => Carbon\Carbon::now()
                ]);
                
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Permission',
                    'table_id'      => $periksaPermission[0]['id'],
                    'action'        => 'add',
                    'changes'       => 'Permission',
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
                    'message' => 'Hak Akses sudah dibuat'
                ], 200);
            }else{
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Hak Akses sudah terdaftar!'
                ], 201);
            }
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi.!"
            ]);
        }
    }
    
    public function updatePermission(Request $request)
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
        $status_awal = htmlentities($request->input('status'));
        if($status_awal == true){
            $status = 'active';
        } else {
            $status = 'inactive';
        }
        $description = htmlentities($request->input('description'));

        $permission = Permission::select("*")
                ->where("name", $name)
                ->get()->toArray();

        if(count($permission) != 0){
            foreach($permission as $row){
                if($id != $row['id']){
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Hak akses sudah terdaftar!',
                        'data'    => ''
                    ], 201);
                }
            }
        } 
        
        DB::beginTransaction();
        try{
            $editPermission = Permission::where('id', $id)->update([
                'name'     => $name,
                'status'     => $status,
                'description'     => $description,
                'updated_at'    => Carbon\Carbon::now()
            ]);

            if($editPermission){
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Permission',
                    'table_id'      => $id,
                    'action'        => 'update',
                    'changes'       => 'Permission',
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
                    'message' => 'Hak Akses sudah diubah'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => true,
                    'message' => 'Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi.!'
                ], 200);
            }
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi.!"
            ]);
        }

        
    }
    
    public function deletePermission(Request $request)
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
            $editPermission = Permission::where('id', $id)->update([
                'status'     => 'inactive',
                'updated_at'     => Carbon\Carbon::now()
            ]);

            if($editPermission){
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Permission',
                    'table_id'      => $id,
                    'action'        => 'delete',
                    'changes'       => 'Permission',
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
                    'message' => 'Hak Akses sudah dihapus'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => false,
                    'message' => 'Hak Akses gagal dihapus, silahkan coba beberapa saat lagi'
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
    
    public function getAllPermission(Request $request)
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
        try{
            $permission = Permission::select("*")
                            // ->where("status", 'Active')
                            ->orderBy('id', 'DESC')
                            // ->skip($skip)
                            // ->take($limit)
                            ->get()->toArray();
            if(count($permission) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Hak Akses tidak ditemukan!',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Daftar semua permission',
                    'data' => $permission
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
    
    public function getPermission(Request $request)
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
            $permission = Permission::select("*")
                            ->where("id", $id)
                            ->get()->toArray();
            
            if(count($permission) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Hak Akses tidak ditemukan',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Data permission!',
                    'data'    => $permission
                ], 200);
            }
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi.!"
            ]);
        }
        
    }
    
    public function getPermissionByParams(Request $request)
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

        $permission = Permission::select("*")
                        ->where("code", 'like', '%'.$params.'%')
                        ->orWhere("name", 'like', '%'.$params.'%')
                        ->orWhere("status", 'like', '%'.$params.'%')
                        ->orWhere("description", 'like', '%'.$params.'%')
                        ->orderBy('id', 'DESC')
                        ->get()->toArray();
        
        if(count($permission) === 0){
            return response()->json([
                'success' => false,
                'message' => 'Hak Akses tidak ditemukan!',
                'data'    => ''
            ], 201);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Data permission!',
                'data'    => $permission
            ], 200);
        } 
    }



}
