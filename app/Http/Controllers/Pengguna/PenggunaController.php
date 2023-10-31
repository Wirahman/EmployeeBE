<?php

namespace App\Http\Controllers\Pengguna;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
use App\Models\Login_Log;

class PenggunaController extends Controller
{
    public function Login(Request $request)
    {
        $authorization = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authorization);
        if($token != '4pb4tech'){
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses!',
                'data'    => ''
            ], 401);      
        }

        $username = htmlentities($request->input('username'));
        $password = htmlentities($request->input('password'));
        
        DB::beginTransaction();
        try{
            $pengguna = user::select("*")
                            ->where("username", $username)
                            ->get()->toArray();

            if(Carbon\Carbon::now() > $pengguna[0]['token_expired']){
                $newTokenExpire = Carbon\Carbon::now()->addDays(30);
                        
                $hashed = Hash::make($password, [
                    'rounds' => 10,
                ]);

                user::where('id', $pengguna[0]['id'])->update([
                    'token'     => $hashed,
                    'token_expired'    => $newTokenExpire
                ]);
                
            }
                
            if($pengguna[0]['status'] != 'active'){
                return response()->json([
                    'success' => false,
                    'message' => 'Akun anda sudah diblokir, harap hubungi administrator untuk mengaktifkan kembali',
                    'data'    => ''
                ], 402);      
            }

            if(count($pengguna) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Pengguna tidak ditemukan!',
                    'data'    => ''
                ], 204);
            } else {
                $dblogin_log = Login_log::select("*")
                ->where("user_id", $pengguna[0]['id'])
                ->where("status", 'suspend')
                ->orderBy('id', 'DESC')
                ->get()->toArray();
                
                if($dblogin_log){
                    if($dblogin_log[0]['created_at'] > Carbon\Carbon::now()->subMinutes(2)){
                        return response()->json([
                            'success' => false,
                            'message' => 'Akun anda ditangguhkan selama 2 menit!.',
                            'data'    => ''
                        ], 202);
                    }
                }
                
                $passwordDB = $pengguna[0]['password'];
                $userIDPengguna = $pengguna[0]['id'];
                $periksaPassword = Hash::check($password, $passwordDB);
                if($periksaPassword == true){
                    $dblogin = Login_log::select("*")
                    ->where("user_id", $pengguna[0]['id'])
                    ->where("status", 'success')
                    ->orderBy('id', 'DESC')
                    ->get()->toArray();
                    
                    if($dblogin){
                        if(($dblogin[0]['agent'] != $request->header('user-agent')) && ($dblogin[0]['active_session'] == 1)){
                            return response()->json([
                                'success' => false,
                                'message' => 'Anda sudah masuk menggunakan perangkat lain!',
                                'data'    => ''
                            ], 202);
                        }    
                    }
                    
                    $dateNow = Carbon\Carbon::now();
                    if($dateNow > $pengguna[0]['token_expired']){
                        $newTokenExpired = $dateNow->addDays(30);
                        $newToken = Str::random(50);
                        $updateToken = User::where('id', $userIDPengguna)->update([
                            'token'     => $newToken,
                            'token_expired'     => $newTokenExpired,
                        ]);
                    }
                    
                    $login_log = Login_Log::create([
                        'user_id'           => $pengguna[0]['id'],
                        'active_session'    => '1',
                        'ip'                => $request->fullUrl(),
                        'agent'             => $request->header('user-agent'),
                        'status'            => 'success',
                        'created_at'        =>Carbon\Carbon::now()
                    ]);

                    if(!$login_log) {
                        DB::rollback();
                        return response()->json([
                            'success' => false,
                            'message' => 'Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi.!',
                            'data'    => ''
                        ], 202);
                    }

                    DB::commit();
                    return response()->json([
                        'success' => true,
                        'message' => 'Data Pengguna!',
                        'data'    => $pengguna
                    ], 200);
                }else{  
                    $dblogin_log = Login_log::select("*")
                                ->where("user_id", $pengguna[0]['id'])
                                ->where('created_at', '>=', Carbon\Carbon::now()->subMinutes(2))
                                ->where("status", 'failed')
                                ->orWhere("status", 'suspend')
                                ->orderBy('id', 'DESC')
                                ->get()->toArray();
  
                    if($dblogin_log){
                        if(count($dblogin_log) < 4){
                            if(count($dblogin_log) == 3){
                                if($dblogin_log[2]['created_at'] > Carbon\Carbon::now()->subMinutes(2)){
                                    $login_log = Login_Log::create([
                                        'user_id'           => $pengguna[0]['id'],
                                        'active_session'    => '0',
                                        'ip'                => $request->fullUrl(),
                                        'agent'             => $request->header('user-agent'),
                                        'status'            => 'suspend',
                                        'created_at'        =>Carbon\Carbon::now()
                                    ]);
                                    DB::commit();
                                    return response()->json([
                                        'success' => false,
                                        'message' => 'Akun anda ditangguhkan selama 2 menit!.',
                                        'data'    => ''
                                    ], 202);
                                }
                            }
                        } else {
                            DB::commit();
                            return response()->json([
                                'success' => false,
                                'message' => 'Akun anda ditangguhkan selama 2 menit!.',
                                'data'    => ''
                            ], 202);
                        }
                    }

                    $login_log = Login_Log::create([
                        'user_id'           => $pengguna[0]['id'],
                        'active_session'    => '0',
                        'ip'                => $request->fullUrl(),
                        'agent'             => $request->header('user-agent'),
                        'status'            => 'failed',
                        'created_at'        =>Carbon\Carbon::now()
                    ]);

                    if(!$login_log) {
                        DB::rollback();
                        return response()->json([
                            'success' => false,
                            'message' => 'Sistem mengalami gangguan, silahkan coba beberapa saat lagi!',
                            'data'    => ''
                        ], 202);
                    }

                    DB::commit();
                    return response()->json([
                        'success' => false,
                        'message' => 'Password anda salah!',
                        'data'    => ''
                    ], 202);
                }
            }
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem mengalami gangguan, silahkan coba beberapa saat lagi"
            ]);
        }
    }

    public function Logout(Request $request)
    {
        $authorization = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authorization);
        if($token != '4pb4tech'){
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses!',
                'data'    => ''
            ], 401);      
        }
          
        $username = htmlentities($request->input('username'));
        
        DB::beginTransaction();
        try{
            $pengguna = user::select("*")
                            ->where("username", $username)
                            ->get()->toArray();

            $login_log = Login_Log::create([
                'user_id'           => $pengguna[0]['id'],
                'active_session'    => '0',
                'ip'                => $request->fullUrl(),
                'agent'             => $request->header('user-agent'),
                'status'            => 'success',
                'created_at'        =>Carbon\Carbon::now()
            ]);

            if(!$login_log) {
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Sistem mengalami gangguan, silahkan coba beberapa saat lagi.!',
                    'data'    => ''
                ], 401);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Anda telah keluar aplikasi!'
            ], 200);
            
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem mengalami gangguan, silahkan coba beberapa saat lagi."
            ], 401);
        }
    }

    public function Register(Request $request)
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
                'message' => 'Token anda sudah tidak dapat digunakan!',
                'data'    => ''
            ], 402);      
        }

        $name = htmlentities($request->input('name'));
        $username = htmlentities($request->input('username'));
        $password = htmlentities($request->input('password'));
        $department_id = htmlentities($request->input('department_id'));
        $role_id = htmlentities($request->input('role_id'));
        $status_awal = htmlentities($request->input('status'));
        if($status_awal == true){
            $status = 'active';
        } else {
            $status = 'inactive';
        }
        $created_date = Carbon\Carbon::now();

        $hashed = Hash::make($password, [
            'rounds' => 10,
        ]);

        DB::beginTransaction();
        try{
            $pengguna = user::select("*")
            ->where("username", $username)
            ->get()->toArray();

            if(count($pengguna) === 0){
                $newTokenExpired = $created_date->addDays(30);
                $newToken = Str::random(50);

                $codeUser = Code_Settings::select("*")
                ->where("table_name", "master_user")
                ->get()->toArray();

                $user = User::select("*")->orderBy('id', 'DESC')->first();
                $codeAngka = str_pad($codeUser[0]['counter'] + 1, $codeUser[0]['digit'], '0', STR_PAD_LEFT);
                $code = $codeUser[0]['prefix'] . $codeAngka;

                user::create([
                    'code'     => $code,
                    'name'   => $name,
                    'username'     => $username,
                    'password'     => $hashed,
                    'department_id'   => $department_id,
                    'role_id'     => $role_id,
                    'status'     => $status,
                    'created_date'     => $created_date,
                    'token' => $newToken,
                    'token_expired' => $newTokenExpired
                ]);

                $periksaPengguna = User::select("*")
                                ->where("code", $code)
                                ->where("name", $name)
                                ->where("username", $username)
                                ->get()->toArray();

                Code_Settings::where('table_name', 'master_user')->update([
                    'counter'     => $codeUser[0]['counter'] + 1,
                    'updated_at'    => Carbon\Carbon::now()
                ]);

                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master User',
                    'table_id'      => $periksaPengguna[0]['id'],
                    'action'        => 'add',
                    'changes'       => 'Pengguna',
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
                    'message' => 'Pengguna sudah dibuat.'
                ], 200);
            }else{
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Pengguna sudah terdaftar!'
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

    public function UpdateUser(Request $request)
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
                'message' => 'Token anda sudah tidak dapat digunakan!',
                'data'    => ''
            ], 402);      
        }
        
        $id = htmlentities($request->input('id'));
        $name = htmlentities($request->input('name'));
        $username = htmlentities($request->input('username'));
        $department_id = htmlentities($request->input('department_id'));
        $role_id = htmlentities($request->input('role_id'));
        $status_awal = htmlentities($request->input('status'));
        if($status_awal == true){
            $status = 'active';
        } else {
            $status = 'inactive';
        }
        $updated_date = Carbon\Carbon::now();

        $user = User::select("*")
                ->where("username", $username)
                ->get()->toArray();

        if(count($user) != 0){
            foreach($user as $row){
                if($id != $row['id']){
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Pengguna sudah digunakan!',
                        'data'    => ''
                    ], 201);
                }
            }
        } 
        
        DB::beginTransaction();
        try{
            $editUser = User::where('id', $id)->update([
                'name'     => $name,
                'username'     => $username,
                'department_id'     => $department_id,
                'role_id'     => $role_id,
                'status'     => $status,
                'updated_date'     => $updated_date
            ]);
            if($editUser){
                $periksaPengguna = User::select("*")
                                ->where("name", $name)
                                ->where("username", $username)
                                ->get()->toArray();
        
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master User',
                    'table_id'      => $periksaPengguna[0]['id'],
                    'action'        => 'update',
                    'changes'       => 'Pengguna',
                    'ip'            => $request->fullUrl(),
                    'agent'         => $request->header('user-agent'),
                    'created_at'    => Carbon\Carbon::now(),
                ]);

                if(!$log_activities) {
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
                    'message' => 'Pengguna sudah diubah'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => true,
                    'message' => 'Pengguna gagal diubah, silahkan coba beberapa saat lagi'
                ], 200);
            }
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Pengguna gagal diubah, silahkan coba beberapa saat lagi."
            ]);
        }
    }

    public function DeleteUser(Request $request)
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
                'message' => 'Token anda sudah tidak dapat digunakan!',
                'data'    => ''
            ], 402);      
        }

        $id = htmlentities($request->input('id'));
        $updated_date = Carbon\Carbon::now();
        $editUser = User::where('id', $id)->update([
            'status'     => 'inactive',
            'updated_date'     => $updated_date
        ]);
        
        if($editUser){
            $periksaPengguna = User::select("*")
                            ->where("id", $id)
                            ->get()->toArray();
    
            $log_activities = Log_Activities::create([
                'user_id'       => $headerID,
                'table_name'    => 'Master User',
                'table_id'      => $periksaPengguna[0]['id'],
                'action'        => 'delete',
                'changes'       => 'Pengguna',
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
                'message' => 'Pengguna sudah dihapus',
                'data'    => ''
            ], 200);
        }else{
            DB::rollback();
            return response([
                'success' => false,
                'message' => 'Pengguna gagal dihapus, silahkan coba beberapa saat lagi'
            ], 202);
        }
    }
    
    public function getAllUser(Request $request)
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
            $user = DB::table('master_user as user')
                        ->select('user.id as id','user.code as code', 'user.name as name', 'user.username as username', 'user.status as status', 'department.name as department', 'role.name as role')
                        ->leftJoin('master_role as role', 'user.role_id', '=', 'role.id')
                        ->leftJoin('master_department as department', 'user.department_id', '=', 'department.id')
                        ->get()->toArray();
            if(count($user) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Pengguna tidak ditemukan!',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Daftar semua data pengguna',
                    'data' => $user
                ], 200);                
            }
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem mengalami gangguan, silahkan coba beberapa saat lagi."
            ]);
        }
    }
    
    public function getUserByID(Request $request)
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
            $user = User::select("*")
                            ->where("id", $id)
                            ->get()->toArray();
            
            if(count($user) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Pengguna tidak ditemukan!',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Daftar semua data pengguna!',
                    'data'    => $user
                ], 200);
            }
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem mengalami gangguan, silahkan coba beberapa saat lagi."
            ]);
        }
        
                 
    }
    
    public function getUserByParams(Request $request)
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
        
        $user = DB::table('master_user as user')
                ->select('user.id as id','user.code as code', 'user.name as name', 'user.username as username', 'user.status as status', 'department.name as department', 'role.name as role')
                ->leftJoin('master_role as role', 'user.role_id', '=', 'role.id')
                ->leftJoin('master_department as department', 'user.department_id', '=', 'department.id')
                ->where("user.code", 'like', '%'.$params.'%')
                ->orWhere("user.name", 'like', '%'.$params.'%')
                ->orWhere("user.username", 'like', '%'.$params.'%')
                ->orWhere("user.status", 'like', '%'.$params.'%')
                ->orWhere("department.name", 'like', '%'.$params.'%')
                ->orWhere("role.name", 'like', '%'.$params.'%')
                ->orderBy('user.id', 'DESC')
                ->get()->toArray();

        if(count($user) === 0){
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak ditemukan!',
                'data'    => ''
            ], 201);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Daftar semua data pengguna!',
                'data'    => $user
            ], 200);
        } 
    }

    public function ResetPassword(Request $request)
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
                'message' => 'Token anda sudah tidak dapat digunakan!',
                'data'    => ''
            ], 402);      
        }
        
        $id = htmlentities($request->input('id'));
        $password = htmlentities('12345');
        
        $hashed = Hash::make($password, [
            'rounds' => 10,
        ]);
        DB::beginTransaction();

        try{
            $editUser = User::where('id', $id)->update([
                'password'     => $hashed
            ]);
            
            $log_activities = Log_Activities::create([
                'user_id'       => $headerID,
                'table_name'    => 'Master User',
                'table_id'      => $id,
                'action'        => 'delete',
                'changes'       => 'Pengguna',
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
                'message' => 'Password sudah direset'
            ], 200);
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi."
            ]);
        }
    }

    
    public function UbahPassword(Request $request)
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
                'message' => 'Token anda sudah tidak dapat digunakan!',
                'data'    => ''
            ], 402);      
        }
        
        $id = htmlentities($request->input('id'));
        $password = htmlentities($request->input('password'));
        
        $hashed = Hash::make($password, [
            'rounds' => 10,
        ]);
        DB::beginTransaction();

        try{
            $editUser = User::where('id', $id)->update([
                'password'     => $hashed
            ]);
            
            $log_activities = Log_Activities::create([
                'user_id'       => $headerID,
                'table_name'    => 'Master User',
                'table_id'      => $id,
                'action'        => 'delete',
                'changes'       => 'Pengguna',
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
                'message' => 'Password sudah direset'
            ], 200);
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi."
            ]);
        }
    }










    
    
    






}
