<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Route::get('/klinik/detail/{id?}', '\App\Http\Controllers\Api\cabang\KlinikController@detailCabang');

// Routes Pengguna
Route::post('/user/login', '\App\Http\Controllers\Pengguna\PenggunaController@login');
Route::post('/user/logout', '\App\Http\Controllers\Pengguna\PenggunaController@logout');
Route::post('/user/register', '\App\Http\Controllers\Pengguna\PenggunaController@register');
Route::post('/user/updateUser', '\App\Http\Controllers\Pengguna\PenggunaController@updateUser');
Route::get('/user/deleteUser', '\App\Http\Controllers\Pengguna\PenggunaController@deleteUser');
Route::get('/user/getAllUser/{offset?}/{limit?}', '\App\Http\Controllers\Pengguna\PenggunaController@getAllUser');
Route::get('/user/getUserByID/{id?}', '\App\Http\Controllers\Pengguna\PenggunaController@getUserByID');
Route::get('/user/getUserByParams/{params?}', '\App\Http\Controllers\Pengguna\PenggunaController@getUserByParams');
Route::get('/user/resetPassword', '\App\Http\Controllers\Pengguna\PenggunaController@resetPassword');
Route::post('/user/ubahPassword', '\App\Http\Controllers\Pengguna\PenggunaController@ubahPassword');

// Code Settings
Route::post('/code-settings/createCodeSettings', '\App\Http\Controllers\CodeSettings\CodeSettingsController@createCodeSettings');
Route::post('/code-settings/updateCodeSettings', '\App\Http\Controllers\CodeSettings\CodeSettingsController@updateCodeSettings');
Route::get('/code-settings/deleteCodeSettings/{id?}', '\App\Http\Controllers\CodeSettings\CodeSettingsController@deleteCodeSettings');
Route::get('/code-settings/getAllCodeSettings/{offset?}/{limit?}', '\App\Http\Controllers\CodeSettings\CodeSettingsController@getAllCodeSettings');
Route::get('/code-settings/getCodeSettingsByID', '\App\Http\Controllers\CodeSettings\CodeSettingsController@getCodeSettingsByID');
Route::get('/code-settings/getCodeSettingsByParams/{params?}', '\App\Http\Controllers\CodeSettings\CodeSettingsController@getCodeSettingsByParams');
Route::get('/code-settings/getByTableName/{table_name?}', '\App\Http\Controllers\CodeSettings\CodeSettingsController@getByTableName');

// Routes Department
Route::post('/department/createDepartment', '\App\Http\Controllers\Department\DepartmentController@createDepartment');
Route::post('/department/updateDepartment', '\App\Http\Controllers\Department\DepartmentController@updateDepartment');
Route::get('/department/deleteDepartment/{id?}', '\App\Http\Controllers\Department\DepartmentController@deleteDepartment');
Route::get('/department/getAllDepartment/{offset?}/{limit?}', '\App\Http\Controllers\Department\DepartmentController@getAllDepartment');
Route::get('/department/GetAllAktif', '\App\Http\Controllers\Department\DepartmentController@GetAllAktif');
Route::get('/department/getDepartmentByID', '\App\Http\Controllers\Department\DepartmentController@getDepartmentByID');
Route::get('/department/getDepartmentByParams/{params?}', '\App\Http\Controllers\Department\DepartmentController@getDepartmentByParams');

// Routes Role
Route::post('/role/createRole', '\App\Http\Controllers\Role\RoleController@createRole');
Route::post('/role/updateRole', '\App\Http\Controllers\Role\RoleController@updateRole');
Route::get('/role/deleteRole/{id?}', '\App\Http\Controllers\Role\RoleController@deleteRole');
Route::get('/role/getAllRole/{offset?}/{limit?}', '\App\Http\Controllers\Role\RoleController@getAllRole');
Route::get('/role/getRoleByID/{id?}', '\App\Http\Controllers\Role\RoleController@getRole');
Route::get('/role/getRoleByParams/{params?}', '\App\Http\Controllers\Role\RoleController@getRoleByParams');

