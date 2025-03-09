<?php

namespace App\Http\Controllers;

use App\Models\Divisi;
use Illuminate\Http\Request;

class DivisiController extends Controller
{
    /**
     * Menampilkan daftar divisi.
     */
    public function index()
    {
        $divisi = Divisi::all();

        return view('dashboard.manajemen-pengguna.divisi.index', [
            'divisis' => $divisi,
        ]);
    }

    /**
     * Menampilkan form untuk menambahkan divisi baru.
     */
    public function create()
    {
        $divisis = Divisi::all();

        return view('dashboard.manajemen-pengguna.divisi.create', compact('divisis'));
    }

    /**
     * Menyimpan divisi baru ke dalam database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_divisi' => 'required|unique:divisis,nama_divisi',
        ], [
            'nama_divisi.unique' => 'Divisi sudah ada, silakan tambahkan divisi lain.',
        ]);

        Divisi::create([
            'nama_divisi' => $request->nama_divisi,
        ]);

        return redirect()->route('dashboard-divisi.index')->with('success', 'Divisi berhasil ditambahkan.');
    }

    /**
     * Menampilkan detail divisi tertentu.
     */
    public function show(Divisi $divisi)
    {
        return view('dashboard.manajemen-pengguna.divisi.show', compact('divisi'));
    }

    /**
     * Menampilkan form untuk mengedit divisi.
     */
    public function edit(Divisi $divisi)
    {
        return view('dashboard.manajemen-pengguna.divisi.edit', compact('divisi'));
    }

    /**
     * Memperbarui data divisi di dalam database.
     */
    public function update(Request $request, Divisi $divisi)
    {
        $request->validate([
            'nama_divisi' => 'required|unique:divisis,nama_divisi,' . $divisi->id,
        ], [
            'nama_divisi.unique' => 'Divisi sudah ada, silakan gunakan nama lain.',
        ]);

        $divisi->update([
            'nama_divisi' => $request->nama_divisi,
        ]);

        return redirect()->route('dashboard-divisi.index')->with('success', 'Divisi berhasil diperbarui.');
    }

    /**
     * Menghapus divisi dari database.
     */
    public function destroy(Divisi $divisi)
    {
        $divisi->delete();

        return redirect()->route('dashboard-divisi.index')->with('delete', 'Divisi berhasil dihapus.');
    }
}
