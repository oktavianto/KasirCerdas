<?php
namespace App\Filament\Widgets;
use Flowframe\Trend\Trend;
use App\Models\DataTransaksi;
use Flowframe\Trend\TrendValue;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
class GrafikKeuntungan extends ChartWidget
{   
    protected static string $color = 'info';
    protected static ?string $heading = 'Grafik Keuntungan';
    protected function getData(): array 
    {
        $data = Trend::query(
            if (Auth::user()->hasRoles(7)) {
                DataTransaksi::where([
                    ['bisnis_id', '=', Auth::user()->bisnis_id],
                    ['cabangs_id', '=', Auth::user()->cabangs_id],
                ]);
            }
            else if (Auth::user()->hasRoles(6)) {
                DataTransaksi::where([
                    ['bisnis_id', '=', Auth::user()->bisnis_id],
                ]);
            }
            else if (Auth::user()->hasRoles(1)) {
                DataTransaksi::all();
            }
        )
        ->between(
            start: now()->startOfYear(),
            end: now()->endOfYear(),
        )
        ->perMonth() 
        ->sum('keuntungan');
        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Keuntungan',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate), 
                    'backgroundColor' => '#36C2EB',
                    'borderColor' => '#9BD0F5',
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ];
    }
    protected function getType(): string
    {
        return 'line'; 
    }
}