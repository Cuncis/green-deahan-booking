<?php

namespace App\Http\Controllers\Superadmin\Toko;

use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\Toko\SupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Supplier toko (CRUD sederhana, form di dialog). Hanya superadmin.
 */
class SupplierTokoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('superadmin/Toko/Supplier', [
            'supplier' => Supplier::withCount('products')->orderBy('name')->get()->map(fn (Supplier $supplier) => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'platform' => $supplier->platform,
                'shop_url' => $supplier->shop_url,
                'whatsapp' => $supplier->whatsapp,
                'notes' => $supplier->notes,
                'jumlahProduk' => $supplier->products_count,
            ])->all(),
            'pilihanPlatform' => Supplier::PLATFORM,
        ]);
    }

    public function store(SupplierRequest $request): RedirectResponse
    {
        $supplier = Supplier::create($request->validated());

        return back()->with('success', "Supplier \"{$supplier->name}\" ditambahkan.");
    }

    public function update(SupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return back()->with('success', "Supplier \"{$supplier->name}\" diperbarui.");
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $nama = $supplier->name;
        $supplier->delete();

        return back()->with('success', "Supplier \"{$nama}\" dihapus. Produknya tetap ada tanpa supplier.");
    }
}
