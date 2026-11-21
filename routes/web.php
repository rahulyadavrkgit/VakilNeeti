<?php

use App\Http\Controllers\OfficialCourtImportController;
use App\Http\Controllers\CourtIntegrationController;
use App\Http\Controllers\FirmProfileController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientActivityController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\LegalCaseController;
use App\Http\Controllers\CaseWorkspaceController;
use App\Http\Controllers\CaseCalendarController;
use App\Http\Controllers\CourtCatalogueController;
use App\Http\Controllers\ModuleDashboardController;

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/', [DashboardController::class, 'index'])
    ->name('dashboard');

Route::get('/court-integrations/ecourts', [CourtIntegrationController::class, 'index'])->name('court-integrations.ecourts');
Route::get('/court-integrations/ecourts/search', [CourtIntegrationController::class, 'search'])->name('court-integrations.ecourts.search');


/*
|--------------------------------------------------------------------------
| Internal Practice Management
|--------------------------------------------------------------------------
*/

Route::resource('clients', ClientController::class);

Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])
    ->name('invoices.pdf');

Route::resource('invoices', InvoiceController::class);

Route::resource('payments', PaymentController::class);

Route::resource('tasks', TaskController::class);


/*
|--------------------------------------------------------------------------
| Case Search ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¦ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â Read Only
|--------------------------------------------------------------------------
*/

Route::get('/case-calendar', [CaseCalendarController::class, 'index'])->name('cases.calendar');

Route::get('/cases', [LegalCaseController::class, 'index'])
    ->name('cases.index');

Route::get('/cases/import', function () {
    return view('cases.import');
})->name('cases.import');

Route::post('/cases/import/preview', [OfficialCourtImportController::class, 'preview'])
    ->name('cases.import.preview');

Route::get('/cases/import/history', [OfficialCourtImportController::class, 'history'])
    ->name('cases.import.history');

Route::post('/cases/import/{import}/save', [OfficialCourtImportController::class, 'store'])
    ->name('cases.import.save');
Route::post('/cases/{case}/notes', [CaseWorkspaceController::class, 'storeNote'])
    ->whereNumber('case')
    ->name('cases.notes.store');
Route::post('/cases/{case}/appointments', [CaseWorkspaceController::class, 'storeAppointment'])
    ->whereNumber('case')
    ->name('cases.appointments.store');
Route::post('/cases/{case}/expenses', [CaseWorkspaceController::class, 'storeExpense'])
    ->whereNumber('case')
    ->name('cases.expenses.store');
Route::post('/cases/{case}/time-entries', [CaseWorkspaceController::class, 'storeTimeEntry'])
    ->whereNumber('case')
    ->name('cases.time-entries.store');
Route::post('/cases/{case}/documents', [CaseWorkspaceController::class, 'storeDocument'])
    ->whereNumber('case')
    ->name('cases.documents.store');
Route::get('/cases/{case}/documents/{document}', [CaseWorkspaceController::class, 'downloadDocument'])
    ->whereNumber('case')
    ->whereNumber('document')
    ->name('cases.documents.download');
Route::get('/cases/{case}', [LegalCaseController::class, 'show'])
    ->whereNumber('case')
    ->name('cases.show');


/*
|--------------------------------------------------------------------------
| Legacy Case URLs ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¦ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â Safe Redirects Only
|--------------------------------------------------------------------------
|
| These routes protect old bookmarks and links.
| They do not allow case creation or editing.
|
*/

Route::get('/cases/create', [LegalCaseController::class, 'create'])
    ->name('cases.create');

Route::post('/cases', [LegalCaseController::class, 'store'])
    ->name('cases.store');

Route::get('/cases/{case}/edit', [LegalCaseController::class, 'edit'])
    ->whereNumber('case')->name('cases.edit');


/*
|--------------------------------------------------------------------------
| Client Activities
|--------------------------------------------------------------------------
*/

Route::resource('clients.activities', ClientActivityController::class)
    ->only([
        'index',
        'create',
        'store',
    ]);


