<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use App\Models\Wilayah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pilihan untuk dropdown alamat di checkout: tanpa parameter daftar provinsi,
 * dengan ?induk=KODE daftar wilayah satu tingkat di bawahnya. Kelurahan
 * membawa kode posnya, supaya kolom kode pos terisi saat kelurahan dipilih.
 */
class WilayahController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $induk = $request->query('induk');

        if ($induk !== null && (! is_string($induk) || ! preg_match('/^\d{2}(\.\d{2}){0,2}$/', $induk))) {
            return response()->json(['message' => 'Kode wilayah tidak valid.'], 422);
        }

        $daftar = Wilayah::query()
            ->when(
                $induk === null,
                fn ($query) => $query->where('level', Wilayah::LEVEL_PROVINSI),
                fn ($query) => $query->where('induk', $induk),
            )
            ->orderBy('nama')
            ->get(['kode', 'nama', 'level', 'kodepos'])
            ->map(fn (Wilayah $wilayah) => array_filter([
                'kode' => $wilayah->kode,
                'nama' => $wilayah->nama,
                'kodepos' => $wilayah->level === Wilayah::LEVEL_KELURAHAN ? $wilayah->kodepos : null,
            ], fn ($nilai) => $nilai !== null))
            ->values();

        // Data wilayah jarang berubah, jadi boleh disimpan browser dan CDN selama sehari.
        return response()->json($daftar)->header('Cache-Control', 'public, max-age=86400');
    }
}
