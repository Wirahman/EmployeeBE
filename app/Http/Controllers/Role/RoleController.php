<?php

namespace App\Http\Controllers\Role;

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
use App\Models\Permission;
use App\Models\Role;
use App\Models\Role_permission;
use App\Models\Code_Settings;
use App\Models\Log_Activities;

class RoleController extends Controller
{
    public function createRole(Request $request){
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
        $isiCheckBox = $request->input('isiCheckBox');
        $status_awal = htmlentities($request->input('status'));
        if($status_awal == true){
            $status = 'active';
        } else {
            $status = 'inactive';
        }
        
        $codeRole = Code_Settings::select("*")
        ->where("table_name", "master_role")
        ->get()->toArray();
        $role = Role::select("*")->orderBy('id', 'DESC')->first();
        $codeAngka = str_pad($codeRole[0]['counter'] + 1, $codeRole[0]['digit'], '0', STR_PAD_LEFT);
        $code = $codeRole[0]['prefix'] . $codeAngka;
        
        $roleLama = role::select("*")
        ->where("name", $name)
        ->get()->toArray();

        if(count($roleLama) != 0){
            return response()->json([
                'success' => false,
                'message' => 'Peran sudah terdaftar!'
            ], 201);  
        }

        DB::beginTransaction();
        try{
            $role = role::select("*")
                    ->where("code", $code)
                    ->orWhere("name", $name)
                    ->get()->toArray();

            if(count($role) === 0){
                $buatRole = Role::create([
                    'code'     => $code,
                    'name'   => $name,
                    'status'     => $status,
                    'description'     => $description,
                    'created_at'    => Carbon\Carbon::now()
                ]);

                if($buatRole){
                    $dbUserBaru = Role::select("*")
                    ->where("code", $code)
                    ->where("name", $name)
                    ->where("status", $status)
                    ->where("description", $description)
                    ->get()->toArray();

                    foreach($isiCheckBox as $row){
                        $dbPermission = Permission::select("*")
                                ->where("name", $row)
                                ->get()->toArray();
                        
                        Role_permission::create([
                            'role_id'   => $dbUserBaru[0]['id'],
                            'permission_id' => $dbPermission[0]['id']
                        ]);
                    }
                }
                
                $periksaRole = Role::select("*")
                                ->where("code", $code)
                                ->get()->toArray();

                Code_Settings::where('table_name', 'master_role')->update([
                    'counter'     => $codeRole[0]['counter'] + 1,
                    'updated_at'    => Carbon\Carbon::now()
                ]);

                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Role',
                    'table_id'      => $periksaRole[0]['id'],
                    'action'        => 'add',
                    'changes'       => 'Role',
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
                    'message' => 'Peran sudah dibuat!'
                ], 200);
            }else{
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Peran sudah terdaftar!'
                ], 201);
            }
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi!"
            ]);
        }

    }

    public function updateRole(Request $request){
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

        $role = Role::select("*")
                ->where("name", $name)
                ->get()->toArray();

        if(count($role) != 0){
            foreach($role as $row){
                if($id != $row['id']){
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Peran sudah digunakan!',
                        'data'    => ''
                    ], 201);
                }
            }
        } 
        
        DB::beginTransaction();
        try{
            $editRole = Role::where('id', $id)->update([
                'name'     => $name,
                'status'     => $status,
                'description'     => $description,
                'updated_at'    => Carbon\Carbon::now()
            ]);

            if($editRole){
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Role',
                    'table_id'      => $id,
                    'action'        => 'update',
                    'changes'       => 'Role',
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
                    'message' => 'Peran sudah diubah'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => true,
                    'message' => 'Peran gagal diubah, silahkan coba beberapa saat lagi'
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
    
    public function deleteRole(Request $request){
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
        
        // Periksa User yang menggunakan role
        $periksaUser = User::select("*")
                ->where("role_id", $id)
                ->get()->toArray();

        if(count($periksaUser) != 0){
            return response()->json([
                'success' => false,
                'message' => 'Peran tidak dapat dihapus, karena ada pengguna yang menggunakan role ini',
                'data'    => ''
            ], 402);      
        }
        
        DB::beginTransaction();
        try{
            $editRole = Role::where('id', $id)->update([
                'status'     => 'inactive',
                'updated_at'    => Carbon\Carbon::now()
            ]);

            if($editRole){
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Role',
                    'table_id'      => $id,
                    'action'        => 'delete',
                    'changes'       => 'Role',
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
                    'message' => 'Peran sudah dihapus!'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => false,
                    'message' => 'Peran gagal dihapus, silahkan coba beberapa saat lagi'
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

    public function getAllRole(Request $request)
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
            $role = Role::select("*")
                            // ->where("status", 'Active')
                            ->orderBy('id', 'DESC')
                            // ->skip($skip)
                            // ->take($limit)
                            ->get()->toArray();
            if(count($role) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Peran tidak ditemukan',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Daftar semua Peran',
                    'data' => $role
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

    public function getRole(Request $request)
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
            $role = Role::select("*")
                            ->where("id", $id)
                            ->get()->toArray();
            
            if(count($role) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Peran tidak ditemukan!',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Data Peran!',
                    'data'    => $role
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
    
    public function getRoleByParams(Request $request)
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

        $role = Role::select("*")
                        ->where("code", 'like', '%'.$params.'%')
                        ->orWhere("name", 'like', '%'.$params.'%')
                        ->orWhere("status", 'like', '%'.$params.'%')
                        ->orWhere("description", 'like', '%'.$params.'%')
                        ->orderBy('id', 'DESC')
                        ->get()->toArray();
        
        if(count($role) === 0){
            return response()->json([
                'success' => false,
                'message' => 'Peran tidak ditemukan!',
                'data'    => ''
            ], 201);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Data Peran!',
                'data'    => $role
            ], 200);
        } 
    }
    






}
