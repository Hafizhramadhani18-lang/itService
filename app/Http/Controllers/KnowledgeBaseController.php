<?php

namespace App\Http\Controllers;

use App\Models\DeviceCategory;
use App\Models\KnowledgeBase;
use App\Models\Location;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KnowledgeBaseController extends Controller
{
    /**
     * Tampilkan katalog Basis Pengetahuan & SOP Troubleshooting
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = KnowledgeBase::query();

        // Jika Pegawai, hanya tampilkan panduan yang diizinkan untuk dibaca pegawai ('all'/'pegawai') dan sudah Terbit
        if ($user && $user->isPegawai()) {
            $query->whereIn('target_audience', ['all', 'pegawai'])->where('status', 'Terbit');
        } elseif ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Default untuk teknisi: tampilkan semua atau yang bukan draft tersembunyi
        }

        // Filter Kategori
        if ($request->filled('kategori') && $request->kategori !== 'all' && $request->kategori !== 'semua') {
            $query->where('kategori', 'like', "%{$request->kategori}%");
        }

        // Filter Audience (Khusus Teknisi: Semua, Teknisi saja, Dibaca pegawai)
        if ($request->filled('audience')) {
            if ($request->audience === 'teknisi') {
                $query->where('target_audience', 'teknisi');
            } elseif ($request->audience === 'pegawai') {
                $query->where('target_audience', 'all');
            }
        }

        // Pencarian Teks
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                  ->orWhere('pengantar', 'like', "%{$search}%")
                  ->orWhere('area_penerapan', 'like', "%{$search}%")
                  ->orWhere('tag', 'like', "%{$search}%")
                  ->orWhere('perintah_cmd', 'like', "%{$search}%");
            });
        }

        $guides = $query->latest()->get();

        // Kategori dinamis dari database (disesuaikan scope akses user)
        $categoriesQuery = KnowledgeBase::select('kategori')
            ->distinct()
            ->whereNotNull('kategori')
            ->where('kategori', '!=', '');
        if ($user && $user->isPegawai()) {
            $categoriesQuery->whereIn('target_audience', ['all', 'pegawai'])
                ->where('status', 'Terbit');
        }
        $categories = $categoriesQuery->orderBy('kategori')->pluck('kategori');

        // Kategori count untuk badges
        $counts = [
            'total' => KnowledgeBase::count(),
            'jaringan' => KnowledgeBase::where('kategori', 'like', '%Jaringan%')->count(),
            'hardware' => KnowledgeBase::where('kategori', 'like', '%Hardware%')->count(),
            'sistem' => KnowledgeBase::where('kategori', 'like', '%Sistem%')->count(),
        ];

        return view('pages.knowledge-base', compact('guides', 'counts', 'categories'));
    }

    /**
     * Tampilkan halaman Editor Panduan Baru
     */
    public function create()
    {
        if (Auth::user()->isPegawai()) {
            abort(403, 'Akses terbatas untuk personil IT Support.');
        }

        $finishedTickets = Service::where('status', 'Selesai')
            ->latest('tanggal_selesai')
            ->take(15)
            ->get();

        $deviceCategories = DeviceCategory::where('is_active', true)->orderBy('nama')->get();
        $masterLocations = Location::where('is_active', true)->orderBy('nama')->get();

        return view('pages.knowledge-base-editor', compact('finishedTickets', 'deviceCategories', 'masterLocations'));
    }

    /**
     * Tampilkan halaman Edit Panduan SOP
     */
    public function edit(KnowledgeBase $knowledgeBase)
    {
        if (Auth::user()->isPegawai()) {
            abort(403, 'Akses terbatas untuk personil IT Support.');
        }

        $finishedTickets = Service::where('status', 'Selesai')
            ->latest('tanggal_selesai')
            ->take(15)
            ->get();

        $deviceCategories = DeviceCategory::where('is_active', true)->orderBy('nama')->get();
        $masterLocations = Location::where('is_active', true)->orderBy('nama')->get();

        return view('pages.knowledge-base-editor', [
            'finishedTickets' => $finishedTickets,
            'guide' => $knowledgeBase,
            'deviceCategories' => $deviceCategories,
            'masterLocations' => $masterLocations,
        ]);
    }

    /**
     * Simpan panduan SOP baru ke database
     */
    public function store(Request $request)
    {
        if (Auth::user()->isPegawai()) {
            abort(403, 'Akses terbatas untuk personil IT Support.');
        }

        $validated = $this->validateAndSanitize($request);
        $validated['user_id'] = Auth::id();

        $guide = KnowledgeBase::create($validated);

        $msg = $guide->status === 'Draft' 
            ? 'Draft panduan "' . $guide->judul . '" berhasil disimpan.'
            : 'Panduan SOP "' . $guide->judul . '" berhasil diterbitkan' . ($guide->isForEveryone() ? ' untuk Teknisi dan Pegawai.' : ' untuk internal Teknisi.');

        return redirect()->route('knowledge.base')->with('success', $msg);
    }

    /**
     * Perbarui panduan SOP yang sudah ada
     */
    public function update(Request $request, KnowledgeBase $knowledgeBase)
    {
        if (Auth::user()->isPegawai()) {
            abort(403, 'Akses terbatas untuk personil IT Support.');
        }

        $validated = $this->validateAndSanitize($request);
        $knowledgeBase->update($validated);

        $msg = $knowledgeBase->status === 'Draft'
            ? 'Draft panduan "' . $knowledgeBase->judul . '" berhasil diperbarui.'
            : 'Panduan SOP "' . $knowledgeBase->judul . '" berhasil diperbarui.';

        return redirect()->route('knowledge.base')->with('success', $msg);
    }

    /**
     * Hapus panduan SOP
     */
    public function destroy(KnowledgeBase $knowledgeBase)
    {
        if (Auth::user()->isPegawai()) {
            abort(403, 'Akses terbatas untuk personil IT Support.');
        }

        $title = $knowledgeBase->judul;
        $knowledgeBase->delete();

        return redirect()->route('knowledge.base')->with('success', 'Panduan SOP "' . $title . '" berhasil dihapus.');
    }

    /**
     * Validasi & sanitasi input editor SOP untuk store dan update.
     */
    private function validateAndSanitize(Request $request): array
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'kategori' => 'required|string|max:100',
            'area_penerapan' => 'nullable|string|max:255',
            'target_audience' => 'required|in:teknisi,all',
            'pengantar' => 'nullable|string',
            'langkah_langkah' => 'nullable|array',
            'langkah_langkah.*' => 'nullable|string',
            'judul_perintah' => 'nullable|string|max:255',
            'perintah_cmd' => 'nullable|string',
            'peringatan' => 'nullable|string',
            'tag' => 'nullable|string|max:255',
            'perlu_izin_operator' => 'nullable|boolean',
            'service_id' => 'nullable|exists:services,id',
            'status' => 'required|in:Draft,Terbit',
        ]);

        $validated['langkah_langkah'] = !empty($validated['langkah_langkah'])
            ? array_values(array_filter(
                array_map('trim', $validated['langkah_langkah']),
                fn ($item) => $item !== ''
            ))
            : [];

        $validated['perlu_izin_operator'] = $request->boolean('perlu_izin_operator');

        return $validated;
    }
}

