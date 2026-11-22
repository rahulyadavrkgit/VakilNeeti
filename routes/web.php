<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdvocateProfileController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Auth\UserRegistrationController;
use App\Http\Controllers\BarDirectoryController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CaseCalendarController;
use App\Http\Controllers\CaseClientController;
use App\Http\Controllers\CaseDashboardController;
use App\Http\Controllers\CaseFileImportController;
use App\Http\Controllers\CaseFinderController;
use App\Http\Controllers\CaseWorkspaceController;
use App\Http\Controllers\ClientActivityController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientManagementController;
use App\Http\Controllers\CourtCatalogueController;
use App\Http\Controllers\CourtDirectoryController;
use App\Http\Controllers\CourtIntegrationController;
use App\Http\Controllers\CourtLinksController;
use App\Http\Controllers\CriminalLawComparisonController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardStudioController;
use App\Http\Controllers\DistrictDirectoryController;
use App\Http\Controllers\ExternalWebviewController;
use App\Http\Controllers\FirmProfileController;
use App\Http\Controllers\HearingController;
use App\Http\Controllers\HearingHistoryController;
use App\Http\Controllers\InitialAdminSetupController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceSettingsController;
use App\Http\Controllers\LegalCaseController;
use App\Http\Controllers\ModuleDashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OfficialCourtImportController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PersonalProfileController;
use App\Http\Controllers\PracticeStateController;
use App\Http\Controllers\ProfessionalProfileController;
use App\Http\Controllers\ProfessionalTaxController;
use App\Http\Controllers\ProfileContactController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaxComplianceController;
use App\Http\Controllers\TdsDeductionController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

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

Route::post('/clients/bulk-delete', [ClientController::class, 'bulkDestroy'])->name('clients.bulk-destroy');
Route::resource('clients', ClientController::class);

Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])
    ->name('invoices.pdf');

Route::resource('invoices', InvoiceController::class);

Route::get('/cases/{case}/billables', [InvoiceController::class, 'billables'])
    ->whereNumber('case')
    ->name('cases.billables');

Route::resource('payments', PaymentController::class);

Route::resource('tasks', TaskController::class);

Route::get('/cases/find', [CaseFinderController::class, 'index'])->name('cases.find');
Route::post('/cases/find/preview', [CaseFinderController::class, 'preview'])->middleware('throttle:30,1')->name('cases.find.preview');
Route::post('/cases/find/import', [CaseFinderController::class, 'import'])->middleware('throttle:30,1')->name('cases.find.import');
Route::post('/cases/find/ecourts-app/preview', [CaseFileImportController::class, 'ecourtsPreview'])->middleware('throttle:20,1')->name('cases.find.ecourts-app.preview');
Route::post('/cases/find/ecourts-app/import', [CaseFileImportController::class, 'ecourtsImport'])->middleware('throttle:20,1')->name('cases.find.ecourts-app.import');
Route::post('/cases/find/calendar/preview', [CaseFileImportController::class, 'calendarPreview'])->middleware('throttle:20,1')->name('cases.find.calendar.preview');
Route::post('/cases/find/calendar/import', [CaseFileImportController::class, 'calendarImport'])->middleware('throttle:20,1')->name('cases.find.calendar.import');

Route::get('/hearings/create', [HearingController::class, 'create'])->name('hearings.create');
Route::post('/hearings', [HearingController::class, 'store'])->name('hearings.store');
Route::get('/hearings/{hearing}/edit', [HearingController::class, 'edit'])->whereNumber('hearing')->name('hearings.edit');
Route::put('/hearings/{hearing}', [HearingController::class, 'update'])->whereNumber('hearing')->name('hearings.update');
Route::delete('/hearings/{hearing}', [HearingController::class, 'destroy'])->whereNumber('hearing')->name('hearings.destroy');

Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])->whereUuid('notification')->name('notifications.open');
Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
Route::post('/home/profile-banner/hide', [DashboardController::class, 'hideProfileBanner'])->name('home.profile-banner.hide');

