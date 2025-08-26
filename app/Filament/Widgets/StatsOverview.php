<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Kode;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $barang = Kode::sum('jumlah');
        $dipinjam = Kode::sum('jumlah_dipinjam');
        $rusak = Kode::sum('jumlah_rusak');

        return [
            Stat::make('Total Barang', $barang),
            Stat::make('Total Dipinjam', $dipinjam),
            Stat::make('Total Barang Rusak', $rusak),
        ];
    }
}