// Routes Permission
Route::post('/permission/createPermission', '\App\Http\Controllers\Permission\PermissionController@createPermission');
Route::post('/permission/updatePermission', '\App\Http\Controllers\Permission\PermissionController@updatePermission');
Route::get('/permission/deletePermission/{id?}', '\App\Http\Controllers\Permission\PermissionController@deletePermission');
Route::get('/permission/getAllPermission/{offset?}/{limit?}', '\App\Http\Controllers\Permission\PermissionController@getAllPermission');
Route::get('/permission/getPermissionByID/{id?}', '\App\Http\Controllers\Permission\PermissionController@getPermission');
Route::get('/permission/getPermissionByParams/{params?}', '\App\Http\Controllers\Permission\PermissionController@getPermissionByParams');

// Routes Role Permission
Route::get('/role-permission/getAllPermission/{offset?}/{limit?}', '\App\Http\Controllers\RolePermission\RolePermissionController@getAllPermission');
Route::get('/role-permission/getRolePermissionByParams/{params?}', '\App\Http\Controllers\RolePermission\RolePermissionController@getPermissionByParams');
Route::post('/role-permission/updateRolePermission', '\App\Http\Controllers\RolePermission\RolePermissionController@updateRolePermission');
Route::get('/role-permission/getAllMenu', '\App\Http\Controllers\RolePermission\RolePermissionController@getAllMenu');
Route::get('/role-permission/getValidasiButton', '\App\Http\Controllers\RolePermission\RolePermissionController@getValidasiButton');
Route::post('/role-permission/updateRoleSemuaPermission', '\App\Http\Controllers\RolePermission\RolePermissionController@updateRoleSemuaPermission');
Route::post('/role-permission/periksaCheckBoxAllRolePermission', '\App\Http\Controllers\RolePermission\RolePermissionController@periksaCheckBoxAllRolePermission');

// Routes Pelanggan
Route::get('/pelanggan/getAllPelanggan/{offset?}/{limit?}', '\App\Http\Controllers\Pelanggan\PelangganController@getAllPelanggan');
Route::post('/pelanggan/createPelanggan', '\App\Http\Controllers\Pelanggan\PelangganController@createPelanggan');
Route::post('/pelanggan/resendOTP', '\App\Http\Controllers\Pelanggan\PelangganController@resendOTP');
Route::post('/pelanggan/verifyOTP', '\App\Http\Controllers\Pelanggan\PelangganController@verifyOTP');
Route::post('/pelanggan/updatePelanggan', '\App\Http\Controllers\Pelanggan\PelangganController@updatePelanggan');
Route::get('/pelanggan/deletePelanggan/{id?}', '\App\Http\Controllers\Pelanggan\PelangganController@deletePelanggan');
Route::get('/pelanggan/getPelangganByID/{id?}', '\App\Http\Controllers\Pelanggan\PelangganController@getPelangganByID');
Route::get('/pelanggan/getPelangganByParams/{params?}', '\App\Http\Controllers\Pelanggan\PelangganController@getPelangganByParams');

// Routes Klinik
Route::get('/klinik/getAllKlinik/{offset?}/{limit?}', '\App\Http\Controllers\Klinik\KlinikController@getAllKlinik');
Route::get('/klinik/getAllKlinikByCustomerID/{customerID?}', '\App\Http\Controllers\Klinik\KlinikController@getAllKlinikByCustomerID');
Route::post('/klinik/createKlinik', '\App\Http\Controllers\Klinik\KlinikController@createKlinik');
Route::post('/klinik/updateKlinik', '\App\Http\Controllers\Klinik\KlinikController@updateKlinik');
Route::get('/klinik/deleteKlinik/{id?}', '\App\Http\Controllers\Klinik\KlinikController@deleteKlinik');
Route::get('/klinik/getKlinikByID/{id?}', '\App\Http\Controllers\Klinik\KlinikController@getKlinikByID');
Route::get('/klinik/getKlinikByParams/{params?}', '\App\Http\Controllers\Klinik\KlinikController@getKlinikByParams');

