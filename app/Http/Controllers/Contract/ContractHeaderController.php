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
use App\Models\Clinic;
use App\Models\Customer;
use App\Models\Contract_Header;
use App\Models\Terms_of_payment;

class ContractHeaderController extends Controller
{
    public function createContractHeader(Request $request)
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
        
        $customer_id = htmlentities($request->input('customer_id'));
        $clinic_id = htmlentities($request->input('clinic_id'));
        $contract_date = htmlentities($request->input('contract_date'));
        $contract_start_date = htmlentities($request->input('contract_start_date'));
        $contract_end_date = htmlentities($request->input('contract_end_date'));
        $subtotal = htmlentities($request->input('subtotal'));
        $discount_type = htmlentities($request->input('discount_type'));
        $discount_value = htmlentities($request->input('discount_value'));
        $tax_type = htmlentities($request->input('tax_type'));
        $tax_percentage = htmlentities($request->input('tax_percentage'));
        $tax_value = htmlentities($request->input('tax_value'));
        $grand_total = htmlentities($request->input('grand_total'));
        $term_of_payments_id = htmlentities($request->input('term_of_payments_id'));
        $status_awal = htmlentities($request->input('status'));
        if($status_awal == true){
            $status = 'active';
        } else {
            $status = 'inactive';
        }

        $description = htmlentities($request->input('description'));
        $created_at = Carbon\Carbon::now();

        $periksaClinicID = Contract_Header::select("*")
                        ->where("clinic_id", $clinic_id)
                        ->get()->toArray();
        
        if($periksaClinicID){
            return response()->json([
                'success' => false,
                'message' => 'Clinic ID sudah digunakan',
                'data'    => ''
            ], 402);      
        }

        $codeContract = Code_Settings::select("*")
                        ->where("table_name", "master_contract_header")
                        ->get()->toArray();
        $codeAngka = str_pad($codeContract[0]['counter'] + 1, $codeContract[0]['digit'], '0', STR_PAD_LEFT);
        $code = $codeContract[0]['prefix'] . $codeAngka;
        
