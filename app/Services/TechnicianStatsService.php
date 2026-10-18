<?php

namespace App\Services;

use App\Models\TechnicianStatsDaily;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TechnicianStatsService
{
    /**
     * Hitung statistik performa lengkap untuk seorang teknisi
     * (Persentase kepatuhan SLA hanya ditampilkan bila sampel >= 5)
     */
    public function calculateTechnicianStats(User $user, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = TicketAssignment::with(['ticket.category'])
            ->where('user_id', $user->id)
            ->where('status', 'approved');

        if ($startDate) {
            $query->where('approved_at', '>=', $startDate->startOfDay());
        }
        if ($endDate) {
            $query->where('approved_at', '<=', $endDate->endOfDay());
        }

        /** @var Collection<int, TicketAssignment> $completedAssignments */
        $completedAssignments = $query->get();
        $totalCompleted = $completedAssignments->count();

        // 1. Durasi pengerjaan & Median
        $durationsMinutes = [];
        $rawOnTime = 0;
        $excusedOnTime = 0;
        $categoryBreakdown = [];
        $ratings = [];

        foreach ($completedAssignments as $assign) {
            $ticket = $assign->ticket;
            if (!$ticket) continue;

            // Durasi dari started_at / accepted_at ke submitted_at
            $startTime = $assign->started_at ?: ($assign->accepted_at ?: $assign->assigned_at);
            $endTime = $assign->submitted_at ?: $assign->approved_at;

            if ($startTime && $endTime) {
                $durMins = max(1, (int) Carbon::parse($startTime)->diffInMinutes(Carbon::parse($endTime)));
                $durationsMinutes[] = $durMins;
            }

            // Kepatuhan SLA Mentah vs Pengecualian
            $isRawBreach = (bool) $ticket->sla_breach;
            $isExcused = (bool) ($ticket->sla_excused ?? false);

            if (!$isRawBreach) {
                $rawOnTime++;
                $excusedOnTime++;
            } elseif ($isExcused) {
                $excusedOnTime++;
            }

            // Rating
            if (!empty($ticket->rating)) {
                $ratings[] = (int) $ticket->rating;
            }

            // Breakdown Kategori
            $catName = $ticket->category?->nama_kategori ?: 'Layanan Lainnya';
            $categoryBreakdown[$catName] = ($categoryBreakdown[$catName] ?? 0) + 1;
        }

        // Kalkulasi Median Durasi
        sort($durationsMinutes);
        $medianMinutes = 0;
        $countDur = count($durationsMinutes);
        if ($countDur > 0) {
            $middle = (int) floor($countDur / 2);
            if ($countDur % 2 === 0) {
                $medianMinutes = (int) round(($durationsMinutes[$middle - 1] + $durationsMinutes[$middle]) / 2);
            } else {
                $medianMinutes = $durationsMinutes[$middle];
            }
        }

        // Hitung Reopen (tiket yang pernah di-reopen oleh pelapor)
        $reopenCount = Ticket::whereHas('assignments', fn($q) => $q->where('user_id', $user->id))
            ->where('reopen_count', '>', 0)
            ->count();

        // Syarat Sampel >= 5 untuk menampilkan persentase
        $hasEnoughSample = $totalCompleted >= 5;
        $rawPercentage = $totalCompleted > 0 ? round(($rawOnTime / $totalCompleted) * 100, 1) : null;
        $excusedPercentage = $totalCompleted > 0 ? round(($excusedOnTime / $totalCompleted) * 100, 1) : null;

        $avgRating = count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 1) : null;

        return [
            'total_completed' => $totalCompleted,
            'median_resolution_minutes' => $medianMinutes,
            'median_formatted' => $this->formatMinutes($medianMinutes),
            'has_enough_sample' => $hasEnoughSample,
            'sample_count' => $totalCompleted,
            'raw_on_time_count' => $rawOnTime,
            'raw_percentage' => $hasEnoughSample ? $rawPercentage : null,
            'raw_percentage_label' => $hasEnoughSample ? "{$rawPercentage}%" : 'Sampel belum cukup (< 5 tiket)',
            'excused_on_time_count' => $excusedOnTime,
            'excused_percentage' => $hasEnoughSample ? $excusedPercentage : null,
            'excused_percentage_label' => $hasEnoughSample ? "{$excusedPercentage}%" : 'Sampel belum cukup (< 5 tiket)',
            'reopen_count' => $reopenCount,
            'avg_rating' => $avgRating ?: 5.0,
            'total_ratings' => count($ratings),
            'category_breakdown' => $categoryBreakdown,
        ];
    }

    /**
     * Format durasi menit ke teks jam & menit
     */
    protected function formatMinutes(int $minutes): string
    {
        if ($minutes <= 0) return '0 menit';
        $hours = (int) floor($minutes / 60);
        $mins = $minutes % 60;

        if ($hours > 0 && $mins > 0) {
            return "{$hours} jam {$mins} menit";
        } elseif ($hours > 0) {
            return "{$hours} jam";
        }
        return "{$mins} menit";
    }

    /**
     * Hitung agregat harian untuk seluruh teknisi dan simpan ke technician_stats_daily
     */
    public function aggregateDaily(Carbon $date): int
    {
        $technicians = User::whereHas('role', fn($r) => $r->where('nama_role', 'Teknisi'))->get();
        $count = 0;

        foreach ($technicians as $tech) {
            $stats = $this->calculateTechnicianStats($tech, $date->copy()->startOfDay(), $date->copy()->endOfDay());

            TechnicianStatsDaily::updateOrCreate(
                [
                    'user_id' => $tech->id,
                    'date' => $date->toDateString(),
                ],
                [
                    'tasks_completed' => $stats['total_completed'],
                    'median_resolution_minutes' => $stats['median_resolution_minutes'],
                    'sla_on_time_raw' => $stats['raw_on_time_count'],
                    'sla_on_time_excused' => $stats['excused_on_time_count'],
                    'reopen_count' => $stats['reopen_count'],
                    'avg_rating' => $stats['avg_rating'],
                    'category_breakdown' => $stats['category_breakdown'],
                ]
            );

            $count++;
        }

        return $count;
    }
}
