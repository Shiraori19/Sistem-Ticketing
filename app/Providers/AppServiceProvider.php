<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            \App\Services\Notification\WhatsAppServiceInterface::class,
            \App\Services\Notification\WhatsAppService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\View::composer('admin.layout', function ($view) {
            if (\Illuminate\Support\Facades\Auth::check()) {
                /** @var \App\Models\User $user */
                $user = \Illuminate\Support\Facades\Auth::user();

                // Request-level memory cache to eliminate redundant queries within the same request
                static $cachedComposerData = null;
                if ($cachedComposerData !== null) {
                    $view->with($cachedComposerData);
                    return;
                }

                $isPusat = $user->isAdminPusat();
                $isBidang = $user->isAdminBidang() && $user->bidang_id;
                $bidangId = $isBidang ? (int) $user->bidang_id : 0;

                $pendingVerifikasiCount = \Illuminate\Support\Facades\Cache::remember('pending_verif_count', 60, function () {
                    return \App\Models\Ticket::where('status', 'pending_verification')->count();
                });

                $eskalasiCount = \Illuminate\Support\Facades\Cache::remember('eskalasi_count', 60, function () {
                    return \App\Models\Ticket::where('status', 'eskalasi_pusat')->count();
                });

                $bidangAntreanCount = 0;
                $bidangPengerjaanCount = 0;
                $countSiaranSiber = 0;
                if ($isBidang) {
                    $isPersandian = $user->bidang && (strtoupper($user->bidang->kode) === 'PERSANDIAN' || str_contains(strtoupper($user->bidang->nama_bidang ?? ''), 'PERSANDIAN'));

                    $bidangAntreanCount = \Illuminate\Support\Facades\Cache::remember("bidang_{$bidangId}_antrean_count", 60, function () use ($bidangId, $isPersandian) {
                        return \App\Models\Ticket::where(function ($q) use ($bidangId, $isPersandian) {
                                $q->where('bidang_id', $bidangId);
                                if ($isPersandian) {
                                    $q->orWhere('tlp_level', 'RED');
                                }
                            })
                            ->whereIn('status', ['verified', 'reopened'])
                            ->count();
                    });

                    $bidangPengerjaanCount = \Illuminate\Support\Facades\Cache::remember("bidang_{$bidangId}_pengerjaan_count", 60, function () use ($bidangId, $isPersandian) {
                        return \App\Models\Ticket::where(function ($q) use ($bidangId, $isPersandian) {
                                $q->where('bidang_id', $bidangId);
                                if ($isPersandian) {
                                    $q->orWhere('tlp_level', 'RED');
                                }
                            })
                            ->where('status', 'in_progress')
                            ->count();
                    });

                    if (!$isPersandian) {
                        $countSiaranSiber = \Illuminate\Support\Facades\Cache::remember("siaran_siber_count_bidang_{$bidangId}", 60, function () use ($bidangId) {
                            return \App\Models\Ticket::where('tlp_level', 'RED')
                                ->where('bidang_id', '!=', $bidangId)
                                ->whereIn('status', ['verified', 'in_progress', 'reopened'])
                                ->count();
                        });
                    }
                }

                $slaKritisCount = \Illuminate\Support\Facades\Cache::remember(
                    $isPusat ? 'sla_kritis_count_pusat' : "sla_kritis_count_bidang_{$bidangId}",
                    30,
                    function () use ($isBidang, $bidangId) {
                        return \App\Models\Ticket::query()
                            ->when($isBidang, fn($q) => $q->where('bidang_id', $bidangId))
                            ->whereIn('status', ['in_progress', 'verified', 'reopened'])
                            ->where(function ($q) {
                                $q->where('sla_breach', true)
                                  ->orWhere(function ($sub) {
                                      $sub->whereNotNull('sla_deadline_at')
                                          ->where('sla_deadline_at', '<=', now());
                                  });
                            })
                            ->count();
                    }
                );

                $cachedComposerData = [
                    'pendingVerifikasiCount' => $pendingVerifikasiCount,
                    'eskalasiCount' => $eskalasiCount,
                    'bidangAntreanCount' => $bidangAntreanCount,
                    'bidangPengerjaanCount' => $bidangPengerjaanCount,
                    'countSiaranSiber' => $countSiaranSiber,
                    'slaKritisCount' => $slaKritisCount,
                ];

                $view->with($cachedComposerData);
            }
        });
    }
}
