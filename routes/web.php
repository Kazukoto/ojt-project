<?php

    use App\Http\Controllers\AddingController;
    use App\Http\Controllers\Auth\LoginController;
    use App\Http\Controllers\Auth\RegisterUserController;
    use App\Http\Controllers\AuthController;
    use App\Http\Controllers\CashAdvanceController;
    use App\Http\Controllers\EmployeeController;
    use App\Http\Controllers\PayrollController;
    use App\Http\Controllers\PayrollMasterController;
    use App\Http\Controllers\SuperAdminController;
    use App\Http\Controllers\AdvancedDiagnosticController;
    use App\Http\Controllers\ModalController;
    use App\Http\Controllers\RegisterController;
    use App\Http\Controllers\RateApprovalController;
    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\ArchiveController;


Route::get('/', function () {
    return view('login');
});
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);

Route::get('/register', [RegisterUserController::class, 'show'])->name('register');
Route::post('/register', [RegisterUserController::class, 'register']);

// Admin rate approval routes
Route::get('/admin/rateapproval', [RateApprovalController::class, 'index'])->name('admin.rateapproval');
Route::put('/admin/rateapproval/{employee}', [RateApprovalController::class, 'update'])->name('admin.rateapproval.update');



Route::get('/admin/archive', [ArchiveController::class, 'index'])->name('admin.archive');