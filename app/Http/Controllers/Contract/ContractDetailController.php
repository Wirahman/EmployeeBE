<?php

namespace App\Http\Controllers\Contract;

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
use App\Models\Contract_Header;
use App\Models\Contract_Detail;
use App\Models\User_Clenic;

class ContractDetailController extends Controller
{
    public function createContractDetail(Request $request)
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
        
        $contract_header_id = htmlentities($request->input('contract_header_id'));
        $user_clenic_id = htmlentities($request->input('user_clenic_id'));
        $qty = htmlentities($request->input('qty'));
        $price = htmlentities($request->input('price'));
        $discount_type = htmlentities($request->input('discount_type'));
        $discount_value = htmlentities($request->input('discount_value'));
        $subtotal = htmlentities($request->input('subtotal'));

        DB::beginTransaction();
        try{
            Contract_Detail::create([
                'contract_header_id'          => $contract_header_id,
                'user_clenic_id'          => $user_clenic_id,
                'qty'          => $qty,
                'price'          => $price,
                'discount_type'          => $discount_type,
                'discount_value'          => $discount_value,
                'subtotal'     => $subtotal
            ]);

            $periksaContract = Contract_Detail::select("*")
                            ->where("contract_header_id", $contract_header_id)
                            ->where("user_clenic_id", $user_clenic_id)
                            ->get()->toArray();

            $log_activities = Log_Activities::create([
                'user_id'       => $headerID,
                'table_name'    => 'Master Contract Header',
                'table_id'      => $periksaContract[0]['id'],
                'action'        => 'add',
                'changes'       => 'Contract Detail',
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
                'message' => 'Data Contract sudah dibuat.'
            ], 200);
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi."
            ]);
        }
    }
    
    public function updateContractDetail(Request $request)
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
        $contract_header_id = htmlentities($request->input('contract_header_id'));
        $user_clenic_id = htmlentities($request->input('user_clenic_id'));
        $qty = htmlentities($request->input('qty'));
        $price = htmlentities($request->input('price'));
        $discount_type = htmlentities($request->input('discount_type'));
        $discount_value = htmlentities($request->input('discount_value'));
        $subtotal = htmlentities($request->input('subtotal'));
        
        DB::beginTransaction();
        try{
            $editContract = Contract_Detail::where('id', $id)->update([
                'contract_header_id'          => $contract_header_id,
                'user_clenic_id'          => $user_clenic_id,
                'qty'          => $qty,
                'price'          => $price,
                'discount_type'          => $discount_type,
                'discount_value'          => $discount_value,
                'subtotal'     => $subtotal
            ]);

            if($editContract){
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Contract Detail',
                    'table_id'      => $id,
                    'action'        => 'update',
                    'changes'       => 'Contract Detail',
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
                    'message' => 'Contract sudah diubah'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => true,
                    'message' => 'Contract gagal diubah, silahkan coba beberapa saat lagi'
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
    
    public function deleteContractDetail(Request $request)
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
            $editContractDetail = Contract_Detail::where('id', $id)->delete();

            if($editContractDetail){
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Contract Detail',
                    'table_id'      => $id,
                    'action'        => 'delete',
                    'changes'       => 'Contract Detail',
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
                    'message' => 'Contract Details sudah dihapus!'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => false,
                    'message' => 'Contract Details gagal dihapus, silahkan coba beberapa saat lagi'
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
    
    public function getAllContractDetail(Request $request)
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
            $contract = DB::table('master_contract_detail as contract_detail')
            ->select(
                'contract_detail.id as id',
                'contract_detail.qty as qty',
                'contract_detail.price as price',
                'contract_detail.discount_type as discount_type',
                'contract_detail.discount_value as discount_value',
                'contract_detail.subtotal as subtotal',
                'contract_header.code as code',
                'user_clenic.name as nama_pengguna_klinik'
            )
            ->leftJoin('master_contract_header as contract_header', 'contract_detail.contract_header_id', '=', 'contract_header.id')
            ->leftJoin('master_user_clenic as user_clenic', 'contract_detail.user_clenic_id', '=', 'user_clenic.id')
            ->get()->toArray();
            if(count($contract) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Contract tidak ditemukan',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Daftar semua Contract',
                    'data' => $contract
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

    public function getContractDetailByID(Request $request)
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
            $contract_Detail = Contract_Detail::select("*")
                            ->where("id", $id)
                            ->get()->toArray();
            
            if(count($contract_Detail) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Contract Detail tidak ditemukan!',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Data Contract Header!',
                    'data'    => $contract_Detail
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
    
    public function getContractDetailByParams(Request $request)
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

        $contract = DB::table('master_contract_detail as contract_detail')
        ->select(
            'contract_detail.id as id',
            'contract_detail.qty as qty',
            'contract_detail.price as price',
            'contract_detail.discount_type as discount_type',
            'contract_detail.discount_value as discount_value',
            'contract_detail.subtotal as subtotal',
            'contract_header.code as code',
            'user_clenic.name as nama_pengguna_klinik'
        )
        ->leftJoin('master_contract_header as contract_header', 'contract_detail.contract_header_id', '=', 'contract_header.id')
        ->leftJoin('master_user_clenic as user_clenic', 'contract_detail.user_clenic_id', '=', 'user_clenic.id')
        ->where("contract_detail.qty", 'like', '%'.$params.'%')
        ->orWhere("contract_detail.price", 'like', '%'.$params.'%')
        ->orWhere("contract_detail.discount_type", 'like', '%'.$params.'%')
        ->orWhere("contract_detail.discount_value", 'like', '%'.$params.'%')
        ->orWhere("contract_detail.subtotal", 'like', '%'.$params.'%')
        ->orWhere("contract_header.code", 'like', '%'.$params.'%')
        ->get()->toArray();

        if(count($contract) === 0){
            return response()->json([
                'success' => false,
                'message' => 'Contract tidak ditemukan!',
                'data'    => ''
            ], 201);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Data Contract!',
                'data'    => $contract
            ], 200);
        } 
    }
    
}
