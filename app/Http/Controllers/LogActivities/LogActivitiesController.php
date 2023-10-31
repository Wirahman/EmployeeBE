<?php

namespace App\Http\Controllers\LogActivities;

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
use App\Models\Log_Activities;

class LogActivitiesController extends Controller
{
    public function deleteLogActivities(Request $request)
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

        $tahun = htmlentities($request->input('tahun'));
        
        DB::beginTransaction();
        try{
            // $deleteLogActivities = Log_Activities::where('created_at','LIKE','%'.$tahun.'%')->delete();
            $deleteLogActivities = Log_Activities::whereYear('created_at', $tahun)->delete();
            DB::commit();
            return response([
                'success' => true,
                'message' => 'Log Aktifitas sudah dihapus!'
                // 'message' => $tahun
            ], 200);
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem mengalami gangguan, silahkan coba beberapa saat lagi.!"
            ]);
        }

    }

    public function getAllLogActivities(Request $request)
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
            $logActivities = DB::table('master_log_activities as la')
            ->select(
                'la.id as id',
                'la.table_name as table_name',
                'la.table_id as table_id',
                'la.action as action',
                'la.changes as changes',
                'la.ip as ip',
                'la.agent as agent',
                'la.created_at as created_at',
                'la.updated_at as updated_at',
                'user.code as code_user',
                'user.name as name_user'
            )
            ->leftJoin('master_user as user', 'la.user_id', '=', 'user.id')
            ->get()->toArray();

            if(count($logActivities) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Log Activities tidak ditemukan',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Daftar semua Log Activities',
                    'data' => $logActivities
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
    
    public function getLogActivitiesByParams(Request $request)
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

        try{
            $logActivities = DB::table('master_log_activities as la')
            ->select(
                'la.id as id',
                'la.table_name as table_name',
                'la.table_id as table_id',
                'la.action as action',
                'la.changes as changes',
                'la.ip as ip',
                'la.agent as agent',
                'la.created_at as created_at',
                'la.updated_at as updated_at',
                'user.code as code_user',
                'user.name as name_user'
            )
            ->leftJoin('master_user as user', 'la.user_id', '=', 'user.id')
            ->where("la.table_name", 'like', '%'.$params.'%')
            ->orWhere("la.table_id", 'like', '%'.$params.'%')
            ->orWhere("la.action", 'like', '%'.$params.'%')
            ->orWhere("la.changes", 'like', '%'.$params.'%')
            ->orWhere("la.ip", 'like', '%'.$params.'%')
            ->orWhere("la.agent", 'like', '%'.$params.'%')
            ->orWhere("la.created_at", 'like', '%'.$params.'%')
            ->orWhere("la.updated_at", 'like', '%'.$params.'%')
            ->orWhere("user.code", 'like', '%'.$params.'%')
            ->orWhere("user.name", 'like', '%'.$params.'%')
            ->get()->toArray();

            if(count($logActivities) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Log Activities tidak ditemukan',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Daftar semua Log Activities',
                    'data' => $logActivities
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
    
    public function getLogActivitiesByID(Request $request)
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
            $logActivities = DB::table('master_log_activities as la')
            ->select(
                'la.id as id',
                'la.table_name as table_name',
                'la.table_id as table_id',
                'la.action as action',
                'la.changes as changes',
                'la.ip as ip',
                'la.agent as agent',
                'la.created_at as created_at',
                'la.updated_at as updated_at',
                'user.code as code_user',
                'user.name as name_user'
            )
            ->leftJoin('master_user as user', 'la.user_id', '=', 'user.id')
            ->where("la.id", $id)
            ->get()->toArray();

            if(count($logActivities) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Log Activities tidak ditemukan',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Daftar semua Log Activities',
                    'data' => $logActivities
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
