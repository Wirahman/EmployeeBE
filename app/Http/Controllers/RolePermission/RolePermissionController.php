<?php

namespace App\Http\Controllers\RolePermission;

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
use App\Models\Role_permission;
use App\Models\Log_Activities;


class RolePermissionController extends Controller
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
        $status = htmlentities($request->input('status'));
        $description = htmlentities($request->input('description'));
        $permission = Permission::select("*")->orderBy('id', 'DESC')->first();
        
        if($permission == null){
            $code = '001';
        } else {
            $code = str_pad($permission['code'] + 1, $permission[0]['digit'], '0', STR_PAD_LEFT);
        }
        
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
                    'description'     => $description
                ]);

                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Permission',
                    'table_id'      => $permission[0]['id'],
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
                    'message' => 'Permission sudah dibuat.'
                ], 200);
            }else{
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Permission sudah terdaftar!'
                ], 201);
            }
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi."
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
        $status = htmlentities($request->input('status'));
        $description = htmlentities($request->input('description'));

        DB::beginTransaction();
        try{
            $editPermission = Permission::where('id', $id)->update([
                'name'     => $name,
                'status'     => $status,
                'description'     => $description,
            ]);

            if($editPermission){
                $log_activity = Log_Activity::create([
                    'user_id'     => $headerID,
                    // 'device'   => Request::header('user-agent'),
                    // 'ip_address'     => Request::fullUrl(),
                    'device'   => $request->header('user-agent'),
                    'ip_address'     => $request->fullUrl(),
                    'activity'     => "Update Permission",
                    'created_date'   => Carbon\Carbon::now(),
                ]);
                if(!$log_activity) {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi.!',
                        'data'    => ''
                    ], 202);
                }

                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Permission sudah diubah'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => true,
                    'message' => 'Permission gagal diubah, silahkan coba beberapa saat lagi'
                ], 200);
            }
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi."
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
                'updated_date'     => Carbon\Carbon::now()
            ]);

            if($editPermission){
                $log_activity = Log_Activity::create([
                    'user_id'     => $headerID,
                    // 'device'   => Request::header('user-agent'),
                    // 'ip_address'     => Request::fullUrl(),
                    'device'   => $request->header('user-agent'),
                    'ip_address'     => $request->fullUrl(),
                    'activity'     => "Delete Permission",
                    'created_date'   => Carbon\Carbon::now(),
                ]);
                if(!$log_activity) {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi.!',
                        'data'    => ''
                    ], 202);
                }

                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Permission sudah didelete'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => false,
                    'message' => 'Permission gagal didelete, silahkan coba beberapa saat lagi'
                ], 202);
            }
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi."
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
        
        $id = htmlentities($request->input('id'));
        try{
            $permission = Permission::select("*", DB::raw('
                master_permission.id as id,
                master_permission.code as code,
                master_permission.name as name,
                master_permission.menu as menu,
                master_permission.router_link as router_link,
                master_permission.status as status,
                master_permission.description as description,
                master_role_permission.role_id as role_id,
                master_role_permission.permission_id as permission_id
            ')) -> leftJoin('master_role_permission', function($join) {
                $join->on('master_permission.id', '=', 'master_role_permission.permission_id');
            })->where("master_permission.status", 'Active')
            ->where("master_role_permission.role_id", '1')
            ->orderBy('master_permission.id', 'DESC')
            ->get()->toArray();

            if(count($permission) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Permission tidak ditemukan!',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Daftar semua data permission',
                    'data' => $permission
                ], 200);                
            }
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi."
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
                    'message' => 'Permission tidak ditemukan!',
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
                "message"=>"Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi."
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

        $permission = Permission::select('*', DB::raw('
            master_permission.id as id,
            master_permission.code as code,
            master_permission.name as name,
            master_permission.menu as menu,
            master_permission.router_link as router_link,
            master_permission.status as status,
            master_permission.description as description,
            master_role_permission.role_id as role_id,
            master_role_permission.permission_id as permission_id
        ')) -> leftJoin('master_role_permission', function($join) {
            $join->on('master_permission.id', '=', 'master_role_permission.permission_id');
        })->where("master_permission.status", 'Active')
            ->where("master_permission.code", 'like', '%'.$params.'%')
            ->orWhere("master_permission.name", 'like', '%'.$params.'%')
            ->orWhere("master_permission.menu", 'like', '%'.$params.'%')
            ->orWhere("master_permission.router_link", 'like', '%'.$params.'%')
            ->orWhere("master_permission.status", 'like', '%'.$params.'%')
            ->orWhere("master_permission.description", 'like', '%'.$params.'%')
            ->orderBy('master_permission.id', 'ASC')
            ->get()->toArray();
        
        if(count($permission) === 0){
            return response()->json([
                'success' => false,
                'message' => 'Permission tidak ditemukan!',
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

    public function updateRolePermission(Request $request)
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
        
        $role_id = htmlentities($request->input('role_id'));
        $name = htmlentities($request->input('name'));

        $dbPermission =  Permission::select("*")
                    ->where("name", $name)
                    ->get()->toArray();

        DB::beginTransaction();
        try{
            $permission_id = $dbPermission[0]['id'];
            $rolePermission = Role_permission::select("*")
                ->where("role_id", $role_id)
                ->where("permission_id", $permission_id)
                ->get()->toArray();  
                
            if(count($rolePermission) === 0){
                $rolePermissionBaru = Role_Permission::create([
                    'role_id'   => $role_id,
                    'permission_id' => $permission_id,
                    'status'    => 'active'
                ]);
                
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Role Permission',
                    'table_id'      => $rolePermissionBaru['id'],
                    'action'        => 'add',
                    'changes'       => 'Role Permission',
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
                    'message' => 'User Data!',
                    'data'    => $rolePermissionBaru
                ], 200);
            }else{
                $res=Role_Permission::where('id',$rolePermission[0]['id'])->delete();
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Role Permission',
                    'table_id'      => $role_id,
                    'action'        => 'delete',
                    'changes'       => 'Role Permission',
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
                    'message' => 'User Data!',
                    'data'    => ''
                ], 200);
            }  

        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi."
            ]);
        }
    }
    
    public function getAllMenu(Request $request)
    {
        $authorization = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authorization);
        $headerID = $request->header('HeaderID');
        if($token != '4pb4tech'){
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses!',
                'data'    => ''
            ], 402);      
        }
        
        try{
            $dataPengguna = user::select("*")
                    ->where("id", $headerID)
                    ->get()->toArray();
                    
            $permission = Role_permission::select(DB::raw('
                master_role_permission.role_id as role_id,
                master_role_permission.permission_id as permission_id,
                master_permission.code as code,
                master_permission.name as name,
                master_permission.status as status,
                master_permission.icon as icon,
                master_permission.description as description,
                master_permission.menu as menu,
                master_permission.router_link as router_link
            ')) -> leftJoin('master_permission', function($join) {
                $join->on('master_permission.id', '=', 'master_role_permission.permission_id');
            }) -> leftJoin('master_role', function($join) {
                $join->on('master_role.id', '=', 'master_role_permission.role_id');
            })->where("master_permission.menu", '!=', NULL)
            ->where("master_permission.menu", '!=', '')
            ->where("master_role.id", '=', $dataPengguna[0]['role_id'])
            ->where("master_permission.status", 'Active')
            ->orderBy('master_permission.id', 'ASC')
            ->get()->toArray();
            
            if(count($permission) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Permission tidak ditemukan!',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Daftar semua data permission',
                    'data' => $permission
                ], 200);                
            }
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi."
            ]);
        } 
    }

    public function getValidasiButton(Request $request)
    {
        $authorization = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authorization);
        $headerID = $request->header('HeaderID');
        if($token != '4pb4tech'){
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses!',
                'data'    => ''
            ], 402);      
        }
        
        $namePermissionCreate = htmlentities($request->input('namePermissionCreate'));
        $namePermissionEdit = htmlentities($request->input('namePermissionEdit'));
        $namePermissionDelete = htmlentities($request->input('namePermissionDelete'));
        try{
            $dataPengguna = user::select("*")
                    ->where("id", $headerID)
                    ->get()->toArray();

            if(count($dataPengguna) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Pengguna tidak ditemukan!',
                    'data'    => ''
                ], 201);
            } else {
                $role_id = $dataPengguna[0]['role_id'];
                
                $permissionCreate = Permission::select("*")
                            ->where("name", $namePermissionCreate)
                            ->get()->toArray();
                
                if(count($permissionCreate) === 0){
                    $statusButton['statusButtonCreate'] = true;
                } else {
                    $Role_permission = Role_permission::select("*")
                    ->where("role_id", $role_id)
                    ->where("permission_id", $permissionCreate[0]['id'])
                    ->get()->toArray();
                    if(count($Role_permission) === 0){
                        $statusButton['statusButtonCreate'] = true;
                    }else{
                        $statusButton['statusButtonCreate'] = false;
                    }
                }

                $permissionEdit = Permission::select("*")
                        ->where("name", $namePermissionEdit)
                        ->get()->toArray();
        
                if(count($permissionEdit) === 0){
                    $statusButton['statusButtonEdit'] = true;
                } else {
                    $Role_permission = Role_permission::select("*")
                    ->where("role_id", $role_id)
                    ->where("permission_id", $permissionEdit[0]['id'])
                    ->get()->toArray();
                    if(count($Role_permission) === 0){
                        $statusButton['statusButtonEdit'] = true;
                    }else{
                        $statusButton['statusButtonEdit'] = false;
                    }
                }
                
                $permissionDelete = Permission::select("*")
                        ->where("name", $namePermissionDelete)
                        ->get()->toArray();
        
                if(count($permissionDelete) === 0){
                    $statusButton['statusButtonDelete'] = true;
                } else {
                    $Role_permission = Role_permission::select("*")
                    ->where("role_id", $role_id)
                    ->where("permission_id", $permissionDelete[0]['id'])
                    ->get()->toArray();
                    if(count($Role_permission) === 0){
                        $statusButton['statusButtonDelete'] = true;
                    }else{
                        $statusButton['statusButtonDelete'] = false;
                    }
                }                
        
                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Daftar Button data',
                    'data' => $statusButton
                ], 200);                
            }
                    
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi."
            ]);
        } 
        
    }

    public function updateRoleSemuaPermission(Request $request)
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
        
        $role_id = htmlentities($request->input('id'));
        
        $Role_permission = Role_permission::select("*")
                    ->where("role_id", $role_id)
                    ->get()->toArray();

        DB::beginTransaction();
        try{
            if(count($Role_permission) === 0){
                $allpermission = permission::select("*")
                ->get()->toArray();

                foreach($allpermission as $row){
                    Role_permission::create([
                        'role_id'     => $role_id,
                        'permission_id'     => $row['id']
                    ]);
                }
                
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Role Permission',
                    'table_id'      => $role_id,
                    'action'        => 'add',
                    'changes'       => 'All Role Permission',
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
                    'message' => 'Masukkan role permission!',
                    'data'    => false
                ], 200);
            } else {
                $permission = Permission::select("*")
                ->get()->toArray();
                
                if(count($permission) === count($Role_permission)){
                    Role_permission::where('role_id', $role_id)->delete();
                    $log_activities = Log_Activities::create([
                        'user_id'       => $headerID,
                        'table_name'    => 'Master Role Permission',
                        'table_id'      => $role_id,
                        'action'        => 'delete',
                        'changes'       => 'All Role Permission',
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
                        'message' => 'Hapus semua role permissions!',
                        'data'    => true
                    ], 200);
                }else{
                    foreach($permission as $row){
                        $Role_permission = Role_permission::select("*")
                                    ->where("role_id", $role_id)
                                    ->where("permission_id", $row['id'])
                                    ->get()->toArray();
                        if(!$Role_permission){
                            Role_permission::create([
                                'role_id'     => $role_id,
                                'permission_id'     => $row['id']
                            ]);
                        }
                        
                    }
                    $log_activities = Log_Activities::create([
                        'user_id'       => $headerID,
                        'table_name'    => 'Master Role Permission',
                        'table_id'      => $role_id,
                        'action'        => 'add',
                        'changes'       => 'All Role Permission',
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
                        'message' => 'Buat semua role permission!',
                        'data'    => true
                    ], 200);
                }
            }
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi."
            ]);
        } 
       
    }


    public function periksaCheckBoxAllRolePermission(Request $request)
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
        
        $role_id = htmlentities($request->input('id'));
        $checkBoxAllRolePermission = [];
        $permission = Permission::select("*")
                    ->get()->toArray();
                    
        $checkBoxAllRolePermission['checkBoxAllFitur'] = false;

        $checkBoxAllRolePermission['checkboxReadUser'] = false;
        $checkBoxAllRolePermission['checkboxCreateUser'] = false;
        $checkBoxAllRolePermission['checkboxUpdateUser'] = false;
        $checkBoxAllRolePermission['checkboxDeleteUser'] = false;
        
        $checkBoxAllRolePermission['checkboxReadRole'] = false;
        $checkBoxAllRolePermission['checkboxCreateRole'] = false;
        $checkBoxAllRolePermission['checkboxUpdateRole'] = false;
        $checkBoxAllRolePermission['checkboxDeleteRole'] = false;
        
        $checkBoxAllRolePermission['checkboxReadPermission'] = false;
        $checkBoxAllRolePermission['checkboxCreatePermission'] = false;
        $checkBoxAllRolePermission['checkboxUpdatePermission'] = false;
        $checkBoxAllRolePermission['checkboxDeletePermission'] = false;
        
        $checkBoxAllRolePermission['checkboxReadDepartment'] = false;
        $checkBoxAllRolePermission['checkboxCreateDepartment'] = false;
        $checkBoxAllRolePermission['checkboxUpdateDepartment'] = false;
        $checkBoxAllRolePermission['checkboxDeleteDepartment'] = false;
        
        $checkBoxAllRolePermission['checkboxReadCodeSettings'] = false;
        $checkBoxAllRolePermission['checkboxCreateCodeSettings'] = false;
        $checkBoxAllRolePermission['checkboxUpdateCodeSettings'] = false;
        $checkBoxAllRolePermission['checkboxDeleteCodeSettings'] = false;

        $checkBoxAllRolePermission['checkboxReadCustomer'] = false;
        $checkBoxAllRolePermission['checkboxCreateCustomer'] = false;
        $checkBoxAllRolePermission['checkboxUpdateCustomer'] = false;
        $checkBoxAllRolePermission['checkboxDeleteCustomer'] = false;

        $checkBoxAllRolePermission['checkboxReadKlinik'] = false;
        $checkBoxAllRolePermission['checkboxCreateKlinik'] = false;
        $checkBoxAllRolePermission['checkboxUpdateKlinik'] = false;
        $checkBoxAllRolePermission['checkboxDeleteKlinik'] = false;
        
        $checkBoxAllRolePermission['checkboxReadUserClenic'] = false;
        $checkBoxAllRolePermission['checkboxCreateUserClenic'] = false;
        $checkBoxAllRolePermission['checkboxUpdateUserClenic'] = false;
        $checkBoxAllRolePermission['checkboxDeleteUserClenic'] = false;

        $checkBoxAllRolePermission['checkboxReadPackageHeader'] = false;
        $checkBoxAllRolePermission['checkboxCreatePackageHeader'] = false;
        $checkBoxAllRolePermission['checkboxUpdatePackageHeader'] = false;
        $checkBoxAllRolePermission['checkboxDeletePackageHeader'] = false;

        $checkBoxAllRolePermission['checkboxReadPackageDetail'] = false;
        $checkBoxAllRolePermission['checkboxCreatePackageDetail'] = false;
        $checkBoxAllRolePermission['checkboxUpdatePackageDetail'] = false;
        $checkBoxAllRolePermission['checkboxDeletePackageDetail'] = false;

        $checkBoxAllRolePermission['checkboxReadTermsOfPayment'] = false;
        $checkBoxAllRolePermission['checkboxCreateTermsOfPayment'] = false;
        $checkBoxAllRolePermission['checkboxUpdateTermsOfPayment'] = false;
        $checkBoxAllRolePermission['checkboxDeleteTermsOfPayment'] = false;

        $checkBoxAllRolePermission['checkboxReadContractHeader'] = false;
        $checkBoxAllRolePermission['checkboxCreateContractHeader'] = false;
        $checkBoxAllRolePermission['checkboxUpdateContractHeader'] = false;
        $checkBoxAllRolePermission['checkboxDeleteContractHeader'] = false;

        $checkBoxAllRolePermission['checkboxReadContractDetail'] = false;
        $checkBoxAllRolePermission['checkboxCreateContractDetail'] = false;
        $checkBoxAllRolePermission['checkboxUpdateContractDetail'] = false;
        $checkBoxAllRolePermission['checkboxDeleteContractDetail'] = false;

        $checkBoxAllRolePermission['checkboxReadLogActivities'] = false;
        $checkBoxAllRolePermission['checkboxCreateLogActivies'] = false;
        $checkBoxAllRolePermission['checkboxUpdateLogActivies'] = false;
        $checkBoxAllRolePermission['checkboxDeleteLogActivies'] = false;

        $no = 0;
        foreach($permission as $row)
        {
            $Role_permission = Role_permission::select("*")
                            ->where("role_id", $role_id)
                            ->where("permission_id", $row['id'])
                            ->get()->toArray();
            if(count($Role_permission) != 0){
                $no++;
            }
            
            if($row['name'] == 'Create User' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxCreateUser'] = true;
            } else if($row['name'] == 'Read User' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxReadUser'] = true;
            } else if($row['name'] == 'Update User' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxUpdateUser'] = true;
            } else if($row['name'] == 'Delete User' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxDeleteUser'] = true;
            }
            
            if($row['name'] == 'Create Role' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxCreateRole'] = true;
            } else if($row['name'] == 'Read Role' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxReadRole'] = true;
            } else if($row['name'] == 'Update Role' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxUpdateRole'] = true;
            } else if($row['name'] == 'Delete Role' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxDeleteRole'] = true;
            }
            
            if($row['name'] == 'Create Permission' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxCreatePermission'] = true;
            } else if($row['name'] == 'Read Permission' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxReadPermission'] = true;
            } else if($row['name'] == 'Update Permission' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxUpdatePermission'] = true;
            } else if($row['name'] == 'Delete Permission' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxDeletePermission'] = true;
            }
            
            if($row['name'] == 'Create Department' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxCreateDepartment'] = true;
            } else if($row['name'] == 'Read Department' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxReadDepartment'] = true;
            } else if($row['name'] == 'Update Department' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxUpdateDepartment'] = true;
            } else if($row['name'] == 'Delete Department' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxDeleteDepartment'] = true;
            }
            
            if($row['name'] == 'Create Code Settings' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxCreateCodeSettings'] = true;
            } else if($row['name'] == 'Read Code Settings' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxReadCodeSettings'] = true;
            } else if($row['name'] == 'Update Code Settings' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxUpdateCodeSettings'] = true;
            } else if($row['name'] == 'Delete Code Settings' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxDeleteCodeSettings'] = true;
            }
            
            if($row['name'] == 'Create Customer' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxCreateCustomer'] = true;
            } else if($row['name'] == 'Read Customer' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxReadCustomer'] = true;
            } else if($row['name'] == 'Update Customer' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxUpdateCustomer'] = true;
            } else if($row['name'] == 'Delete Customer' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxDeleteCustomer'] = true;
            }
            
            if($row['name'] == 'Create Klinik' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxCreateKlinik'] = true;
            } else if($row['name'] == 'Read Klinik' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxReadKlinik'] = true;
            } else if($row['name'] == 'Update Klinik' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxUpdateKlinik'] = true;
            } else if($row['name'] == 'Delete Klinik' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxDeleteKlinik'] = true;
            }
            
            if($row['name'] == 'Create User Clenic' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxCreateUserClenic'] = true;
            } else if($row['name'] == 'Read User Clenic' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxReadUserClenic'] = true;
            } else if($row['name'] == 'Update User Clenic' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxUpdateUserClenic'] = true;
            } else if($row['name'] == 'Delete User Clenic' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxDeleteUserClenic'] = true;
            }
         
            if($row['name'] == 'Create Package Header' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxCreatePackageHeader'] = true;
            } else if($row['name'] == 'Read Package Header' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxReadPackageHeader'] = true;
            } else if($row['name'] == 'Update Package Header' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxUpdatePackageHeader'] = true;
            } else if($row['name'] == 'Delete Package Header' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxDeletePackageHeader'] = true;
            }
         
            if($row['name'] == 'Create Package Detail' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxCreatePackageDetail'] = true;
            } else if($row['name'] == 'Read Package Detail' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxReadPackageDetail'] = true;
            } else if($row['name'] == 'Update Package Detail' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxUpdatePackageDetail'] = true;
            } else if($row['name'] == 'Delete Package Detail' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxDeletePackageDetail'] = true;
            }
         
            if($row['name'] == 'Create Terms Of Payment' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxCreateTermsOfPayment'] = true;
            } else if($row['name'] == 'Read Terms Of Payment' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxReadTermsOfPayment'] = true;
            } else if($row['name'] == 'Update Terms Of Payment' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxUpdateTermsOfPayment'] = true;
            } else if($row['name'] == 'Delete Terms Of Payment' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxDeleteTermsOfPayment'] = true;
            }
         
            if($row['name'] == 'Create Contract Header' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxCreateContractHeader'] = true;
            } else if($row['name'] == 'Read Contract Header' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxReadContractHeader'] = true;
            } else if($row['name'] == 'Update Contract Header' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxUpdateContractHeader'] = true;
            } else if($row['name'] == 'Delete Contract Header' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxDeleteContractHeader'] = true;
            }
         
            if($row['name'] == 'Create Contract Detail' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxCreateContractDetail'] = true;
            } else if($row['name'] == 'Read Contract Detail' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxReadContractDetail'] = true;
            } else if($row['name'] == 'Update Contract Detail' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxUpdateContractDetail'] = true;
            } else if($row['name'] == 'Delete Contract Detail' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxDeleteContractDetail'] = true;
            }
         
            if($row['name'] == 'Create Log Activities' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxCreateLogActivities'] = true;
            } else if($row['name'] == 'Read Log Activities' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxReadLogActivities'] = true;
            } else if($row['name'] == 'Update Log Activities' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxUpdateLogActivities'] = true;
            } else if($row['name'] == 'Delete Log Activities' && count($Role_permission) != 0){
                $checkBoxAllRolePermission['checkboxDeleteLogActivities'] = true;
            }
        }

        if(count($permission) == $no){
            $checkBoxAllRolePermission['checkBoxAllFitur'] = true;
        }

        return response()->json([
            'success' => false,
            'message' => 'Check Box semua Role Permission',
            'data'    => $checkBoxAllRolePermission
        ], 200);   
    }
    
          





}
