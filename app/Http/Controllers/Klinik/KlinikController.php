<?php

namespace App\Http\Controllers\Klinik;

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
use App\Models\Provinsi;
use App\Models\Kabupaten_Kota;
use App\Models\Kecamatan;
use App\Models\Desa_Kelurahan;

class KlinikController extends Controller
{
    public function createKlinik(Request $request)
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
        $customer_id = htmlentities($request->input('customer_id'));
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

        $created_at = Carbon\Carbon::now();

        $periksaEmailClinic = Clinic::select("*")
                            ->where("email", $email)
                            ->get()->toArray();

        if($periksaEmailClinic){
            return response()->json([
                'success' => false,
                'message' => 'Email anda sudah digunakan oleh klinik yang lain',
                'data'    => ''
            ], 402);      
        }
        
        $periksaNPWPClinic = Clinic::select("*")
                        ->where("npwp", $npwp)
                        ->get()->toArray();
        
        if($periksaNPWPClinic){
            return response()->json([
                'success' => false,
                'message' => 'NPWP anda sudah digunakan oleh klinik yang lain',
                'data'    => ''
            ], 402);      
        }

        $periksaTelpClinic = Clinic::select("*")
                        ->where("phone_number", $phone_number)
                        ->get()->toArray();
        
        if($periksaTelpClinic){
            return response()->json([
                'success' => false,
                'message' => 'Telp anda sudah digunakan oleh klinik yang lain',
                'data'    => ''
            ], 402);      
        }

        $periksaPICNumberClinic = Clinic::select("*")
                        ->where("pic_phone_number", $pic_phone_number)
                        ->get()->toArray();
        
        if($periksaPICNumberClinic){
            return response()->json([
                'success' => false,
                'message' => 'Nomor handphone anda sudah digunakan oleh klinik yang lain',
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
        $codeClinic = Code_Settings::select("*")
                        ->where("table_name", "master_clinic")
                        ->get()->toArray();
        $clinic = Clinic::select("*")->orderBy('id', 'DESC')->first();
        $codeAngka = str_pad($codeClinic[0]['counter'] + 1, $codeClinic[0]['digit'], '0', STR_PAD_LEFT);
        $code = $codeClinic[0]['prefix'] . $codeAngka;
        
        DB::beginTransaction();
        try{
            Clinic::create([
                'code'          => $code,
                'name'          => $name,
                'customer_id'   => $customer_id,
                'npwp'     => $npwp,
                'address'     => $address,
                'provinsi_id'     => $provinsi_id,
                'kabupaten_kota_id'     => $kabupaten_kota_id,
                'kecamatan_id'          => $kecamatan_id,
                'desa_kelurahan_id'          => $desa_kelurahan_id,
                'zipcode'          => $zipcode,
                'phone_number'          => $phone_number,
                'email'          => $email,
                'pic_name'          => $pic_name,
                'pic_phone_number'          => $pic_phone_number,
                'status'     => $status,
                'created_at'     => $created_at
            ]);

            Code_Settings::where('table_name', 'master_clinic')->update([
                'counter'     => $codeClinic[0]['counter'] + 1,
                'updated_at'    => Carbon\Carbon::now()
            ]);

            $periksaClinic = Clinic::select("*")
                            ->where("code", $code)
                            ->orWhere("email", $email)
                            ->get()->toArray();

            $log_activities = Log_Activities::create([
                'user_id'       => $headerID,
                'table_name'    => 'Master Clinic',
                'table_id'      => $periksaClinic[0]['id'],
                'action'        => 'add',
                'changes'       => 'Clinic',
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
                'message' => 'Data klinik sudah dibuat.'
            ], 200);
        } catch (Throwable $e) {
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Sistem sedang mengalami gangguan, silahkan coba beberapa saat lagi."
            ]);
        }
    }

    public function updateKlinik(Request $request)
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
        $customer_id = htmlentities($request->input('customer_id'));
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

        $updated_at = Carbon\Carbon::now();

        $periksaNPWPKlinik = Clinic::select("*")
                            ->where("npwp", $npwp)
                            ->get()->toArray();

