<?php

namespace App\Enums;

/**
 * Status pesanan toko. Alur normal: PendingPayment -> Paid ->
 * OrderedToSupplier -> Shipped -> Completed. Cancelled dan Expired adalah
 * akhir alur untuk pesanan yang tidak jadi.
 */
enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case OrderedToSupplier = 'ordered_to_supplier';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Menunggu pembayaran',
            self::Paid => 'Dibayar',
            self::OrderedToSupplier => 'Diproses',
            self::Shipped => 'Dikirim',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
            self::Expired => 'Kedaluwarsa',
        };
    }

    /**
     * Langkah berikutnya yang boleh dipilih admin dari status ini.
     *
     * @return array<int, self>
     */
    public function langkahBerikutnya(): array
    {
        return match ($this) {
            self::Paid => [self::OrderedToSupplier, self::Cancelled],
            self::OrderedToSupplier => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Completed],
            self::PendingPayment => [self::Cancelled],
            default => [],
        };
    }

    public function bisaMenjadi(self $tujuan): bool
    {
        return in_array($tujuan, $this->langkahBerikutnya(), true);
    }
}
