<?php

use App\Http\Controllers\SuperFinanceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FinanceController;

Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');

Route::get('/finance/users', [FinanceController::class, 'users'])->name('finance.users');
Route::post('/finance/users', [FinanceController::class, 'storeNewUser'])->name('finance.store');

Route::get('/finance/payroll/employees', [FinanceController::class, 'employees'])->name('finance.payroll.employees');
Route::get('/finance/payroll/', [FinanceController::class, 'showPayroll'])->name('finance.payroll');
Route::get('/finance/payslip', [FinanceController::class, 'payslip'])->name('finance.payslip');

Route::prefix('finance')->group(function () {
    Route::get('/cashadvance', [FinanceController::class, 'cashadvance'])->name('finance.cashadvance');
    Route::post('/cashadvance', [FinanceController::class, 'storeCashAdvance'])->name('finance.cashadvance.store');
    Route::get('/cashadvance/{id}/modal', [FinanceController::class, 'getCashAdvanceModal']);
    Route::put('/cashadvance/{id}', [FinanceController::class, 'updateCashAdvance'])->name('finance.cashadvance.update');
    Route::delete('/cashadvance/{id}', [FinanceController::class, 'deleteCashAdvance'])->name('finance.cashadvance.delete');

    // Statutory (store only — GET is now handled by the combined cashadvance page)
    Route::post('/statutory', [FinanceController::class, 'storeStatutory'])->name('finance.statutory.store');
});


Route::get('/finance/attendance', [FinanceController::class, 'attendance'])->name('finance.attendance');
Route::post('/finance/attendance', [FinanceController::class, 'storeAttendance'])->name('finance.attendance.store');
Route::post('/finance/attendance/nsd', [FinanceController::class, 'storeNsd'])->name('finance.attendance.storeNsd');

Route::get('/finance/employees', [FinanceController::class, 'employed'])->name('finance.employees');
Route::post('/finance/employees', [FinanceController::class, 'storeNewEmployee'])->name('finance.employees.store');
Route::get('/finance/employees/{id}/modal',  [FinanceController::class, 'getEmployeeModal'])->name('finance.employees.modal');
Route::put('/finance/employees/{id}',         [FinanceController::class, 'updateEmployee'])->name('finance.employees.update');
Route::post('/finance/employees/{id}/archive', [FinanceController::class, 'deleteEmployee']);

Route::prefix('finance')->name('finance.')->group(function () {
    Route::get('/holiday',         [FinanceController::class, 'indexHoliday'])   ->name('holiday.index');
    Route::post('/holiday',        [FinanceController::class, 'store'])   ->name('holiday.store');
    Route::put('/holiday/{id}',    [FinanceController::class, 'update'])  ->name('holiday.update');
    Route::delete('/holiday/{id}', [FinanceController::class, 'destroy']) ->name('holiday.destroy');

});


//newly added

Route::get('/finance/payroll/export', [FinanceController::class, 'exportPayroll'])
    ->name('finance.payroll_pdf');

Route::get('/finance/payslip/export', [FinanceController::class, 'exportPayslip'])
->name('finance.payslip.export');


Route::get('/finance/statutory', [FinanceController::class, 'statutory'])->name('finance.statutory');
Route::post('/finance/statutory/store', [FinanceController::class, 'storeStatutory'])->name('finance.statutory.store');