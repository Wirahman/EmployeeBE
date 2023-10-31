<?php

namespace App\Http\Controllers\Pelanggan;

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
use App\Models\Customer;
use App\Models\Provinsi;
use App\Models\Kabupaten_Kota;
use App\Models\Kecamatan;
use App\Models\Desa_Kelurahan;

class PelangganController extends Controller
{   
    public function createPelanggan(Request $request)
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
        
        $jenis_device = htmlentities($request->input('jenis_device'));
        $company_name = htmlentities($request->input('company_name'));
        $owner_name = htmlentities($request->input('owner_name'));
        $ktp_id = htmlentities($request->input('ktp_id'));
        $npwp = htmlentities($request->input('npwp'));
        $address = htmlentities($request->input('address'));
        $provinsi_id = htmlentities($request->input('provinsi_id'));
        $kabupaten_kota_id = htmlentities($request->input('kabupaten_kota_id'));
        $kecamatan_id = htmlentities($request->input('kecamatan_id'));
        $desa_kelurahan_id = htmlentities($request->input('desa_kelurahan_id'));
        $zipcode = htmlentities($request->input('zipcode'));
        $phone_number = htmlentities($request->input('phone_number'));
        $email = htmlentities($request->input('email'));
        $pic_name = htmlentities($request->input('pic_name'));
        $pic_phone_number = htmlentities($request->input('pic_phone_number'));
        $status_awal = htmlentities($request->input('status'));
        if($status_awal == true){
            $status = 'active';
        } else {
            $status = 'inactive';
        }

        $created_date = Carbon\Carbon::now();

        $periksaKTPCustomer = Customer::select("*")
                        ->where("ktp_id", $ktp_id)
                        ->get()->toArray();
        
        if($periksaKTPCustomer){
            return response()->json([
                'success' => false,
                'message' => 'KTP anda sudah digunakan oleh pengguna yang lain',
                'data'    => ''
            ], 402);      
        }

        $periksaNPWPCustomer = Customer::select("*")
                        ->where("npwp", $npwp)
                        ->get()->toArray();
        
        if($periksaNPWPCustomer){
            return response()->json([
                'success' => false,
                'message' => 'NPWP anda sudah digunakan oleh pengguna yang lain',
                'data'    => ''
            ], 402);      
        }

        $periksaTelpCustomer = Customer::select("*")
                        ->where("phone_number", $phone_number)
                        ->get()->toArray();
        
        if($periksaTelpCustomer){
            return response()->json([
                'success' => false,
                'message' => 'Telp anda sudah digunakan oleh pengguna yang lain',
                'data'    => ''
            ], 402);      
        }

        $periksaPICNumberCustomer = Customer::select("*")
                        ->where("pic_phone_number", $pic_phone_number)
                        ->get()->toArray();
        
        if($periksaPICNumberCustomer){
            return response()->json([
                'success' => false,
                'message' => 'Nomor handphone anda sudah digunakan oleh pengguna yang lain',
                'data'    => ''
            ], 402);      
        }

        $periksaKabupatenKota = Kabupaten_Kota::select("*")
                            ->where("id", $kabupaten_kota_id)
                            ->get()->toArray();

        if($periksaKabupatenKota[0]['provinsi_id'] != $provinsi_id){
            return response()->json([
                'success' => false,
                'message' => 'Periksa kembali kabupaten / kota anda',
                'data'    => ''
            ], 402);      
        }
             
        $periksaKecamatan = Kecamatan::select("*")
                            ->where("id", $kecamatan_id)
                            ->get()->toArray();

        if($periksaKecamatan[0]['kabupaten_kota_id'] != $kabupaten_kota_id){
            return response()->json([
                'success' => false,
                'message' => 'Periksa kembali kecamatan anda',
                'data'    => ''
            ], 402);      
        }
                    
        $periksaDesaKelurahan = Desa_Kelurahan::select("*")
                            ->where("id", $desa_kelurahan_id)
                            ->get()->toArray();

        if($periksaDesaKelurahan[0]['kecamatan_id'] != $kecamatan_id){
            return response()->json([
                'success' => false,
                'message' => 'Periksa kembali desa / kelurahan anda',
                'data'    => ''
            ], 402);      
        }
         
