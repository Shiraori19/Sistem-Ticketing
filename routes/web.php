<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BidangManagementController;
use App\Http\Controllers\Admin\BidangTicketController;
use App\Http\Controllers\Admin\CategoryManagementController;
use App\Http\Controllers\Admin\EscalationController;
use App\Http\Controllers\Admin\LaporanKinerjaController;
use App\Http\Controllers\Admin\OpdManagementController;
use App\Http\Controllers\Admin\SlaMonitoringController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\PublicTicketController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (Portal ASN Kota Cirebon — Tanpa Login)
|--------------------------------------------------------------------------
*/

Route::get('/', [PublicTicketController::class, 'index'])
    ->middleware('throttle:30,1')
    ->name('public.index');
Route::post('/tiket', [PublicTicketController::class, 'store'])
    ->middleware('throttle:5,1') // Rate limit 5 submission/menit per IP
    ->name('public.ticket.store');

Route::get('/tiket/lacak', [PublicTicketController::class, 'track'])
    ->middleware('throttle:30,1')
    ->name('public.track');
Route::redirect('/lacak', '/tiket/lacak');
Route::post('/tiket/{kode}/verify-pin', [PublicTicketController::class, 'verifyPin'])->name('public.ticket.verify_pin');
Route::post('/tiket/{kode}/konfirmasi', [PublicTicketController::class, 'confirmClose'])->name('public.ticket.confirm');
Route::post('/tiket/{kode}/reopen', [PublicTicketController::class, 'reopen'])->name('public.ticket.reopen');
Route::post('/tiket/{kode}/lengkapi-data', [PublicTicketController::class, 'submitCompletion'])->name('public.ticket.submit_completion');
Route::post('/tiket/{kode}/batal', [PublicTicketController::class, 'cancelTicket'])->name('public.ticket.cancel');
Route::get('/tiket/{kode}/berkas/{type}', [\App\Http\Controllers\TicketFileController::class, 'downloadPublic'])->name('public.tiket.download');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::redirect('/admin/login', '/login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Admin Management Routes (Auth Required)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->middleware(['auth'])->group(function () {
    // Dashboard umum untuk seluruh admin yang login
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/monitoring-sla', [SlaMonitoringController::class, 'index'])->name('admin.sla.index');
    Route::get('/tiket/{kode}/berkas/{type}', [\App\Http\Controllers\TicketFileController::class, 'downloadAdmin'])->name('admin.tiket.download');

    /*
    |--------------------------------------------------------------------------
    | Khusus Admin Pusat (admin.pusat)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['admin.pusat'])->group(function () {
        // Verifikasi & Disposisi Tiket
        Route::get('/verifikasi', [VerificationController::class, 'index'])->name('admin.verifikasi.index');
        Route::post('/verifikasi/batch-link', [VerificationController::class, 'batchLink'])->name('admin.verifikasi.batchLink');
        Route::post('/tiket/{id}/verifikasi', [VerificationController::class, 'verify'])->name('admin.verifikasi.verify');
        Route::post('/tiket/{id}/minta-kelengkapan', [VerificationController::class, 'requestCompletion'])->name('admin.verifikasi.requestCompletion');

        // Manajemen Eskalasi Pusat & Force Close
        Route::get('/eskalasi', [EscalationController::class, 'index'])->name('admin.eskalasi.index');
        Route::post('/tiket/{id}/force-close', [EscalationController::class, 'forceClose'])->name('admin.eskalasi.forceClose');

        // Semua Tiket + Multi-Filter (Status, Bidang, TLP, SLA Breach)
        Route::get('/tiket', [TicketController::class, 'index'])->name('admin.tiket.index');
        Route::get('/tiket/{id}', [TicketController::class, 'show'])->name('admin.tiket.show');

        // Rekap Laporan & Audit Log
        Route::get('/laporan', [LaporanKinerjaController::class, 'index'])->name('admin.laporan');
        Route::get('/laporan/drilldown', [LaporanKinerjaController::class, 'drilldown'])->name('admin.laporan.drilldown');
        Route::get('/laporan/export-excel', [LaporanKinerjaController::class, 'exportExcel'])->name('admin.laporan.exportExcel');
        Route::get('/audit-log', [TicketController::class, 'auditLog'])->name('admin.audit-log');
        Route::post('/tiket/{id}/excuse-sla', [TicketController::class, 'excuseSla'])->name('admin.tiket.excuseSla');

        // Kelola Akun Petugas & Admin Bidang
        Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::post('/users/{id}/toggle-status', [UserController::class, 'toggleActive'])->name('admin.users.toggleStatus');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('admin.users.update');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('admin.users.destroy');

        // Kelola Master Bidang Pengampu Layanan
        Route::resource('kelola-bidang', BidangManagementController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->names('admin.bidang-master');

        // Kelola Master Kategori Layanan
        Route::resource('kelola-kategori', CategoryManagementController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->names('admin.kategori');

        // Kelola Master OPD / SKPD
        Route::resource('kelola-opd', OpdManagementController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->names('admin.opd');
        Route::post('/kelola-opd/merge-unmapped', [OpdManagementController::class, 'mergeUnmapped'])
            ->name('admin.opd.mergeUnmapped');
    });

    /*
    |--------------------------------------------------------------------------
    | Khusus Admin Bidang (admin.bidang)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['admin.bidang'])->group(function () {
        // Tiket Khusus Bidang yang Ditangani
        Route::get('/bidang/tiket', [BidangTicketController::class, 'index'])->name('admin.bidang.tiket.index');
        Route::post('/tiket/{id}/tunjuk-teknisi', [BidangTicketController::class, 'assignTechnician'])->name('admin.bidang.tiket.assignTechnician');
        Route::post('/tiket/{id}/resolved', [BidangTicketController::class, 'markResolved'])->name('admin.bidang.tiket.markResolved');
        Route::post('/tiket/{id}/review-teknisi', [BidangTicketController::class, 'reviewSubmission'])->name('admin.bidang.tiket.reviewSubmission');
        Route::post('/bidang/tiket/{id}/return', [BidangTicketController::class, 'returnToPusat'])->name('admin.bidang.tiket.return');
        Route::post('/bidang/tiket/{id}/unlink', [BidangTicketController::class, 'unlinkParent'])->name('admin.bidang.tiket.unlink');
    });
});

/*
|--------------------------------------------------------------------------
| Portal Khusus Teknisi Lapangan (Auth & Role Teknisi Required)
|--------------------------------------------------------------------------
*/
Route::prefix('teknisi')->middleware(['auth', 'teknisi'])->group(function () {
    Route::redirect('/', '/teknisi/tugas');
    Route::get('/tugas', [\App\Http\Controllers\Teknisi\TeknisiTicketController::class, 'index'])->name('teknisi.tugas.index');
    Route::get('/tugas/{id}', [\App\Http\Controllers\Teknisi\TeknisiTicketController::class, 'show'])->name('teknisi.tugas.show');
    
    // Action routes (both English & Indonesian path aliases supported)
    Route::post('/tugas/{id}/accept', [\App\Http\Controllers\Teknisi\TeknisiTicketController::class, 'acceptTask'])->name('teknisi.tugas.accept');
    Route::post('/tugas/{id}/terima', [\App\Http\Controllers\Teknisi\TeknisiTicketController::class, 'acceptTask'])->name('teknisi.tugas.terima');
    
    Route::post('/tugas/{id}/decline', [\App\Http\Controllers\Teknisi\TeknisiTicketController::class, 'declineTask'])->name('teknisi.tugas.decline');
    Route::post('/tugas/{id}/tolak', [\App\Http\Controllers\Teknisi\TeknisiTicketController::class, 'declineTask'])->name('teknisi.tugas.tolak');
    
    Route::post('/tugas/{id}/mulai', [\App\Http\Controllers\Teknisi\TeknisiTicketController::class, 'startProgress'])->name('teknisi.tugas.mulai');
    Route::post('/tugas/{id}/start', [\App\Http\Controllers\Teknisi\TeknisiTicketController::class, 'startProgress'])->name('teknisi.tugas.start');
    
    Route::post('/tugas/{id}/catatan', [\App\Http\Controllers\Teknisi\TeknisiTicketController::class, 'addWorklog'])->name('teknisi.tugas.catatan');
    Route::post('/tugas/{id}/progres', [\App\Http\Controllers\Teknisi\TeknisiTicketController::class, 'addWorklog'])->name('teknisi.tugas.progres');
    
    Route::post('/tugas/{id}/hambatan', [\App\Http\Controllers\Teknisi\TeknisiTicketController::class, 'reportBlocker'])->name('teknisi.tugas.hambatan');
    Route::post('/tugas/{id}/hambatan/{blockerId}/selesai', [\App\Http\Controllers\Teknisi\TeknisiTicketController::class, 'resolveBlocker'])->name('teknisi.tugas.hambatan.resolve');
    
    Route::post('/tugas/{id}/ajukan-selesai', [\App\Http\Controllers\Teknisi\TeknisiTicketController::class, 'submitForReview'])->name('teknisi.tugas.submit');
    Route::post('/tugas/{id}/selesai', [\App\Http\Controllers\Teknisi\TeknisiTicketController::class, 'submitForReview'])->name('teknisi.tugas.selesai');
    
    Route::get('/notifikasi', [\App\Http\Controllers\Teknisi\TeknisiTicketController::class, 'notifications'])->name('teknisi.notifikasi');
    Route::get('/riwayat', [\App\Http\Controllers\Teknisi\TeknisiTicketController::class, 'riwayat'])->name('teknisi.riwayat');
    Route::get('/profil', [\App\Http\Controllers\Teknisi\TeknisiTicketController::class, 'profil'])->name('teknisi.profil');
    Route::post('/profil/ketersediaan', [\App\Http\Controllers\Teknisi\TeknisiTicketController::class, 'updateAvailability'])->name('teknisi.profil.ketersediaan');
    Route::post('/profil/password', [\App\Http\Controllers\Teknisi\TeknisiTicketController::class, 'updatePassword'])->name('teknisi.profil.password');
});
