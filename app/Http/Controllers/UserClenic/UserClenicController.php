<?php

namespace App\Http\Controllers\UserClenic;

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
use App\Models\User_Clenic;
use App\Models\Package_Header;

class UserClenicController extends Controller
{
    public function createUserClenic(Request $request)
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
        $category = htmlentities($request->input('category'));
        $description = htmlentities($request->input('description'));
        $status_awal = htmlentities($request->input('status'));
        if($status_awal == true){
            $status = 'active';
        } else {
            $status = 'inactive';
        }
        $user_sales_type = htmlentities($request->input('user_sales_type'));
        $package_header_id = htmlentities($request->input('package_header_id'));
        $price = htmlentities($request->input('price'));

        $created_at = Carbon\Carbon::now();

        $codeUserClenic = Code_Settings::select("*")
                        ->where("table_name", "master_user_clenic")
                        ->get()->toArray();
        $userClenic = User_Clenic::select("*")->orderBy('id', 'DESC')->first();
        $codeAngka = str_pad($codeUserClenic[0]['counter'] + 1, $codeUserClenic[0]['digit'], '0', STR_PAD_LEFT);
        $code = $codeUserClenic[0]['prefix'] . $codeAngka;
        
        DB::beginTransaction();
        try{
            User_Clenic::create([
                'code'          => $code,
                'name'          => $name,
                'category'   => $category,
                'description'   => $description,
                'user_sales_type'   => $user_sales_type,
                'package_header_id'   => $package_header_id,
                'price'   => $price,
                'status'     => $status,
                'created_at'     => $created_at
            ]);

            Code_Settings::where('table_name', 'master_user_clenic')->update([
                'counter'     => $codeUserClenic[0]['counter'] + 1,
                'updated_at'    => Carbon\Carbon::now()
            ]);

            $periksaUserClenic = User_Clenic::select("*")
                            ->where("code", $code)
                            ->get()->toArray();

            $log_activities = Log_Activities::create([
                'user_id'       => $headerID,
                'table_name'    => 'Master User Clenic',
                'table_id'      => $periksaUserClenic[0]['id'],
                'action'        => 'add',
                'changes'       => 'User Clenic',
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
                'message' => 'User Clenic sudah dibuat.'
            ], 200);
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi."
            ]);
        }

    }
    
    public function updateUserClenic(Request $request)
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
        $category = htmlentities($request->input('category'));
        $description = htmlentities($request->input('description'));
        $status_awal = htmlentities($request->input('status'));
        if($status_awal == true){
            $status = 'active';
        } else {
            $status = 'inactive';
        }
        $user_sales_type = htmlentities($request->input('user_sales_type'));
        $package_header_id = htmlentities($request->input('package_header_id'));
        $price = htmlentities($request->input('price'));

        $updated_at = Carbon\Carbon::now();
        
        DB::beginTransaction();
        try{
            $editUserClenic = User_Clenic::where('id', $id)->update([
                'code'          => $code,
                'name'          => $name,
                'category'          => $category,
                'description'   => $description,
                'status'     => $status,
                'user_sales_type'     => $user_sales_type,
                'package_header_id'     => $package_header_id,
                'price'     => $price,
                'updated_at'     => $updated_at
            ]);
        
            if($editUserClenic){
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master User Clenic',
                    'table_id'      => $id,
                    'action'        => 'update',
                    'changes'       => 'User Clenic',
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
                    'message' => 'User Clenic sudah diubah'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => true,
                    'message' => 'User Clenic gagal diubah, silahkan coba beberapa saat lagi'
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
    
    public function deleteUserClenic(Request $request)
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
            $editUserClenic = User_Clenic::where('id', $id)->update([
                'status'     => 'inactive',
                'updated_at'    => Carbon\Carbon::now()
            ]);

            if($editUserClenic){
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master User Clenic',
                    'table_id'      => $id,
                    'action'        => 'delete',
                    'changes'       => 'User Clenic',
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
                    'message' => 'User Clenic sudah dihapus!'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => false,
                    'message' => 'User Clenic gagal dihapus, silahkan coba beberapa saat lagi'
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
    
    public function getAllUserClenic(Request $request)
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
            $user_Clenic = DB::table('master_user_clenic as muc')
            ->select(
                'muc.id as id',
                'muc.code as code',
                'muc.name as name',
                'muc.category as category',
                'muc.description as description',
                'muc.status as status',
                'muc.user_sales_type as user_sales_type',
                'muc.package_header_id as package_header_id',
                'muc.price as price',
                'muc.created_at as created_at',
                'muc.updated_at as updated_at',
                'mph.code as code_package',
                'mph.name as name_package'
            )
            ->leftJoin('master_package_header as mph', 'muc.package_header_id', '=', 'mph.id')
            ->get()->toArray();

            if(count($user_Clenic) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'User Clenic tidak ditemukan!',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Daftar semua user clenic',
                    'data' => $user_Clenic
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
    
    public function getUserClenicByID(Request $request)
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
            $user_Clenic = User_Clenic::select("*")
                            ->where("id", $id)
                            ->get()->toArray();
            
            if(count($user_Clenic) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'User Clenic tidak ditemukan!',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'User Clenic Header!',
                    'data'    => $user_Clenic
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
    
    public function getUserClenicByParams(Request $request)
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
            $user_Clenic = User_Clenic::select("*")
                            ->where("code", 'like', '%'.$params.'%')
                            ->orWhere("name", 'like', '%'.$params.'%')
                            ->orWhere("category", 'like', '%'.$params.'%')
                            ->orWhere("description", 'like', '%'.$params.'%')
                            ->orWhere("status", 'like', '%'.$params.'%')
                            ->orWhere("user_sales_type", 'like', '%'.$params.'%')
                            ->orWhere("package_header_id", 'like', '%'.$params.'%')
                            ->orWhere("price", 'like', '%'.$params.'%')
                            ->get()->toArray();
                            
            if(count($user_Clenic) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'User Clenic tidak ditemukan!',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'User Clenic Header!',
                    'data'    => $user_Clenic
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