        $codePelanggan = Code_Settings::select("*")
                        ->where("table_name", "master_customer")
                        ->get()->toArray();
        $customer = Customer::select("*")->orderBy('id', 'DESC')->first();
        $codeAngka = str_pad($codePelanggan[0]['counter'] + 1, $codePelanggan[0]['digit'], '0', STR_PAD_LEFT);
        $code = $codePelanggan[0]['prefix'] . $codeAngka;
        
        DB::beginTransaction();
        try{
            $periksaCustomer = Customer::select("*")
                    ->where("code", $code)
                    ->orWhere("email", $email)
                    ->get()->toArray();

            if(count($periksaCustomer) === 0){    
                if($jenis_device == 'administrator'){
                    $otp = null;
                    $otp_expired = null;
                } else if($jenis_device == 'sms_otp') {
                    $otp = floor(rand(1000, 9999));
                    $otp_expired = \Carbon\Carbon::now()->addMinutes(10)->toDateTimeString();
                    $message = "Kode OTP Anda: " . $otp . ", RAHASIAKAN kode OTP Anda" ;
                    $smsAPI = $this->smsAPI($pic_phone_number, $message);
                    $status = 'registered';
                    // if($smsAPI == 'Success'){
                    //     return response()->json([
                    //         'success' => true,
                    //         'message' => 'Token successfully send to your phone.'
                    //     ], 200);
                    // } else {
                    //     return response()->json([
                    //         'success' => false,
                    //         'message' => 'Token Failed send to your phone'
                    //     ], 422);
                    // }

                }

                Customer::create([
                    'code'     => $code,
                    'company_name'   => $company_name,
                    'owner_name'     => $owner_name,
                    'ktp_id'     => $ktp_id,
                    'npwp'     => $npwp,
                    'address'     => $address,
                    'provinsi_id'     => $provinsi_id,
                    'kabupaten_kota_id'     => $kabupaten_kota_id,
                    'kecamatan_id'     => $kecamatan_id,
                    'desa_kelurahan_id'     => $desa_kelurahan_id,
                    'zipcode'     => $zipcode,
                    'phone_number'     => $phone_number,
                    'otp'   => $otp,
                    'otp_expire'     => $otp_expired,
                    'email'     => $email,
                    'pic_name'     => $pic_name,
                    'pic_phone_number'     => $pic_phone_number,
                    'status'     => $status,
                    'created_date'     => $created_date
                ]);

                Code_Settings::where('table_name', 'master_customer')->update([
                    'counter'     => $codePelanggan[0]['counter'] + 1,
                    'updated_at'    => Carbon\Carbon::now()
                ]);

                $periksaCustomer = Customer::select("*")
                                ->where("code", $code)
                                ->orWhere("email", $email)
                                ->get()->toArray();

                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Customer',
                    'table_id'      => $periksaCustomer[0]['id'],
                    'action'        => 'add',
                    'changes'       => 'Customer',
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
                    'message' => 'Data pelanggan sudah dibuat.'
                ], 200);
            }else{
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Email sudah digunakan, silahkan gunakan email yang lain!'
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
    
    public function updatePelanggan(Request $request)
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
        $company_name = htmlentities($request->input('company_name'));
        $owner_name = htmlentities($request->input('owner_name'));
        $ktp_id = htmlentities($request->input('ktp_id'));
        $npwp = htmlentities($request->input('npwp'));
        $address = htmlentities($request->input('address'));
        $provinsi_id = htmlentities($request->input('provinsi_id'));
        $kabupaten_kota_id = htmlentities($request->input('kabupaten_kota_id'));
        $kecamatan_id = htmlentities($request->input('kecamatan_id'));
        $desa_kelurahan_id = htmlentities($request->input('desa_kelurahan_id'));
        $zipcode = htmlentities($request->input('zipcode'));
        $phone_number = htmlentities($request->input('phone_number'));
        $email = htmlentities($request->input('email'));
        $pic_name = htmlentities($request->input('pic_name'));
        $pic_phone_number = htmlentities($request->input('pic_phone_number'));
        $status_awal = htmlentities($request->input('status'));
        if($status_awal == true){
            $status = 'active';
        } else {
            $status = 'inactive';
        }

        $updated_date = Carbon\Carbon::now();

        $periksaKTPCustomer = Customer::select("*")
                            ->where("ktp_id", $ktp_id)
                            ->get()->toArray();

        if(count($periksaKTPCustomer) != 0){
            foreach($periksaKTPCustomer as $row){
                if($id != $row['id']){
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'KTP anda sudah digunakan oleh pengguna yang lain!',
                        'data'    => ''
                    ], 201);
                }
            }
        } 
        
        $periksaNPWPCustomer = Customer::select("*")
                            ->where("npwp", $npwp)
                            ->get()->toArray();

        if(count($periksaNPWPCustomer) != 0){
            foreach($periksaNPWPCustomer as $row){
                if($id != $row['id']){
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'NPWP anda sudah digunakan oleh pengguna yang lain!',
                        'data'    => ''
                    ], 201);
                }
            }
        } 
        
        $periksaTelpCustomer = Customer::select("*")
                        ->where("phone_number", $phone_number)
                        ->get()->toArray();
        
        if(count($periksaTelpCustomer) != 0){
            foreach($periksaTelpCustomer as $row){
                if($id != $row['id']){
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Telp anda sudah digunakan oleh pengguna yang lain',
                        'data'    => ''
                    ], 402);      
                }
            }
        }

        $periksaPICNumberCustomer = Customer::select("*")
                            ->where("pic_phone_number", $pic_phone_number)
                            ->get()->toArray();

        if(count($periksaPICNumberCustomer) != 0){
            foreach($periksaPICNumberCustomer as $row){
                if($id != $row['id']){
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Nomor handphone anda sudah digunakan oleh pengguna yang lain!',
                        'data'    => ''
                    ], 201);
                }
            }
        } 
        
        $periksaEmailCustomer = Customer::select("*")
                            ->where("email", $email)
                            ->get()->toArray();

        if(count($periksaEmailCustomer) != 0){
            foreach($periksaEmailCustomer as $row){
                if($id != $row['id']){
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Email anda sudah digunakan oleh pengguna yang lain!',
                        'data'    => ''
                    ], 201);
                }
            }
        } 
        
        $periksaKabupatenKota = Kabupaten_Kota::select("*")
                            ->where("id", $kabupaten_kota_id)
                            ->get()->toArray();

        if($periksaKabupatenKota[0]['provinsi_id'] != $provinsi_id){
            return response()->json([
                'success' => false,
                'message' => 'Periksa kembali kabupaten / kota anda',
                'data'    => ''
            ], 402);      
        }
             
        $periksaKecamatan = Kecamatan::select("*")
                            ->where("id", $kecamatan_id)
                            ->get()->toArray();

        if($periksaKecamatan[0]['kabupaten_kota_id'] != $kabupaten_kota_id){
            return response()->json([
                'success' => false,
                'message' => 'Periksa kembali kecamatan anda',
                'data'    => ''
            ], 402);      
        }
                    
        $periksaDesaKelurahan = Desa_Kelurahan::select("*")
                            ->where("id", $desa_kelurahan_id)
                            ->get()->toArray();
        

        if($periksaDesaKelurahan[0]['kecamatan_id'] != $kecamatan_id){
            return response()->json([
                'success' => false,
                'message' => 'Periksa kembali desa / kelurahan anda',
                'data'    => ''
            ], 402);      
        }
         
        DB::beginTransaction();
        try{
            $editCustomer = Customer::where('id', $id)->update([
                'company_name'   => $company_name,
                'owner_name'     => $owner_name,
                'ktp_id'     => $ktp_id,
                'npwp'     => $npwp,
                'address'     => $address,
                'provinsi_id'     => $provinsi_id,
                'kabupaten_kota_id'     => $kabupaten_kota_id,
                'kecamatan_id'     => $kecamatan_id,
                'desa_kelurahan_id'     => $desa_kelurahan_id,
                'zipcode'     => $zipcode,
                'phone_number'     => $phone_number,
                'email'     => $email,
                'pic_name'     => $pic_name,
                'pic_phone_number'     => $pic_phone_number,
                'status'     => $status,
                'updated_date'     => $updated_date
            ]);

            if($editCustomer){
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Customer',
                    'table_id'      => $id,
                    'action'        => 'update',
                    'changes'       => 'Customer',
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
                    'message' => 'Pelanggan sudah diubah'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => true,
                    'message' => 'Pelanggan gagal diubah, silahkan coba beberapa saat lagi'
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
    
    public function deletePelanggan(Request $request)
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
            $editCustomer = Customer::where('id', $id)->update([
                'status'     => 'inactive',
                'updated_date'    => Carbon\Carbon::now()
            ]);

            if($editCustomer){
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Customer',
                    'table_id'      => $id,
                    'action'        => 'delete',
                    'changes'       => 'Customer',
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
                    'message' => 'Pengguna sudah dihapus!'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => false,
                    'message' => 'Pengguna gagal dihapus, silahkan coba beberapa saat lagi'
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
    
    public function getAllPelanggan(Request $request)
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
            $customer = DB::table('master_customer as customer')
            ->select(
                'customer.id as id',
                'customer.code as code',
                'customer.company_name as company_name',
                'customer.owner_name as owner_name',
                'customer.ktp_id as ktp_id',
                'customer.npwp as npwp',
                'customer.address as address',
                'customer.provinsi_id as provinsi_id',
                'customer.kabupaten_kota_id as kabupaten_kota_id',
                'customer.kecamatan_id as kecamatan_id',
                'customer.desa_kelurahan_id as desa_kelurahan_id',
                'customer.zipcode as zipcode',
                'customer.phone_number as phone_number',
                'customer.email as email',
                'customer.pic_name as pic_name',
                'customer.pic_phone_number as pic_phone_number',
                'customer.status as status',
                'customer.created_date as created_date',
                'customer.updated_date as updated_date',
                'provinsi.name as nama_provinsi',
                'kabupatenkota.name as nama_kabupaten_kota',
                'kecamatan.name as nama_kecamatan',
                'desa_kelurahan.name as nama_desa_kelurahan',
            )
            ->leftJoin('master_provinsi as provinsi', 'customer.provinsi_id', '=', 'provinsi.id')
            ->leftJoin('master_kabupaten_kota as kabupatenkota', 'customer.kabupaten_kota_id', '=', 'kabupatenkota.id')
            ->leftJoin('master_kecamatan as kecamatan', 'customer.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoin('master_desa_kelurahan as desa_kelurahan', 'customer.desa_kelurahan_id', '=', 'desa_kelurahan.id')
            ->get()->toArray();

            if(count($customer) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Pelanggan tidak ditemukan',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Daftar semua Pelanggan',
                    'data' => $customer
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

    public function getPelangganById(Request $request)
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
            $customer = Customer::select("*")
                            ->where("id", $id)
                            ->get()->toArray();
            
            if(count($customer) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Pelanggan tidak ditemukan!',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Data Pelanggan!',
                    'data'    => $customer
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
    
    public function getPelangganByParams(Request $request)
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

        $customer = DB::table('master_customer as customer')
                    ->select(
                        'customer.id as idcustomer',
                        'customer.code as code',
                        'customer.company_name as company_name',
                        'customer.owner_name as owner_name',
                        'customer.ktp_id as ktp_id',
                        'customer.npwp as npwp',
                        'customer.address as address',
                        'customer.provinsi_id as provinsi_id',
                        'customer.kabupaten_kota_id as kabupaten_kota_id',
                        'customer.kecamatan_id as kecamatan_id',
                        'customer.desa_kelurahan_id as desa_kelurahan_id',
                        'customer.zipcode as zipcode',
                        'customer.phone_number as phone_number',
                        'customer.email as email',
                        'customer.pic_name as pic_name',
                        'customer.pic_phone_number as pic_phone_number',
                        'customer.status as status',
                        'customer.created_date as created_date',
                        'customer.updated_date as updated_date',
                        'provinsi.name as nama_provinsi',
                        'kabupatenkota.name as nama_kabupaten_kota',
                        'kecamatan.name as nama_kecamatan',
                        'desa_kelurahan.name as nama_desa_kelurahan',
                    )
                    ->leftJoin('master_provinsi as provinsi', 'customer.provinsi_id', '=', 'provinsi.id')
                    ->leftJoin('master_kabupaten_kota as kabupatenkota', 'customer.kabupaten_kota_id', '=', 'kabupatenkota.id')
                    ->leftJoin('master_kecamatan as kecamatan', 'customer.kecamatan_id', '=', 'kecamatan.id')
                    ->leftJoin('master_desa_kelurahan as desa_kelurahan', 'customer.desa_kelurahan_id', '=', 'desa_kelurahan.id')
                    ->where("code", 'like', '%'.$params.'%')
                    ->orWhere("company_name", 'like', '%'.$params.'%')
                    ->orWhere("owner_name", 'like', '%'.$params.'%')
                    ->orWhere("ktp_id", 'like', '%'.$params.'%')
                    ->orWhere("npwp", 'like', '%'.$params.'%')
                    ->orWhere("address", 'like', '%'.$params.'%')
                    ->orWhere("provinsi.name", 'like', '%'.$params.'%')
                    ->orWhere("kabupatenkota.name", 'like', '%'.$params.'%')
                    ->orWhere("kecamatan.name", 'like', '%'.$params.'%')
                    ->orWhere("desa_kelurahan.name", 'like', '%'.$params.'%')
                    ->orWhere("zipcode", 'like', '%'.$params.'%')
                    ->orWhere("phone_number", 'like', '%'.$params.'%')
                    ->orWhere("email", 'like', '%'.$params.'%')
                    ->orWhere("pic_name", 'like', '%'.$params.'%')
                    ->orWhere("pic_phone_number", 'like', '%'.$params.'%')
                    ->orWhere("status", 'like', '%'.$params.'%')
                    ->get()->toArray();

        if(count($customer) === 0){
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak ditemukan!',
                'data'    => ''
            ], 201);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Data Pengguna!',
                'data'    => $customer
            ], 200);
        } 
    }

    // Function OTP
    public function resendOTP(Request $request){
        $handphone = htmlentities($request->input('handphone'));
        $angka = floor(rand(1000, 9999));
        $current_date_time = \Carbon\Carbon::now()->toDateTimeString();
        $otp_expired = \Carbon\Carbon::now()->addMinutes(10)->toDateTimeString();

        $pengguna = Customer::select("*")
                            ->where("pic_phone_number", $handphone)
                            ->get()->toArray();
                
        $updatePengguna = Customer::where('pic_phone_number', $handphone)->update([
            'otp'   => $angka,
            'otp_expire'   => $otp_expired,
            'status'     => 'registered'
        ]);
        
        $message = "Kode OTP Anda: " . $angka . ", RAHASIAKAN kode OTP Anda" ;
        $smsAPI = $this->smsAPI($handphone, $message);
        if($smsAPI == 'Success'){
            return response()->json([
                'success' => true,
                'message' => 'Token successfully send to your phone.'
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Token Failed send to your phone'
            ], 422);
        }
        
    }

    public function verifyOTP(Request $request){
        $handphone = htmlentities($request->input('handphone'));
        $otp = htmlentities($request->input('otp'));
        $current_date_time = \Carbon\Carbon::now()->toDateTimeString();
        
        $pengguna = Customer::select("*")
                            ->where("pic_phone_number", $handphone)
                            ->where("otp", $otp)
                            ->get()->toArray();
                            
        if(count($pengguna) === 0){
            return response()->json([
                'success' => false,
                'message' => 'OTP not found!',
                'data'    => ''
            ], 204);
        } else {
            if($current_date_time < $pengguna[0]['otp_expire']){     
                // Proses update OTP
                $updatePengguna = Customer::where('pic_phone_number', $handphone)->update([
                    'otp'   => null,
                    'otp_expire'   => null,
                    'status'     => 'active'
                ]);

                if($updatePengguna){
                    return response()->json([
                        'success' => true,
                        'message' => 'Token Valid!',
                        'data'    => ''
                    ], 200);
                }else{
                    return response()->json([
                        'success' => false,
                        'message' => 'System Error, please insert otp again!',
                        'data'    => ''
                    ], 202);
                }                               
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Otp expired!',
                    'data'    => ''
                ], 202);
            }
        }
    }

    public function smsAPI($username, $message){
        $url = 'https://api-sms.clenicapp.com/otp_api.php';

        $response = Http::withHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->withBody(http_build_query([
            "no_hp" => $username,
            "message" => $message
        ]), 'application/json')->post($url)->collect()->toArray();
        
        return $response['message'];

    }





    
}