        DB::beginTransaction();
        try{
            Contract_Header::create([
                'code'          => $code,
                'customer_id'   => $customer_id,
                'clinic_id'          => $clinic_id,
                'contract_date'     => $contract_date,
                'contract_start_date'     => $contract_start_date,
                'contract_end_date'     => $contract_end_date,
                'subtotal'     => $subtotal,
                'discount_type'          => $discount_type,
                'discount_value'          => $discount_value,
                'tax_type'          => $tax_type,
                'tax_percentage'          => $tax_percentage,
                'tax_value'          => $tax_value,
                'grand_total'          => $grand_total,
                'term_of_payments_id'          => $term_of_payments_id,
                'status'     => $status,
                'description'     => $description,
                'created_at'     => $created_at
            ]);

            Code_Settings::where('table_name', 'master_contract_header')->update([
                'counter'     => $codeContract[0]['counter'] + 1,
                'updated_at'    => Carbon\Carbon::now()
            ]);

            $periksaContract = Contract_Header::select("*")
                            ->where("code", $code)
                            ->orWhere("clinic_id", $clinic_id)
                            ->get()->toArray();

            $log_activities = Log_Activities::create([
                'user_id'       => $headerID,
                'table_name'    => 'Master Contract Header',
                'table_id'      => $periksaContract[0]['id'],
                'action'        => 'add',
                'changes'       => 'Contract Header',
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
    
    public function updateContractHeader(Request $request)
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
        $customer_id = htmlentities($request->input('customer_id'));
        $clinic_id = htmlentities($request->input('clinic_id'));
        $contract_date = htmlentities($request->input('contract_date'));
        $contract_start_date = htmlentities($request->input('contract_start_date'));
        $contract_end_date = htmlentities($request->input('contract_end_date'));
        $subtotal = htmlentities($request->input('subtotal'));
        $discount_type = htmlentities($request->input('discount_type'));
        $discount_value = htmlentities($request->input('discount_value'));
        $tax_type = htmlentities($request->input('tax_type'));
        $tax_percentage = htmlentities($request->input('tax_percentage'));
        $tax_value = htmlentities($request->input('tax_value'));
        $grand_total = htmlentities($request->input('grand_total'));
        $term_of_payments_id = htmlentities($request->input('term_of_payments_id'));
        $status_awal = htmlentities($request->input('status'));
        if($status_awal == true){
            $status = 'active';
        } else {
            $status = 'inactive';
        }

        $description = htmlentities($request->input('description'));
        $updated_at = Carbon\Carbon::now();

        $periksaClinicID = Contract_Header::select("*")
                            ->where("clinic_id", $clinic_id)
                            ->get()->toArray();

        if(count($periksaClinicID) != 0){
            foreach($periksaClinicID as $row){
                if($id != $row['id']){
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Clinic ID sudah digunakan!',
                        'data'    => ''
                    ], 201);
                }
            }
        } 

        DB::beginTransaction();
        try{
            $editContract = Contract_Header::where('id', $id)->update([
                'customer_id'   => $customer_id,
                'clinic_id'          => $clinic_id,
                'contract_date'     => $contract_date,
                'contract_start_date'     => $contract_start_date,
                'contract_end_date'     => $contract_end_date,
                'subtotal'     => $subtotal,
                'discount_type'          => $discount_type,
                'discount_value'          => $discount_value,
                'tax_type'          => $tax_type,
                'tax_percentage'          => $tax_percentage,
                'tax_value'          => $tax_value,
                'grand_total'          => $grand_total,
                'term_of_payments_id'          => $term_of_payments_id,
                'status'     => $status,
                'description'     => $description,
                'updated_at'     => $updated_at
            ]);

            if($editContract){
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Contract Header',
                    'table_id'      => $id,
                    'action'        => 'update',
                    'changes'       => 'Contract Header',
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
    
    public function deleteContractHeader(Request $request)
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
            $editContract = Contract_Header::where('id', $id)->update([
                'status'     => 'inactive',
                'updated_at'    => Carbon\Carbon::now()
            ]);

            if($editContract){
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Contract Header',
                    'table_id'      => $id,
                    'action'        => 'delete',
                    'changes'       => 'Contract Header',
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
                    'message' => 'Contract Header sudah dihapus!'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => false,
                    'message' => 'Kontrak gagal dihapus, silahkan coba beberapa saat lagi'
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

    public function getAllContractHeader(Request $request)
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
            $contract = DB::table('master_contract_header as contract_header')
            ->select(
                'contract_header.id as id',
                'contract_header.code as code',
                'contract_header.contract_date as contract_date',
                'contract_header.contract_start_date as contract_start_date',
                'contract_header.contract_end_date as contract_end_date',
                'contract_header.subtotal as subtotal',
                'contract_header.discount_type as discount_type',
                'contract_header.discount_value as discount_value',
                'contract_header.tax_type as tax_type',
                'contract_header.tax_percentage as tax_percentage',
                'contract_header.tax_value as tax_value',
                'contract_header.grand_total as grand_total',
                'contract_header.status as status',
                'contract_header.description as description',
                'customer.company_name as perusahaan_pengguna',
                'customer.owner_name as nama_pemilik_klinik',
                'clinic.name as nama_klinik',
                'terms_of_payment.name as terms_of_payment'
            )
            ->leftJoin('master_customer as customer', 'contract_header.customer_id', '=', 'customer.id')
            ->leftJoin('master_clinic as clinic', 'contract_header.clinic_id', '=', 'clinic.id')
            ->leftJoin('master_terms_of_payment as terms_of_payment', 'contract_header.term_of_payments_id', '=', 'terms_of_payment.id')
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
    
    public function getContractHeaderByID(Request $request)
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
            $contractHeader = Contract_Header::select("*")
                            ->where("id", $id)
                            ->get()->toArray();
            
            if(count($contractHeader) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Contract Header tidak ditemukan!',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Data Contract Header!',
                    'data'    => $contractHeader
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
    
    public function getContractHeaderByParams(Request $request)
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
        if($params == 'Aktif'){
            $params = 'active';
        } else if($params == 'Tidak Aktif'){
            $params = 'inactive';
        }

        $contract = DB::table('master_contract_header as contract_header')
        ->select(
            'contract_header.id as id',
            'contract_header.code as code',
            'contract_header.contract_date as contract_date',
            'contract_header.contract_start_date as contract_start_date',
            'contract_header.contract_end_date as contract_end_date',
            'contract_header.subtotal as subtotal',
            'contract_header.discount_type as discount_type',
            'contract_header.discount_value as discount_value',
            'contract_header.tax_type as tax_type',
            'contract_header.tax_percentage as tax_percentage',
            'contract_header.tax_value as tax_value',
            'contract_header.grand_total as grand_total',
            'contract_header.status as status',
            'contract_header.description as description',
            'customer.company_name as perusahaan_pengguna',
            'customer.owner_name as nama_pemilik_klinik',
            'clinic.name as nama_klinik',
            'terms_of_payment.name as terms_of_payment'
        )
        ->leftJoin('master_customer as customer', 'contract_header.customer_id', '=', 'customer.id')
        ->leftJoin('master_clinic as clinic', 'contract_header.clinic_id', '=', 'clinic.id')
        ->leftJoin('master_terms_of_payment as terms_of_payment', 'contract_header.term_of_payments_id', '=', 'terms_of_payment.id')
        ->where("contract_header.code", 'like', '%'.$params.'%')
        ->orWhere("contract_header.contract_date", 'like', '%'.$params.'%')
        ->orWhere("contract_header.contract_start_date", 'like', '%'.$params.'%')
        ->orWhere("contract_header.contract_end_date", 'like', '%'.$params.'%')
        ->orWhere("contract_header.subtotal", 'like', '%'.$params.'%')
        ->orWhere("contract_header.discount_type", 'like', '%'.$params.'%')
        ->orWhere("contract_header.discount_value", 'like', '%'.$params.'%')
        ->orWhere("contract_header.tax_type", 'like', '%'.$params.'%')
        ->orWhere("contract_header.grand_total", 'like', '%'.$params.'%')
        ->orWhere("contract_header.status", 'like', '%'.$params.'%')
        ->orWhere("contract_header.description", 'like', '%'.$params.'%')
        ->orWhere("customer.company_name", 'like', '%'.$params.'%')
        ->orWhere("clinic.name", 'like', '%'.$params.'%')
        ->orWhere("terms_of_payment.name", 'like', '%'.$params.'%')
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
    
    public function checkWaktuSewa(Request $request)
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
        
        $customer_id = htmlentities($request->input('clinic_id'));
        $contractHeader = Contract_Header::select("*")
                        ->where("clinic_id", $customer_id)
                        ->get()->toArray();
                        
        $hasil['contract_end_date'] = $contractHeader[0]['contract_end_date'];
        $hasil['date_now'] = Carbon\Carbon::now();
        if($hasil['date_now'] <= $hasil['contract_end_date'] ){
            $hasil['selisih'] = $hasil['date_now']->diffInDays($hasil['contract_end_date']);
        }else{
            $hasil['selisih'] = 'Kontrak sudah expired';
        }
        

        return response()->json([
            'success' => true,
            'message' => 'Data Contract!',
            'data'    => $hasil
        ], 200);
    }
    
    
}