/*
|--------------------------------------------------------------------------
| Court Catalogue ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¦ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â Selection First
|--------------------------------------------------------------------------
*/

Route::get(
    '/court-catalogue',
    [CourtCatalogueController::class, 'index']
)->name('court-catalogue.index');


/*
|--------------------------------------------------------------------------
| Court Catalogue Data Endpoints
|--------------------------------------------------------------------------
*/

Route::prefix('court-catalogue/data')
    ->name('court-catalogue.data.')
    ->controller(CourtCatalogueController::class)
    ->group(function () {

        Route::get('/courts', 'courts')
            ->name('courts');

        Route::get('/districts', 'districts')
            ->name('districts');

        Route::get('/establishments', 'establishments')
            ->name('establishments');

    });


Route::get('/firm-profile', [FirmProfileController::class, 'index'])
    ->name('firm-profile.index');

Route::post('/firm-profile', [FirmProfileController::class, 'update'])
    ->name('firm-profile.update');




// VAKILNEETI COURT DIRECTORY API
\Illuminate\Support\Facades\Route::prefix('api/court-directory')
    ->name('api.court-directory.')
    ->group(function () {
        \Illuminate\Support\Facades\Route::get(
            '/states',
            [\App\Http\Controllers\Api\CourtDirectoryController::class, 'states']
        )->name('states');

        \Illuminate\Support\Facades\Route::get(
            '/states/{stateId}/districts',
            [\App\Http\Controllers\Api\CourtDirectoryController::class, 'districts']
        )->whereNumber('stateId')->name('districts');

        \Illuminate\Support\Facades\Route::get(
            '/districts/{districtId}/establishments',
            [\App\Http\Controllers\Api\CourtDirectoryController::class, 'establishments']
        )->whereNumber('districtId')->name('establishments');
    });


// VAKILNEETI COURT DIRECTORY PAGE
\Illuminate\Support\Facades\Route::get('/court-directory/districts/{districtId}', [\App\Http\Controllers\DistrictDirectoryController::class, 'show'])
    ->whereNumber('districtId')
    ->name('court-directory.districts.show');
Route::view(
    '/court-directory',
    'court-directory.index'
)->name('court-directory.index');


Route::get('/case-management/hearing-history', [\App\Http\Controllers\HearingHistoryController::class, 'index'])->name('cases.hearing-history');

// Hearing History
Route::get('/hearing-history', [\App\Http\Controllers\CaseCalendarController::class, 'history'])
    ->name('hearings.history');
// Combined Invoices and Payments
Route::get('/billing', [\App\Http\Controllers\BillingController::class, 'index'])
    ->name('billing.index');
Route::get('/client-management', [\App\Http\Controllers\ClientManagementController::class, 'index'])
    ->name('client-management.index');
Route::get('/case-dashboard', [\App\Http\Controllers\CaseDashboardController::class, 'index'])
    ->name('case-dashboard.index');
Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'index'])
    ->name('profile.index');
Route::post('/profile/preferences', [\App\Http\Controllers\ProfileController::class, 'updatePreferences'])
    ->name('profile.preferences.update');
Route::get('/profile/advocate', [\App\Http\Controllers\AdvocateProfileController::class, 'index'])
    ->name('profile.advocate.index');
Route::post('/profile/advocate', [\App\Http\Controllers\AdvocateProfileController::class, 'update'])
    ->name('profile.advocate.update');
Route::get('/profile/practice-states', [\App\Http\Controllers\PracticeStateController::class, 'index'])
    ->name('profile.practice-states.index');

Route::post('/profile/practice-states', [\App\Http\Controllers\PracticeStateController::class, 'store'])
    ->name('profile.practice-states.store');
Route::get('/profile/personal', [\App\Http\Controllers\PersonalProfileController::class, 'index'])
    ->name('profile.personal.index');

Route::post('/profile/personal', [\App\Http\Controllers\PersonalProfileController::class, 'update'])
    ->name('profile.personal.update');
