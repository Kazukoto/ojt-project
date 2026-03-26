<?php


use App\Http\Controllers\AddingController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CashAdvanceController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\ModalController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\TimekeeperController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PayrollMasterController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SuperAdminPayrollController;
use App\Http\Controllers\HolidayController;

Route::get('/superadmin', [SuperAdminController::class, 'index'])->name('superadmin.index');
Route::get('/superadmin/payroll/gross', [SuperAdminController::class, 'getGrossPay'])->name('superadmin.payroll.gross');

Route::prefix('superadmin')->group(function () {
    Route::get('/users', [SuperAdminController::class, 'users'])->name('superadmin.users');
    Route::post('/users', [SuperAdminController::class, 'storeNewUser'])->name('superadmin.store');

});//Payroll and payslip routes

Route::get('/superadmin/payroll/employees', [ SuperAdminController::class, 'employees'])->name('superadmin.payroll.employees');
Route::get('/superadmin/payroll/', [SuperAdminPayrollController::class, 'index'])->name('superadmin.payroll');
Route::get('/superadmin/payslip', [SuperAdminController::class, 'payslip'])->name('superadmin.payslip');

//Cash advance routes

Route::prefix('superadmin')->group(function () {
    Route::get('/cashadvance', [SuperAdminController::class, 'cashadvance'])->name('superadmin.cashadvance');
    Route::post('/cashadvance', [SuperAdminController::class, 'storeCashAdvance'])->name('superadmin.cashadvance.store');
    Route::get('/cashadvance/{id}/modal', [SuperAdminController::class, 'getCashAdvanceModal']);
    Route::put('/cashadvance/{id}', [SuperAdminController::class, 'updateCashAdvance'])->name('superadmin.cashadvance.update');
    Route::delete('/cashadvance/{id}', [SuperAdminController::class, 'deleteCashAdvance'])->name('superadmin.cashadvance.delete');

    // Statutory (store only — GET is now handled by the combined cashadvance page)
    Route::post('/statutory', [SuperAdminController::class, 'storeStatutory'])->name('superadmin.statutory.store');
});

//Attendance routes

Route::get('/superadmin/attendance', [SuperAdminController::class, 'attendance'])->name('superadmin.attendance');
Route::post('/superadmin/attendance/store', [SuperAdminController::class, 'storeAttendance'])->name('superadmin.attendance.store');
Route::post('/superadmin/attendance/store-nsd', [SuperAdminController::class, 'storeNsd'])->name('superadmin.attendance.storeNsd');
//Employees routes
Route::get('/superadmin/employees', [SuperAdminController::class, 'employed'])->name('superadmin.employees');
Route::post('/superadmin/employees', [SuperAdminController::class, 'storeNewEmployee'])->name('superadmin.employees.store');
Route::get('/superadmin/employees/{id}/modal',  [SuperAdminController::class, 'getEmployeeModal'])->name('superadmin.employees.modal');
Route::put('/superadmin/employees/{id}',         [SuperAdminController::class, 'updateEmployee'])->name('superadmin.employees.update');
Route::get('/superadmin/employees/{id}/archive-precheck', [SuperAdminController::class, 'archivePrecheck']);
//Rates and approval routes

Route::get('/superadmin/rateapproval', [SuperAdminController::class, 'read'])->name('superadmin.rateapproval');
Route::put('/superadmin/rateapproval/{employee}', [SuperAdminController::class, 'update'])->name('superadmin.rateapproval.update');

//Archives routes
Route::get('/superadmin/archive', [SuperAdminController::class, 'indexArchive'])->name('superadmin.archive');
Route::post('/superadmin/archive/{id}/restore', [SuperAdminController::class, 'restore'])->name('superadmin.archive.restore');
Route::delete('/superadmin/archive/{id}', [SuperAdminController::class, 'destroyArchived'])->name('superadmin.archive.destroy');
Route::post('/superadmin/employees/{id}/archive', [SuperAdminController::class, 'deleteEmployee']);
Route::get('/superadmin/archive', [SuperAdminController::class, 'indexArchive']);
Route::post('/superadmin/archive/{id}/restore', [SuperAdminController::class, 'restore']);
Route::delete('/superadmin/archive/{id}', [SuperAdminController::class, 'destroyArchived']);
Route::get('/superadmin/employees/by-username/{username}', [SuperAdminController::class, 'getEmployeeByUsername']);
Route::put('/superadmin/users/{id}',          [SuperAdminController::class, 'updateUser'])       ->name('superadmin.users.update');
Route::get('/superadmin/users/{id}/status',   [SuperAdminController::class, 'getUserStatus'])    ->name('superadmin.users.status');
Route::patch('/superadmin/users/{id}/status', [SuperAdminController::class, 'updateUserStatus']) ->name('superadmin.users.updateStatus');
//Roles Routing

Route::get('/superadmin/holiday', [SuperAdminController::class, 'holidays']);

Route::prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/rolemanagement', [SuperAdminController::class, 'indexRole'])->name('roles.index');
    Route::post('/rolemanagement', [SuperAdminController::class, 'store'])->name('roles.store');
    Route::put('/rolemanagement/{id}', [SuperAdminController::class, 'updateRole'])->name('roles.update');
    Route::delete('/rolemanagement/{id}', [SuperAdminController::class, 'destroyRole'])->name('roles.destroy');
    Route::post('/projects', [SuperAdminController::class, 'storeProject'])->name('projects.store');
    Route::put('/projects/{id}', [SuperAdminController::class, 'updateProject'])->name('projects.update');
    Route::delete('/projects/{id}', [SuperAdminController::class, 'destroyProject'])->name('projects.destroy');
});

Route::get('/roles', function () {return \App\Models\Role::orderBy('role_name', 'asc')->get();});

Route::get('/api/roles', function () {return \App\Models\Role::orderBy('role_name', 'asc')->get();})->name('api.roles');

Route::prefix('superadmin')->name('superadmin.')->group(function () {

    // Holiday management (Superadmin only)
    Route::get('/holiday',         [HolidayController::class, 'index'])   ->name('holiday.index');
    Route::post('/holiday',        [HolidayController::class, 'store'])   ->name('holiday.store');
    Route::put('/holiday/{id}',    [HolidayController::class, 'update'])  ->name('holiday.update');
    Route::delete('/holiday/{id}', [HolidayController::class, 'destroy']) ->name('holiday.destroy');

});

// Public API used by Attendance auto-detect on clock-in
Route::get('/api/superadmin/holiday-check', [HolidayController::class, 'checkDate'])
     ->name('api.holiday.check');

Route::get('/superadmin/payslip/export', [SuperAdminController::class, 'exportPayslip'])
->name('superadmin.payslip.export');

// Payroll page (blade)
Route::get('/superadmin/payroll',        [SuperAdminPayrollController::class, 'showPayroll'])
    ->name('superadmin.payroll');

// PDF export only
Route::get('/superadmin/payroll/export', [SuperAdminPayrollController::class, 'exportPayroll'])
    ->name('superadmin.payroll_pdf');

Route::get('/superadmin/statutory', [SuperAdminController::class, 'statutory'])->name('superadmin.statutory');
Route::post('/superadmin/statutory/store', [SuperAdminController::class, 'storeStatutory'])->name('superadmin.statutory.store');
