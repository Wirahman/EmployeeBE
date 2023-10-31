<?php

namespace App\Http\Controllers\Department;

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
use App\Models\Department;
use App\Models\User;
use App\Models\Code_Settings;
use App\Models\Log_Activities;

class DepartmentController extends Controller
{
    public function createDepartment(Request $request)
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
        
        $periksaDepartment = Department::select("*")
                            ->where("name", $name)
                            ->get()->toArray();

        if(count($periksaDepartment) != 0){
            return response()->json([
                'success' => false,
                'message' => 'Unit sudah terdaftar!',
                'data'    => ''
            ], 201);
        }

        $codeDepartment = Code_Settings::select("*")
        ->where("table_name", "master_department")
        ->get()->toArray();

        $department = Department::select("*")->orderBy('id', 'DESC')->first();
        $codeAngka = str_pad($codeDepartment[0]['counter'] + 1, $codeDepartment[0]['digit'], '0', STR_PAD_LEFT);
        $code = $codeDepartment[0]['prefix'] . $codeAngka;

        DB::beginTransaction();
        try{
            
            Department::create([
                'code'     => $code,
                'name'   => $name,
                'status'     => $status,
                'created_at'    => Carbon\Carbon::now()
            ]);

            $departmentTerakhir = Department::select("*")
                            ->where("code", $code)
                            ->get()->toArray();

            Code_Settings::where('table_name', 'master_department')->update([
                'counter'     => $codeDepartment[0]['counter'] + 1,
                'updated_at'    => Carbon\Carbon::now()
            ]);
            
            $log_activities = Log_Activities::create([
                'user_id'       => $headerID,
                'table_name'    => 'Master Department',
                'table_id'      => $departmentTerakhir[0]['id'],
                'action'        => 'add',
                'changes'       => 'Department',
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
                'message' => 'Unit sudah dibuat'
            ], 200);
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi.!"
            ]);
        }
    }

    public function updateDepartment(Request $request)
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
        
        $department = Department::select("*")
                ->where("name", $name)
                ->get()->toArray();

        if(count($department) != 0){
            foreach($department as $row){
                if($id != $row['id']){
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Unit sudah digunakan!',
                        'data'    => ''
                    ], 201);
                }
            }
        } 
        DB::beginTransaction();
        try{
            $editDepartment = Department::where('id', $id)->update([
                'name'     => $name,
                'status'     => $status,
                'updated_at'    => Carbon\Carbon::now()
            ]);
            if($editDepartment){
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
                    'message' => 'Unit sudah diubah'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => true,
                    'message' => 'Unit gagal diubah, silahkan coba beberapa saat lagi'
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
    
    public function deleteDepartment(Request $request)
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
            $editDepartment = Department::where('id', $id)->update([
                'status'     => 'inactive',
                'updated_at'    => Carbon\Carbon::now()
            ]);

            if($editDepartment){
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Department',
                    'table_id'      => $id,
                    'action'        => 'delete',
                    'changes'       => 'Department',
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
                    'message' => 'Unit sudah dihapus'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => false,
                    'message' => 'Unit gagal dihapus, silahkan coba beberapa saat lagi'
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
    
    public function getAllDepartment(Request $request)
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
        
        $department = Department::select("*")
                        ->orderBy('id', 'DESC')
                        ->get()->toArray();
                                
        return response([
            'success' => true,
            'message' => 'Daftar Unit',
            'data' => $department
        ], 200);
    }

    public function GetAllAktif(Request $request)
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
        
        $department = Department::select("*")
                        ->orderBy('id', 'DESC')
                        ->where("status", "active")
                        ->get()->toArray();
                                
        return response([
            'success' => true,
            'message' => 'Daftar Unit',
            'data' => $department
        ], 200);
    }

    public function getDepartmentByID(Request $request)
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
        $department = Department::select("*")
                        ->orderBy('id', 'DESC')
                        ->where("id", $id)
                        ->get()->toArray();
                                
        return response([
            'success' => true,
            'message' => 'Data Unit',
            'data' => $department
        ], 200);
    }

    public function getDepartmentByParams(Request $request)
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

        $department = Department::select("*")
                        ->where("code", 'like', '%'.$params.'%')
                        ->orWhere("name", 'like', '%'.$params.'%')
                        ->orWhere("status", 'like', '%'.$params.'%')
                        ->orderBy('id', 'DESC')
                        ->get()->toArray();
        
        if(count($department) === 0){
            return response()->json([
                'success' => false,
                'message' => 'Unit tidak ditemukan!',
                'data'    => ''
            ], 201);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Data Unit!',
                'data'    => $department
            ], 200);
        }
        
    }
    

    



}