// Wilayah
Route::get('/wilayah/getAllProvinsi/{offset?}/{limit?}', '\App\Http\Controllers\Wilayah\ProvinsiController@getAllProvinsi');
Route::get('/wilayah/getProvinsiByID/{offset?}/{limit?}', '\App\Http\Controllers\Wilayah\ProvinsiController@getProvinsiByID');
Route::get('/wilayah/getAllKabupatenKota/{offset?}/{limit?}', '\App\Http\Controllers\Wilayah\KabupatenKotaController@getAllKabupatenKota');
Route::get('/wilayah/getAllKabupatenKotaByParams/{params?}', '\App\Http\Controllers\Wilayah\KabupatenKotaController@getAllKabupatenKotaByParams');
Route::get('/wilayah/getKabupatenKotaByID/{offset?}/{limit?}', '\App\Http\Controllers\Wilayah\KabupatenKotaController@getKabupatenKotaByID');
Route::get('/wilayah/getAllKecamatan/{offset?}/{limit?}', '\App\Http\Controllers\Wilayah\KecamatanController@getAllKecamatan');
Route::get('/wilayah/getAllKecamatanByParams/{params?}', '\App\Http\Controllers\Wilayah\KecamatanController@getAllKecamatanByParams');
Route::get('/wilayah/getKecamatanByID/{params?}', '\App\Http\Controllers\Wilayah\KecamatanController@getKecamatanByID');
Route::get('/wilayah/getAllDesaKelurahan/{offset?}/{limit?}', '\App\Http\Controllers\Wilayah\DesaKelurahanController@getAllDesaKelurahan');
Route::get('/wilayah/getAllDesaKelurahanByParams/{params?}', '\App\Http\Controllers\Wilayah\DesaKelurahanController@getAllDesaKelurahanByParams');
Route::get('/wilayah/getDesaKelurahanByID/{params?}', '\App\Http\Controllers\Wilayah\DesaKelurahanController@getDesaKelurahanByID');

// User Clenic
Route::post('/userClenic/createUserClenic', '\App\Http\Controllers\UserClenic\UserClenicController@createUserClenic');
Route::post('/userClenic/updateUserClenic', '\App\Http\Controllers\UserClenic\UserClenicController@updateUserClenic');
Route::get('/userClenic/deleteUserClenic/{id?}', '\App\Http\Controllers\UserClenic\UserClenicController@deleteUserClenic');
Route::get('/userClenic/getUserClenicByID/{id?}', '\App\Http\Controllers\UserClenic\UserClenicController@getUserClenicByID');
Route::get('/userClenic/getUserClenicByParams/{params?}', '\App\Http\Controllers\UserClenic\UserClenicController@getUserClenicByParams');
Route::get('/userClenic/getAllUserClenic/{offset?}/{limit?}', '\App\Http\Controllers\UserClenic\UserClenicController@getAllUserClenic');

// Package Header
Route::post('/package/createPackageHeader', '\App\Http\Controllers\Package\PackageHeaderController@createPackageHeader');
Route::post('/package/updatePackageHeader', '\App\Http\Controllers\Package\PackageHeaderController@updatePackageHeader');
Route::get('/package/deletePackageHeader/{id?}', '\App\Http\Controllers\Package\PackageHeaderController@deletePackageHeader');
Route::get('/package/getPackageHeaderByID/{id?}', '\App\Http\Controllers\Package\PackageHeaderController@getPackageHeaderByID');
Route::get('/package/getPackageHeaderByParams/{params?}', '\App\Http\Controllers\Package\PackageHeaderController@getPackageHeaderByParams');
Route::get('/package/getAllPackageHeader/{offset?}/{limit?}', '\App\Http\Controllers\Package\PackageHeaderController@getAllPackageHeader');

// Package Detail
Route::post('/package/createPackageDetail', '\App\Http\Controllers\Package\PackageDetailController@createPackageDetail');
Route::post('/package/updatePackageDetail', '\App\Http\Controllers\Package\PackageDetailController@updatePackageDetail');
Route::get('/package/deletePackageDetail/{id?}', '\App\Http\Controllers\Package\PackageDetailController@deletePackageDetail');
Route::get('/package/getPackageDetailByID/{id?}', '\App\Http\Controllers\Package\PackageDetailController@getPackageDetailByID');
Route::get('/package/getPackageDetailByParams/{params?}', '\App\Http\Controllers\Package\PackageDetailController@getPackageDetailByParams');
Route::get('/package/getAllPackageDetail/{offset?}/{limit?}', '\App\Http\Controllers\Package\PackageDetailController@getAllPackageDetail');

