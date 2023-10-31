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
use App\Models\User_Clenic;
use App\Models\Package_Header;
use App\Models\Package_Detail;

class PackageDetailController extends Controller
{
    public function createPackageDetail(Request $request)
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


        $package_header_id = htmlentities($request->input('package_header_id'));
        $user_clenic_id = htmlentities($request->input('user_clenic_id'));
        $qty = htmlentities($request->input('qty'));

        $periksaIsiDB = Package_Detail::select("*")
        ->where("package_header_id", $package_header_id)
        ->where("user_clenic_id", $user_clenic_id)
        ->get()->toArray();
        
        if($periksaIsiDB){
            return response()->json([
                'success' => false,
                'message' => 'Data tersebut sudah tersedia di database.'
            ], 202);
        }

        DB::beginTransaction();
        try{
            Package_Detail::create([
                'package_header_id'          => $package_header_id,
                'user_clenic_id'          => $user_clenic_id,
                'qty'   => $qty
            ]);

            $periksaPackageDetail = Package_Detail::select("*")
                            ->where("package_header_id", $package_header_id)
                            ->where("user_clenic_id", $user_clenic_id)
                            ->get()->toArray();

            $log_activities = Log_Activities::create([
                'user_id'       => $headerID,
                'table_name'    => 'Master Package Detail',
                'table_id'      => $periksaPackageDetail[0]['id'],
                'action'        => 'add',
                'changes'       => 'Package Detail',
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
                'message' => 'Package Detail sudah dibuat.'
            ], 200);

        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi."
            ]);
        }
    }
    
    public function updatePackageDetail(Request $request)
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
        $package_header_id = htmlentities($request->input('package_header_id'));
        $user_clenic_id = htmlentities($request->input('user_clenic_id'));
        $qty = htmlentities($request->input('qty'));

        $periksaIsiDB = Package_Detail::select("*")
                        ->where("package_header_id", $package_header_id)
                        ->where("user_clenic_id", $user_clenic_id)
                        ->get()->toArray();
        
        if(count($periksaIsiDB) != 0){
            foreach($periksaIsiDB as $row){
                if($id != $row['id']){
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Data anda sudah digunakan oleh klinik yang lain!',
                        'data'    => ''
                    ], 201);
                }
            }
        } 
        
        DB::beginTransaction();
        try{
            $editPackageDetail = Package_Detail::where('id', $id)->update([
                'package_header_id'          => $package_header_id,
                'user_clenic_id'          => $user_clenic_id,
                'qty'   => $qty
            ]);
        
            if($editPackageDetail){
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Package Detail',
                    'table_id'      => $id,
                    'action'        => 'update',
                    'changes'       => 'Package Detail',
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
                    'message' => 'Package Detail sudah diubah'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => true,
                    'message' => 'Package Detail gagal diubah, silahkan coba beberapa saat lagi'
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
    
    public function deletePackageDetail(Request $request)
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
            $editPackageDetail = Package_Detail::where('id', $id)->delete();

            if($editPackageDetail){
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Package Detail',
                    'table_id'      => $id,
                    'action'        => 'delete',
                    'changes'       => 'Package Detail',
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
                    'message' => 'Package Details sudah dihapus!'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => false,
                    'message' => 'Package Details gagal dihapus, silahkan coba beberapa saat lagi'
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
    
    public function getAllPackageDetail(Request $request)
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
            $packageDetail = DB::table('master_package_detail as mpd')
            ->select(
                'mpd.id as id',
                'mpd.qty as qty',
                'muc.id as user_clenic_id',
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
            ->leftJoin('master_package_header as mph', 'mpd.package_header_id', '=', 'mph.id')
            ->leftJoin('master_user_clenic as muc', 'mpd.user_clenic_id', '=', 'muc.id')
            ->get()->toArray();

            if(count($packageDetail) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Package Details tidak ditemukan!',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Daftar semua detail paket',
                    'data' => $packageDetail
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
   
    public function getPackageDetailByParams(Request $request)
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
            $packageDetail = DB::table('master_package_detail as mpd')
            ->select(
                'mpd.id as id',
                'mpd.qty as qty',
                'muc.id as user_clenic_id',
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
            ->leftJoin('master_package_header as mph', 'mpd.package_header_id', '=', 'mph.id')
            ->leftJoin('master_user_clenic as muc', 'mpd.user_clenic_id', '=', 'muc.id')
            ->where("mpd.qty", 'like', '%'.$params.'%')
            ->orWhere("muc.code", 'like', '%'.$params.'%')
            ->orWhere("muc.name", 'like', '%'.$params.'%')
            ->orWhere("muc.category", 'like', '%'.$params.'%')
            ->orWhere("muc.description", 'like', '%'.$params.'%')
            ->orWhere("muc.status", 'like', '%'.$params.'%')
            ->orWhere("muc.user_sales_type", 'like', '%'.$params.'%')
            ->orWhere("muc.price", 'like', '%'.$params.'%')
            ->orWhere("mph.code", 'like', '%'.$params.'%')
            ->orWhere("mph.name", 'like', '%'.$params.'%')
            ->get()->toArray();
                
            if(count($packageDetail) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Package Details tidak ditemukan!',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Data Package Details!',
                    'data'    => $packageDetail
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
     
    public function getPackageDetailByID(Request $request)
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
            $package_Detail = Package_Detail::select("*")
                            ->where("id", $id)
                            ->get()->toArray();
            
            if(count($package_Detail) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Package Details tidak ditemukan!',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'List Package Details!',
                    'data'    => $package_Detail
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
