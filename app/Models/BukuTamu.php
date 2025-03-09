<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BukuTamu extends Model
{
    protected $fillable = [
        'tanggal',
        'nama',
        'asal',
        'pekerjaan',
        'usia',
        'kesan',
        'pesan',
    ];

    /**
     * Ambil semua data buku tamu berdasarkan tanggal pembuatan.
     */
    public static function getAllBukuTamuByCreatedAt()
    {
        return BukuTamu::select(DB::raw('DATE(created_at) as created_date'), DB::raw('count(*) as count'))
            ->groupBy('created_date')
            ->orderBy('created_date', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'created_date' => $item->created_date,
                    'count' => $item->count,
                ];
            })
            ->toArray();
    }

    /**
     * Ambil data buku tamu berdasarkan interval waktu untuk grafik.
     */
    public static function getAllBukuTamuGrafik($interval = 'today', $value = null)
    {
        $query = BukuTamu::select(DB::raw('DATE(created_at) as created_date'), DB::raw('count(*) as count'));

        switch ($interval) {
            case 'year':
                $query->whereYear('created_at', $value ?? now()->year);
                break;

            case 'month':
                $query->whereYear('created_at', date('Y', strtotime($value ?? now())))
                    ->whereMonth('created_at', date('m', strtotime($value ?? now())));
                break;

            case 'week':
                $startDate = date('Y-m-d', strtotime($value ?? 'monday this week'));
                $endDate = date('Y-m-d', strtotime("$startDate +6 days"));
                $query->whereBetween('created_at', [$startDate, $endDate]);
                break;

            case 'today':
            default:
                $query->whereDate('created_at', now()->toDateString());
                break;
        }

        return $query->groupBy('created_date')
            ->orderBy('created_date', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'created_date' => $item->created_date,
                    'count' => $item->count,
                ];
            })
            ->toArray();
    }
}
