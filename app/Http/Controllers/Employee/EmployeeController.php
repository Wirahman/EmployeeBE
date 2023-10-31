<?php

namespace App\Http\Controllers\Employee;

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
use App\Models\Employee;

class EmployeeController extends Controller
{   
    public function createEmployee(Request $request)
    {
        $username = htmlentities($request->input('username'));
        $first_name = htmlentities($request->input('firstName'));
        $last_name = htmlentities($request->input('lastName'));
        $email = htmlentities($request->input('email'));
        $birth_date = htmlentities($request->input('birthDate'));
        $basic_salary = htmlentities($request->input('basicSalary'));
        $status = htmlentities($request->input('status'));
        $group = htmlentities($request->input('group'));
        $description = htmlentities($request->input('description'));

        $create = Employee::create([
            'username'     => $username,
            'first_name'     => $first_name,
            'last_name'     => $last_name,
            'email'     => $email,
            'birth_date'     => $birth_date,
            'basic_salary'     => $basic_salary,
            'status'     => $status,
            'group'     => $group,
            'description'     => $description
        ]);

        if($create) {
            return response()->json([
                'success' => true,
                'message' => 'Data Pegawai Sudah Dibuat',
                'data'    => ''
            ], 202);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Data Pegawai Gagal Dibuat',
                'data'    => ''
            ], 404);
        }
            
    }

    public function getAllEmployee(Request $request)
    {
        $employee = DB::table('master_employee as employee')
        ->select(
            'employee.id as id',
            'employee.username as username',
            'employee.first_name as firstName',
            'employee.last_name as lastName',
            'employee.email as email',
            'employee.birth_date as birthDate',
            'employee.basic_salary as basicSalary',
            'employee.status as status',
            'employee.group as group',
            'employee.description as description',
        )
        ->get()->toArray();
        
        if($employee) {
            return response()->json([
                'success' => true,
                'message' => 'All Employee Data',
                'employee'    => $employee
            ], 202);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'All Employee Data Cannot Reach',
                'employee'    => ''
            ], 404);
        }
    }

    public function getEmployeeById(Request $request)
    {
        $id = htmlentities($request->input('id'));

        $employee = Employee::select("*")
                ->where("id", $id)
                ->get()->toArray();
                        
        if($employee) {
            return response()->json([
                'success' => true,
                'message' => 'Employee Data By ID',
                'employee'    => $employee
            ], 202);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Employee Data By ID Cannot Reach',
                'employee'    => ''
            ], 404);
        }
    }

    
    
}