Route::get('/profile/professional', [\App\Http\Controllers\ProfessionalProfileController::class, 'index'])
    ->name('profile.professional.index');

Route::post('/profile/professional', [\App\Http\Controllers\ProfessionalProfileController::class, 'update'])
    ->name('profile.professional.update');
Route::get('/profile/contacts', [\App\Http\Controllers\ProfileContactController::class, 'index'])
    ->name('profile.contacts.index');

Route::post('/profile/contacts', [\App\Http\Controllers\ProfileContactController::class, 'store'])
    ->name('profile.contacts.store');

Route::delete('/profile/contacts/{id}', [\App\Http\Controllers\ProfileContactController::class, 'destroy'])
    ->whereNumber('id')
    ->name('profile.contacts.destroy');
Route::get('/profile/invoice-settings', [\App\Http\Controllers\InvoiceSettingsController::class, 'index'])
    ->name('profile.invoice-settings.index');

Route::post('/profile/invoice-settings', [\App\Http\Controllers\InvoiceSettingsController::class, 'update'])
    ->name('profile.invoice-settings.update');
Route::get('/tds', [\App\Http\Controllers\TdsDeductionController::class, 'index'])
    ->name('tds.index');

Route::get('/tds/create', [\App\Http\Controllers\TdsDeductionController::class, 'create'])
    ->name('tds.create');

Route::post('/tds', [\App\Http\Controllers\TdsDeductionController::class, 'store'])
    ->name('tds.store');

Route::get('/tds/follow-up-dashboard', [\App\Http\Controllers\TdsDeductionController::class, 'followUpDashboard'])->name('tds.follow-up-dashboard');
Route::get('/tds/follow-ups/history', [\App\Http\Controllers\TdsDeductionController::class, 'followUpHistory'])->name('tds.follow-ups.history');

Route::get('/tds/{tdsDeduction}', [\App\Http\Controllers\TdsDeductionController::class, 'show'])
    ->name('tds.show');
Route::get('/professional-tax', [\App\Http\Controllers\ProfessionalTaxController::class, 'index'])
    ->name('professional-tax.index');

Route::get('/professional-tax/create', [\App\Http\Controllers\ProfessionalTaxController::class, 'create'])
    ->name('professional-tax.create');

Route::post('/professional-tax', [\App\Http\Controllers\ProfessionalTaxController::class, 'store'])
    ->name('professional-tax.store');

Route::get('/professional-tax/{professionalTax}/edit', [\App\Http\Controllers\ProfessionalTaxController::class, 'edit'])
    ->name('professional-tax.edit');

Route::put('/professional-tax/{professionalTax}', [\App\Http\Controllers\ProfessionalTaxController::class, 'update'])
    ->name('professional-tax.update');

Route::get('/professional-tax/{professionalTax}', [\App\Http\Controllers\ProfessionalTaxController::class, 'show'])
    ->name('professional-tax.show');
Route::get('/profile/tax-compliance', [\App\Http\Controllers\TaxComplianceController::class, 'index'])
    ->name('profile.tax-compliance');
Route::put('/tds/{tdsDeduction}/reconciliation', [\App\Http\Controllers\TdsDeductionController::class, 'updateReconciliation'])->name('tds.reconciliation.update');


Route::post('/tds/{tdsDeduction}/follow-ups', [\App\Http\Controllers\TdsDeductionController::class, 'storeFollowUp'])->name('tds.follow-ups.store');


Route::get('/tds/follow-ups/due', [\App\Http\Controllers\TdsDeductionController::class, 'dueFollowUps'])->name('tds.follow-ups.due');
Route::put('/tds/follow-ups/{tdsFollowUp}/complete', [\App\Http\Controllers\TdsDeductionController::class, 'completeFollowUp'])->name('tds.follow-ups.complete');

Route::get('/tds/follow-ups/upcoming', [\App\Http\Controllers\TdsDeductionController::class, 'upcomingFollowUps'])->name('tds.follow-ups.upcoming');





