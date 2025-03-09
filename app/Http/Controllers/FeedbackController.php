<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Tampilkan daftar feedback milik pengguna yang sedang masuk.
     */
    public function index()
    {
        $user = auth()->user();
        $feedback = Feedback::where('user_id', $user->id)->latest()->get(); // Urutkan dari yang terbaru

        return view('dashboard.feedback', compact('feedback'));
    }

    /**
     * Tampilkan form untuk membuat feedback baru.
     */
    public function create()
    {
        return view('frontend.feedback');
    }

    /**
     * Simpan feedback yang baru dibuat ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5', // Validasi rating (1-5)
            'kesan' => 'required|string|max:500', // Maksimal 500 karakter
        ]);

        $user = auth()->user();

        Feedback::create([
            'user_id' => $user->id,
            'rating' => $request->rating,
            'kesan' => $request->kesan,
        ]);

        return redirect('/dashboard-feedback')->with('success', 'Feedback berhasil disimpan.');
    }

    /**
     * Tampilkan detail feedback tertentu.
     */
    public function show(Feedback $feedback)
    {
        $this->authorize('view', $feedback); // Pastikan hanya pemilik yang bisa melihat

        return view('dashboard.feedback-show', compact('feedback'));
    }

    /**
     * Tampilkan form edit feedback.
     */
    public function edit(Feedback $feedback)
    {
        $this->authorize('update', $feedback); // Pastikan hanya pemilik yang bisa mengedit

        return view('dashboard.feedback-edit', compact('feedback'));
    }

    /**
     * Perbarui feedback di database.
     */
    public function update(Request $request, Feedback $feedback)
    {
        $this->authorize('update', $feedback);

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'kesan' => 'required|string|max:500',
        ]);

        $feedback->update([
            'rating' => $request->rating,
            'kesan' => $request->kesan,
        ]);

        return redirect('/dashboard-feedback')->with('success', 'Feedback berhasil diperbarui.');
    }

    /**
     * Hapus feedback dari database.
     */
    public function destroy(Feedback $feedback)
    {
        $this->authorize('delete', $feedback);

        $feedback->delete();

        return redirect('/dashboard-feedback')->with('success', 'Feedback berhasil dihapus.');
    }
}
