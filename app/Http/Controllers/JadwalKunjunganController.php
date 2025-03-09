<?php

namespace App\Http\Controllers;

use App\Models\JadwalKunjungan;
use App\Models\KunjunganPetugas;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\JadwalKunjunganRequest;

class JadwalKunjunganController extends Controller
{
    /**
     * Tampilkan daftar jadwal kunjungan.
     */
    public function index()
    {
        $sekolahUsers = User::where('role', 'sekolah')->pluck('id');

        $kunjunganPetugas = KunjunganPetugas::with('petugas')
            ->where('status', 0)
            ->get();

        $jadwalKunjungan = JadwalKunjungan::whereIn('user_id', $sekolahUsers)
            ->latest()
            ->get();

        return view(
            'dashboard.manajemen-kegiatan.jadwal-kunjungan.index',
            compact('jadwalKunjungan', 'kunjunganPetugas')
        );
    }

    /**
     * Tampilkan form tambah jadwal kunjungan.
     */
    public function create()
    {
        $sekolahUsers = User::where('role', 'sekolah')->get();

        return view('dashboard.manajemen-kegiatan.jadwal-kunjungan.create', compact('sekolahUsers'));
    }

    /**
     * Simpan jadwal kunjungan ke database.
     */
    public function store(JadwalKunjunganRequest $request)
    {
        $validatedData = $request->validated();

        if ($this->cekJadwalBentrok($validatedData)) {
            return redirect()
                ->back()
                ->withErrors(['message' => 'Jadwal kunjungan pada tanggal dan jam tersebut sudah ada.'])
                ->withInput();
        }

        JadwalKunjungan::create($validatedData);

        return redirect()
            ->route('dashboard-jadwal-kunjungan')
            ->with('success', 'Jadwal berhasil ditambahkan.');
    }

    /**
     * Tampilkan form edit jadwal kunjungan.
     */
    public function edit(JadwalKunjungan $jadwalKunjungan)
    {
        $this->authorize('update', $jadwalKunjungan);

        return view('dashboard.manajemen-kegiatan.jadwal-kunjungan.edit', compact('jadwalKunjungan'));
    }

    /**
     * Perbarui jadwal kunjungan di database.
     */
    public function update(JadwalKunjunganRequest $request, JadwalKunjungan $jadwalKunjungan)
    {
        $this->authorize('update', $jadwalKunjungan);

        $validatedData = $request->validated();

        if ($this->cekJadwalBentrok($validatedData, $jadwalKunjungan->id)) {
            return redirect()
                ->back()
                ->withErrors(['message' => 'Jadwal kunjungan pada tanggal dan jam tersebut sudah ada.'])
                ->withInput();
        }

        $jadwalKunjungan->update($validatedData);

        return redirect()
            ->route('dashboard-jadwal-kunjungan')
            ->with('success', 'Jadwal berhasil diperbarui.');
    }

    /**
     * Hapus jadwal kunjungan dari database.
     */
    public function destroy(JadwalKunjungan $jadwalKunjungan)
    {
        $this->authorize('delete', $jadwalKunjungan);

        $jadwalKunjungan->delete();

        return redirect()
            ->route('dashboard-jadwal-kunjungan')
            ->with('success', 'Jadwal berhasil dihapus.');
    }

    /**
     * Periksa apakah jadwal bentrok dengan jadwal lain.
     */
    private function cekJadwalBentrok($validatedData, $excludedId = null)
    {
        $query = JadwalKunjungan::where('tgl_kunjungan', $validatedData['tgl_kunjungan'])
            ->where(function ($q) use ($validatedData) {
                $q->whereRaw('? BETWEEN jam_mulai AND jam_selesai', [$validatedData['jam_mulai']])
                    ->orWhereRaw('? BETWEEN jam_mulai AND jam_selesai', [$validatedData['jam_selesai']])
                    ->orWhereRaw(
                        'jam_mulai <= ? AND jam_selesai >= ?',
                        [$validatedData['jam_mulai'], $validatedData['jam_selesai']]
                    );
            });

        if ($excludedId) {
            $query->where('id', '<>', $excludedId);
        }

        return $query->exists();
    }
}