        if(count($periksaNPWPKlinik) != 0){
            foreach($periksaNPWPKlinik as $row){
                if($id != $row['id']){
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'NPWP anda sudah digunakan oleh klinik yang lain!',
                        'data'    => ''
                    ], 201);
                }
            }
        } 
        
        $periksaTelpKlinik = Clinic::select("*")
                        ->where("phone_number", $phone_number)
                        ->get()->toArray();
        
        if(count($periksaTelpKlinik) != 0){
            foreach($periksaTelpKlinik as $row){
                if($id != $row['id']){
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Telp anda sudah digunakan oleh klinik yang lain',
                        'data'    => ''
                    ], 402);      
                }
            }
        }

        $periksaPICNumberKlinik = Clinic::select("*")
                            ->where("pic_phone_number", $pic_phone_number)
                            ->get()->toArray();

        if(count($periksaPICNumberKlinik) != 0){
            foreach($periksaPICNumberKlinik as $row){
                if($id != $row['id']){
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Nomor handphone anda sudah digunakan oleh klinik yang lain!',
                        'data'    => ''
                    ], 201);
                }
            }
        } 
        
        $periksaEmailKlinik = Clinic::select("*")
                            ->where("email", $email)
                            ->get()->toArray();

        if(count($periksaEmailKlinik) != 0){
            foreach($periksaEmailKlinik as $row){
                if($id != $row['id']){
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Email anda sudah digunakan oleh klinik yang lain!',
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
            $editKlinik = Clinic::where('id', $id)->update([
                'name'   => $name,
                'customer_id'     => $customer_id,
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
                'updated_at'     => $updated_at
            ]);

            if($editKlinik){
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Klinik',
                    'table_id'      => $id,
                    'action'        => 'update',
                    'changes'       => 'Klinik',
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
                    'message' => 'Klinik sudah diubah'
                ], 200);
            }else{
                DB::rollback();
                return response([
                    'success' => true,
                    'message' => 'Klinik gagal diubah, silahkan coba beberapa saat lagi'
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
    
    public function deleteKlinik(Request $request)
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
            $editKlinik = Clinic::where('id', $id)->update([
                'status'     => 'inactive',
                'updated_at'    => Carbon\Carbon::now()
            ]);

            if($editKlinik){
                $log_activities = Log_Activities::create([
                    'user_id'       => $headerID,
                    'table_name'    => 'Master Klinik',
                    'table_id'      => $id,
                    'action'        => 'delete',
                    'changes'       => 'Klinik',
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
                    'message' => 'Klinik sudah dihapus!'
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
    
    public function getAllKlinik(Request $request)
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
            $klinik = DB::table('master_clinic as clinic')
            ->select(
                'clinic.id as id',
                'clinic.code as code',
                'clinic.name as name',
                'clinic.npwp as npwp',
                'clinic.address as address',
                'clinic.provinsi_id as provinsi_id',
                'clinic.kabupaten_kota_id as kabupaten_kota_id',
                'clinic.kecamatan_id as kecamatan_id',
                'clinic.desa_kelurahan_id as desa_kelurahan_id',
                'clinic.zipcode as zipcode',
                'clinic.phone_number as phone_number',
                'clinic.email as email',
                'clinic.pic_name as pic_name',
                'clinic.pic_phone_number as pic_phone_number',
                'clinic.status as status',
                'clinic.created_at as created_at',
                'clinic.updated_at as updated_at',
                'customer.id as id_customer',
                'customer.code as code_customer',
                'customer.company_name as company_name_customer',
                'customer.owner_name as owner_name_customer',
                'customer.ktp_id as ktp_id_customer',
                'customer.npwp as npwp_customer',
                'customer.address as address_customer',
                'customer.provinsi_id as provinsi_id_customer',
                'customer.kabupaten_kota_id as kabupaten_kota_id_customer',
                'customer.kecamatan_id as kecamatan_id_customer',
                'customer.desa_kelurahan_id as desa_kelurahan_id_customer',
                'customer.zipcode as zipcode_customer',
                'customer.phone_number as phone_number_customer',
                'customer.email as email_customer',
                'customer.pic_name as pic_name_customer',
                'customer.pic_phone_number as pic_phone_number_customer',
                'customer.status as status_customer',
                'customer.created_date as created_date_customer',
                'customer.updated_date as updated_date_customer',
                'provinsi.name as nama_provinsi',
                'kabupatenkota.name as nama_kabupaten_kota',
                'kecamatan.name as nama_kecamatan',
                'desa_kelurahan.name as nama_desa_kelurahan',
            )
            ->leftJoin('master_customer as customer', 'clinic.customer_id', '=', 'customer.id')
            ->leftJoin('master_provinsi as provinsi', 'customer.provinsi_id', '=', 'provinsi.id')
            ->leftJoin('master_kabupaten_kota as kabupatenkota', 'customer.kabupaten_kota_id', '=', 'kabupatenkota.id')
            ->leftJoin('master_kecamatan as kecamatan', 'customer.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoin('master_desa_kelurahan as desa_kelurahan', 'customer.desa_kelurahan_id', '=', 'desa_kelurahan.id')
            ->get()->toArray();
            if(count($klinik) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Klinik tidak ditemukan',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Daftar semua Klinik',
                    'data' => $klinik
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

    public function getAllKlinikByCustomerID(Request $request)
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
        $customerID = htmlentities($request->input('customerID'));

        try{
            $klinik = DB::table('master_clinic as clinic')
            ->select(
                'clinic.id as id',
                'clinic.code as code',
                'clinic.name as name',
                'clinic.npwp as npwp',
                'clinic.address as address',
                'clinic.provinsi_id as provinsi_id',
                'clinic.kabupaten_kota_id as kabupaten_kota_id',
                'clinic.kecamatan_id as kecamatan_id',
                'clinic.desa_kelurahan_id as desa_kelurahan_id',
                'clinic.zipcode as zipcode',
                'clinic.phone_number as phone_number',
                'clinic.email as email',
                'clinic.pic_name as pic_name',
                'clinic.pic_phone_number as pic_phone_number',
                'clinic.status as status',
                'clinic.created_at as created_at',
                'clinic.updated_at as updated_at',
                'customer.id as id_customer',
                'customer.code as code_customer',
                'customer.company_name as company_name_customer',
                'customer.owner_name as owner_name_customer',
                'customer.ktp_id as ktp_id_customer',
                'customer.npwp as npwp_customer',
                'customer.address as address_customer',
                'customer.provinsi_id as provinsi_id_customer',
                'customer.kabupaten_kota_id as kabupaten_kota_id_customer',
                'customer.kecamatan_id as kecamatan_id_customer',
                'customer.desa_kelurahan_id as desa_kelurahan_id_customer',
                'customer.zipcode as zipcode_customer',
                'customer.phone_number as phone_number_customer',
                'customer.email as email_customer',
                'customer.pic_name as pic_name_customer',
                'customer.pic_phone_number as pic_phone_number_customer',
                'customer.status as status_customer',
                'customer.created_date as created_date_customer',
                'customer.updated_date as updated_date_customer',
                'provinsi.name as nama_provinsi',
                'kabupatenkota.name as nama_kabupaten_kota',
                'kecamatan.name as nama_kecamatan',
                'desa_kelurahan.name as nama_desa_kelurahan',
            )
            ->leftJoin('master_customer as customer', 'clinic.customer_id', '=', 'customer.id')
            ->leftJoin('master_provinsi as provinsi', 'customer.provinsi_id', '=', 'provinsi.id')
            ->leftJoin('master_kabupaten_kota as kabupatenkota', 'customer.kabupaten_kota_id', '=', 'kabupatenkota.id')
            ->leftJoin('master_kecamatan as kecamatan', 'customer.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoin('master_desa_kelurahan as desa_kelurahan', 'customer.desa_kelurahan_id', '=', 'desa_kelurahan.id')
            ->where("clinic.customer_id", $customerID)
            ->get()->toArray();
            if(count($klinik) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Klinik tidak ditemukan',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response([
                    'success' => true,
                    'message' => 'Daftar semua Klinik',
                    'data' => $klinik
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

    public function getKlinikByID(Request $request)
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
            $clinic = Clinic::select("*")
                            ->where("id", $id)
                            ->get()->toArray();
            
            if(count($clinic) === 0){
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Klinik tidak ditemukan!',
                    'data'    => ''
                ], 201);
            } else {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Data Klinik!',
                    'data'    => $clinic
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

    public function getKlinikByParams(Request $request)
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

        $clinic = DB::table('master_clinic as clinic')
                    ->select(
                    'clinic.id as id',
                    'clinic.code as code',
                    'clinic.name as name',
                    'clinic.npwp as npwp',
                    'clinic.address as address',
                    'clinic.provinsi_id as provinsi_id',
                    'clinic.kabupaten_kota_id as kabupaten_kota_id',
                    'clinic.kecamatan_id as kecamatan_id',
                    'clinic.desa_kelurahan_id as desa_kelurahan_id',
                    'clinic.zipcode as zipcode',
                    'clinic.phone_number as phone_number',
                    'clinic.email as email',
                    'clinic.pic_name as pic_name',
                    'clinic.pic_phone_number as pic_phone_number',
                    'clinic.status as status',
                    'clinic.created_at as created_at',
                    'clinic.updated_at as updated_at',
                    'customer.id as id_customer',
                    'customer.code as code_customer',
                    'customer.company_name as company_name_customer',
                    'customer.owner_name as owner_name_customer',
                    'customer.ktp_id as ktp_id_customer',
                    'customer.npwp as npwp_customer',
                    'customer.address as address_customer',
                    'customer.provinsi_id as provinsi_id_customer',
                    'customer.kabupaten_kota_id as kabupaten_kota_id_customer',
                    'customer.kecamatan_id as kecamatan_id_customer',
                    'customer.desa_kelurahan_id as desa_kelurahan_id_customer',
                    'customer.zipcode as zipcode_customer',
                    'customer.phone_number as phone_number_customer',
                    'customer.email as email_customer',
                    'customer.pic_name as pic_name_customer',
                    'customer.pic_phone_number as pic_phone_number_customer',
                    'customer.status as status_customer',
                    'customer.created_date as created_date_customer',
                    'customer.updated_date as updated_date_customer',
                    'provinsi.name as nama_provinsi',
                    'kabupatenkota.name as nama_kabupaten_kota',
                    'kecamatan.name as nama_kecamatan',
                    'desa_kelurahan.name as nama_desa_kelurahan',
                    )
                    ->leftJoin('master_customer as customer', 'clinic.customer_id', '=', 'customer.id')
                    ->leftJoin('master_provinsi as provinsi', 'customer.provinsi_id', '=', 'provinsi.id')
                    ->leftJoin('master_kabupaten_kota as kabupatenkota', 'customer.kabupaten_kota_id', '=', 'kabupatenkota.id')
                    ->leftJoin('master_kecamatan as kecamatan', 'customer.kecamatan_id', '=', 'kecamatan.id')
                    ->leftJoin('master_desa_kelurahan as desa_kelurahan', 'customer.desa_kelurahan_id', '=', 'desa_kelurahan.id')
                    ->where("clinic.code", 'like', '%'.$params.'%')
                    ->orWhere("clinic.name", 'like', '%'.$params.'%')
                    ->orWhere("clinic.npwp", 'like', '%'.$params.'%')
                    ->orWhere("clinic.address", 'like', '%'.$params.'%')
                    ->orWhere("provinsi.name", 'like', '%'.$params.'%')
                    ->orWhere("kabupatenkota.name", 'like', '%'.$params.'%')
                    ->orWhere("kecamatan.name", 'like', '%'.$params.'%')
                    ->orWhere("desa_kelurahan.name", 'like', '%'.$params.'%')
                    ->orWhere("clinic.zipcode", 'like', '%'.$params.'%')
                    ->orWhere("clinic.phone_number", 'like', '%'.$params.'%')
                    ->orWhere("clinic.email", 'like', '%'.$params.'%')
                    ->orWhere("clinic.pic_name", 'like', '%'.$params.'%')
                    ->orWhere("clinic.pic_phone_number", 'like', '%'.$params.'%')
                    ->orWhere("clinic.status", 'like', '%'.$params.'%')
                    ->orWhere("customer.code", 'like', '%'.$params.'%')
                    ->orWhere("customer.company_name", 'like', '%'.$params.'%')
                    ->orWhere("customer.owner_name", 'like', '%'.$params.'%')
                    ->orWhere("customer.ktp_id", 'like', '%'.$params.'%')
                    ->orWhere("customer.npwp", 'like', '%'.$params.'%')
                    ->orWhere("customer.address", 'like', '%'.$params.'%')
                    ->orWhere("customer.phone_number", 'like', '%'.$params.'%')
                    ->orWhere("customer.email", 'like', '%'.$params.'%')
                    ->orWhere("customer.pic_name", 'like', '%'.$params.'%')
                    ->orWhere("customer.pic_phone_number", 'like', '%'.$params.'%')
                    ->get()->toArray();

        if(count($clinic) === 0){
            return response()->json([
                'success' => false,
                'message' => 'Klinik tidak ditemukan!',
                'data'    => ''
            ], 201);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Data Klinik!',
                'data'    => $clinic
            ], 200);
        } 
    }

}
