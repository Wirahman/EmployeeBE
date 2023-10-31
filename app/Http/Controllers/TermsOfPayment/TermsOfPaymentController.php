<?php

namespace App\Http\Controllers\TermsOfPayment;

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
use App\Models\Terms_of_payment;

class TermsOfPaymentController extends Controller
{
    public function createTermsOfPayment(Request $request)
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
        $tempo = htmlentities($request->input('tempo'));
        $description = htmlentities($request->input('description'));
        $status_awal = htmlentities($request->input('status'));
        if($status_awal == true){
            $status = 'active';
        } else {
            $status = 'inactive';
        }
        
        $codeTOP = Code_Settings::select("*")
        ->where("table_name", "master_terms_of_payment")
        ->get()->toArray();
        
        $codeAngka = str_pad($codeTOP[0]['counter'] + 1, $codeTOP[0]['digit'], '0', STR_PAD_LEFT);
        $code = $codeTOP[0]['prefix'] . $codeAngka;
        $created_at = Carbon\Carbon::now();

        $topLama = Terms_of_payment::select("*")
        ->where("name", $name)
        ->get()->toArray();

        if(count($topLama) != 0){
            return response()->json([
                'success' => false,
                'message' => 'Terms of payment sudah terdaftar!'
            ], 201);  
        }

        
        DB::beginTransaction();
        try{
            $buatTOP = Terms_of_payment::create([
                'code'     => $code,
                'name'   => $name,
                'tempo'     => $tempo,
                'status'     => $status,
                'description'     => $description,
                'created_at'    => $created_at
            ]);

            Code_Settings::where('table_name', 'master_terms_of_payment')->update([
                'counter'     => $codeTOP[0]['counter'] + 1,
                'updated_at'    => Carbon\Carbon::now()
            ]);

            $periksaTOP = Terms_of_payment::select("*")
                            ->where("code", $code)
                            ->get()->toArray();

            $log_activities = Log_Activities::create([
                'user_id'       => $headerID,
                'table_name'    => 'Master Terms of Payments',
                'table_id'      => $periksaTOP[0]['id'],
                'action'        => 'add',
                'changes'       => 'Terms of Payments',
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
                'message' => 'Data terms of payment sudah dibuat.'
            ], 200);
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi!"
            ]);
        }

    }
    
    public function updateTermsOfPayment(Request $request)
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
        $name = htmlentities($request->input('name'));
        $tempo = htmlentities($request->input('tempo'));
        $description = htmlentities($request->input('description'));
        $status_awal = htmlentities($request->input('status'));
        if($status_awal == true){
            $status = 'active';
        } else {
            $status = 'inactive';
        }
        $updated_at = Carbon\Carbon::now();
        
        $periksaNamaTOP = Terms_of_payment::select("*")
                            ->where("name", $name)
                            ->get()->toArray();

        if(count($periksaNamaTOP) != 0){
            foreach($periksaNamaTOP as $row){
                if($id != $row['id']){
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Terms of Payment sudah digunakan!',
                        'data'    => ''
                    ], 201);
                }
            }
        } 
        
        DB::beginTransaction();
        try{
            $editTOP = Terms_of_payment::where('id', $id)->update([
                'name'   => $name,
                'tempo'     => $tempo,
                'status'     => $status,
                'description'     => $description,
                'updated_at'     => $updated_at
            ]);

        
            if($editTOP){
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Terms of Payment',
                    'table_id'      => $id,
                    'action'        => 'update',
                    'changes'       => 'Terms of Payment',
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
                    'message' => 'Terms of Payment sudah diubah'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => true,
                    'message' => 'Terms of Payment gagal diubah, silahkan coba beberapa saat lagi'
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
    
    public function deleteTermsOfPayment(Request $request)
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
        $updated_at = Carbon\Carbon::now();

        DB::beginTransaction();
        try{
            $editTOP = Terms_of_payment::where('id', $id)->update([
                'status'   => 'inactive',
                'updated_at'     => $updated_at
            ]);

        
            if($editTOP){
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Terms of Payment',
                    'table_id'      => $id,
                    'action'        => 'delete',
                    'changes'       => 'Terms of Payment',
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
                    'message' => 'Terms of Payment sudah dihapus'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => true,
                    'message' => 'Terms of Payment gagal dihapus, silahkan coba beberapa saat lagi'
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
    
    public function getAllTermsOfPayment(Request $request)
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

        $termsOfPayment = Terms_of_payment::select("*")
                        ->orderBy('id', 'DESC')
                        // ->where("status", "active")
                        ->get()->toArray();
                                
        return response([
            'success' => true,
            'message' => 'Daftar Pembayaran',
            'data' => $termsOfPayment
        ], 200);

    }
    
    public function getTermsOfPaymentByID(Request $request)
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
        $termsOfPayment = Terms_of_payment::select("*")
                        ->orderBy('id', 'DESC')
                        ->where("id", $id)
                        ->get()->toArray();
                                
        return response([
            'success' => true,
            'message' => 'Data Pembayaran',
            'data' => $termsOfPayment
        ], 200);

    }
    
    public function getTermsOfPaymentByParams(Request $request)
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
        $termsOfPayment = Terms_of_payment::select("*")
                        ->orderBy('id', 'DESC')
                        ->where("name", 'like', '%'.$params.'%')
                        ->orWhere("tempo", 'like', '%'.$params.'%')
                        ->orWhere("status", 'like', '%'.$params.'%')
                        ->orWhere("description", 'like', '%'.$params.'%')
                        ->get()->toArray();
                                
        return response([
            'success' => true,
            'message' => 'Data Pembayaran',
            'data' => $termsOfPayment
        ], 200);


    }
    
}
