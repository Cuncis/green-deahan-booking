<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\LogErrorService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman Log Error: isi file log Laravel dengan pencarian, filter level,
 * dan 25/50/100 entri per halaman. Hanya superadmin (grup rute superadmin).
 */
class LogErrorController extends Controller
{
    /** @var array<int, int> */
    private const PER_HALAMAN = [25, 50, 100];

    public function __construct(private readonly LogErrorService $logError) {}

    public function index(Request $request): Response
    {
        $kata = mb_substr(trim((string) $request->query('q')), 0, 100);
        $level = (string) $request->query('level', LogErrorService::ERROR_KE_ATAS);
        $level = in_array($level, [LogErrorService::ERROR_KE_ATAS, LogErrorService::SEMUA, ...LogErrorService::LEVEL], true) ? $level : LogErrorService::ERROR_KE_ATAS;
        $perHalaman = (int) $request->query('per_halaman', self::PER_HALAMAN[0]);
        $perHalaman = in_array($perHalaman, self::PER_HALAMAN, true) ? $perHalaman : self::PER_HALAMAN[0];
        $halaman = max(1, (int) $request->query('page', 1));

        ['entri' => $entri, 'terpotong' => $terpotong, 'ukuranLog' => $ukuran] = $this->logError->cari($kata, $level);

        $daftar = (new LengthAwarePaginator(
            array_slice($entri, ($halaman - 1) * $perHalaman, $perHalaman),
            count($entri),
            $perHalaman,
            $halaman,
            ['path' => route('superadmin.log-error'), 'query' => array_filter(['q' => $kata, 'level' => $level, 'per_halaman' => $perHalaman])],
        ));

        return Inertia::render('superadmin/LogError', [
            'daftar' => $daftar,
            'filter' => ['q' => $kata, 'level' => $level, 'perHalaman' => $perHalaman],
            'pilihanLevel' => [
                ['nilai' => LogErrorService::ERROR_KE_ATAS, 'label' => 'Error ke atas'],
                ['nilai' => LogErrorService::SEMUA, 'label' => 'Semua level'],
                ...array_map(fn (string $nilai) => ['nilai' => $nilai, 'label' => ucfirst($nilai)], LogErrorService::LEVEL),
            ],
            'pilihanPerHalaman' => self::PER_HALAMAN,
            'terpotong' => $terpotong,
            'ukuranLogMb' => round($ukuran / 1048576, 1),
            'bacaMaksMb' => (int) round((int) config('monitoring.log_maks_byte') / 1048576),
        ]);
    }
}
