<?php

namespace App\Http\Controllers;

use App\Http\Requests\JadwalKunjunganRequest;
use App\Models\JadwalKunjungan;
use App\Models\KunjunganPetugas;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class JadwalKunjunganController extends Controller
{
    /**
     * Tampilkan daftar jadwal kunjungan.
     */
    public function index(): View
    {
        $sekolahUsers = User::where('role', 'sekolah')->pluck('id');

        $kunjunganPetugas = KunjunganPetugas::with('petugas')
            ->where('status', 0)
            ->get();

        $jadwalKunjungan = JadwalKunjungan::whereIn('user_id', $sekolahUsers)
            ->latest()
            ->get();

            return view('dashboard.manajemen-kegiatan.jadwal-kunjungan.index', [
                'jadwalKunjungan' => $jadwalKunjungan,
                'kunjunganPetugas' => $kunjunganPetugas
            ]);
    }

    /**
     * Tampilkan form tambah jadwal kunjungan.
     */
    public function create(): View
    {
        $sekolahUsers = User::select('id', 'name')->where('role', 'sekolah')->get();

        return view('dashboard.manajemen-kegiatan.jadwal-kunjungan.create', compact('sekolahUsers'));
    }

    /**
     * Simpan jadwal kunjungan ke database.
     */
    public function store(JadwalKunjunganRequest $request): RedirectResponse
    {
        $validatedData = $request->validated();

        if ($this->cekJadwalBentrok($validatedData)) {
            return redirect()
                ->back()
                ->withErrors(['message' => 'Jadwal kunjungan pada tanggal dan jam tersebut sudah ada.'])
                ->withInput();
        }

        JadwalKunjungan::create($validatedData);

        return redirect()->route('dashboard-jadwal-kunjungan')->with('success', 'Jadwal berhasil ditambahkan.');
    }

    /**
     * Tampilkan form edit jadwal kunjungan.
     */
    public function edit(JadwalKunjungan $jadwalKunjungan): View
    {
        abort_if(auth()->user()->cannot('update', $jadwalKunjungan), 403);

        return view('dashboard.manajemen-kegiatan.jadwal-kunjungan.edit', compact('jadwalKunjungan'));
    }

    /**
     * Perbarui jadwal kunjungan di database.
     */
    public function update(JadwalKunjunganRequest $request, JadwalKunjungan $jadwalKunjungan): RedirectResponse
    {
        abort_if(auth()->user()->cannot('update', $jadwalKunjungan), 403);

        $validatedData = $request->validated();

        if ($this->cekJadwalBentrok($validatedData, $jadwalKunjungan->id)) {
            return redirect()
                ->back()
                ->withErrors(['message' => 'Jadwal kunjungan pada tanggal dan jam tersebut sudah ada.'])
                ->withInput();
        }

        $jadwalKunjungan->update($validatedData);

        return redirect()->route('dashboard-jadwal-kunjungan')->with('success', 'Jadwal berhasil diperbarui.');
    }

    /**
     * Hapus jadwal kunjungan dari database.
     */
    public function destroy(JadwalKunjungan $jadwalKunjungan): RedirectResponse
    {
        abort_if(auth()->user()->cannot('delete', $jadwalKunjungan), 403);

        $jadwalKunjungan->delete();

        return redirect()->route('dashboard-jadwal-kunjungan')->with('success', 'Jadwal berhasil dihapus.');
    }

    /**
     * Periksa apakah jadwal bentrok dengan jadwal lain.
     *
     * @param  array  $validatedData  Data jadwal yang divalidasi
     * @param  int|null  $excludedId  ID jadwal yang dikecualikan saat update
     * @return bool  True jika bentrok, false jika tidak
     */
    private function cekJadwalBentrok(array $validatedData, ?int $excludedId = null): bool
    {
        $query = JadwalKunjungan::query()
            ->where('tgl_kunjungan', $validatedData['tgl_kunjungan'])
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
