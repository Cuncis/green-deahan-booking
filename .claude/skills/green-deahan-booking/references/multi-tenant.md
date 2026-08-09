# Multi-Tenant: Aturan Wajib

Project ini melayani banyak klien (tenant) dari satu codebase. Kebocoran data antar tenant adalah bug paling serius yang bisa terjadi di project ini. Perlakukan aturan di file ini sebagai non-negotiable.

## Aturan Inti

**Setiap tabel yang menyimpan data milik klien punya kolom `tenant_id`.** Tabel-tabel ini: `cabang`, `lapangan`, `jadwal_slot`, `booking`, `pembayaran`, `membership`, `kode_promo`, `reminder_log`, `ulasan`, `staf`.

**Setiap query Eloquent ke tabel-tabel di atas WAJIB difilter `tenant_id`.** Tidak peduli sekecil apapun query-nya, tidak peduli sesimpel apapun fiturnya.

```php
// SALAH, bisa menarik data tenant lain
$lapangan = Lapangan::find($id);

// BENAR, selalu filter tenant_id
$lapangan = Lapangan::where('tenant_id', $tenant->id)
    ->where('id', $id)
    ->firstOrFail();
```

## Cara Dapat `$tenant` di Controller

Middleware `IdentifikasiTenant` sudah jalan di semua route web, jadi di controller manapun tinggal:

```php
$tenant = app('tenant');
```

Jangan cari tenant manual lewat `Tenant::where('domain', request()->getHost())->first()` di tiap controller. Itu sudah jadi tugas middleware, panggil saja `app('tenant')`.

## Pola Aman: Global Scope (Disarankan untuk Project Skala Besar)

Kalau project sudah punya banyak model yang butuh filter tenant_id terus-menerus, pertimbangkan bikin trait `BelongsToTenant` supaya tidak perlu tulis `where('tenant_id', ...)` manual di setiap query:

```php
// app/Models/Concerns/BelongsToTenant.php
namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->bound('tenant')) {
                $builder->where('tenant_id', app('tenant')->id);
            }
        });

        static::creating(function ($model) {
            if (app()->bound('tenant') && !$model->tenant_id) {
                $model->tenant_id = app('tenant')->id;
            }
        });
    }
}
```

Lalu di model:
```php
class Lapangan extends Model
{
    use Concerns\BelongsToTenant;
    // sekarang Lapangan::find($id) OTOMATIS difilter tenant_id
}
```

**Catatan penting:** global scope ini nyaman tapi jangan jadi alasan untuk lengah. Tetap eksplisit dengan `where('tenant_id', ...)` di query yang kritis (misal di dalam transaction booking), supaya kode mudah dibaca dan di-review tanpa harus inget "oh ini ada hidden scope".

## Webhook Pembayaran: Pengecualian

Endpoint webhook dari Mayar **tidak lewat middleware `IdentifikasiTenant`**, karena yang memanggil endpoint itu server payment gateway, bukan browser customer yang akses lewat domain tenant. Di webhook, tenant_id didapat dari data `booking` yang sudah tersimpan:

```php
$booking = Booking::where('kode_booking', $data['kode_booking'])->firstOrFail();
$tenantId = $booking->tenant_id; // ambil dari sini, bukan dari domain
```

## Checklist Sebelum Submit Kode yang Menyentuh Data Tenant

- [ ] Semua query SELECT ke tabel tenant-aware sudah difilter `tenant_id`?
- [ ] Semua query INSERT/UPDATE sudah menyertakan `tenant_id` yang benar?
- [ ] Kalau bikin relasi baru antar model, apakah foreign key tetap dalam tenant yang sama (tidak mungkin lapangan tenant A muncul di booking tenant B)?
- [ ] Kalau bikin endpoint API baru, apakah endpoint itu butuh middleware `IdentifikasiTenant` atau memang pengecualian seperti webhook?
