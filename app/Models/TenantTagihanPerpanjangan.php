<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

#[Table('tenant_tagihan_perpanjangan')]
#[Fillable([
    'tenant_id',
    'kode',
    'jumlah',
    'link_pembayaran',
    'status',
    'dikirim_pada',
    'peringatan_terakhir_terkirim_at',
    'dibayar_pada',
])]
class TenantTagihanPerpanjangan extends Model
{
    use BelongsToTenant;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'dikirim_pada' => 'datetime',
            'peringatan_terakhir_terkirim_at' => 'datetime',
            'dibayar_pada' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Kode referensi unik untuk mencocokkan invoice Mayar kembali ke baris
     * tagihan ini di webhook (lihat
     * PaymentService::createPerpanjanganTransaction() dan
     * PembayaranController::prosesWebhookPerpanjangan()), sama seperti
     * Tenant::generateKodePendaftaran().
     */
    public static function generateKode(): string
    {
        do {
            $kode = 'PRP'.now()->format('ymd').strtoupper(Str::random(4));
        } while (self::where('kode', $kode)->exists());

        return $kode;
    }

    /**
     * Kirim tagihan perpanjangan ke owner tenant. Best-effort saja (sama
     * seperti TenantInvitation::kirimEmail()), kegagalan kirim email tidak
     * boleh menggagalkan pembuatan invoice-nya.
     */
    public function kirimTagihan(): void
    {
        $tenant = $this->tenant;

        if (! $tenant->email_admin) {
            return;
        }

        $jumlahFormat = 'Rp'.number_format($this->jumlah, 0, ',', '.');

        try {
            Mail::raw(
                "Halo,\n\nMasa aktif \"{$tenant->nama_bisnis}\" ({$tenant->domain}) akan berakhir tanggal {$tenant->tanggal_berakhir->format('d/m/Y')}.\n\n".
                "Perpanjang sekarang ({$jumlahFormat}/tahun) lewat link berikut:\n{$this->link_pembayaran}\n\n".
                'Kalau sudah dibayar, akun otomatis aktif lagi tanpa perlu langkah tambahan.',
                function ($message) use ($tenant) {
                    $message->to($tenant->email_admin)
                        ->subject("Tagihan Perpanjangan, {$tenant->nama_bisnis}");
                },
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Peringatan terakhir begitu masa tenggang mulai berjalan (tanggal_
     * berakhir sudah lewat tapi belum dinonaktifkan), lihat
     * NonaktifkanTenantKadaluarsa.
     */
    public function kirimPeringatanTerakhir(int $sisaHariMasaTenggang): void
    {
        $tenant = $this->tenant;

        if (! $tenant->email_admin) {
            return;
        }

        try {
            Mail::raw(
                "Halo,\n\nMasa aktif \"{$tenant->nama_bisnis}\" ({$tenant->domain}) sudah lewat tanggal {$tenant->tanggal_berakhir->format('d/m/Y')}.\n\n".
                "Kamu punya waktu {$sisaHariMasaTenggang} hari lagi sebelum website ini otomatis dinonaktifkan. Bayar sekarang lewat link berikut:\n{$this->link_pembayaran}",
                function ($message) use ($tenant) {
                    $message->to($tenant->email_admin)
                        ->subject("Peringatan Terakhir, {$tenant->nama_bisnis} Akan Dinonaktifkan");
                },
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