/*
|--------------------------------------------------------------------------
| Case Search
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
Route::delete('/cases/{case}/documents/{document}', [CaseWorkspaceController::class, 'destroyDocument'])
    ->whereNumber('case')
    ->whereNumber('document')
    ->name('cases.documents.destroy');
Route::post('/cases/{case}/clients', [CaseClientController::class, 'store'])
    ->whereNumber('case')
    ->name('cases.clients.store');
Route::delete('/cases/{case}/clients/{party}', [CaseClientController::class, 'destroy'])
    ->whereNumber('case')
    ->whereNumber('party')
    ->name('cases.clients.destroy');
Route::post('/cases/{case}/folders', [CaseWorkspaceController::class, 'storeFolder'])
    ->whereNumber('case')
    ->name('cases.folders.store');
Route::delete('/cases/{case}/folders/{folder}', [CaseWorkspaceController::class, 'destroyFolder'])
    ->whereNumber('case')
    ->whereNumber('folder')
    ->name('cases.folders.destroy');
Route::get('/cases/{case}/documents/{document}', [CaseWorkspaceController::class, 'downloadDocument'])
    ->whereNumber('case')
    ->whereNumber('document')
    ->name('cases.documents.download');
Route::get('/cases/{case}', [LegalCaseController::class, 'show'])
    ->whereNumber('case')
    ->name('cases.show');

/*
|--------------------------------------------------------------------------
| Case management
|--------------------------------------------------------------------------
*/

Route::get('/cases/create', [LegalCaseController::class, 'create'])
    ->name('cases.create');

Route::get('/cases/new', [LegalCaseController::class, 'createManual'])
    ->name('cases.manual');

Route::get('/cases/export', [LegalCaseController::class, 'export'])
    ->name('cases.export');

Route::post('/cases', [LegalCaseController::class, 'store'])
    ->name('cases.store');

Route::get('/cases/{case}/edit', [LegalCaseController::class, 'edit'])
    ->whereNumber('case')->name('cases.edit');

Route::put('/cases/{case}', [LegalCaseController::class, 'update'])
    ->whereNumber('case')->name('cases.update');

Route::delete('/cases/{case}', [LegalCaseController::class, 'destroy'])
    ->whereNumber('case')->name('cases.destroy');

Route::post('/cases/{case}/close', [LegalCaseController::class, 'close'])
    ->whereNumber('case')->name('cases.close');

Route::post('/cases/{case}/reopen', [LegalCaseController::class, 'reopen'])
    ->whereNumber('case')->name('cases.reopen');

Route::patch('/cases/{case}/file-number', [LegalCaseController::class, 'updateFileNumber'])
    ->whereNumber('case')->name('cases.file-number');

Route::post('/cases/{case}/tags', [LegalCaseController::class, 'attachTag'])
    ->whereNumber('case')->name('cases.tags.attach');

Route::delete('/cases/{case}/tags/{tag}', [LegalCaseController::class, 'detachTag'])
    ->whereNumber('case')->whereNumber('tag')->name('cases.tags.detach');

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
| Court Catalogue
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

// VAKILNITI COURT DIRECTORY API
Route::prefix('api/court-directory')
    ->name('api.court-directory.')
    ->group(function () {
        Route::get(
            '/states',
            [App\Http\Controllers\Api\CourtDirectoryController::class, 'states']
        )->name('states');

        Route::get(
            '/states/{stateId}/districts',
            [App\Http\Controllers\Api\CourtDirectoryController::class, 'districts']
        )->whereNumber('stateId')->name('districts');

        Route::get(
            '/districts/{districtId}/establishments',
            [App\Http\Controllers\Api\CourtDirectoryController::class, 'establishments']
        )->whereNumber('districtId')->name('establishments');
    });

// LOCATIONS API (India: States/UTs → districts → cities)
Route::get(
    '/api/locations/districts/{districtId}/cities',
    [LocationController::class, 'cities']
)->whereNumber('districtId')->name('api.locations.cities');

// VAKILNITI COURT DIRECTORY PAGE
Route::get('/court-directory/districts/{districtId}', [DistrictDirectoryController::class, 'show'])
    ->whereNumber('districtId')
    ->name('court-directory.districts.show');
