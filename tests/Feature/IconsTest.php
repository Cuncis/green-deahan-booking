<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IconsTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function namaIconProvider(): array
    {
        return [
            'futsal-goal' => ['futsal-goal'],
            'padel-racket' => ['padel-racket'],
            'shuttlecock' => ['shuttlecock'],
            'tennis-racket' => ['tennis-racket'],
            'calendar' => ['calendar'],
            'clock' => ['clock'],
            'check-circle' => ['check-circle'],
            'warning' => ['warning'],
            'crown' => ['crown'],
            'wa-chat' => ['wa-chat'],
            'location-pin' => ['location-pin'],
            'chart-trend' => ['chart-trend'],
            'globe' => ['globe'],
        ];
    }

    #[DataProvider('namaIconProvider')]
    public function test_icon_bisa_dirender_lewat_komponen_x_icon(string $nama): void
    {
        $this->assertTrue(view()->exists("icons.{$nama}"), "View icons.{$nama} tidak ditemukan.");

        $view = $this->blade('<x-icon name="'.$nama.'" />');

        $view->assertSee('<svg', false);
        $view->assertSee('viewBox="0 0 24 24"', false);
        $view->assertSee('stroke="currentColor"', false);
        $view->assertSee('stroke-width="1.6"', false);
    }
}
