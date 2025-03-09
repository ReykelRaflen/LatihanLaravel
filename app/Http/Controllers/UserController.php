<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Tampilkan daftar pengguna berdasarkan peran.
     */
    public function index()
    {
        $loggedInUser = auth()->user();

        $users = match ($loggedInUser->role) {
            'admin' => User::whereIn('role', ['sekolah', 'dinas_pendidikan'])->select('id', 'name', 'role')->get(),
            'superadmin' => User::select('id', 'name', 'role')->get(),
            default => collect(),
        };

        return view('dashboard.manajemen-pengguna.pengguna.index', compact('users'));
    }

    /**
     * Tampilkan form tambah pengguna.
     */
    public function create()
    {
        return view('dashboard.manajemen-pengguna.pengguna.create');
    }

    /**
     * Simpan pengguna ke dalam basis data.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users,username',
            'email' => 'nullable|email|unique:users,email',
            'role' => 'required|in:admin,superadmin,sekolah,dinas_pendidikan',
            'password' => 'required|min:6',
        ]);

        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'role' => $request->role,
            'password' => bcrypt($request->password),
        ]);

        return redirect()->route('dashboard-pengguna')->with('success', 'Pengguna berhasil ditambahkan!');
    }

    /**
     * Tampilkan form edit pengguna.
     */
    public function edit(User $user)
    {
        return view('dashboard.manajemen-pengguna.pengguna.edit', compact('user'));
    }

    /**
     * Perbarui data pengguna.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users,username,' . $user->id,
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
        ]);

        $user->update([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => bcrypt($request->password)]);
        }

        return redirect()->route('dashboard-pengguna')->with('success', 'Pengguna berhasil diperbarui!');
    }

    /**
     * Hapus pengguna.
     */
    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('dashboard-pengguna')->with('delete', 'Pengguna berhasil dihapus.');
    }

    /**
     * Cetak informasi pengguna (hanya untuk admin yang mencetak data sekolah).
     */
    public function printUser(User $user)
    {
        Gate::authorize('print-user', $user);

        return view('dashboard.manajemen-pengguna.pengguna.print', compact('user'));
    }
}