Route::view(
    '/court-directory',
    'court-directory.index'
)->name('court-directory.index');

Route::get('/case-management/hearing-history', [HearingHistoryController::class, 'index'])->name('cases.hearing-history');

// Hearing History
Route::get('/hearing-history', [CaseCalendarController::class, 'history'])
    ->name('hearings.history');
// Combined Invoices and Payments
Route::get('/billing', [BillingController::class, 'index'])
    ->name('billing.index');
Route::get('/billing/overdues', [BillingController::class, 'overdues'])->name('billing.overdues');
Route::post('/invoices/{invoice}/write-off', [BillingController::class, 'writeOff'])->whereNumber('invoice')->name('invoices.write-off');
Route::post('/invoices/{invoice}/undo-write-off', [BillingController::class, 'undoWriteOff'])->whereNumber('invoice')->name('invoices.undo-write-off');
Route::get('/billing/time', [BillingController::class, 'timeSheet'])->name('billing.time');
Route::post('/billing/time', [BillingController::class, 'storeTimeEntry'])->name('billing.time.store');
Route::get('/billing/time/export', [BillingController::class, 'exportTime'])->name('billing.time.export');
Route::get('/billing/expenses', [BillingController::class, 'expenses'])->name('billing.expenses');
Route::post('/billing/expenses', [BillingController::class, 'storeExpense'])->name('billing.expenses.store');
Route::get('/billing/expenses/export', [BillingController::class, 'exportExpenses'])->name('billing.expenses.export');
Route::get('/billing/expenses/{expense}/receipt', [BillingController::class, 'downloadReceipt'])->whereNumber('expense')->name('billing.expenses.receipt');
Route::get('/billing/payments/export', [BillingController::class, 'exportPayments'])->name('billing.payments.export');
Route::get('/client-management', [ClientManagementController::class, 'index'])
    ->name('client-management.index');
Route::get('/case-dashboard', [CaseDashboardController::class, 'index'])
    ->name('case-dashboard.index');
Route::get('/profile', [ProfileController::class, 'index'])
    ->name('profile.index');
Route::post('/profile/preferences', [ProfileController::class, 'updatePreferences'])
    ->name('profile.preferences.update');
Route::get('/profile/advocate', [AdvocateProfileController::class, 'index'])
    ->name('profile.advocate.index');
Route::post('/profile/advocate', [AdvocateProfileController::class, 'update'])
    ->name('profile.advocate.update');
Route::get('/profile/practice-states', [PracticeStateController::class, 'index'])
    ->name('profile.practice-states.index');

Route::post('/profile/practice-states', [PracticeStateController::class, 'store'])
    ->name('profile.practice-states.store');
Route::get('/profile/personal', [PersonalProfileController::class, 'index'])
    ->name('profile.personal.index');

Route::post('/profile/personal', [PersonalProfileController::class, 'update'])
    ->name('profile.personal.update');
Route::get('/profile/professional', [ProfessionalProfileController::class, 'index'])
    ->name('profile.professional.index');

Route::post('/profile/professional', [ProfessionalProfileController::class, 'update'])
    ->name('profile.professional.update');
Route::get('/profile/contacts', [ProfileContactController::class, 'index'])
    ->name('profile.contacts.index');

Route::post('/profile/contacts', [ProfileContactController::class, 'store'])
    ->name('profile.contacts.store');

Route::delete('/profile/contacts/{id}', [ProfileContactController::class, 'destroy'])
    ->whereNumber('id')
    ->name('profile.contacts.destroy');
Route::get('/profile/invoice-settings', [InvoiceSettingsController::class, 'index'])
    ->name('profile.invoice-settings.index');

Route::post('/profile/invoice-settings', [InvoiceSettingsController::class, 'update'])
    ->name('profile.invoice-settings.update');
Route::get('/tds', [TdsDeductionController::class, 'index'])
    ->name('tds.index');

Route::get('/tds/create', [TdsDeductionController::class, 'create'])
    ->name('tds.create');

