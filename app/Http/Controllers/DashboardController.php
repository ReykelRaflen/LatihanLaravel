<?php

namespace App\Http\Controllers;

use App\Models\BukuTamu;
use App\Models\HistoriKunjungan;
use App\Models\JadwalKunjungan;
use App\Models\Koleksi;
use App\Models\Pegawai;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Menampilkan dashboard utama.
     */
    public function index()
    {
        $user = Auth::user();
        $historiKunjunganQuery = HistoriKunjungan::with(['kunjunganPetugas.jadwalKunjungan']);

        // Cek role user untuk menentukan histori kunjungan
        if (! in_array($user->role, ['superadmin', 'admin'])) {
            $historiKunjunganQuery->whereHas('kunjunganPetugas.jadwalKunjungan', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
        }

        $historiKunjungan = $historiKunjunganQuery->get();

        // Hitung total data yang dibutuhkan untuk dashboard
        $dashboardData = [
            'totalPegawai' => Pegawai::count(),
            'totalPengguna' => User::count(),
            'totalJadwal' => JadwalKunjungan::count(),
            'totalKoleksi' => Koleksi::count(),
            'totalTamu' => BukuTamu::count(),
            'adminCount' => User::where('role', 'admin')->count(),
            'bukuTamuCounts' => BukuTamu::getAllBukuTamaByCreatedAt(),
        ];

        // Gunakan paginate agar konsisten dengan `filterKunjungan`
        $bukutamu = BukuTamu::latest()->paginate(10);

        return view('dashboard.index', array_merge($dashboardData, compact('historiKunjungan', 'bukutamu')));
    }

    /**
     * Filter histori kunjungan berdasarkan rentang waktu.
     */
    public function filterKunjungan(Request $request)
    {
        $user = Auth::user();
        $historiKunjunganQuery = HistoriKunjungan::with(['kunjunganPetugas.jadwalKunjungan']);

        // Cek role user untuk menentukan histori kunjungan
        if (! in_array($user->role, ['superadmin', 'admin'])) {
            $historiKunjunganQuery->whereHas('kunjunganPetugas.jadwalKunjungan', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
        }

        $historiKunjungan = $historiKunjunganQuery->get();

        // Ambil filter dari request
        $filterType = $request->input('filter_type', 'minggu');
        $bukutamuQuery = BukuTamu::query();

        // Tentukan rentang tanggal berdasarkan filter
        $tanggalAwal = null;
        $tanggalAkhir = null;

        switch ($filterType) {
            case 'minggu':
                $tanggalAwal = Carbon::now()->startOfWeek();
                $tanggalAkhir = Carbon::now()->endOfWeek();
                break;
            case 'bulan':
                $tanggalAwal = Carbon::now()->startOfMonth();
                $tanggalAkhir = Carbon::now()->endOfMonth();
                break;
            case 'tahun':
                $tanggalAwal = Carbon::now()->startOfYear();
                $tanggalAkhir = Carbon::now()->endOfYear();
                break;
            default:
                return redirect()->back()->withErrors(['message' => 'Filter tidak valid.']);
        }

        // Terapkan filter tanggal
        $bukutamuQuery->whereBetween('created_at', [$tanggalAwal, $tanggalAkhir]);

        // Gunakan paginate agar konsisten dengan index()
        $bukutamu = $bukutamuQuery->latest()->paginate(10);

        return view('dashboard.index', compact('historiKunjungan', 'bukutamu'));
    }
}