/*
|--------------------------------------------------------------------------
| VakilNeeti Additional Modules
|--------------------------------------------------------------------------
*/

Route::get('/police-administration', [ModuleDashboardController::class, 'policeAdministration'])
    ->name('police-administration.index');
Route::get('/police-administration/stations/{stationId}', [ModuleDashboardController::class, 'policeStationDetails'])
    ->whereNumber('stationId')
    ->name('police-administration.stations.show');

Route::get('/bar-associations', [ModuleDashboardController::class, 'barAssociations'])
    ->name('bar-associations.index');

Route::get('/court-fee', [ModuleDashboardController::class, 'courtFee'])
    ->name('court-fee.index');

Route::get('/bare-acts', [ModuleDashboardController::class, 'bareActs'])
    ->name('bare-acts.index');

Route::get('/new-criminal-laws', [ModuleDashboardController::class, 'newCriminalLaws'])
    ->name('new-criminal-laws.index');

Route::get('/miscellaneous-forms', [ModuleDashboardController::class, 'miscellaneousForms'])
    ->name('miscellaneous-forms.index');

Route::get('/pdf-annotator', [ModuleDashboardController::class, 'pdfAnnotator'])
    ->name('pdf-annotator.index');
Route::get('/webview', [\App\Http\Controllers\ExternalWebviewController::class, 'show'])
    ->name('webview.show');
\Illuminate\Support\Facades\Route::get('/court-management', function () {
    $countTable = function ($table) {
        return \Illuminate\Support\Facades\Schema::hasTable($table)
            ? \Illuminate\Support\Facades\DB::table($table)->count()
            : 0;
    };

    return view('court-management.index', [
        'courtCount' => $countTable('courts'),
        'districtCount' => $countTable('judicial_districts'),
        'establishmentCount' => $countTable('court_establishments'),
        'stateCount' => $countTable('states'),
    ]);
})->name('court-management.index');

// Administrator authentication
Route::get('/admin/login', [\App\Http\Controllers\AdminAuthController::class, 'showLogin'])
    ->name('admin.login');