Route::post('/tds', [TdsDeductionController::class, 'store'])
    ->name('tds.store');

Route::get('/tds/follow-up-dashboard', [TdsDeductionController::class, 'followUpDashboard'])->name('tds.follow-up-dashboard');
Route::get('/tds/follow-ups/history', [TdsDeductionController::class, 'followUpHistory'])->name('tds.follow-ups.history');

Route::get('/tds/{tdsDeduction}', [TdsDeductionController::class, 'show'])
    ->name('tds.show');
Route::get('/professional-tax', [ProfessionalTaxController::class, 'index'])
    ->name('professional-tax.index');

Route::get('/professional-tax/create', [ProfessionalTaxController::class, 'create'])
    ->name('professional-tax.create');

Route::post('/professional-tax', [ProfessionalTaxController::class, 'store'])
    ->name('professional-tax.store');

Route::get('/professional-tax/{professionalTax}/edit', [ProfessionalTaxController::class, 'edit'])
    ->name('professional-tax.edit');

Route::put('/professional-tax/{professionalTax}', [ProfessionalTaxController::class, 'update'])
    ->name('professional-tax.update');

Route::get('/professional-tax/{professionalTax}', [ProfessionalTaxController::class, 'show'])
    ->name('professional-tax.show');
Route::get('/profile/tax-compliance', [TaxComplianceController::class, 'index'])
    ->name('profile.tax-compliance');
Route::put('/tds/{tdsDeduction}/reconciliation', [TdsDeductionController::class, 'updateReconciliation'])->name('tds.reconciliation.update');

Route::post('/tds/{tdsDeduction}/follow-ups', [TdsDeductionController::class, 'storeFollowUp'])->name('tds.follow-ups.store');

Route::get('/tds/follow-ups/due', [TdsDeductionController::class, 'dueFollowUps'])->name('tds.follow-ups.due');
Route::put('/tds/follow-ups/{tdsFollowUp}/complete', [TdsDeductionController::class, 'completeFollowUp'])->name('tds.follow-ups.complete');

Route::get('/tds/follow-ups/upcoming', [TdsDeductionController::class, 'upcomingFollowUps'])->name('tds.follow-ups.upcoming');

/*
|--------------------------------------------------------------------------
| VakilNiti Additional Modules
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
Route::get('/webview', [ExternalWebviewController::class, 'show'])
    ->name('webview.show');
Route::get('/court-management', function () {
    $countTable = function ($table) {
        return Schema::hasTable($table)
            ? DB::table($table)->count()
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
Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])
    ->name('admin.login');

Route::post('/admin/login', [AdminAuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('admin.login.submit');

Route::post('/admin/logout', [AdminAuthController::class, 'logout'])
    ->middleware(['auth', 'admin'])
    ->name('admin.logout');

// Temporary first-administrator setup.
// Controller restricts access to local environment and loopback requests.
Route::get('/admin/initial-setup', [InitialAdminSetupController::class, 'show'])
    ->name('admin.initial-setup');

Route::post('/admin/initial-setup', [InitialAdminSetupController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('admin.initial-setup.store');

// Bar Councils and Associations Directory

Route::get('/bar-directory', [BarDirectoryController::class, 'index'])->name('bar-directory.index');
// Judicial Architecture & Court Complexes Directory

Route::get('/court-directory', [CourtDirectoryController::class, 'index'])->name('court-directory.index');

// Search engines.
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');

// Courts: quick links to official court services (public).
Route::prefix('courts')->name('courts.')->controller(CourtLinksController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/supreme-court', 'supremeCourt')->name('supreme-court');
    Route::get('/delhi-high-court', 'delhiHighCourt')->name('delhi-high-court');
    Route::get('/delhi-district-courts', 'delhiDistrictCourts')->name('delhi-district-courts');
    Route::get('/display-boards', 'displayBoards')->name('display-boards');
    Route::get('/ecourts-services', 'ecourtsServices')->name('ecourts-services');
});
// Criminal Laws: old and new statutory section comparison.
Route::get(
    '/new-criminal-laws/compare/{pair}',
    [CriminalLawComparisonController::class, 'show']
)->where('pair', 'bns-ipc|bnss-crpc|bsa-iea')
    ->name('new-criminal-laws.compare');

/*
|--------------------------------------------------------------------------
| Dashboard Studio
|--------------------------------------------------------------------------
| Private, user-owned dashboards and data.gov.in data sources.
*/
Route::get('/dashboard-studio/public/{dashboard}', [DashboardStudioController::class, 'publicShow'])
    ->whereNumber('dashboard')
    ->name('dashboard-studio.public-show');

