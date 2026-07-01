<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Tests\TestCase;

class BladeComponentsTest extends TestCase
{
    public function test_button_default_variant_adalah_primary(): void
    {
        $view = $this->blade('<x-button>Booking Sekarang</x-button>');

        $view->assertSee('Booking Sekarang');
        $view->assertSeeInOrder(['<button', 'bg-green', 'text-white', 'hover:bg-green-mid']);
    }

    public function test_button_variant_secondary(): void
    {
        $view = $this->blade('<x-button variant="secondary">Detail</x-button>');

        $view->assertSee('border-sand', false);
        $view->assertSee('hover:border-brown-light', false);
    }

    public function test_button_variant_gold(): void
    {
        $view = $this->blade('<x-button variant="gold">Premium</x-button>');

        $view->assertSee('bg-gold', false);
    }

    public function test_button_variant_danger(): void
    {
        $view = $this->blade('<x-button variant="danger">Batalkan</x-button>');

        $view->assertSee('bg-danger/10', false);
        $view->assertSee('text-danger', false);
        $view->assertSee('border-danger/20', false);
    }

    public function test_card_menampilkan_slot_dengan_class_yang_benar(): void
    {
        $view = $this->blade('<x-card>Isi kartu</x-card>');

        $view->assertSee('Isi kartu');
        $view->assertSee('border-cream-deep', false);
        $view->assertSee('rounded-card', false);
        $view->assertSee('p-5 md:p-6', false);
    }

    public function test_badge_type_confirmed(): void
    {
        $view = $this->blade('<x-badge type="confirmed">Dikonfirmasi</x-badge>');

        $view->assertSee('bg-green-pale', false);
        $view->assertSee('text-green', false);
        $view->assertSee('rounded-full', false);
    }

    public function test_badge_type_pending(): void
    {
        $view = $this->blade('<x-badge type="pending">Menunggu</x-badge>');

        $view->assertSee('bg-amber-pale', false);
        $view->assertSee('text-amber', false);
    }

    public function test_badge_type_cancelled(): void
    {
        $view = $this->blade('<x-badge type="cancelled">Dibatalkan</x-badge>');

        $view->assertSee('bg-danger-pale', false);
        $view->assertSee('text-danger', false);
    }

    public function test_badge_type_info_default(): void
    {
        $view = $this->blade('<x-badge>Info</x-badge>');

        $view->assertSee('bg-cream-deep', false);
        $view->assertSee('text-ink-mid', false);
    }

    public function test_input_menampilkan_label_dan_atribut(): void
    {
        $view = $this->blade(
            '<x-input label="Nama" name="nama" placeholder="Masukkan nama" />',
        );

        $view->assertSee('Nama');
        $view->assertSee('name="nama"', false);
        $view->assertSee('id="nama"', false);
        $view->assertSee('placeholder="Masukkan nama"', false);
        $view->assertSee('type="text"', false);
        $view->assertSee('border-cream-deep', false);
        $view->assertSee('focus:border-green', false);
        $view->assertSee('bg-cream', false);
    }

    public function test_input_tanpa_label_tidak_menampilkan_tag_label(): void
    {
        $view = $this->blade('<x-input name="whatsapp" />');

        $view->assertDontSee('<label', false);
    }

    public function test_input_bisa_ganti_type(): void
    {
        $view = $this->blade('<x-input name="whatsapp" type="tel" />');

        $view->assertSee('type="tel"', false);
    }

    public function test_icon_merender_partial_dan_ukuran_yang_diminta(): void
    {
        $view = $this->blade('<x-icon name="check-circle" size="32" class="text-green" />');

        $view->assertSee('width: 32px', false);
        $view->assertSee('height: 32px', false);
        $view->assertSee('text-green', false);
        $view->assertSee('<svg', false);
    }

    public function test_icon_default_size_20(): void
    {
        $view = $this->blade('<x-icon name="check-circle" />');

        $view->assertSee('width: 20px', false);
        $view->assertSee('height: 20px', false);
    }

    public function test_manual_transfer_info_menampilkan_info_rekening_dan_tombol_whatsapp(): void
    {
        $tenant = new Tenant([
            'bank_nama' => 'Bank Sinarmas',
            'bank_no_rekening' => '1234567890',
            'bank_pemilik_rekening' => 'PT Green Deahan',
        ]);

        $view = $this->blade('<x-manual-transfer-info :tenant="$tenant" />', ['tenant' => $tenant]);

        $view->assertSee('Bank Sinarmas');
        $view->assertSee('1234567890');
        $view->assertSee('PT Green Deahan');
        $view->assertSee('Konfirmasi via WhatsApp');
        $view->assertSee('<svg', false);
        $view->assertDontSee('💬');
    }
}
