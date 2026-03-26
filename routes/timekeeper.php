<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterUserController;
use App\Http\Controllers\CashAdvanceController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\ModalController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;
use App\http\Controllers\TimekeeperController;
use App\Http\Controllers\AttendanceController;
use App\http\Controllers\EmployeeController;
use App\http\Controllers\AuthController;
use App\http\Controllers\DashboardController;
use App\http\Controllers\UserController;

// Live search for dashboard employees

/* =========================
   TIMEKEEPER DASHBOARD
========================= */


Route::get('/timekeeper/index',
    [TimekeeperController::class, 'index'])
    ->name('timekeeper.index');

Route::get('/register', [RegisterUserController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterUserController::class, 'register'])->name('register.submit');

/* =========================
   USERS
========================= */

Route::get('/timekeeper/users',
    [TimekeeperController::class, 'users'])
    ->name('timekeeper.users');

Route::get('/timekeeper/users/search',
    [FilterController::class, 'search'])
    ->name('timekeeper.users.search');

Route::post('/timekeeper/users',
    [RegisterController::class, 'store'])
    ->name('register.store');


/* =========================
   EMPLOYEES
========================= */

Route::get('/timekeeper/employees',[EmployeeController::class, 'index'])
    ->name('timekeeper.employees');

Route::get('/timekeeper/index/search',[TimekeeperController::class, 'search'])
    ->name('timekeeper.index.search');

Route::get('/timekeeper/employees/search',[TimekeeperController::class, 'searchEmployees'])
    ->name('timekeeper.employees.search');

Route::get('/timekeeper/employees/{employee}/modal',[EmployeeController::class, 'showModal'])
    ->name('employees.modal');

Route::put('/timekeeper/employees/{employee}',[EmployeeController::class, 'update'])
    ->name('timekeeper.employees.update');

Route::delete('/timekeeper/employees/{employee}',[EmployeeController::class, 'destroy'])
    ->name('timekeeper.employees.destroy');

Route::get('/timekeeper/employees', [TimekeeperController::class, 'employees'])->name('timekeeper.employees');
Route::post('/timekeeper/employees', [TimekeeperController::class, 'storeEmployee'])->name('timekeeper.employees.store');

Route::patch('/timekeeper/employees/{id}/status', [TimekeeperController::class, 'updateStatus'])
    ->name('timekeeper.employees.updateStatus');

Route::post('/timekeeper/employees/{id}/archive', [TimekeeperController::class, 'archiveEmployee'])
    ->name('timekeeper.employees.archive');
    
/* =========================
   ATTENDANCE
========================= */

Route::get('/timekeeper/attendance', [TimekeeperController::class, 'attendance'])->name('timekeeper.attendance');
Route::post('/timekeeper/attendance', [TimekeeperController::class, 'storeAttendance'])->name('timekeeper.attendance.store');
Route::post('/timekeeper/attendance/store-nsd', [TimekeeperController::class, 'storeNsd'])->name('timekeeper.attendance.storeNsd');



/* =========================
   CASH ADVANCE
========================= */
Route::prefix('timekeeper')->group(function () {
    Route::get('/cashadvance/{id}/modal', [CashAdvanceController::class, 'getCashAdvanceModal']);
    Route::put('/cashadvance/{id}', [CashAdvanceController::class, 'updateCashAdvance'])->name('timekeeper.cashadvance.update');
    Route::delete('/cashadvance/{id}', [CashAdvanceController::class, 'deleteCashAdvance'])->name('timekeeper.cashadvance.delete');
});

/* =========================
   MODAL REGISTER
========================= */

Route::post('/modal/store', [ModalController::class, 'store']) ->name('modal.store');



//cash advance routes

Route::prefix('timekeeper')->group(function () {
    Route::get('/cashadvance', [CashAdvanceController::class, 'index'])->name('timekeeper.cashadvance');
    Route::post('/cashadvance', [CashAdvanceController::class, 'storeCashAdvance'])->name('timekeeper.cashadvance.store');
    Route::get('/cashadvance/{id}/modal', [CashAdvanceController::class, 'getCashAdvanceModal']);
    Route::put('/cashadvance/{id}', [CashAdvanceController::class, 'updateCashAdvance'])->name('timekeeper.cashadvance.update');
    Route::delete('/cashadvance/{id}', [CashAdvanceController::class, 'deleteCashAdvance'])->name('timekeeper.cashadvance.delete');
}); 
//Payroll master index

Route::get('/payrollmaster.index', function() {return view('payrollmaster.index');})
    ->name('payrollmaster.index');


// Optional: Redirect /timekeeper to index
Route::get('/timekeeper', function () {
    return redirect()->route('timekeeper.index');

});
//Route::get('/timekeeper/cashadvance', [TimekeeperController::class, 'project'])->name('timekeeper.cashadvance');


Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/timekeeper/holiday', [TimekeeperController::class, 'holidays']);

Route::prefix('timekeeper')->name('timekeeper.')->group(function () {
    Route::get('/holiday',         [TimekeeperController::class, 'indexHoliday'])   ->name('holiday.index');
    Route::post('/holiday',        [TimekeeperController::class, 'store'])   ->name('holiday.store');
    Route::put('/holiday/{id}',    [TimekeeperController::class, 'update'])  ->name('holiday.update');
    Route::delete('/holiday/{id}', [TimekeeperController::class, 'destroy']) ->name('holiday.destroy');

});

///////////////////////////////////////////////////////
///////////PAYROLL MASTER ROUTINGS/////////////////////
///////////////////////////////////////////////////////
// Add these routes to your routes/web.php file

// ==========================================
// AUTH ROUTES (No Auth Middleware)
// ==========================================