Route::middleware(['auth'])
    ->prefix('dashboard-studio')
    ->name('dashboard-studio.')
    ->group(function () {
        Route::get('/', [DashboardStudioController::class, 'index'])
            ->name('index');

        Route::post('/', [DashboardStudioController::class, 'store'])
            ->name('store');

        Route::get('/{dashboard}', [DashboardStudioController::class, 'show'])
            ->whereNumber('dashboard')
            ->name('show');

        Route::patch('/{dashboard}', [DashboardStudioController::class, 'update'])
            ->whereNumber('dashboard')
            ->name('update');

        Route::delete('/{dashboard}', [DashboardStudioController::class, 'destroy'])
            ->whereNumber('dashboard')
            ->name('destroy');

        Route::post('/{dashboard}/sources', [DashboardStudioController::class, 'storeSource'])
            ->whereNumber('dashboard')
            ->name('sources.store');

        Route::post('/{dashboard}/widgets', [DashboardStudioController::class, 'storeWidget'])
            ->whereNumber('dashboard')
            ->name('widgets.store');

        Route::patch('/{dashboard}/widgets/{widget}', [DashboardStudioController::class, 'updateWidget'])
            ->whereNumber('dashboard')
            ->whereNumber('widget')
            ->name('widgets.update');

        Route::delete('/{dashboard}/widgets/{widget}', [DashboardStudioController::class, 'destroyWidget'])
            ->whereNumber('dashboard')
            ->whereNumber('widget')
            ->name('widgets.destroy');

        Route::post('/{dashboard}/sources/{source}/test', [DashboardStudioController::class, 'testSource'])
            ->whereNumber('dashboard')
            ->whereNumber('source')
            ->name('sources.test');

        Route::delete('/{dashboard}/sources/{source}', [DashboardStudioController::class, 'destroySource'])
            ->whereNumber('dashboard')
            ->whereNumber('source')
            ->name('sources.destroy');
    });

// Ordinary user registration and authentication
Route::middleware('guest')->group(function () {
    Route::get('/register', [UserRegistrationController::class, 'showRegister'])->name('register');
    Route::post('/register/send-otp', [UserRegistrationController::class, 'sendOtp'])->middleware('throttle:5,10')->name('register.send-otp');
    Route::get('/register/verify', [UserRegistrationController::class, 'showVerify'])->name('register.verify');
    Route::post('/register/verify', [UserRegistrationController::class, 'verifyOtp'])->middleware('throttle:10,10')->name('register.verify.submit');
    Route::post('/register/resend-otp', [UserRegistrationController::class, 'resendOtp'])->middleware('throttle:5,10')->name('register.resend-otp');
    Route::get('/register/profile', [UserRegistrationController::class, 'showProfileSetup'])->name('register.profile');
    Route::post('/register/profile', [UserRegistrationController::class, 'completeProfile'])->middleware('throttle:10,10')->name('register.profile.submit');
    Route::get('/login', [UserRegistrationController::class, 'showLogin'])->name('login');
    Route::post('/login', [UserRegistrationController::class, 'sendLoginOtp'])->middleware('throttle:5,10')->name('login.submit');
    Route::post('/login/verify', [UserRegistrationController::class, 'verifyLoginOtp'])->middleware('throttle:10,10')->name('login.verify.submit');
});

Route::post('/user-logout', [UserRegistrationController::class, 'logout'])
    ->middleware('auth')
    ->name('user.logout');
