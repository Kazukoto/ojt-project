<?php


use App\Http\Controllers\AddingController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CashAdvanceController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\ModalController;
use App\Http\Controllers\SuperAdminPayrollController;
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
use App\Http\Controllers\RoleController;

Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
Route::get('/admin/payroll/gross', [AdminController::class, 'getGrossPay'])->name('admin.payroll.gross');

// CORRECT ORDER — static paths before {id} wildcards
Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
Route::post('/admin/users', [AdminController::class, 'storeNewUser'])->name('admin.store');
Route::get('/admin/users/{id}/status', [AdminController::class, 'getUserStatus'])->name('admin.users.status');
Route::patch('/admin/users/{id}/status', [AdminController::class, 'updateUserStatus'])->name('admin.users.updateStatus');
Route::put('/admin/users/{id}', [AdminController::class, 'updateUser'])->name('admin.users.update');
Route::get('/admin/employees/by-username/{username}', [AdminController::class, 'getEmployeeByUsername']);

Route::get('/admin/employees/{id}/archive-precheck', [AdminController::class, 'archivePrecheck']);
Route::delete('/admin/archive/{id}', action: [AdminController::class, 'destroy']);
Route::post('/admin/employees/{id}/archive', [AdminController::class, 'deleteEmployee']);


Route::get('/admin/payroll/employees', [PayrollController::class, 'employees'])->name('admin.payroll.employees');
Route::get('/admin/payroll/', [AdminController::class, 'showPayroll'])->name('admin.payroll');
Route::get('/admin/payslip', [PayrollController::class, 'payslip'])->name('admin.payslip');
Route::get('/admin/payslip', function () {
    return view('admin.payslip');
})->name('admin.payslip');

Route::get('/admin/payslip/export', [PayrollMasterController::class, 'exportPayslip'])
    ->name('admin.payslip.export');

Route::get('/admin/payslip', [PayrollMasterController::class, 'payslip'])->name('admin.payslip');


//Routes for attendance

Route::get('/admin/attendance', [AdminController::class, 'attendance'])->name('admin.attendance');
Route::post('/admin/attendance', [AdminController::class, 'storeAttendance'])->name('admin.attendance.store');
Route::post('/admin/attendance/store-nsd', [AdminController::class, 'storeNsd'])->name('admin.attendance.storeNsd');

//employees routes

Route::get('/admin/employees', [AdminController::class, 'employed'])->name('admin.employees');
Route::post('/admin/employees', [AdminController::class, 'storeNewEmployee'])->name('admin.employees.store');
Route::get('/admin/employees/{id}/modal',  [AdminController::class, 'getEmployeeModal'])->name('admin.employees.modal');
Route::put('/admin/employees/{id}',         [AdminController::class, 'updateEmployee'])->name('admin.employees.update');
Route::get('/admin/employees/{id}/archive-precheck', [AdminController::class, 'archivePrecheck']);


Route::prefix('admin')->group(function () {
    Route::get('/cashadvance', [AdminController::class, 'cashadvance'])->name('admin.cashadvance');
    Route::post('/cashadvance', [AdminController::class, 'storeCashAdvance'])->name('admin.cashadvance.store');
    Route::get('/cashadvance/{id}/modal', [AdminController::class, 'getCashAdvanceModal']);
    Route::put('/cashadvance/{id}', [AdminController::class, 'updateCashAdvance'])->name('admin.cashadvance.update');
    Route::delete('/cashadvance/{id}', [AdminController::class, 'deleteCashAdvance'])->name('admin.cashadvance.delete');

    // Statutory (store only — GET is now handled by the combined cashadvance page)
    Route::post('/statutory', [AdminController::class, 'storeStatutory'])->name('admin.statutory.store');
});

// In routes/web.php (admin.php)
Route::post('/admin/archive/{id}/restore', [ArchiveController::class, 'restore'])->name('admin.archive.restore');
Route::delete('/admin/archive/{id}', [ArchiveController::class, 'destroy'])->name('admin.archive.destroy');


Route::get('/admin/holiday', [AdminController::class, 'holidays']);

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/holiday', [AdminController::class, 'indexHoliday'])->name('holiday.index');
    Route::post('/holiday', [AdminController::class, 'store'])->name('holiday.store');
    Route::put('/holiday/{id}', [AdminController::class, 'update'])->name('holiday.update');
    Route::delete('/holiday/{id}', [AdminController::class, 'destroy'])->name('holiday.destroy');

});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/rolemanagement', [RoleController::class, 'index'])->name('roles.index');
    Route::post('/rolemanagement', [RoleController::class, 'store'])->name('roles.store');
    Route::put('/rolemanagement/{id}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/rolemanagement/{id}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::post('/projects', [RoleController::class, 'storeProject'])->name('projects.store');
    Route::put('/projects/{id}', [RoleController::class, 'updateProject'])->name('projects.update');
    Route::delete('/projects/{id}', [RoleController::class, 'destroyProject'])->name('projects.destroy');
});

Route::get('/roles', function () {
    return \App\Models\Role::orderBy('role_name', 'asc')->get();
});

Route::get('/api/roles', function () {
    return \App\Models\Role::orderBy('role_name', 'asc')->get();
})->name('api.roles');

Route::get('/admin/payroll/export', [AdminController::class, 'exportPayroll'])
    ->name('admin.payroll_pdf');

Route::get('/admin/statutory', [AdminController::class, 'statutory'])->name('admin.statutory');
Route::post('/admin/statutory/store', [AdminController::class, 'storeStatutory'])->name('admin.statutory.store');