Route::post('/admin/login', [\App\Http\Controllers\AdminAuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('admin.login.submit');

Route::post('/admin/logout', [\App\Http\Controllers\AdminAuthController::class, 'logout'])
    ->middleware(['auth', 'admin'])
    ->name('admin.logout');

// Temporary first-administrator setup.
// Controller restricts access to local environment and loopback requests.
Route::get('/admin/initial-setup', [\App\Http\Controllers\InitialAdminSetupController::class, 'show'])
    ->name('admin.initial-setup');

Route::post('/admin/initial-setup', [\App\Http\Controllers\InitialAdminSetupController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('admin.initial-setup.store');

// Bar Councils and Associations Directory
use App\Http\Controllers\BarDirectoryController;
Route::get('/bar-directory', [BarDirectoryController::class, 'index'])->name('bar-directory.index');
// Judicial Architecture & Court Complexes Directory
use App\Http\Controllers\CourtDirectoryController;
Route::get('/court-directory', [CourtDirectoryController::class, 'index'])->name('court-directory.index');
// Criminal Laws: old and new statutory section comparison.
Route::get(
    '/new-criminal-laws/compare/{pair}',
    [\App\Http\Controllers\CriminalLawComparisonController::class, 'show']
)->where('pair', 'bns-ipc|bnss-crpc|bsa-iea')
 ->name('new-criminal-laws.compare');

/*
|--------------------------------------------------------------------------
| Dashboard Studio
|--------------------------------------------------------------------------
| Private, user-owned dashboards and data.gov.in data sources.
*/
Route::get('/dashboard-studio/public/{dashboard}', [\App\Http\Controllers\DashboardStudioController::class, 'publicShow'])
    ->whereNumber('dashboard')
    ->name('dashboard-studio.public-show');

Route::middleware(['auth'])
    ->prefix('dashboard-studio')
    ->name('dashboard-studio.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\DashboardStudioController::class, 'index'])
            ->name('index');

        Route::post('/', [\App\Http\Controllers\DashboardStudioController::class, 'store'])
            ->name('store');

        Route::get('/{dashboard}', [\App\Http\Controllers\DashboardStudioController::class, 'show'])
            ->whereNumber('dashboard')
            ->name('show');

        Route::patch('/{dashboard}', [\App\Http\Controllers\DashboardStudioController::class, 'update'])
            ->whereNumber('dashboard')
            ->name('update');

        Route::delete('/{dashboard}', [\App\Http\Controllers\DashboardStudioController::class, 'destroy'])
            ->whereNumber('dashboard')
            ->name('destroy');

        Route::post('/{dashboard}/sources', [\App\Http\Controllers\DashboardStudioController::class, 'storeSource'])
            ->whereNumber('dashboard')
            ->name('sources.store');

        Route::post('/{dashboard}/widgets', [\App\Http\Controllers\DashboardStudioController::class, 'storeWidget'])
            ->whereNumber('dashboard')
            ->name('widgets.store');

        Route::patch('/{dashboard}/widgets/{widget}', [\App\Http\Controllers\DashboardStudioController::class, 'updateWidget'])
            ->whereNumber('dashboard')
            ->whereNumber('widget')
            ->name('widgets.update');

        Route::delete('/{dashboard}/widgets/{widget}', [\App\Http\Controllers\DashboardStudioController::class, 'destroyWidget'])
            ->whereNumber('dashboard')
            ->whereNumber('widget')
            ->name('widgets.destroy');

        Route::post('/{dashboard}/sources/{source}/test', [\App\Http\Controllers\DashboardStudioController::class, 'testSource'])
            ->whereNumber('dashboard')
            ->whereNumber('source')
            ->name('sources.test');

        Route::delete('/{dashboard}/sources/{source}', [\App\Http\Controllers\DashboardStudioController::class, 'destroySource'])
            ->whereNumber('dashboard')
            ->whereNumber('source')
            ->name('sources.destroy');
    });


// Ordinary user registration and authentication
Route::middleware('guest')->group(function () {
    Route::get('/register', [\App\Http\Controllers\Auth\UserRegistrationController::class, 'showRegister'])->name('register');
    Route::post('/register/send-otp', [\App\Http\Controllers\Auth\UserRegistrationController::class, 'sendOtp'])->middleware('throttle:5,10')->name('register.send-otp');
    Route::get('/register/verify', [\App\Http\Controllers\Auth\UserRegistrationController::class, 'showVerify'])->name('register.verify');
    Route::post('/register/verify', [\App\Http\Controllers\Auth\UserRegistrationController::class, 'verifyOtp'])->middleware('throttle:10,10')->name('register.verify.submit');
    Route::post('/register/resend-otp', [\App\Http\Controllers\Auth\UserRegistrationController::class, 'resendOtp'])->middleware('throttle:5,10')->name('register.resend-otp');
    Route::get('/register/profile', [\App\Http\Controllers\Auth\UserRegistrationController::class, 'showProfileSetup'])->name('register.profile');
    Route::post('/register/profile', [\App\Http\Controllers\Auth\UserRegistrationController::class, 'completeProfile'])->middleware('throttle:10,10')->name('register.profile.submit');
    Route::get('/login', [\App\Http\Controllers\Auth\UserRegistrationController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\UserRegistrationController::class, 'sendLoginOtp'])->middleware('throttle:5,10')->name('login.submit');
    Route::post('/login/verify', [\App\Http\Controllers\Auth\UserRegistrationController::class, 'verifyLoginOtp'])->middleware('throttle:10,10')->name('login.verify.submit');
});

Route::post('/user-logout', [\App\Http\Controllers\Auth\UserRegistrationController::class, 'logout'])
    ->middleware('auth')
    ->name('user.logout');