// Terms of Payment
Route::post('/termsofpayment/createTermsOfPayment', '\App\Http\Controllers\TermsOfPayment\TermsOfPaymentController@createTermsOfPayment');
Route::post('/termsofpayment/updateTermsOfPayment', '\App\Http\Controllers\TermsOfPayment\TermsOfPaymentController@updateTermsOfPayment');
Route::get('/termsofpayment/deleteTermsOfPayment/{id?}', '\App\Http\Controllers\TermsOfPayment\TermsOfPaymentController@deleteTermsOfPayment');
Route::get('/termsofpayment/getAllTermsOfPayment/{offset?}/{limit?}', '\App\Http\Controllers\TermsOfPayment\TermsOfPaymentController@getAllTermsOfPayment');
Route::get('/termsofpayment/getTermsOfPaymentByID/{id?}', '\App\Http\Controllers\TermsOfPayment\TermsOfPaymentController@getTermsOfPaymentByID');
Route::get('/termsofpayment/getTermsOfPaymentByParams/{params?}', '\App\Http\Controllers\TermsOfPayment\TermsOfPaymentController@getTermsOfPaymentByParams');

// Contract Header
Route::post('/contract/createContractHeader', '\App\Http\Controllers\Contract\ContractHeaderController@createContractHeader');
Route::post('/contract/updateContractHeader', '\App\Http\Controllers\Contract\ContractHeaderController@updateContractHeader');
Route::get('/contract/deleteContractHeader/{id?}', '\App\Http\Controllers\Contract\ContractHeaderController@deleteContractHeader');
Route::get('/contract/getContractHeaderByID/{id?}', '\App\Http\Controllers\Contract\ContractHeaderController@getContractHeaderByID');
Route::get('/contract/getContractHeaderByParams/{params?}', '\App\Http\Controllers\Contract\ContractHeaderController@getContractHeaderByParams');
Route::get('/contract/getAllContractHeader/{offset?}/{limit?}', '\App\Http\Controllers\Contract\ContractHeaderController@getAllContractHeader');
Route::post('/contract/checkWaktuSewa', '\App\Http\Controllers\Contract\ContractHeaderController@checkWaktuSewa');

// Contract Detail
Route::post('/contract/createContractDetail', '\App\Http\Controllers\Contract\ContractDetailController@createContractDetail');
Route::post('/contract/updateContractDetail', '\App\Http\Controllers\Contract\ContractDetailController@updateContractDetail');
Route::get('/contract/deleteContractDetail/{id?}', '\App\Http\Controllers\Contract\ContractDetailController@deleteContractDetail');
Route::get('/contract/getContractDetailByID/{id?}', '\App\Http\Controllers\Contract\ContractDetailController@getContractDetailByID');
Route::get('/contract/getContractDetailByParams/{params?}', '\App\Http\Controllers\Contract\ContractDetailController@getContractDetailByParams');
Route::get('/contract/getAllContractDetail/{offset?}/{limit?}', '\App\Http\Controllers\Contract\ContractDetailController@getAllContractDetail');

// Log Activities
Route::get('/logactivities/deleteLogActivities/{tahun?}', '\App\Http\Controllers\LogActivities\LogActivitiesController@deleteLogActivities');
Route::get('/logactivities/getLogActivitiesByID/{id?}', '\App\Http\Controllers\LogActivities\LogActivitiesController@getLogActivitiesByID');
Route::get('/logactivities/getLogActivitiesByParams/{params?}', '\App\Http\Controllers\LogActivities\LogActivitiesController@getLogActivitiesByParams');
Route::get('/logactivities/getAllLogActivities/{offset?}/{limit?}', '\App\Http\Controllers\LogActivities\LogActivitiesController@getAllLogActivities');

// Email
Route::post('/email/emailOTP', '\App\Http\Controllers\Email\EmailController@emailOTP1');

// Mandiri
// Pegawai
Route::post('/employee/createEmployee', '\App\Http\Controllers\Employee\EmployeeController@createEmployee');
Route::get('/employee/getAllEmployee', '\App\Http\Controllers\Employee\EmployeeController@getAllEmployee');
Route::get('/employee/getEmployeeById/{id?}', '\App\Http\Controllers\Employee\EmployeeController@getEmployeeById');