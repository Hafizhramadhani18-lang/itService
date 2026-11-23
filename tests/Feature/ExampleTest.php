<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_page_loads_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('PT Sumber Segara Primadaya');
    }

    public function test_teknisi_login_shows_slim_flash_alert_under_header(): void
    {
        $response = $this->post('/login', [
            'email' => 'it.support@s2p.co.id',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        
        $dashboardResponse = $this->followRedirects($response);
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('flash-success-alert');
        $dashboardResponse->assertSee('Berhasil masuk sebagai Tim IT Support');
        $dashboardResponse->assertSee('dismissFlashAlert');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_teknisi_can_access_dashboard(): void
    {
        $teknisi = User::where('role', 'teknisi')->first();
        $response = $this->actingAs($teknisi)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Dashboard Teknisi');
        // Pastikan badge antrian di sidebar muncul di dashboard
        $response->assertSee('tiket belum diambil teknisi');
    }

    public function test_pegawai_can_access_dashboard(): void
    {
        $pegawai = User::where('role', 'pegawai')->first();
        $response = $this->actingAs($pegawai)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Beranda');
        $response->assertSee('Tiket aktif');
        $response->assertSee('Perlu konfirmasi');
        $response->assertSee('Selesai bulan ini');
        $response->assertSee('Cari solusi mandiri');
        $response->assertSee('<i class="fa-solid fa-plus text-xs"></i>', false);
        $response->assertSee('Buat Tiket Baru');
    }

    public function test_services_index_loads(): void
    {
        $teknisi = User::where('role', 'teknisi')->first();
        $response = $this->actingAs($teknisi)->get('/services');
        $response->assertStatus(200);
        $response->assertSee('Antrian Tiket');
    }

    public function test_teknisi_can_access_tiket_saya_with_metrics_and_progress(): void
    {
        $teknisi = User::where('role', 'teknisi')->first();
        
        // 1. Default Tiket Saya: Menampilkan khusus tugas aktif teknisi yang login (Hafizh)
        $response = $this->actingAs($teknisi)->get('/services?filter=my');
        $response->assertStatus(200);
        $response->assertSee('Tiket Saya');
        // Dropdown nama teknisi sudah dihapus untuk role Teknisi di menu Tiket Saya agar tidak tertukar/salah pilih
        $response->assertDontSee('-- Semua Teknisi Bertugas --');
        $response->assertSee('Total Ditangani');
        $response->assertSee('Sedang Dikerjakan');
        $response->assertSee('Perbaikan Selesai');
        $response->assertSee('Teknisi Bertugas');
        $response->assertSee('Progres Checklist');
        $response->assertSee('Sedang menangani');
        // Hanya tiket Hafizh (TKT-0139) yang tampil di default Tiket Saya, tiket teknisi lain (Bagas TKT-0131) tidak tampil
        $response->assertSee('TKT-0139');
        $this->assertFalse($response->viewData('services')->contains('ticket_number', 'TKT-0131'));
        preg_match('/<table\b.*?<\/table>/s', $response->getContent(), $defaultTableMatch);
        $this->assertStringNotContainsString('TKT-0131', $defaultTableMatch[0] ?? '');

        // 2. Uji filter teknisi 'semua' di Tiket Saya
        $resSemua = $this->actingAs($teknisi)->get('/services?filter=my&teknisi=semua');
        $resSemua->assertStatus(200);
        $resSemua->assertSee('Seluruh Tugas Aktif Tim Teknisi');
        $resSemua->assertSee('TKT-0139');
        $resSemua->assertSee('TKT-0131');

        // 3. Uji filter teknisi spesifik (Hafizh) - hanya tiket Hafizh yang tampil
        $resHafizh = $this->actingAs($teknisi)->get('/services?filter=my&teknisi=Hafizh');
        $resHafizh->assertStatus(200);
        $resHafizh->assertSee('Tiket Penanganan Hafizh');
        $resHafizh->assertSee('Penugasan servis Hafizh');
        $resHafizh->assertSee('TKT-0139');
        $this->assertFalse($resHafizh->viewData('services')->contains('ticket_number', 'TKT-0131'));
        preg_match('/<table\b.*?<\/table>/s', $resHafizh->getContent(), $hafizhTableMatch);
        $this->assertStringNotContainsString('TKT-0131', $hafizhTableMatch[0] ?? '');

        // 4. Uji filter teknisi spesifik (Bagas) - hanya tiket Bagas yang tampil
        $resBagas = $this->actingAs($teknisi)->get('/services?filter=my&teknisi=Bagas');
        $resBagas->assertStatus(200);
        $resBagas->assertSee('Tiket Penanganan Bagas');
        $resBagas->assertSee('Penugasan servis Bagas');
        $resBagas->assertSee('TKT-0131');
        $this->assertFalse($resBagas->viewData('services')->contains('ticket_number', 'TKT-0139'));
        preg_match('/<table\b.*?<\/table>/s', $resBagas->getContent(), $bagasTableMatch);
        $this->assertStringNotContainsString('TKT-0139', $bagasTableMatch[0] ?? '');

        // 5. Uji isolasi hak buka tiket: Hafizh hanya dapat membuka tiket miliknya sendiri, tiket teknisi lain tidak memiliki aksi buka & di-block 403
        $tktHafizh = Service::where('ticket_number', 'TKT-0139')->first();
        $tktBagas = Service::where('ticket_number', 'TKT-0131')->first();
        $bagasUser = User::where('name', 'Bagas Aditya')->first();

        // Hafizh membuka tiket miliknya (TKT-0139) -> Sukses 200
        $this->actingAs($teknisi)->get("/services/{$tktHafizh->id}")->assertStatus(200);

        // Hafizh mencoba membuka tiket Bagas (TKT-0131) -> Ditolak 403
        $this->actingAs($teknisi)->get("/services/{$tktBagas->id}")->assertStatus(403);

        // Sebaliknya, Bagas membuka tiket miliknya (TKT-0131) -> Sukses 200
        $this->actingAs($bagasUser)->get("/services/{$tktBagas->id}")->assertStatus(200);

        // Bagas mencoba membuka tiket Hafizh (TKT-0139) -> Ditolak 403
        $this->actingAs($bagasUser)->get("/services/{$tktHafizh->id}")->assertStatus(403);
    }

    public function test_pegawai_can_create_service_ticket(): void
    {
        $pegawai = User::where('email', 'operasi@s2p.co.id')->first();
        
        $response = $this->actingAs($pegawai)->post('/services', [
            'lokasi_ruangan' => 'Gedung Boiler Lantai 3',
            'jenis_perangkat' => 'Kabel LAN / Jaringan',
            'kode_perangkat' => 'LAN-BLR-09',
            'prioritas' => 'Mendesak',
            'keluhan' => 'Konektor RJ45 patah penguncinya dan kabel sering lepas saat tersenggol.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('services', [
            'nama_pelapor' => $pegawai->name,
            'lokasi_ruangan' => 'Gedung Boiler Lantai 3',
            'status' => 'Baru',
        ]);
    }

    public function test_teknisi_can_update_service_and_finish_it(): void
    {
        $teknisi = User::where('role', 'teknisi')->first();
        $service = Service::where('status', 'Baru')->first();

        $response = $this->actingAs($teknisi)->put("/services/{$service->id}", [
            'status' => 'Selesai',
            'teknisi_nama' => 'Hafizh',
            'prioritas' => 'Sedang',
            'tindakan_perbaikan' => 'Krimping ulang konektor RJ-45 Cat6 baru dengan crimping tool, test pin 1-8 menyala.',
            'sparepart' => '1x Konektor RJ-45 Cat6',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'status' => 'Selesai',
            'teknisi_nama' => 'Hafizh',
            'sparepart' => '1x Konektor RJ-45 Cat6',
        ]);
    }

    public function test_teknisi_can_toggle_checklist_item(): void
    {
        $teknisi = User::where('role', 'teknisi')->first();
        $service = Service::whereNotNull('checklist')->first();

        $response = $this->actingAs($teknisi)->post("/services/{$service->id}/checklist", [
            'index' => 0,
        ]);

        $response->assertRedirect();
    }

    public function test_teknisi_can_access_cetak_rekap(): void
    {
        $teknisi = User::where('role', 'teknisi')->first();
        $response = $this->actingAs($teknisi)->get('/services/cetak');
        $response->assertStatus(200);
        $response->assertSee('PT SUMBER SEGARA PRIMADAYA');
        $response->assertSee('PLTU CILACAP');
    }

    public function test_teknisi_can_export_excel(): void
    {
        $teknisi = User::where('role', 'teknisi')->first();
        $response = $this->actingAs($teknisi)->get('/services/export');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_claim_ticket_removes_from_antrian_and_moves_to_tiket_saya(): void
    {
        $teknisi = User::where('role', 'teknisi')->first();
        $unassignedTicket = Service::where('status', 'Baru')->whereNull('teknisi_id')->first();

        // 1. Sebelum diambil: tiket muncul di Antrian Tiket
        $responseQueueBefore = $this->actingAs($teknisi)->get('/services');
        $responseQueueBefore->assertSee($unassignedTicket->ticket_number);

        // 2. Teknisi klaim tiket
        $claimResponse = $this->actingAs($teknisi)->post("/services/{$unassignedTicket->id}/claim");
        $claimResponse->assertRedirect();
        $claimResponse->assertSessionHas('success');

        // 3. Setelah diambil: tiket HILANG dari Antrian Tiket
        $this->flushSession();
        $responseQueueAfter = $this->actingAs($teknisi)->get('/services');
        $responseQueueAfter->assertStatus(200);
        $this->assertFalse($responseQueueAfter->viewData('services')->contains('ticket_number', $unassignedTicket->ticket_number));
        preg_match('/<table\b.*?<\/table>/s', $responseQueueAfter->getContent(), $queueTableMatch);
        $this->assertStringNotContainsString($unassignedTicket->ticket_number, $queueTableMatch[0] ?? '');

        // 4. Tiket sekarang MUNCUL di Tiket Saya
        $responseMyTickets = $this->actingAs($teknisi)->get('/services?filter=my');
        $responseMyTickets->assertSee($unassignedTicket->ticket_number);
    }

    public function test_claim_ticket_assigned_to_specific_technician(): void
    {
        $teknisi = User::where('role', 'teknisi')->first();
        $unassignedTicket = Service::where('status', 'Baru')->whereNull('teknisi_id')->first();

        $response = $this->actingAs($teknisi)->post("/services/{$unassignedTicket->id}/claim", [
            'teknisi_nama' => 'Hendra Wijaya, S.T.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('services', [
            'id' => $unassignedTicket->id,
            'teknisi_nama' => 'Hendra Wijaya, S.T.',
            'status' => 'Diproses',
        ]);
    }

    public function test_teknisi_can_claim_ticket_as_team_collaboration(): void
    {
        $hafizh = User::where('email', 'hafizh@s2p.co.id')->firstOrFail();
        $bagas = User::where('email', 'bagas@s2p.co.id')->firstOrFail();
        $pegawai = User::where('role', 'pegawai')->firstOrFail();

        // Buat tiket baru belum diambil
        $teamTicket = Service::create([
            'ticket_number' => 'TKT-TEAM-01',
            'user_id' => $pegawai->id,
            'nama_pelapor' => $pegawai->name,
            'divisi' => 'Operasi Boiler',
            'lokasi_ruangan' => 'Control Room',
            'jenis_perangkat' => 'Jaringan LAN',
            'keluhan' => 'Tarik kabel jaringan 4 titik di Control Room bersama tim',
            'prioritas' => 'Sedang',
            'status' => 'Baru',
        ]);

        // 1. Pada antrian tiket (/services), tombol Tim & modal team claim tampil
        $resQueue = $this->actingAs($hafizh)->get('/services');
        $resQueue->assertStatus(200);
        $resQueue->assertSee('TKT-TEAM-01');
        $resQueue->assertSee('modal-team-claim', false);
        $resQueue->assertSee('Ambil sebagai Kerja Tim (Multi-Teknisi)', false);

        // 2. Pada halaman detail (/services/{id}), tombol Kerja Tim juga tampil
        $resShow = $this->actingAs($hafizh)->get("/services/{$teamTicket->id}");
        $resShow->assertStatus(200);
        $resShow->assertSee('Kerja Tim');
        $resShow->assertSee('modal-team-claim', false);

        // 3. Teknisi (Hafizh) mengambil tiket bersama tim (Bagas sebagai rekan pendamping)
        $resClaim = $this->actingAs($hafizh)->post("/services/{$teamTicket->id}/claim", [
            'teknisi_id' => $hafizh->id,
            'teknisi_pendamping' => [$bagas->id],
            'catatan_penugasan' => 'Dikerjakan bersama Bagas mulai jam 09.00 WIB',
        ]);
        $resClaim->assertRedirect();
        $resClaim->assertSessionHas('success');

        // 4. Verifikasi di database: berstatus Diproses, teknisi_id = Hafizh, teknisi_pendamping berisi Bagas
        $teamTicket->refresh();
        $this->assertSame('Diproses', $teamTicket->status);
        $this->assertEquals($hafizh->id, $teamTicket->teknisi_id);
        $this->assertTrue($teamTicket->is_team);
        $this->assertNotEmpty($teamTicket->teknisi_pendamping);

        // 5. Tiket muncul di menu "Tiket Saya" (/services?filter=my) baik untuk Hafizh maupun Bagas
        $resMyHafizh = $this->actingAs($hafizh)->get('/services?filter=my');
        $resMyHafizh->assertStatus(200);
        $resMyHafizh->assertSee('TKT-TEAM-01');

        $resMyBagas = $this->actingAs($bagas)->get('/services?filter=my');
        $resMyBagas->assertStatus(200);
        $resMyBagas->assertSee('TKT-TEAM-01');
    }

    public function test_teknisi_can_access_riwayat_tiket(): void
    {
        $teknisi = User::where('role', 'teknisi')->first();
        $response = $this->actingAs($teknisi)->get('/services?filter=history');
        $response->assertStatus(200);
        $response->assertSee('Riwayat Tiket Selesai');
        $response->assertSee('Total Tiket Tuntas');
        // Saat default: Card 2 menampilkan Selesai Bulan Ini (Universal SOP)
        $response->assertSee('Selesai Bulan Ini');

        // Harus menampilkan tiket dari ketiga teknisi (Hafizh, Hendra, Bagas)
        $response->assertSee('Hafizh');
        $response->assertSee('Hendra Wijaya, S.T.');
        $response->assertSee('Bagas Aditya');

        // Uji filter teknisi spesifik Hendra (Card 2 dinamis menampilkan Diselesaikan Hendra)
        $responseHendra = $this->actingAs($teknisi)->get('/services?filter=history&teknisi=Hendra');
        $responseHendra->assertStatus(200);
        $responseHendra->assertSee('TKT-0120');
        $responseHendra->assertSee('Diselesaikan Hendra');
        $this->assertFalse($responseHendra->viewData('services')->contains('ticket_number', 'TKT-0125')); // Tiket Hafizh tidak boleh muncul di tabel saat filter Hendra
        preg_match('/<table\b.*?<\/table>/s', $responseHendra->getContent(), $hendraTableMatch);
        $this->assertStringNotContainsString('TKT-0125', $hendraTableMatch[0] ?? '');

        // Uji filter teknisi spesifik Hafizh (Card 2 dinamis menampilkan Diselesaikan Hafizh)
        $responseHafizh = $this->actingAs($teknisi)->get('/services?filter=history&teknisi=Hafizh');
        $responseHafizh->assertStatus(200);
        $responseHafizh->assertSee('TKT-0125');
        $responseHafizh->assertSee('Diselesaikan Hafizh');

        // Pastikan dropdown Tahun, Bulan, Minggu, Prioritas & Cari tetep ada di Riwayat Tiket untuk memantau data
        $response->assertSee('Tahun');
        $response->assertSee('Bulan');
        $response->assertSee('Minggu 1');
        $response->assertSee('Minggu 4');
        $response->assertDontSee('Minggu 5');
        $response->assertSee('-- Prioritas --');

        // Pastikan tombol Ekspor Excel & Cetak Laporan serta Filter Cepat periode tidak ada di Riwayat Tiket (karena khusus Laporan Teknisi)
        $response->assertDontSee('Ekspor Excel');
        $response->assertDontSee('Cetak Laporan');
        $response->assertDontSee('Filter Cepat:');
    }

    public function test_history_period_filters_and_excel_export(): void
    {
        $teknisi = User::where('role', 'teknisi')->first();

        // 1. Laporan Teknisi / Laporan Saya (Menu Khusus Pelaporan & Dokumen di Sidebar dengan Filter Cepat)
        $resReport = $this->actingAs($teknisi)->get('/services?filter=report&quick=bulan_ini');
        $resReport->assertStatus(200);
        $resReport->assertSee('Laporan Saya');
        $resReport->assertSee('Hanya milik Anda');
        $resReport->assertSee('Tiket selesai');
        $resReport->assertSee('Laporan draft');
        $resReport->assertSee('Rata-rata waktu saya');
        $resReport->assertSee('Daftar laporan saya');
        $resReport->assertSee('Laporan Teknisi');
        $resReport->assertSee('Periode');
        $resReport->assertSee('Tahun');
        $resReport->assertSee('Bulan');
        $resReport->assertSee('Minggu');
        $resReport->assertSee('Ekspor Excel');
        $resReport->assertSee('Cetak / PDF');
        $resReport->assertSee('Cetak Laporan');
        $resReport->assertSee('Pelaporan & Dokumen', false);

        // 2. Filter Spesifik Tahun, Bulan & Minggu pada Laporan
        $year = now()->year;
        $month = now()->month;
        $resCustom = $this->actingAs($teknisi)->get("/services?filter=report&tahun={$year}&bulan={$month}&minggu=1");
        $resCustom->assertStatus(200);

        // 3. Unduh Ekspor Excel Laporan Teknisi
        $resExport = $this->actingAs($teknisi)->get("/services/export?filter=report&tahun={$year}&bulan={$month}");
        $resExport->assertStatus(200);
        $resExport->assertHeader('content-type', 'text/csv; charset=UTF-8');

        // 4. Cetak Laporan Format Resmi PT S2P
        $resCetak = $this->actingAs($teknisi)->get('/services/cetak?filter=report&quick=bulan_ini');
        $resCetak->assertStatus(200);
        $resCetak->assertSee('LAPORAN REKAPITULASI KINERJA & SERVIS TEKNISI IT', false);
    }

    public function test_completed_tickets_automatically_move_from_tiket_saya_to_riwayat_tiket(): void
    {
        $teknisi = User::where('role', 'teknisi')->first();
        
        // 1. Tiket aktif (TKT-0142) ada di Tiket Saya
        $activeTicket = Service::where('status', 'Diproses')->where('teknisi_nama', 'like', '%Hafizh%')->first();
        $this->assertNotNull($activeTicket);

        $responseMy = $this->actingAs($teknisi)->get('/services?filter=my');
        $responseMy->assertStatus(200);
        $responseMy->assertSee($activeTicket->ticket_number);

        // Tiket yang berstatus Selesai (seperti TKT-0125) TIDAK boleh muncul di tabel Tiket Saya
        $completedTicket = Service::where('status', 'Selesai')->where('teknisi_nama', 'like', '%Hafizh%')->first();
        $this->assertNotNull($completedTicket);
        $this->assertFalse($responseMy->viewData('services')->contains('ticket_number', $completedTicket->ticket_number));
        preg_match('/<table\b.*?<\/table>/s', $responseMy->getContent(), $myBeforeTableMatch);
        $this->assertStringNotContainsString($completedTicket->ticket_number, $myBeforeTableMatch[0] ?? '');

        // Tiket selesai HARUS muncul di Riwayat Tiket
        $responseHistory = $this->actingAs($teknisi)->get('/services?filter=history');
        $responseHistory->assertStatus(200);
        $responseHistory->assertSee($completedTicket->ticket_number);

        // 2. Tandai tiket aktif (TKT-0142) sebagai Selesai
        $doneResponse = $this->actingAs($teknisi)->post("/services/{$activeTicket->id}/done");
        $doneResponse->assertRedirect();

        // 3. Setelah selesai: tiket aktif tersebut HILANG dari tabel Tiket Saya
        $this->flushSession();
        $responseMyAfter = $this->actingAs($teknisi)->get('/services?filter=my');
        $responseMyAfter->assertStatus(200);
        $this->assertFalse($responseMyAfter->viewData('services')->contains('ticket_number', $activeTicket->ticket_number));
        preg_match('/<table\b.*?<\/table>/s', $responseMyAfter->getContent(), $myAfterTableMatch);
        $this->assertStringNotContainsString($activeTicket->ticket_number, $myAfterTableMatch[0] ?? '');

        // 4. Tiket tersebut sekarang MUNCUL di Riwayat Tiket
        $responseHistoryAfter = $this->actingAs($teknisi)->get('/services?filter=history');
        $responseHistoryAfter->assertSee($activeTicket->ticket_number);
    }

    public function test_notification_badge_decrements_when_viewed_and_ticket_stays_in_antrian(): void
    {
        $teknisi = User::where('role', 'teknisi')->first();

        // 1. Awalnya ada 3 tiket baru belum dibaca
        $newTickets = Service::where('status', 'Baru')->where('is_read', false)->get();
        $this->assertCount(3, $newTickets);

        $responseDash = $this->actingAs($teknisi)->get('/dashboard');
        $responseDash->assertStatus(200);
        $responseDash->assertSee('Notifikasi');
        $responseDash->assertSee('3 baru');

        // 2. Teknisi mengklik 1 tiket untuk melihat detailnya
        $firstTicket = $newTickets->first();
        $responseShow = $this->actingAs($teknisi)->get("/services/{$firstTicket->id}");
        $responseShow->assertStatus(200);

        // Tiket tersebut sekarang sudah is_read = true
        $this->assertTrue($firstTicket->fresh()->is_read);

        // 3. Kembali ke dashboard: sisa notifikasi yang belum dibaca sekarang tinggal 2!
        $responseDash2 = $this->actingAs($teknisi)->get('/dashboard');
        $responseDash2->assertStatus(200);
        $responseDash2->assertSee('2 baru');
        // Status notifikasi tiket tersebut pada dropdown kini sudah terbaca (is_unread = false)
        $notifList2 = collect($responseDash2->getOriginalContent()->getData()['headerNotifications']);
        $viewedItem = $notifList2->firstWhere('service_id', $firstTicket->id);
        $this->assertNotNull($viewedItem);
        $this->assertFalse($viewedItem['is_unread']);

        // 4. Namun di Antrian Tiket (/services), tiket tersebut TETAP ADA karena belum diambil teknisi
        $responseQueue = $this->actingAs($teknisi)->get('/services');
        $responseQueue->assertStatus(200);
        $responseQueue->assertSee($firstTicket->ticket_number);

        // 5. Uji tombol "Tandai semua dibaca"
        $responseMarkAll = $this->actingAs($teknisi)->post('/notifications/mark-all-read');
        $responseMarkAll->assertRedirect();

        // Sisa notifikasi belum dibaca sekarang 0
        $this->assertEquals(0, Service::where('status', 'Baru')->where('is_read', false)->count());

        // Di dashboard, badge notifikasi merah sudah hilang dan teks menjadi "Semua terbaca"
        $this->flushSession();
        $responseDash3 = $this->actingAs($teknisi)->get('/dashboard');
        $responseDash3->assertStatus(200);
        $responseDash3->assertSee('Semua terbaca');
        $responseDash3->assertDontSee('3 baru');
        $responseDash3->assertDontSee('2 baru');

        // Tiket tetap ada di antrian tiket!
        $responseQueue2 = $this->actingAs($teknisi)->get('/services');
        $responseQueue2->assertSee($firstTicket->ticket_number);
    }

    public function test_pegawai_tiket_saya_and_status_filtering(): void
    {
        $pegawai = User::where('role', 'pegawai')->first();
        $response = $this->actingAs($pegawai)->get('/services');
        $response->assertStatus(200);
        $response->assertSee('Tiket Saya');
        $response->assertSee('Pantau status laporan Anda');
        $response->assertSee('Semua');
        $response->assertSee('Aktif');
        $response->assertSee('Selesai');

        // Uji filter aktif
        $resAktif = $this->actingAs($pegawai)->get('/services?status=aktif');
        $resAktif->assertStatus(200);

        // Uji filter selesai
        $resSelesai = $this->actingAs($pegawai)->get('/services?status=Selesai');
        $resSelesai->assertStatus(200);
    }

    public function test_pegawai_create_ticket_with_wireframe_fields(): void
    {
        $pegawai = User::where('role', 'pegawai')->first();

        // Verifikasi halaman form Buat Tiket memuat dropdown/listbox kategori
        $resCreate = $this->actingAs($pegawai)->get('/services/create');
        $resCreate->assertStatus(200);
        $resCreate->assertSee('select name="jenis_perangkat"', false);
        $resCreate->assertSee('Jaringan &amp; Kabel LAN / Switch', false);
        $resCreate->assertSee('Printer &amp; Scanner', false);
        $resCreate->assertSee('Hardware &amp; Komputer PC', false);
        $resCreate->assertSee('Server CCTV &amp; NVR Keamanan', false);

        $response = $this->actingAs($pegawai)->post('/services', [
            'jenis_perangkat' => 'Printer',
            'judul_kendala' => 'Printer tidak mencetak',
            'lokasi_ruangan' => 'Control Room Unit 1',
            'deskripsi' => 'Printer offline setelah update Windows, antrian cetak menumpuk.',
            'prioritas' => 'Sedang',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('services', [
            'user_id' => $pegawai->id,
            'jenis_perangkat' => 'Printer',
            'judul_kendala' => 'Printer tidak mencetak',
            'lokasi_ruangan' => 'Control Room Unit 1',
            'prioritas' => 'Sedang',
            'status' => 'Baru',
        ]);
    }

    public function test_pegawai_ticket_detail_progress_and_commenting(): void
    {
        $pegawai = User::where('role', 'pegawai')->first();
        $ticket = Service::where('user_id', $pegawai->id)->first();

        // 1. Buka halaman detail tiket pegawai
        $response = $this->actingAs($pegawai)->get('/services/' . $ticket->id);
        $response->assertStatus(200);
        $response->assertSee('Progres');
        $response->assertSee('Tiket dikirim');
        $response->assertSee('Sedang dicek');
        $response->assertSee('Komentar');

        // 2. Kirim komentar dari pegawai
        $resComment = $this->actingAs($pegawai)->post('/services/' . $ticket->id . '/comment', [
            'message' => 'Mohon dibantu segera mas teknisi, dokumen laporan penting.',
        ]);
        $resComment->assertRedirect();

        $this->assertDatabaseHas('service_comments', [
            'service_id' => $ticket->id,
            'user_id' => $pegawai->id,
            'author_role' => 'pegawai',
            'message' => 'Mohon dibantu segera mas teknisi, dokumen laporan penting.',
        ]);

        // 3. Pastikan komentar muncul di detail tiket
        $responseAfter = $this->actingAs($pegawai)->get('/services/' . $ticket->id);
        $responseAfter->assertSee('Mohon dibantu segera mas teknisi, dokumen laporan penting.');
    }

    public function test_login_page_renders_wireframe_elements_and_demo_buttons(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Layanan IT terpadu untuk PLTU Karangkandri');
        $response->assertSee('Email');
        $response->assertSee('Masukkan email dan password Anda untuk mengakses layanan IT');
        $response->assertSee('Kata sandi');
        $response->assertSee('Tampilkan');
        $response->assertSee('Ingat saya');
        $response->assertSee('Lupa kata sandi?');
        $response->assertSee('Masuk');
        $response->assertSee('Masuk Tim IT');
        $response->assertSee('Masuk Rina (Pegawai)');
        $response->assertSee('Belum punya akun? Hubungi IT Support divisi Anda.');
        $response->assertSee('PLTU Karangkandri');
    }

    public function test_login_with_username_nip_without_domain(): void
    {
        $response = $this->post('/login', [
            'login' => 'operasi',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_header_widget_date_and_notification_bell_renders_across_teknisi_pages(): void
    {
        $teknisi = User::factory()->create([
            'role' => 'teknisi',
            'name' => 'Hafizh',
        ]);

        // 1. Antrian Tiket
        $resAntrian = $this->actingAs($teknisi)->get('/services');
        $resAntrian->assertStatus(200);
        $resAntrian->assertSee('Notifikasi');
        $resAntrian->assertSee('Tandai semua dibaca');
        $resAntrian->assertSee('Lihat semua notifikasi');
        $resAntrian->assertSee('fa-regular fa-calendar', false);
        $resAntrian->assertSee('fa-regular fa-bell', false);

        // 2. Tiket Saya
        $resMy = $this->actingAs($teknisi)->get('/services?filter=my');
        $resMy->assertStatus(200);
        $resMy->assertSee('Notifikasi');
        $resMy->assertSee('fa-regular fa-bell', false);

        // 3. Riwayat Tiket
        $resHistory = $this->actingAs($teknisi)->get('/services?filter=history');
        $resHistory->assertStatus(200);
        $resHistory->assertSee('Notifikasi');
        $resHistory->assertSee('fa-regular fa-bell', false);

        // 4. Dashboard Teknisi
        $resDashboard = $this->actingAs($teknisi)->get('/dashboard');
        $resDashboard->assertStatus(200);
        $resDashboard->assertSee('Notifikasi');
        $resDashboard->assertSee('fa-regular fa-bell', false);
    }

    public function test_teknisi_can_view_knowledge_base_with_sop_cards_and_filters(): void
    {
        $teknisi = User::factory()->create(['role' => 'teknisi']);
        
        \App\Models\KnowledgeBase::create([
            'judul' => 'SOP Uji Ping Gateway PLTU',
            'kategori' => 'Jaringan & LAN',
            'target_audience' => 'teknisi',
            'pengantar' => 'Lakukan ping jika ada putus jaringan',
            'status' => 'Terbit',
        ]);

        $response = $this->actingAs($teknisi)->get('/basis-pengetahuan');
        $response->assertStatus(200);
        $response->assertSee('Basis Pengetahuan & SOP Troubleshooting');
        $response->assertSee('Tulis Panduan');
        $response->assertSee('SOP Uji Ping Gateway PLTU');
        $response->assertSee('Semua SOP');
        $response->assertSee('Jaringan & LAN');
    }

    public function test_teknisi_can_open_editor_and_create_sop_guide(): void
    {
        $teknisi = User::factory()->create(['role' => 'teknisi']);

        // 1. Buka editor
        $resEditor = $this->actingAs($teknisi)->get('/basis-pengetahuan/tulis');
        $resEditor->assertStatus(200);
        $resEditor->assertSee('Tulis Panduan Baru');
        $resEditor->assertSee('Informasi Dasar');
        $resEditor->assertSee('Pratinjau Kartu');

        // 2. Simpan panduan baru
        $resStore = $this->actingAs($teknisi)->post('/basis-pengetahuan', [
            'judul' => 'SOP Kalibrasi Printer Thermal Timbangan',
            'kategori' => 'Sistem & Printer',
            'area_penerapan' => 'Weighbridge Coal Yard',
            'target_audience' => 'all', // untuk teknisi dan pegawai
            'pengantar' => 'Jika kertas thermal timbangan tidak keluar otomatis',
            'langkah_langkah' => [
                'Buka penutup roller thermal',
                'Bersihkan sensor optik kertas',
            ],
            'judul_perintah' => 'Restart Spooler',
            'perintah_cmd' => 'net stop spooler && net start spooler',
            'status' => 'Terbit',
        ]);

        $resStore->assertRedirect('/basis-pengetahuan');
        $this->assertDatabaseHas('knowledge_bases', [
            'judul' => 'SOP Kalibrasi Printer Thermal Timbangan',
            'target_audience' => 'all',
            'status' => 'Terbit',
        ]);
    }

    public function test_sop_with_target_all_appears_in_pegawai_panduan_mandiri(): void
    {
        $pegawai = User::factory()->create(['role' => 'pegawai']);

        \App\Models\KnowledgeBase::create([
            'judul' => 'Panduan Mandiri: Konek Wi-Fi Tamu PLTU',
            'kategori' => 'Jaringan & LAN',
            'target_audience' => 'all',
            'pengantar' => 'Panduan untuk karyawan yang ingin menghubungkan laptop dinas',
            'status' => 'Terbit',
        ]);

        \App\Models\KnowledgeBase::create([
            'judul' => 'SOP Rahasia Konfigurasi Switch Core',
            'kategori' => 'Jaringan & LAN',
            'target_audience' => 'teknisi', // Khusus teknisi saja
            'pengantar' => 'Konfigurasi VLAN internal',
            'status' => 'Terbit',
        ]);

        $response = $this->actingAs($pegawai)->get('/basis-pengetahuan');
        $response->assertStatus(200);
        $response->assertSee('Panduan Mandiri Pegawai');
        $response->assertSee('Panduan Mandiri: Konek Wi-Fi Tamu PLTU');
        // Pastikan panduan teknisi saja tidak muncul pada pegawai
        $response->assertDontSee('SOP Rahasia Konfigurasi Switch Core');
    }

    public function test_pegawai_cannot_open_sop_editor(): void
    {
        $pegawai = User::factory()->create(['role' => 'pegawai']);

        $response = $this->actingAs($pegawai)->get('/basis-pengetahuan/tulis');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_dashboard_with_all_metrics_and_workload(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@s2p.co.id'],
            [
                'name' => 'Hendra Wijaya, S.T.',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'divisi' => 'Koordinator IT Support',
            ]
        );

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertStatus(200);
        $response->assertSee('Dashboard Admin');
        $response->assertSee('Koordinator IT');
        $response->assertSee('Belum ditugaskan');
        $response->assertSee('Tiket aktif');
        $response->assertSee('Teknisi aktif');
        $response->assertSee('Pengguna terdaftar');
        $response->assertSee('Tiket Belum Ditugaskan');
        $response->assertSee('Beban kerja teknisi');
        $response->assertSee('Ringkasan bulan ini');

        // Pastikan route /dashboard otomatis memuat dashboard admin jika login sebagai admin
        $resDash = $this->actingAs($admin)->get('/dashboard');
        $resDash->assertStatus(200);
        $resDash->assertSee('Dashboard Admin');
    }

    public function test_admin_can_assign_unassigned_ticket_to_technician(): void
    {
        $admin = User::where('role', 'admin')->first();
        $teknisi = User::where('role', 'teknisi')->first();
        $ticket = Service::where('status', 'Baru')->whereNull('teknisi_id')->first();

        $this->assertNotNull($ticket);
        $this->assertNotNull($teknisi);

        $response = $this->actingAs($admin)->post('/admin/assign-ticket', [
            'service_id' => $ticket->id,
            'teknisi_id' => $teknisi->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('services', [
            'id' => $ticket->id,
            'teknisi_id' => $teknisi->id,
            'teknisi_nama' => $teknisi->name,
            'status' => 'Diproses',
        ]);
    }

    public function test_admin_can_access_user_management_and_reset_password(): void
    {
        $admin = User::where('role', 'admin')->first();
        $targetPegawai = User::where('role', 'pegawai')->first();

        // 1. Akses halaman Pengguna
        $resUsers = $this->actingAs($admin)->get('/admin/users');
        $resUsers->assertStatus(200);
        $resUsers->assertSee('Manajemen Pengguna');
        $resUsers->assertSee('Tambah Pengguna');
        $resUsers->assertSee('Reset Sandi');

        // 1b. Foto profil akun yang tersedia tampil di tabel Manajemen Pengguna, dan inisial Pegawai tanpa foto tetap terlihat kontras
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('avatars/test-user-management.png', 'fake-image-content');
        $targetPegawai->update([
            'foto' => 'avatars/test-user-management.png',
        ]);
        $pegawaiTanpaFoto = User::factory()->create([
            'name' => 'Zulfa Tanpa Foto',
            'role' => 'pegawai',
            'divisi' => 'Keuangan & SDM',
            'foto' => null,
        ]);

        $resFotoTarget = $this->actingAs($admin)->get('/admin/users?search=' . urlencode($targetPegawai->email));
        $resFotoTarget->assertStatus(200);
        $resFotoTarget->assertSee('storage/avatars/test-user-management.png', false);
        $resFotoTarget->assertSee('alt="Foto ' . e($targetPegawai->name) . '"', false);

        $resTanpaFoto = $this->actingAs($admin)->get('/admin/users?search=' . urlencode($pegawaiTanpaFoto->email));
        $resTanpaFoto->assertStatus(200);
        $resTanpaFoto->assertSee('bg-teal-50 text-teal-700 border border-teal-200 flex items-center justify-center', false);
        $resTanpaFoto->assertDontSee('border-teal-200 text-white', false);

        // 2. Reset Sandi pengguna
        $resReset = $this->actingAs($admin)->post("/admin/users/{$targetPegawai->id}/reset-password", [
            'new_password' => 'password123',
        ]);
        $resReset->assertRedirect();
        $resReset->assertSessionHas('success');

        // Verifikasi kata sandi baru bisa digunakan untuk login
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password123', $targetPegawai->fresh()->password));
    }

    public function test_admin_can_view_penugasan_and_kinerja_pages(): void
    {
        $admin = User::where('role', 'admin')->first();

        // 1. Halaman Penugasan Tiket
        $resPenugasan = $this->actingAs($admin)->get('/admin/penugasan');
        $resPenugasan->assertStatus(200);
        $resPenugasan->assertSee('Penugasan Tiket Masuk');

        // 2. Halaman Kinerja Teknisi (Mockup Baru: Tim Lapangan vs Tim Support)
        $resKinerja = $this->actingAs($admin)->get('/admin/kinerja');
        $resKinerja->assertStatus(200);
        $resKinerja->assertSee('Kinerja Teknisi');
        $resKinerja->assertSee('Perbandingan teknisi');
        $resKinerja->assertSee('Beban kerja saat ini');
        $resKinerja->assertSee('Tiket terakhir diselesaikan');
        $resKinerja->assertSee('Tim IT Lapangan', false);
        $resKinerja->assertSee('Tim IT Support', false);

        // 3. Halaman Data Master (Divisi, Kategori & Lokasi)
        $resKategori = $this->actingAs($admin)->get('/admin/kategori');
        $resKategori->assertStatus(200);
        $resKategori->assertSee('Data Master', false);

        // 4. Halaman Rekap Laporan Admin (Mockup Referensi Baru)
        $resRekap = $this->actingAs($admin)->get('/admin/rekap');
        $resRekap->assertStatus(200);
        $resRekap->assertSee('Rekap Laporan');
        $resRekap->assertSee('Seluruh tim');
        $resRekap->assertSee('Tiket masuk dan selesai per hari');
        $resRekap->assertSee('Daftar laporan');
        $resRekap->assertSee('Kinerja teknisi');
        $resRekap->assertSee('Tiket per divisi');
        $resRekap->assertSee('Tiket per kategori');
        $resRekap->assertSee('Ekspor Excel');
        $resRekap->assertSee('Cetak / PDF');

        // Admin Cetak / PDF Rekap Laporan Standar Perusahaan
        $resCetak = $this->actingAs($admin)->get('/services/cetak?filter=report');
        $resCetak->assertStatus(200);
        $resCetak->assertSee('Pratinjau Cetak');
        $resCetak->assertSee('Pengaturan cetak');
        $resCetak->assertSee('LAPORAN REKAPITULASI SERVIS IT');
        $resCetak->assertSee('1. Ringkasan');
        $resCetak->assertSee('2. Tiket per divisi dan kategori');
        $resCetak->assertSee('3. Daftar laporan');
    }

    public function test_pegawai_cannot_access_admin_routes(): void
    {
        $pegawai = User::where('role', 'pegawai')->first();

        $resAdmin = $this->actingAs($pegawai)->get('/admin');
        $resAdmin->assertStatus(403);

        $resUsers = $this->actingAs($pegawai)->get('/admin/users');
        $resUsers->assertStatus(403);

        $resRekap = $this->actingAs($pegawai)->get('/admin/rekap');
        $resRekap->assertStatus(403);
    }

    public function test_role_synchronization_across_admin_teknisi_pegawai(): void
    {
        $admin = User::where('role', 'admin')->first();
        $teknisi = User::where('role', 'teknisi')->first();
        $pegawai = User::where('role', 'pegawai')->first();

        // 1. Admin bisa mengakses Basis Pengetahuan lengkap dengan tombol Tulis Panduan
        $resKbAdmin = $this->actingAs($admin)->get('/basis-pengetahuan');
        $resKbAdmin->assertStatus(200);
        $resKbAdmin->assertSee('Tulis Panduan');
        $resKbAdmin->assertSee('Target:');

        // 2. Admin membuat akun teknisi baru "Dimas Saputra"
        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Dimas Saputra',
            'email' => 'dimas@s2p.co.id',
            'password' => 'password123',
            'role' => 'teknisi',
            'divisi' => 'Staff IT Jaringan',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Dimas Saputra',
            'email' => 'dimas@s2p.co.id',
            'role' => 'teknisi',
        ]);

        // 3. Pastikan teknisi baru "Dimas Saputra" otomatis muncul di daftar penugasan admin & detail tiket
        $ticket = Service::first();
        $resShow = $this->actingAs($teknisi)->get('/services/' . $ticket->id);
        $resShow->assertStatus(200);
        $resShow->assertSee('Dimas Saputra');

        $resAdminDash = $this->actingAs($admin)->get('/admin');
        $resAdminDash->assertStatus(200);
        $resAdminDash->assertSee('Dimas Saputra');
    }

    public function test_admin_can_crud_divisions_and_categories(): void
    {
        $admin = User::where('role', 'admin')->first();

        // 1. Akses halaman Data Master
        $resKategori = $this->actingAs($admin)->get('/admin/kategori');
        $resKategori->assertStatus(200);
        $resKategori->assertSee('Data Master');
        $resKategori->assertSee('Divisi');
        $resKategori->assertSee('Kategori Kendala');
        $resKategori->assertSee('Area Lokasi');

        // 2. Tambah Divisi Baru
        $resStoreDiv = $this->actingAs($admin)->post('/admin/divisi', [
            'nama' => 'Pemeliharaan Listrik & I&C',
            'keterangan' => 'Unit proteksi instrumen PLTU',
        ]);
        $resStoreDiv->assertRedirect();
        $this->assertDatabaseHas('divisions', [
            'nama' => 'Pemeliharaan Listrik & I&C',
        ]);

        $division = \App\Models\Division::where('nama', 'Pemeliharaan Listrik & I&C')->first();

        // 3. Update Divisi
        $resUpdateDiv = $this->actingAs($admin)->put('/admin/divisi/' . $division->id, [
            'nama' => 'Pemeliharaan Listrik & Instrumen',
            'keterangan' => 'Unit I&C updated',
        ]);
        $resUpdateDiv->assertRedirect();
        $this->assertDatabaseHas('divisions', [
            'nama' => 'Pemeliharaan Listrik & Instrumen',
        ]);

        // 4. Hapus Divisi
        $resDelDiv = $this->actingAs($admin)->delete('/admin/divisi/' . $division->id);
        $resDelDiv->assertRedirect();
        $this->assertDatabaseMissing('divisions', [
            'id' => $division->id,
        ]);

        // 5. Tambah Kategori Baru
        $resStoreCat = $this->actingAs($admin)->post('/admin/kategori', [
            'nama' => 'Telemetri & Sensor IoT',
            'kode' => 'IoT',
            'deskripsi' => 'Sensor suhu turbin dan water pressure',
        ]);
        $resStoreCat->assertRedirect();
        $this->assertDatabaseHas('device_categories', [
            'nama' => 'Telemetri & Sensor IoT',
            'kode' => 'IoT',
        ]);

        $category = \App\Models\DeviceCategory::where('kode', 'IoT')->first();

        // 6. Update Kategori
        $resUpdateCat = $this->actingAs($admin)->put('/admin/kategori/' . $category->id, [
            'nama' => 'Telemetri & Sensor IoT Pembangkit',
            'kode' => 'IoT',
            'deskripsi' => 'Sensor getaran dan tekanan uap',
        ]);
        $resUpdateCat->assertRedirect();
        $this->assertDatabaseHas('device_categories', [
            'nama' => 'Telemetri & Sensor IoT Pembangkit',
        ]);

        // 7. Hapus Kategori
        $resDelCat = $this->actingAs($admin)->delete('/admin/kategori/' . $category->id);
        $resDelCat->assertRedirect();
        $this->assertDatabaseMissing('device_categories', [
            'id' => $category->id,
        ]);

        // 8. Tambah Area Lokasi Baru Hanya dengan Nama (Inline Form tanpa deskripsi)
        $resStoreLoc = $this->actingAs($admin)->post('/admin/lokasi', [
            'nama' => 'Maintenance Building',
        ]);
        $resStoreLoc->assertRedirect();
        $this->assertDatabaseHas('locations', [
            'nama' => 'Maintenance Building',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_access_and_update_profile_and_password(): void
    {
        $admin = User::where('role', 'admin')->first();

        // 1. Akses halaman profil via /profil dan /admin/profil
        $resProfile = $this->actingAs($admin)->get('/profil');
        $resProfile->assertStatus(200);
        $resProfile->assertSee('Pengaturan Profil');
        $resProfile->assertSee('Kelola data akun dan kata sandi Anda');
        $resProfile->assertSee('Ubah kata sandi');
        $resProfile->assertSee('Simpan Perubahan');
        $resProfile->assertSee('Perbarui Kata Sandi');

        $resAdminProfile = $this->actingAs($admin)->get('/admin/profil');
        $resAdminProfile->assertStatus(200);
        $resAdminProfile->assertSee('Pengaturan Profil');

        // 2. Perbarui profil
        $resUpdate = $this->actingAs($admin)->put('/profil', [
            'name' => 'Admin IT S2P PLTU',
            'email' => $admin->email,
            'no_hp' => '0812-9999-8888',
            'jabatan' => 'Koordinator IT & Infrastruktur',
        ]);
        $resUpdate->assertRedirect();
        $resUpdate->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Admin IT S2P PLTU',
            'no_hp' => '0812-9999-8888',
            'jabatan' => 'Koordinator IT & Infrastruktur',
        ]);

        // 3. Ubah kata sandi dengan kata sandi saat ini yang benar
        $resPass = $this->actingAs($admin)->put('/profil/password', [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        $resPass->assertRedirect();
        $resPass->assertSessionHas('success');

        // Kembalikan password ke 'password' agar test berikutnya tidak terganggu
        $admin->refresh();
        $admin->password = \Illuminate\Support\Facades\Hash::make('password');
        $admin->name = 'Admin IT';
        $admin->save();

        // 4. Teknisi mengakses /profil: email dan jabatan terkunci oleh sistem
        $teknisi = User::where('role', 'teknisi')->first();
        $resTeknisiProfile = $this->actingAs($teknisi)->get('/profil');
        $resTeknisiProfile->assertStatus(200);
        $resTeknisiProfile->assertSee('Email resmi dikelola oleh Admin');
        $resTeknisiProfile->assertSee('Jabatan kedinasan dikelola oleh Admin');

        // Teknisi memperbarui profil mandiri (nama & no_hp diizinkan, email & jabatan tetap aman)
        $resTeknisiUpdate = $this->actingAs($teknisi)->put('/profil', [
            'name' => 'Hafizh Fauzan',
            'no_hp' => '0812-3456-7890',
        ]);
        $resTeknisiUpdate->assertRedirect();
        $resTeknisiUpdate->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $teknisi->id,
            'name' => 'Hafizh Fauzan',
            'no_hp' => '0812-3456-7890',
            'email' => $teknisi->email,
        ]);

        // 5. Pegawai mengakses /profil: email dan divisi terkunci oleh sistem
        $pegawai = User::where('role', 'pegawai')->first();
        $resPegawaiProfile = $this->actingAs($pegawai)->get('/profil');
        $resPegawaiProfile->assertStatus(200);
        $resPegawaiProfile->assertSee('Pengaturan Profil');
        $resPegawaiProfile->assertSee('Email resmi dikelola oleh Admin');
        $resPegawaiProfile->assertSee('Divisi resmi dikelola oleh Admin');

        // Pegawai memperbarui profil mandiri (nama & no_hp diizinkan)
        $resPegawaiUpdate = $this->actingAs($pegawai)->put('/profil', [
            'name' => 'Rina Wijayanti, S.E.',
            'no_hp' => '0813-8888-7777',
        ]);
        $resPegawaiUpdate->assertRedirect();
        $resPegawaiUpdate->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $pegawai->id,
            'name' => 'Rina Wijayanti, S.E.',
            'no_hp' => '0813-8888-7777',
            'email' => $pegawai->email,
        ]);
    }

    public function test_admin_executive_notifications_and_read_tracking(): void
    {
        $admin = User::where('role', 'admin')->first();
        $pegawai = User::where('role', 'pegawai')->first();

        // Buat tiket mendesak baru agar muncul di notifikasi admin
        $urgentTicket = Service::create([
            'ticket_number' => 'TKT-9901',
            'judul_kendala' => 'Switch Ruang Boiler Down',
            'user_id' => $pegawai->id,
            'nama_pelapor' => $pegawai->name,
            'divisi' => 'Operasi Boiler',
            'lokasi_ruangan' => 'Control Room',
            'jenis_perangkat' => 'Jaringan',
            'keluhan' => 'Switch mati total.',
            'prioritas' => 'Mendesak',
            'status' => 'Baru',
            'is_read' => false,
        ]);

        // 1. Admin membuka dashboard & melihat pusat notifikasi eksekutif sesuai mockup
        $resAdmin = $this->actingAs($admin)->get('/admin');
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('Notifikasi');
        $resAdmin->assertSee('Tandai semua dibaca');
        $resAdmin->assertSee('Belum dibaca');
        $resAdmin->assertSee('Lihat semua notifikasi');
        $resAdmin->assertSee('TKT-9901');

        // 2. Admin dapat mengakses halaman pusat notifikasi lengkap
        $resNotifPage = $this->actingAs($admin)->get('/admin/notifikasi');
        $resNotifPage->assertStatus(200);
        $resNotifPage->assertSee('Pusat Notifikasi');
        $resNotifPage->assertSee('TKT-9901');

        // 3. Admin menandai 1 notifikasi sebagai sudah dibaca
        $resMarkSingle = $this->actingAs($admin)->postJson('/notifications/mark-read', [
            'notif_id' => 'urgent_' . $urgentTicket->id,
        ]);
        $resMarkSingle->assertStatus(200)->assertJson(['success' => true]);
        $this->assertTrue($urgentTicket->fresh()->is_read);

        // 4. Admin menandai semua notifikasi sebagai sudah dibaca
        $resMarkAll = $this->actingAs($admin)->post('/notifications/mark-all-read');
        $resMarkAll->assertRedirect();

        $resAdminAfter = $this->actingAs($admin)->get('/admin');
        $resAdminAfter->assertStatus(200);
        $resAdminAfter->assertSee('Semua terbaca');

        // 5. Pegawai tidak boleh mengakses halaman notifikasi admin
        $this->actingAs($pegawai)->get('/admin/notifikasi')->assertStatus(403);
        $this->actingAs($pegawai)->get('/notifikasi')->assertStatus(403);
        $this->actingAs($pegawai)->post('/notifications/mark-read', ['notif_id' => 'urgent_1'])->assertStatus(403);
    }

    public function test_teknisi_executive_notifications_categories_and_full_page(): void
    {
        $admin = User::where('role', 'admin')->first();
        $pegawai = User::where('role', 'pegawai')->first();
        $teknisi = User::where('role', 'teknisi')->where('name', 'like', '%Hafizh%')->first()
            ?? User::factory()->create(['role' => 'teknisi', 'name' => 'Hafizh']);

        // Pastikan ada tiket baru yang dialihkan/ditugaskan ke teknisi oleh Koordinator IT
        $assignedTicket = Service::create([
            'ticket_number' => 'TKT-9902',
            'judul_kendala' => 'Masalah Jaringan Control Room',
            'user_id' => $pegawai->id,
            'nama_pelapor' => $pegawai->name,
            'divisi' => 'Operasi Boiler',
            'lokasi_ruangan' => 'Control Room Unit 1',
            'jenis_perangkat' => 'Kabel LAN / Jaringan',
            'keluhan' => 'Masalah jaringan terputus.',
            'prioritas' => 'Sedang',
            'status' => 'Diproses',
            'teknisi_id' => $teknisi->id,
            'teknisi_nama' => $teknisi->name,
            'is_read' => false,
        ]);

        \App\Models\ServiceComment::create([
            'service_id' => $assignedTicket->id,
            'user_id' => $admin->id,
            'author_name' => $admin->name,
            'author_role' => 'admin',
            'message' => "Tiket telah ditugaskan kepada teknisi {$teknisi->name} oleh Koordinator IT.",
        ]);

        // 1. Teknisi melihat dashboard dengan kategori notifikasi sesuai mockup referensi
        $resDash = $this->actingAs($teknisi)->get('/dashboard');
        $resDash->assertStatus(200);
        $resDash->assertSee('Notifikasi');
        $resDash->assertSee('Tandai semua dibaca');
        $resDash->assertSee('Belum dibaca');
        $resDash->assertSee('Tiket mendesak belum diambil');
        $resDash->assertSee('Tiket baru untuk Tim IT Lapangan');
        $resDash->assertSee('Tiket dialihkan ke Anda');
        $resDash->assertSee('TKT-9902');
        $resDash->assertSee('Pelapor mengonfirmasi selesai');
        $resDash->assertSee('Tiket diambil teknisi lain');
        $resDash->assertSee('Lihat semua notifikasi');

        // 2. Teknisi mengakses halaman pusat notifikasi lengkap
        $resPage = $this->actingAs($teknisi)->get('/notifikasi');
        $resPage->assertStatus(200);
        $resPage->assertSee('Pusat Notifikasi');
        $resPage->assertSee('Tiket Mendesak');
        $resPage->assertSee('Tiket Baru');
        $resPage->assertSee('Penugasan');
        $resPage->assertSee('TKT-9902');

        // 3. Teknisi menandai 1 notifikasi sebagai sudah dibaca via endpoint JSON
        $resSingle = $this->actingAs($teknisi)->postJson('/notifications/mark-read', [
            'notif_id' => 'assigned_' . $assignedTicket->id,
        ]);
        $resSingle->assertStatus(200)->assertJson(['success' => true]);
        $this->assertTrue($assignedTicket->fresh()->is_read);
    }

    public function test_teknisi_laporan_saya_cetak_pdf_preview_matches_spec_and_preserves_admin(): void
    {
        $teknisi = \App\Models\User::where('role', 'teknisi')->orderBy('id')->firstOrFail();
        $otherTeknisi = \App\Models\User::where('role', 'teknisi')->where('id', '!=', $teknisi->id)->first();
        $admin = \App\Models\User::where('role', 'admin')->firstOrFail();
        $pegawai = \App\Models\User::where('role', 'pegawai')->firstOrFail();

        // Buat tiket selesai milik teknisi lain di periode ini untuk memastikan tidak ikut tercetak di Laporan Saya Teknisi
        \App\Models\Service::create([
            'user_id' => $pegawai->id,
            'ticket_number' => 'TKT-9988',
            'judul_kendala' => 'Tiket milik teknisi lain',
            'nama_pelapor' => 'Pelapor Lain',
            'divisi' => 'Keuangan & SDM',
            'lokasi_ruangan' => 'Gedung Admin',
            'jenis_perangkat' => 'Printer',
            'keluhan' => 'Tiket milik teknisi lain',
            'prioritas' => 'Sedang',
            'status' => 'Selesai',
            'teknisi_id' => $otherTeknisi?->id,
            'teknisi_nama' => $otherTeknisi?->name ?? 'Bagas Aditya',
            'tindakan_perbaikan' => 'Perbaikan oleh teknisi lain',
            'tanggal_selesai' => now()->subDays(2),
        ]);

        // 1. Teknisi membuka Cetak / PDF dari Laporan Saya (Bulan Ini)
        $resTeknisi = $this->actingAs($teknisi)->get('/services/cetak?filter=report&quick=bulan_ini');
        $resTeknisi->assertStatus(200);
        $resTeknisi->assertSee('LAPORAN KERJA TEKNISI', false);
        $resTeknisi->assertSee('Kembali ke Laporan Saya', false);
        $resTeknisi->assertSee('Hanya laporan milik Anda', false);
        $resTeknisi->assertSee('Status Terkirim', false);
        $resTeknisi->assertSee('1. Ringkasan', false);
        $resTeknisi->assertSee('Diambil', false);
        $resTeknisi->assertSee('Selesai', false);
        $resTeknisi->assertSee('Aktif', false);
        $resTeknisi->assertSee('2. Rincian per prioritas dan kategori', false);
        $resTeknisi->assertSee('Detail sparepart', false);
        $resTeknisi->assertSee('Foto lampiran', false);
        $resTeknisi->assertSee('TKT-0125', false);
        $resTeknisi->assertSee('laporan draft tidak disertakan', false);
        $resTeknisi->assertSee('onclick="downloadPdf()"', false);
        $resTeknisi->assertDontSee('TKT-0142', false);
        $resTeknisi->assertDontSee('TKT-9988', false);

        // 1b. Tombol Unduh PDF mengunduh file .pdf asli (bukan membuka dialog print)
        $resTeknisiPdf = $this->actingAs($teknisi)->get('/services/cetak?filter=report&quick=bulan_ini&download=pdf&orientasi=lanskap&kertas=F4&show_sparepart=1&show_foto=1');
        $resTeknisiPdf->assertStatus(200);
        $resTeknisiPdf->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('.pdf', (string) $resTeknisiPdf->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF-', (string) $resTeknisiPdf->getContent());

        // 2. Admin membuka Cetak / PDF dari Rekap Laporan tetap memakai template Admin dan bisa unduh PDF
        $resAdmin = $this->actingAs($admin)->get('/services/cetak?filter=report');
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('LAPORAN REKAPITULASI SERVIS IT', false);
        $resAdmin->assertSee('Kembali ke Rekap Laporan', false);

        $resAdminPdf = $this->actingAs($admin)->get('/services/cetak?filter=report&download=pdf&show_grafik=1&show_kinerja=1');
        $resAdminPdf->assertStatus(200);
        $resAdminPdf->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', (string) $resAdminPdf->getContent());

        // 3. Pegawai tidak berhak mengakses halaman Cetak / PDF maupun unduh PDF
        $resPegawai = $this->actingAs($pegawai)->get('/services/cetak?download=pdf');
        $resPegawai->assertStatus(403);
    }

    public function test_admin_rekap_laporan_pdf_paper_sizes_and_layout_sync(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $pegawai = User::where('role', 'pegawai')->firstOrFail();

        // Buat tiket terbuka pada divisi khusus untuk memastikan tidak memakai durasi palsu 1,8 jam / 2,4 jam
        \App\Models\Service::create([
            'user_id' => $pegawai->id,
            'ticket_number' => 'TKT-9977',
            'nama_pelapor' => $pegawai->name,
            'divisi' => 'Divisi Uji Sinkron',
            'jenis_perangkat' => 'Jaringan',
            'keluhan' => 'Uji durasi terbuka di Rekap Laporan PDF',
            'lokasi_ruangan' => 'Ruang Server',
            'prioritas' => 'Sedang',
            'status' => 'Diproses',
        ]);

        // 1. Pratinjau Admin tanpa parameter tahun/bulan sinkron ke Semua Periode & mendukung kelas ukuran kertas F4/Letter/A4
        $resPreview = $this->actingAs($admin)->get('/services/cetak?filter=report&divisi=Divisi+Uji+Sinkron');
        $resPreview->assertStatus(200);
        $resPreview->assertSee('Semua Periode', false);
        $resPreview->assertSee('paper-f4-landscape', false);
        $resPreview->assertSee('330mm 215mm', false);
        $resPreview->assertSee('TKT-9977', false);
        $resPreview->assertDontSee('2,4 jam', false);
        $resPreview->assertDontSee('1,8 jam', false);

        // 2. Pastikan seluruh kombinasi ukuran kertas & orientasi menghasilkan PDF dengan MediaBox presisi
        $expectedMediaBoxes = [
            ['A4', 'potret', '0.000 0.000 595.280 841.890'],
            ['A4', 'lanskap', '0.000 0.000 841.890 595.280'],
            ['F4', 'potret', '0.000 0.000 609.450 935.430'],
            ['F4', 'lanskap', '0.000 0.000 935.430 609.450'],
            ['Letter', 'potret', '0.000 0.000 612.000 792.000'],
            ['Letter', 'lanskap', '0.000 0.000 792.000 612.000'],
        ];

        foreach ($expectedMediaBoxes as [$kertas, $orientasi, $box]) {
            $resPdf = $this->actingAs($admin)->get("/services/cetak?filter=report&download=pdf&kertas={$kertas}&orientasi={$orientasi}&show_grafik=1&show_kinerja=1");
            $resPdf->assertStatus(200);
            $resPdf->assertHeader('content-type', 'application/pdf');
            $content = (string) $resPdf->getContent();
            $this->assertStringStartsWith('%PDF-', $content);
            $this->assertStringContainsString("/MediaBox [{$box}]", $content);
        }
    }

    public function test_admin_data_master_category_location_division_syncs_with_pegawai(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $pegawai = User::where('role', 'pegawai')->firstOrFail();

        // 1. Halaman Data Master Admin menampilkan input Deskripsi kategori, edit modal deskripsi, & solusi cepat
        $resMaster = $this->actingAs($admin)->get('/admin/kategori');
        $resMaster->assertStatus(200);
        $resMaster->assertSee('editKategoriDeskripsi', false);
        $resMaster->assertSee('editKategoriSolusi', false);
        $resMaster->assertSee('Deskripsi / contoh kendala', false);
        $resMaster->assertSee('Solusi cepat untuk Pegawai', false);

        // 2. Admin menambahkan kategori baru lengkap dengan deskripsi & solusi cepat (mis. adadeh) serta kategori hanya kode (mis. bismilah)
        $this->actingAs($admin)->post('/admin/kategori', [
            'nama' => 'adadeh',
            'kode' => 'AdadehTag',
            'deskripsi' => 'Deskripsi resmi adadeh',
            'solusi_cepat' => "- Cek sensor panel unit\n2. Restart modul kontrol\n\n   ",
        ])->assertRedirect();

        $adadehCat = \App\Models\DeviceCategory::where('nama', 'adadeh')->firstOrFail();
        $this->assertSame(['Cek sensor panel unit', 'Restart modul kontrol'], $adadehCat->solusi_cepat);

        // Update kategori tanpa mengirim field solusi_cepat mempertahankan solusi yang sudah ada
        $this->actingAs($admin)->put('/admin/kategori/' . $adadehCat->id, [
            'nama' => 'adadeh',
            'kode' => 'AdadehTag',
            'deskripsi' => 'Deskripsi resmi adadeh',
        ])->assertRedirect();
        $this->assertSame(['Cek sensor panel unit', 'Restart modul kontrol'], $adadehCat->fresh()->solusi_cepat);

        $this->actingAs($admin)->post('/admin/kategori', [
            'nama' => 'Kategori Kode Saja',
            'kode' => 'bismilah',
        ])->assertRedirect();

        // Data Master Admin menampilkan badge jumlah solusi cepat
        $this->actingAs($admin)->get('/admin/kategori')->assertSee('2 solusi cepat', false);

        // 3. Admin menghapus kategori CCTV dan menonaktifkan Printer di Data Master
        $cctvCat = \App\Models\DeviceCategory::where('kode', 'CCTV')->firstOrFail();
        $this->actingAs($admin)->delete('/admin/kategori/' . $cctvCat->id)->assertRedirect();

        $printerCat = \App\Models\DeviceCategory::where('kode', 'Printer')->firstOrFail();
        $this->actingAs($admin)->post('/admin/kategori/' . $printerCat->id . '/toggle')->assertRedirect();

        // 4. Admin menambahkan lokasi aktif & menonaktifkan satu lokasi lama
        $this->actingAs($admin)->post('/admin/lokasi', [
            'nama' => 'Ruang Turbin Baru',
            'deskripsi' => 'Area lantai 2 dekat panel kontrol',
        ])->assertRedirect();

        $oldLoc = \App\Models\Location::where('nama', 'Gudang Material')->firstOrFail();
        $this->actingAs($admin)->post('/admin/lokasi/' . $oldLoc->id . '/toggle')->assertRedirect();

        // Bersihkan flash session Admin sebelum membuka halaman sebagai Pegawai
        $this->flushSession();

        // 5. Role Pegawai Buat Tiket Baru sinkron 100% dengan Data Master Admin termasuk Solusi Cepat
        $resCreate = $this->actingAs($pegawai)->get('/services/create');
        $resCreate->assertStatus(200);
        $resCreate->assertSee('adadeh', false);
        $resCreate->assertSee('Deskripsi resmi adadeh', false);
        $resCreate->assertSee('categoryCustomTips', false);
        $resCreate->assertSee('Cek sensor panel unit', false);
        $resCreate->assertSee('Restart modul kontrol', false);
        $resCreate->assertSee('Kategori Kode Saja', false);
        $resCreate->assertSee('bismilah', false);
        $resCreate->assertSee('Ruang Turbin Baru', false);
        $resCreate->assertSee('Area lantai 2 dekat panel kontrol', false);
        $resCreate->assertDontSee('Server CCTV &amp; NVR Keamanan', false);
        $resCreate->assertDontSee('Printer &amp; Scanner', false);
        $resCreate->assertDontSee('data-value="Gudang Material"', false);

        // 5b. Admin memperbarui Solusi Cepat adadeh (dan karakter HTML aman di-encode JSON) -> tampil di Pegawai
        $this->actingAs($admin)->put('/admin/kategori/' . $adadehCat->id, [
            'nama' => 'adadeh',
            'kode' => 'AdadehTag',
            'deskripsi' => 'Deskripsi resmi adadeh',
            'solusi_cepat' => "Periksa indikator <LED> panel\nHubungi teknisi jaga",
        ])->assertRedirect();

        $this->flushSession();
        $resCreateUpdated = $this->actingAs($pegawai)->get('/services/create');
        $resCreateUpdated->assertStatus(200);
        $resCreateUpdated->assertSee('Hubungi teknisi jaga', false);
        $resCreateUpdated->assertDontSee('Cek sensor panel unit', false);
        $resCreateUpdated->assertDontSee('Periksa indikator <LED> panel', false);
        $resCreateUpdated->assertSee('Periksa indikator \u003CLED\u003E panel', false);

        // 6. Rename divisi di Data Master Admin otomatis mensinkronkan divisi akun Pegawai terkait
        $division = \App\Models\Division::where('nama', $pegawai->divisi)->firstOrFail();
        $renamedDiv = $division->nama . ' Terpadu';
        $this->actingAs($admin)->put('/admin/divisi/' . $division->id, [
            'nama' => $renamedDiv,
        ])->assertRedirect();

        $this->assertEquals($renamedDiv, $pegawai->fresh()->divisi);
    }

    public function test_admin_kinerja_teknisi_dropdown_and_filters_work_without_format_bugs(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $pegawai = User::where('role', 'pegawai')->firstOrFail();

        // Ubah nama Hafizh menjadi huruf besar semua seperti skenario laporan pengguna
        $hafizh = User::where('name', 'like', '%Hafizh%')->firstOrFail();
        $hafizh->update(['name' => 'HAFIZH RAMADHANI']);

        // Tambahkan teknisi baru untuk memastikan dropdown dinamis mengambil seluruh akun dari DB
        $extraTech = User::create([
            'name' => 'Teknisi Dinamis Baru',
            'email' => 'teknisi.dinamis@s2p.co.id',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'teknisi',
            'spesialisasi' => 'aplikasi',
            'divisi' => 'IT Support (Aplikasi)',
        ]);

        // Buat tiket selesai pada Minggu (01/11/2026 00:23) untuk memastikan &bull; tidak merusak format tanggal PHP
        $doneAt = \Carbon\Carbon::create(2026, 11, 1, 0, 23, 0);
        Service::create([
            'ticket_number' => 'TKT-9801',
            'user_id' => $pegawai->id,
            'nama_pelapor' => $pegawai->name,
            'divisi' => $pegawai->divisi ?? 'Operasi Boiler',
            'lokasi_ruangan' => 'Gedung Security Pos 1',
            'jenis_perangkat' => 'PC Desktop',
            'kode_perangkat' => 'PC-POS-01',
            'judul_kendala' => 'Uji Tiket Selesai Minggu',
            'keluhan' => 'PC Blue Screen setelah update.',
            'prioritas' => 'Sedang',
            'status' => 'Selesai',
            'teknisi_id' => $hafizh->id,
            'teknisi_nama' => 'HAFIZH RAMADHANI',
            'created_at' => $doneAt->copy()->subHours(5),
            'tanggal_selesai' => $doneAt,
        ]);

        // 1. Default view /admin/kinerja menampilkan dropdown Teknisi dinamis & tanggal WIB valid
        $resDefault = $this->actingAs($admin)->get('/admin/kinerja');
        $resDefault->assertStatus(200);
        $resDefault->assertSee('id="tabTeknisiSelect"', false);
        $resDefault->assertSee('name="tab_teknisi"', false);
        $resDefault->assertSee('Semua teknisi', false);
        $resDefault->assertSee('Teknisi Dinamis Baru', false);
        $resDefault->assertSee('&bull;', false);
        $resDefault->assertDontSee('&b00000', false);
        $resDefault->assertDontSee('Sunday', false);

        // 2. Filter atas ?teknisi=Hafizh tetap menampilkan HAFIZH RAMADHANI di tabel Perbandingan teknisi
        $resFilterName = $this->actingAs($admin)->get('/admin/kinerja?teknisi=Hafizh');
        $resFilterName->assertStatus(200);
        $resFilterName->assertSee('HAFIZH RAMADHANI', false);
        $resFilterName->assertDontSee('Tidak ada data teknisi yang sesuai dengan filter yang dipilih.', false);

        // 3. Dropdown tab_teknisi memfilter Tiket terakhir diselesaikan per teknisi & format tanggal Minggu valid
        $resTabHafizh = $this->actingAs($admin)->get('/admin/kinerja?tab_teknisi=' . $hafizh->id);
        $resTabHafizh->assertStatus(200);
        $resTabHafizh->assertSee('TKT-9801', false);
        $resTabHafizh->assertSee('01/11/2026 &bull; 00:23 WIB', false);
        $resTabHafizh->assertDontSee('&b00000', false);
        $resTabHafizh->assertDontSee('Sunday', false);

        $resTabEmpty = $this->actingAs($admin)->get('/admin/kinerja?tab_teknisi=' . $extraTech->id);
        $resTabEmpty->assertStatus(200);
        $resTabEmpty->assertDontSee('TKT-9801', false);
        $resTabEmpty->assertSee('Belum ada riwayat tiket selesai untuk teknisi ini.', false);
    }

    public function test_pegawai_confirmation_reopen_cancel_metrics_and_panduan_anchors(): void
    {
        $pegawai = User::factory()->create([
            'role' => 'pegawai',
            'name' => 'Pegawai Uji',
            'divisi' => 'Operasi Boiler',
        ]);
        $otherPegawai = User::factory()->create(['role' => 'pegawai']);
        $teknisi = User::where('role', 'teknisi')->firstOrFail();

        // 1. Tiket Selesai lama tanpa tanggal_selesai (updated_at bulan lalu) TIDAK boleh dihitung sebagai Selesai bulan ini
        $oldTicket = new Service([
            'ticket_number' => 'TKT-9701',
            'judul_kendala' => 'Tiket Selesai Bulan Lalu',
            'user_id' => $pegawai->id,
            'nama_pelapor' => $pegawai->name,
            'divisi' => 'Operasi Boiler',
            'lokasi_ruangan' => 'Control Room',
            'jenis_perangkat' => 'Printer',
            'keluhan' => 'Paper jam lama.',
            'prioritas' => 'Rendah',
            'status' => 'Selesai',
            'teknisi_id' => $teknisi->id,
            'teknisi_nama' => $teknisi->name,
            'tanggal_selesai' => null,
            'pelapor_confirmed_at' => now()->subMonths(2),
        ]);
        $oldTicket->timestamps = false;
        $oldTicket->created_at = now()->subMonths(2);
        $oldTicket->updated_at = now()->subMonths(2);
        $oldTicket->save();

        // 2. Tiket Baru tidak boleh dihitung sebagai Perlu konfirmasi
        $baruTicket = Service::create([
            'ticket_number' => 'TKT-9702',
            'judul_kendala' => 'Tiket Baru Belum Diambil',
            'user_id' => $pegawai->id,
            'nama_pelapor' => $pegawai->name,
            'divisi' => 'Operasi Boiler',
            'lokasi_ruangan' => 'Control Room',
            'jenis_perangkat' => 'Printer',
            'keluhan' => 'Baru dikirim.',
            'prioritas' => 'Sedang',
            'status' => 'Baru',
        ]);

        // 3. Tiket Selesai bulan ini yang belum dikonfirmasi pelapor
        $needConfirmTicket = Service::create([
            'ticket_number' => 'TKT-9703',
            'judul_kendala' => 'Printer Kantor Sudah Diperbaiki',
            'user_id' => $pegawai->id,
            'nama_pelapor' => $pegawai->name,
            'divisi' => 'Operasi Boiler',
            'lokasi_ruangan' => 'Ruang Admin',
            'jenis_perangkat' => 'Printer',
            'keluhan' => 'Tinta macet.',
            'prioritas' => 'Sedang',
            'status' => 'Selesai',
            'teknisi_id' => $teknisi->id,
            'teknisi_nama' => $teknisi->name,
            'tanggal_selesai' => now(),
            'pelapor_confirmed_at' => null,
        ]);

        $resDash = $this->actingAs($pegawai)->get('/dashboard');
        $resDash->assertStatus(200);
        $resDash->assertViewHas('myNeedConfirmCount', 1);
        $resDash->assertViewHas('mySelesaiBulanIniCount', 1);
        $resDash->assertSee('Ada 1 tiket selesai yang menunggu konfirmasi Anda');
        $resDash->assertSee(route('services.index', ['status' => 'perlu_konfirmasi']), false);

        // Filter Perlu Konfirmasi hanya menampilkan TKT-9703
        $resFilterConfirm = $this->actingAs($pegawai)->get('/services?status=perlu_konfirmasi');
        $resFilterConfirm->assertStatus(200);
        $resFilterConfirm->assertSee('TKT-9703');
        $resFilterConfirm->assertDontSee('TKT-9701');
        $resFilterConfirm->assertDontSee('TKT-9702');

        // Detail tiket menampilkan tombol Konfirmasi & Batalkan Tiket sesuai status
        $this->actingAs($pegawai)->get('/services/' . $needConfirmTicket->id)
            ->assertStatus(200)
            ->assertSee('Konfirmasi Penyelesaian Tiket')
            ->assertSee('Ya, Sudah Selesai');

        $this->actingAs($pegawai)->get('/services/' . $baruTicket->id)
            ->assertStatus(200)
            ->assertSee('Batalkan Tiket');

        // Pegawai lain dilarang mengonfirmasi atau membatalkan tiket orang lain
        $this->actingAs($otherPegawai)->post('/services/' . $needConfirmTicket->id . '/confirm', [
            'action' => 'confirm',
        ])->assertStatus(403);
        $this->actingAs($otherPegawai)->post('/services/' . $baruTicket->id . '/cancel')->assertStatus(403);

        // Pegawai mengonfirmasi TKT-9703 selesai
        $this->actingAs($pegawai)->post('/services/' . $needConfirmTicket->id . '/confirm', [
            'action' => 'confirm',
        ])->assertRedirect();

        $this->assertNotNull($needConfirmTicket->fresh()->pelapor_confirmed_at);
        $this->actingAs($pegawai)->get('/dashboard')
            ->assertViewHas('myNeedConfirmCount', 0);

        // Teknisi menyelesaikan tiket lain, lalu Pegawai membuka kembali karena masih bermasalah
        $reopenTicket = Service::create([
            'ticket_number' => 'TKT-9704',
            'judul_kendala' => 'Wi-Fi Masih Putus',
            'user_id' => $pegawai->id,
            'nama_pelapor' => $pegawai->name,
            'divisi' => 'Operasi Boiler',
            'lokasi_ruangan' => 'Ruang Rapat',
            'jenis_perangkat' => 'Jaringan',
            'keluhan' => 'Koneksi lambat.',
            'prioritas' => 'Sedang',
            'status' => 'Selesai',
            'teknisi_id' => $teknisi->id,
            'teknisi_nama' => $teknisi->name,
            'tanggal_selesai' => now(),
        ]);

        $this->actingAs($pegawai)->post('/services/' . $reopenTicket->id . '/confirm', [
            'action' => 'reopen',
            'note' => 'Masih putus saat dipakai rapat',
        ])->assertRedirect();

        $reopenFresh = $reopenTicket->fresh();
        $this->assertEquals('Diproses', $reopenFresh->status);
        $this->assertNull($reopenFresh->tanggal_selesai);

        // Pegawai membatalkan tiket Baru miliknya
        $this->actingAs($pegawai)->post('/services/' . $baruTicket->id . '/cancel')->assertRedirect();
        $this->assertEquals('Dibatalkan', $baruTicket->fresh()->status);

        // Tiket Diproses tidak bisa dibatalkan mandiri oleh Pegawai
        $this->actingAs($pegawai)->post('/services/' . $reopenTicket->id . '/cancel')->assertRedirect();
        $this->assertEquals('Diproses', $reopenTicket->fresh()->status);

        // Halaman Panduan Mandiri memiliki seluruh target anchor shortcut Beranda Pegawai & search tersinkron
        $resKb = $this->actingAs($pegawai)->get('/basis-pengetahuan');
        $resKb->assertStatus(200);
        $resKb->assertSee('id="printer"', false);
        $resKb->assertSee('id="akun"', false);
        $resKb->assertSee('id="jaringan"', false);

        $resKbSearch = $this->actingAs($pegawai)->get('/basis-pengetahuan?search=printer');
        $resKbSearch->assertStatus(200);
        $resKbSearch->assertSee('value="printer"', false);
    }

    public function test_teknisi_ownership_prefix_collision_dropdown_export_and_dashboard_filters(): void
    {
        $pegawai = User::where('role', 'pegawai')->firstOrFail();

        // 1. Hapus teknisi sementara yang tidak punya tiket aktif/historis -> tidak boleh tertinggal sebagai ghost dari konstanta hardcoded
        $tempTech = User::create([
            'name' => 'Teknisi Sementara',
            'email' => 'temp.tech@s2p.co.id',
            'password' => bcrypt('password'),
            'role' => 'teknisi',
            'divisi' => 'IT Support (Hardware & Jaringan)',
            'spesialisasi' => 'Hardware & Jaringan',
        ]);

        $baruTicket = Service::create([
            'ticket_number' => 'TKT-9801',
            'user_id' => $pegawai->id,
            'nama_pelapor' => $pegawai->name,
            'divisi' => $pegawai->divisi ?? 'Operasi',
            'jenis_perangkat' => 'Hardware & Komputer PC',
            'lokasi_ruangan' => 'Gedung Admin',
            'keluhan' => 'Cek dropdown teknisi aktif DB',
            'status' => 'Baru',
            'prioritas' => 'Sedang',
        ]);

        $hafizh = User::where('email', 'hafizh@s2p.co.id')->firstOrFail();
        $hafizh->update(['name' => 'HAFIZH RAMADHANI']);

        $resEditBefore = $this->actingAs($hafizh->fresh())->get('/services/' . $baruTicket->id . '/edit');
        $resEditBefore->assertStatus(200);
        $resEditBefore->assertSee('HAFIZH RAMADHANI');
        $resEditBefore->assertSee('Teknisi Sementara');

        $tempTech->delete();

        $resEditAfter = $this->actingAs($hafizh->fresh())->get('/services/' . $baruTicket->id . '/edit');
        $resEditAfter->assertStatus(200);
        $resEditAfter->assertDontSee('Teknisi Sementara');

        // 2. Uji anti-tabrakan prefix nama (Arif vs Arifin)
        $arif = User::where('email', 'arif@s2p.co.id')->firstOrFail();
        $arifin = User::create([
            'name' => 'Arifin',
            'email' => 'arifin@s2p.co.id',
            'password' => bcrypt('password'),
            'role' => 'teknisi',
            'divisi' => 'IT Support (Aplikasi & Web)',
            'spesialisasi' => 'Aplikasi & Web',
        ]);

        $arifActive = Service::create([
            'ticket_number' => 'TKT-9811',
            'user_id' => $pegawai->id,
            'nama_pelapor' => $pegawai->name,
            'divisi' => 'Operasi',
            'jenis_perangkat' => 'Software & Aplikasi Internal',
            'lokasi_ruangan' => 'Ruang Kontrol',
            'keluhan' => 'Tugas aktif milik Arif saja',
            'status' => 'Diproses',
            'prioritas' => 'Mendesak',
            'teknisi_id' => $arif->id,
            'teknisi_nama' => 'Arif',
        ]);

        $arifinActive = Service::create([
            'ticket_number' => 'TKT-9812',
            'user_id' => $pegawai->id,
            'nama_pelapor' => $pegawai->name,
            'divisi' => 'Keuangan',
            'jenis_perangkat' => 'Software & Aplikasi Internal',
            'lokasi_ruangan' => 'Ruang Keuangan',
            'keluhan' => 'Tugas aktif milik Arifin saja',
            'status' => 'Diproses',
            'prioritas' => 'Mendesak',
            'teknisi_id' => $arifin->id,
            'teknisi_nama' => 'Arifin',
        ]);

        $arifDoneThisMonth = Service::create([
            'ticket_number' => 'TKT-9813',
            'user_id' => $pegawai->id,
            'nama_pelapor' => $pegawai->name,
            'divisi' => 'Operasi',
            'jenis_perangkat' => 'Software & Aplikasi Internal',
            'lokasi_ruangan' => 'Ruang Kontrol',
            'keluhan' => 'Selesai bulan ini milik Arif',
            'status' => 'Selesai',
            'prioritas' => 'Sedang',
            'teknisi_id' => $arif->id,
            'teknisi_nama' => 'Arif',
            'tanggal_selesai' => now(),
        ]);

        $arifDoneLastMonth = Service::create([
            'ticket_number' => 'TKT-9814',
            'user_id' => $pegawai->id,
            'nama_pelapor' => $pegawai->name,
            'divisi' => 'Operasi',
            'jenis_perangkat' => 'Software & Aplikasi Internal',
            'lokasi_ruangan' => 'Ruang Kontrol',
            'keluhan' => 'Selesai bulan lalu milik Arif',
            'status' => 'Selesai',
            'prioritas' => 'Sedang',
            'teknisi_id' => $arif->id,
            'teknisi_nama' => 'Arif',
            'tanggal_selesai' => now()->subMonth(),
        ]);

        $arifinDoneThisMonth = Service::create([
            'ticket_number' => 'TKT-9815',
            'user_id' => $pegawai->id,
            'nama_pelapor' => $pegawai->name,
            'divisi' => 'Keuangan',
            'jenis_perangkat' => 'Software & Aplikasi Internal',
            'lokasi_ruangan' => 'Ruang Keuangan',
            'keluhan' => 'Selesai bulan ini milik Arifin',
            'status' => 'Selesai',
            'prioritas' => 'Sedang',
            'teknisi_id' => $arifin->id,
            'teknisi_nama' => 'Arifin',
            'tanggal_selesai' => now(),
        ]);

        // Filter Tiket Saya -> Tugas Saya untuk Arif tidak boleh bocor ke Arifin
        $resMyArif = $this->actingAs($arif)->get('/services?filter=my&teknisi=Arif');
        $resMyArif->assertStatus(200);
        $resMyArif->assertSee('TKT-9811');
        $resMyArif->assertSee('Tugas aktif milik Arif saja');
        $resMyArif->assertDontSee('Tugas aktif milik Arifin saja');
        $resMyArif->assertSee('Tugas Saya');

        // Laporan Saya untuk Arif tidak boleh menarik milik Arifin (default Semua tahun & Semua bulan)
        $resReportArif = $this->actingAs($arif)->get('/services?filter=report');
        $resReportArif->assertStatus(200);
        $resReportArif->assertSee('Semua tahun');
        $resReportArif->assertSee('Semua bulan');
        $resReportArif->assertSee('2030');
        $resReportArif->assertSee('TKT-9813');
        $resReportArif->assertSee('TKT-9814');
        $resReportArif->assertDontSee('TKT-9815');
        $resReportArif->assertDontSee('Selesai bulan ini milik Arifin');

        // Filter quick=bulan_ini pada Laporan Saya menyaring hanya tiket bulan berjalan
        $resReportBulanIni = $this->actingAs($arif)->get('/services?filter=report&quick=bulan_ini');
        $resReportBulanIni->assertStatus(200);
        $resReportBulanIni->assertSee('TKT-9813');
        $resReportBulanIni->assertDontSee('TKT-9814');

        // Ekspor Excel Laporan Saya oleh Arif harus terkunci ke milik Arif meski parameter teknisi=Arifin disisipkan
        $resExportArif = $this->actingAs($arif)->get('/services/export?filter=report&quick=bulan_ini&teknisi=Arifin');
        $resExportArif->assertStatus(200);
        $csvContent = $resExportArif->streamedContent();
        $this->assertStringContainsString('TKT-9813', $csvContent);
        $this->assertStringNotContainsString('TKT-9814', $csvContent);
        $this->assertStringNotContainsString('TKT-9815', $csvContent);

        // NotificationCenterService tidak boleh menganggap tugas aktif Arifin sebagai penugasan milik Arif
        $notifItems = collect(\App\Services\NotificationCenterService::getForUser($arif, 20)['items'] ?? []);
        $this->assertTrue($notifItems->contains('id', 'assigned_' . $arifActive->id));
        $this->assertFalse($notifItems->contains('id', 'assigned_' . $arifinActive->id));

        // Dashboard Teknisi mempertahankan parameter tim saat search dan menampilkan link tugas aktif user
        $resDash = $this->actingAs($arif)->get('/dashboard?tim=lapangan&search=KendalaTest');
        $resDash->assertStatus(200);
        $resDash->assertSee('<input type="hidden" name="tim" value="lapangan">', false);
        $resDash->assertSee('hari ini', false);
    }

    public function test_admin_fixes_rekap_export_user_crud_guards_and_prefix_isolation(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $pegawai = User::where('role', 'pegawai')->firstOrFail();
        $arif = User::where('email', 'arif@s2p.co.id')->firstOrFail();

        $arifin = User::create([
            'name' => 'Arifin',
            'email' => 'arifin.admin@s2p.co.id',
            'password' => bcrypt('password'),
            'role' => 'teknisi',
            'spesialisasi' => 'aplikasi',
            'divisi' => 'IT Support (Aplikasi & Web)',
        ]);

        // 1. Default /admin/rekap tidak menampilkan "Reset Filter" saat belum difilter & bebas angka durasi palsu
        $resRekapDefault = $this->actingAs($admin)->get('/admin/rekap');
        $resRekapDefault->assertStatus(200);
        $resRekapDefault->assertDontSee('Reset Filter');
        $resRekapDefault->assertDontSee('2,1 jam');
        $resRekapDefault->assertDontSee('2,8 jam');

        // Jika ada parameter filter aktif (mis. divisi=Operasi Boiler), barulah tombol Reset Filter tampil
        $resRekapFiltered = $this->actingAs($admin)->get('/admin/rekap?divisi=Operasi+Boiler');
        $resRekapFiltered->assertStatus(200);
        $resRekapFiltered->assertSee('Reset Filter');

        // 2. Uji isolasi prefix Arif vs Arifin di Rekap Admin, Kinerja Admin, dan Ekspor Excel Admin
        $tktArif = Service::create([
            'ticket_number' => 'TKT-9901',
            'user_id' => $pegawai->id,
            'nama_pelapor' => $pegawai->name,
            'divisi' => 'Operasi Boiler',
            'lokasi_ruangan' => 'Ruang Kontrol',
            'jenis_perangkat' => 'Software & Aplikasi Internal',
            'keluhan' => 'Tiket selesai Arif untuk Rekap Admin',
            'prioritas' => 'Sedang',
            'status' => 'Selesai',
            'teknisi_id' => $arif->id,
            'teknisi_nama' => 'Arif',
            'tanggal_selesai' => now(),
        ]);

        $tktArifin = Service::create([
            'ticket_number' => 'TKT-9902',
            'user_id' => $pegawai->id,
            'nama_pelapor' => $pegawai->name,
            'divisi' => 'Operasi Boiler',
            'lokasi_ruangan' => 'Ruang Kontrol',
            'jenis_perangkat' => 'Software & Aplikasi Internal',
            'keluhan' => 'Tiket selesai Arifin untuk Rekap Admin',
            'prioritas' => 'Sedang',
            'status' => 'Selesai',
            'teknisi_id' => $arifin->id,
            'teknisi_nama' => 'Arifin',
            'tanggal_selesai' => now(),
        ]);

        $resRekapArif = $this->actingAs($admin)->get('/admin/rekap?teknisi=Arif');
        $resRekapArif->assertStatus(200);
        $resRekapArif->assertSee('TKT-9901');
        $resRekapArif->assertSee('Tiket selesai Arif untuk Rekap Admin');
        $resRekapArif->assertDontSee('Tiket selesai Arifin untuk Rekap Admin');

        $resKinerjaTabArif = $this->actingAs($admin)->get('/admin/kinerja?tab_teknisi=' . $arif->id);
        $resKinerjaTabArif->assertStatus(200);
        $resKinerjaTabArif->assertSee('TKT-9901');
        $resKinerjaTabArif->assertSee('Tiket selesai Arif untuk Rekap Admin');
        $resKinerjaTabArif->assertDontSee('Tiket selesai Arifin untuk Rekap Admin');

        // Ekspor Excel dari halaman Kinerja/Rekap Admin mendukung parameter numeric teknisi ID & menamai file Rekap_Laporan_IT_PT_S2P
        $resAdminExport = $this->actingAs($admin)->get('/services/export?filter=report&teknisi=' . $arif->id);
        $resAdminExport->assertStatus(200);
        $disposition = (string) $resAdminExport->headers->get('content-disposition');
        $this->assertStringContainsString('Rekap_Laporan_IT_PT_S2P', $disposition);
        $csvAdmin = $resAdminExport->streamedContent();
        $this->assertStringContainsString('TKT-9901', $csvAdmin);
        $this->assertStringNotContainsString('TKT-9902', $csvAdmin);

        // 3. Admin Manajemen Pengguna: Edit pengguna + proteksi hapus user yang punya histori/tiket aktif
        $this->actingAs($admin)->get('/admin/users')
            ->assertStatus(200)
            ->assertSee('modalEditUser', false)
            ->assertSee('Edit');

        $resUpdate = $this->actingAs($admin)->put('/admin/users/' . $arifin->id, [
            'name' => 'Arifin Santoso',
            'email' => $arifin->email,
            'role' => 'teknisi',
            'spesialisasi' => 'lapangan',
            'divisi' => 'IT Lapangan (Hardware & Jaringan)',
            'no_hp' => '0812-0000-1111',
        ]);
        $resUpdate->assertRedirect();
        $resUpdate->assertSessionHas('success');
        $this->assertEquals('Arifin Santoso', $arifin->fresh()->name);
        $this->assertEquals('lapangan', $arifin->fresh()->spesialisasi);
        $this->assertEquals('Arifin Santoso', $tktArifin->fresh()->teknisi_nama);

        // Tidak boleh menghapus pegawai yang sudah memiliki histori tiket (menjaga riwayat tiket agar tidak cascade-delete)
        $resDelPegawai = $this->actingAs($admin)->delete('/admin/users/' . $pegawai->id);
        $resDelPegawai->assertRedirect();
        $resDelPegawai->assertSessionHasErrors('error');
        $this->assertDatabaseHas('users', ['id' => $pegawai->id]);

        // Pengguna baru tanpa tiket tetap bisa dihapus secara normal
        $unusedUser = User::create([
            'name' => 'Akun Uji Hapus',
            'email' => 'hapus.aman@s2p.co.id',
            'password' => bcrypt('password'),
            'role' => 'pegawai',
            'divisi' => 'Keuangan & SDM',
        ]);
        $this->actingAs($admin)->delete('/admin/users/' . $unusedUser->id)
            ->assertRedirect()
            ->assertSessionHas('info');
        $this->assertDatabaseMissing('users', ['id' => $unusedUser->id]);
    }

    public function test_teknisi_and_admin_can_edit_and_delete_sop_while_pegawai_is_blocked(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $teknisi = User::where('role', 'teknisi')->firstOrFail();
        $pegawai = User::where('role', 'pegawai')->firstOrFail();

        $sop = \App\Models\KnowledgeBase::create([
            'user_id' => $teknisi->id,
            'judul' => 'SOP Awal Backup Konfigurasi Switch',
            'kategori' => 'Jaringan & CCTV',
            'area_penerapan' => 'Server Room Utama',
            'target_audience' => 'teknisi',
            'pengantar' => 'Panduan awal backup switch core.',
            'langkah_langkah' => ['Login SSH ke switch', 'Jalankan show running-config'],
            'judul_perintah' => 'Salin Konfigurasi',
            'perintah_cmd' => 'copy running-config tftp:',
            'peringatan' => 'Jangan reboot saat proses copy.',
            'tag' => 'switch,backup',
            'perlu_izin_operator' => true,
            'service_id' => null,
            'status' => 'Draft',
        ]);

        // Baik Teknisi maupun Admin melihat tombol Edit dan Hapus pada kartu SOP
        $this->actingAs($teknisi)->get('/basis-pengetahuan')
            ->assertOk()
            ->assertSee('SOP Awal Backup Konfigurasi Switch')
            ->assertSee('/basis-pengetahuan/' . $sop->id . '/edit')
            ->assertSee('Hapus');

        $this->actingAs($admin)->get('/basis-pengetahuan')
            ->assertOk()
            ->assertSee('SOP Awal Backup Konfigurasi Switch')
            ->assertSee('/basis-pengetahuan/' . $sop->id . '/edit')
            ->assertSee('Hapus');

        // Halaman edit memuat data SOP saat ini
        $this->actingAs($admin)->get('/basis-pengetahuan/' . $sop->id . '/edit')
            ->assertOk()
            ->assertSee('SOP Awal Backup Konfigurasi Switch')
            ->assertSee('Server Room Utama')
            ->assertSee('copy running-config tftp:');

        // Pegawai diblokir dari edit, update, maupun delete SOP
        $this->actingAs($pegawai)->get('/basis-pengetahuan/' . $sop->id . '/edit')->assertForbidden();
        $this->actingAs($pegawai)->put('/basis-pengetahuan/' . $sop->id, [
            'judul' => 'Diretas Pegawai',
            'kategori' => 'Jaringan & CCTV',
            'target_audience' => 'all',
            'status' => 'Terbit',
        ])->assertForbidden();
        $this->actingAs($pegawai)->delete('/basis-pengetahuan/' . $sop->id)->assertForbidden();

        // Teknisi mengubah SOP menjadi Terbit dan target_audience 'all' agar tampil di Panduan Mandiri Pegawai
        $this->actingAs($teknisi)->put('/basis-pengetahuan/' . $sop->id, [
            'judul' => 'SOP Terbaru Backup Switch & Panduan Pegawai',
            'kategori' => 'Perangkat Keras',
            'area_penerapan' => 'Seluruh Lantai PLTU',
            'target_audience' => 'all',
            'pengantar' => 'Panduan yang sudah diperbarui.',
            'langkah_langkah' => ['Periksa kabel uplink', '', 'Simpan log ke server arsip'],
            'judul_perintah' => 'Backup TFTP',
            'perintah_cmd' => 'copy startup-config tftp:',
            'peringatan' => 'Pastikan TFTP server aktif.',
            'tag' => 'switch,arsip',
            'perlu_izin_operator' => '0',
            'service_id' => null,
            'status' => 'Terbit',
        ])->assertRedirect(route('knowledge.base'));

        $sop->refresh();
        $this->assertSame('SOP Terbaru Backup Switch & Panduan Pegawai', $sop->judul);
        $this->assertSame('Perangkat Keras', $sop->kategori);
        $this->assertSame('all', $sop->target_audience);
        $this->assertSame('Terbit', $sop->status);
        $this->assertSame(['Periksa kabel uplink', 'Simpan log ke server arsip'], $sop->langkah_langkah);
        $this->assertFalse((bool) $sop->perlu_izin_operator);

        // Karena sudah Terbit & target_audience 'all', Pegawai kini melihatnya di Panduan Mandiri
        $this->actingAs($pegawai)->get('/basis-pengetahuan')
            ->assertOk()
            ->assertSee('SOP Terbaru Backup Switch & Panduan Pegawai')
            ->assertDontSee('/basis-pengetahuan/' . $sop->id . '/edit');

        // Admin menghapus SOP dari katalog
        $this->actingAs($admin)->delete('/basis-pengetahuan/' . $sop->id)
            ->assertRedirect(route('knowledge.base'));

        $this->assertDatabaseMissing('knowledge_bases', ['id' => $sop->id]);
    }

    public function test_admin_user_modal_division_dropdown_is_refined_and_saves_both_selected_and_custom_divisions(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        // 1. Halaman admin /admin/users menyajikan dropdown select rapi tanpa datalist mentah & tanpa opsi IT
        $res = $this->actingAs($admin)->get('/admin/users');
        $res->assertOk();
        $res->assertSee('id="tambahUserDivisi"', false);
        $res->assertSee('id="editUserDivisi"', false);
        $res->assertSee('🏢 Divisi Operasional PLTU', false);
        $res->assertDontSee('💻 Unit IT Support & Lapangan', false);
        $res->assertSee('➕ Ketik Divisi Lainnya (Ketik Manual)...', false);
        $res->assertDontSee('list="divisi-datalist"', false);

        // 2. Tambah user dengan memilih divisi dari dropdown terdaftar
        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Karyawan Boiler Baru',
            'email' => 'boiler.baru@s2p.co.id',
            'password' => 'password123',
            'role' => 'pegawai',
            'divisi' => 'Operasi Boiler',
            'no_hp' => '0812-9988-7711',
        ])->assertRedirect()->assertSessionHas('success');

        $userBoiler = User::where('email', 'boiler.baru@s2p.co.id')->firstOrFail();
        $this->assertSame('Operasi Boiler', $userBoiler->divisi);
        $this->assertNull($userBoiler->spesialisasi);

        // 3. Tambah user dengan mengetik divisi baru/manual
        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Staff Pengawas Lingkungan',
            'email' => 'lingkungan.pltu@s2p.co.id',
            'password' => 'password123',
            'role' => 'pegawai',
            'divisi' => 'Monitoring Emisi Cerobong',
            'no_hp' => '0813-1122-3344',
        ])->assertRedirect()->assertSessionHas('success');

        $userEnv = User::where('email', 'lingkungan.pltu@s2p.co.id')->firstOrFail();
        $this->assertSame('Monitoring Emisi Cerobong', $userEnv->divisi);

        // 4. Update data divisi user melalui modal edit
        $this->actingAs($admin)->put('/admin/users/' . $userBoiler->id, [
            'name' => 'Karyawan Boiler Senior',
            'email' => 'boiler.baru@s2p.co.id',
            'role' => 'pegawai',
            'divisi' => 'Pemeliharaan Turbin',
            'no_hp' => '0812-9988-7711',
        ])->assertRedirect();

        $this->assertSame('Pemeliharaan Turbin', $userBoiler->fresh()->divisi);
    }

    public function test_smart_assignment_with_workload_indicators_and_team_collaboration(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $pegawai = User::where('role', 'pegawai')->firstOrFail();
        $hafizh = User::where('email', 'hafizh@s2p.co.id')->firstOrFail();
        $bagas = User::where('email', 'bagas@s2p.co.id')->firstOrFail();

        // Buat tiket aduan baru untuk uji penugasan cerdas & kerja tim
        $heavyTicket = Service::create([
            'ticket_number' => 'TKT-9950',
            'judul_kendala' => 'Instalasi Kabel LAN Backbone Ruang Kontrol',
            'user_id' => $pegawai->id,
            'nama_pelapor' => $pegawai->name,
            'divisi' => 'Operasi Boiler',
            'lokasi_ruangan' => 'Gedung Kontrol Utama',
            'jenis_perangkat' => 'Kabel LAN / Jaringan',
            'keluhan' => 'Pemasangan kabel LAN 4 titik baru dan perapihan patch panel switch.',
            'prioritas' => 'Mendesak',
            'status' => 'Baru',
            'is_read' => false,
        ]);

        // 1. Admin membuka halaman penugasan (pada jam operasional kerja) dan melihat indikator cerdas beban kerja serta tombol Tim
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::create(2026, 11, 18, 9, 0, 0, 'Asia/Jakarta'));
        User::where('role', 'teknisi')->update([
            'status_ketersediaan' => 'available',
            'status_ketersediaan_updated_at' => now(),
        ]);
        try {
            $resPenugasan = $this->actingAs($admin)->get('/admin/penugasan');
            $resPenugasan->assertStatus(200);
            $resPenugasan->assertSee('TKT-9950');
            $resPenugasan->assertSee('Available • 0 tugas');
            $resPenugasan->assertSee('Tim');
            $resPenugasan->assertSee('modal-team-assign', false);
        } finally {
            \Carbon\Carbon::setTestNow();
        }

        // 2. Admin menugaskan tiket kepada Tim IT (Lead: Hafizh, Pendamping: Bagas)
        $resAssignTeam = $this->actingAs($admin)->post('/admin/assign-ticket', [
            'service_id' => $heavyTicket->id,
            'teknisi_id' => $hafizh->id,
            'teknisi_pendamping' => [$bagas->id],
            'catatan_penugasan' => 'Tarik kabel bersama tim dan uji LAN tester.',
        ]);

        $resAssignTeam->assertRedirect();
        $resAssignTeam->assertSessionHas('success');

        $heavyTicketFresh = $heavyTicket->fresh();
        $this->assertSame('Diproses', $heavyTicketFresh->status);
        $this->assertEquals($hafizh->id, $heavyTicketFresh->teknisi_id);
        $this->assertTrue($heavyTicketFresh->is_team);
        $this->assertNotEmpty($heavyTicketFresh->teknisi_pendamping);

        // 3. Hak akses dan kepemilikan sinkron: Baik Lead maupun Co-Technician melihat tiket di Tiket Saya
        $this->assertTrue(Service::ownedByTeknisi($hafizh)->where('id', $heavyTicket->id)->exists());
        $this->assertTrue(Service::ownedByTeknisi($bagas)->where('id', $heavyTicket->id)->exists());

        // 4. Lead (Hafizh) & Co-Technician (Bagas) dapat membuka detail tiket dengan badge Kerja Tim IT
        $resLeadView = $this->actingAs($hafizh)->get("/services/{$heavyTicket->id}");
        $resLeadView->assertStatus(200);
        $resLeadView->assertSee('Kerja Tim IT');
        $resLeadView->assertSee('Hafizh');
        $resLeadView->assertSee('Bagas Aditya');

        $resCoView = $this->actingAs($bagas)->get("/services/{$heavyTicket->id}");
        $resCoView->assertStatus(200);
        $resCoView->assertSee('Kerja Tim IT');

        // Komentar riwayat penugasan otomatis mencatat penugasan Tim IT
        $resLeadView->assertSee('Tim IT dengan PIC Hafizh');
    }

    public function test_preventive_maintenance_log_system_with_checklists_auto_tickets_and_reports(): void
    {
        $hafizh = User::where('email', 'hafizh@s2p.co.id')->first()
            ?? User::factory()->create(['name' => 'Hafizh', 'role' => 'teknisi', 'email' => 'hafizh@s2p.co.id']);
        $bagas = User::where('email', 'bagas@s2p.co.id')->first()
            ?? User::factory()->create(['name' => 'Bagas Aditya', 'role' => 'teknisi', 'email' => 'bagas@s2p.co.id']);
        $admin = User::where('role', 'admin')->first()
            ?? User::factory()->create(['name' => 'Hendra Wijaya, S.T.', 'role' => 'admin', 'email' => 'admin@s2p.co.id']);

        // 1. Admin/Teknisi dapat mengakses halaman Log Pemeliharaan sesuai mockup referensi
        $resIndex = $this->actingAs($admin)->get('/pemeliharaan');
        $resIndex->assertStatus(200);
        $resIndex->assertSee('Log Pemeliharaan', false);
        $resIndex->assertSee('Pantau jadwal dan hasil pemeliharaan seluruh tim', false);
        $resIndex->assertSee('Terjadwal', false);
        $resIndex->assertSee('Selesai', false);
        $resIndex->assertSee('Terlambat', false);
        $resIndex->assertSee('Perlu tindak lanjut', false);
        $resIndex->assertSee('Jadwal rutin', false);
        $resIndex->assertSee('Ekspor Excel', false);
        $resIndex->assertSee('Jadwalkan', false);

        // 2. Akses form tambah log pemeliharaan
        $resCreate = $this->actingAs($hafizh)->get('/pemeliharaan/create');
        $resCreate->assertStatus(200);
        $resCreate->assertSee('Catat Log Pemeliharaan Rutin', false);
        $resCreate->assertSee('Checklist Pengecekan Rutin', false);

        // 3. Teknisi menyimpan log pemeliharaan rutin kondisi Prima dengan checklist
        $resStore = $this->actingAs($hafizh)->post('/pemeliharaan', [
            'tanggal_cek' => now()->toDateString(),
            'kode_perangkat' => 'PC-TURBIN-TEST-01',
            'nama_perangkat' => 'Workstation SCADA Turbin Generator',
            'jenis_perangkat' => 'Komputer PC',
            'divisi' => 'Pemeliharaan Turbin',
            'lokasi_ruangan' => 'Workshop Turbin Lt. 1',
            'kondisi' => 'Prima',
            'teknisi_pendamping' => [$bagas->id],
            'checklist' => [
                ['item' => 'Pembersihan debu batu bara di casing & heatsink', 'checked' => true],
                ['item' => 'Pemeriksaan suhu CPU & thermal paste', 'checked' => true],
            ],
            'tindakan' => 'Pembersihan debu filter intake dan update patch keamanan.',
            'catatan_rekomendasi' => 'Semua komponen berjalan dalam toleransi normal.',
        ]);

        $resStore->assertRedirect('/pemeliharaan');
        $resStore->assertSessionHas('success');

        $logPrima = \App\Models\MaintenanceLog::where('kode_perangkat', 'PC-TURBIN-TEST-01')->first();
        $this->assertNotNull($logPrima);
        $this->assertSame('Prima', $logPrima->kondisi);
        $this->assertStringStartsWith('PM-', $logPrima->log_number);
        $this->assertTrue($logPrima->isPrima());

        // 4. Teknisi mencatat pemeriksaan kondisi Kritis -> Otomatis Buat Tiket Servis Perbaikan!
        $resStoreKritis = $this->actingAs($hafizh)->post('/pemeliharaan', [
            'tanggal_cek' => now()->toDateString(),
            'kode_perangkat' => 'SW-BOILER-TEST-99',
            'nama_perangkat' => 'Distribution Switch Cisco 2960 Boiler',
            'jenis_perangkat' => 'Kabel LAN / Jaringan',
            'divisi' => 'Operasi Boiler',
            'lokasi_ruangan' => 'Ruang Kontrol Boiler Lt. 2',
            'kondisi' => 'Kritis',
            'buat_tiket_servis' => '1',
            'checklist' => [
                ['item' => 'Kebersihan port switch & debu industri/batu bara', 'checked' => false],
            ],
            'tindakan' => 'Port 1-8 mati akibat lonjakan tegangan petir semalam.',
            'catatan_rekomendasi' => 'Perlu penggantian modul port switch atau unit pengganti segera.',
        ]);

        $resStoreKritis->assertRedirect('/pemeliharaan');
        $logKritis = \App\Models\MaintenanceLog::where('kode_perangkat', 'SW-BOILER-TEST-99')->first();
        $this->assertNotNull($logKritis);
        $this->assertSame('Kritis', $logKritis->kondisi);
        $this->assertSame('Eskalasi Tiket', $logKritis->status_tindak_lanjut);
        $this->assertNotNull($logKritis->service_id);

        // Verifikasi tiket servis otomatis terbentuk
        $autoTicket = Service::find($logKritis->service_id);
        $this->assertNotNull($autoTicket);
        $this->assertSame('Mendesak', $autoTicket->prioritas);
        $this->assertSame('Baru', $autoTicket->status);
        $this->assertSame('SW-BOILER-TEST-99', $autoTicket->kode_perangkat);
        $this->assertStringContainsString('SW-BOILER-TEST-99', $autoTicket->keluhan);

        // 5. Buka halaman detail & kartu pemeliharaan aset
        $resShow = $this->actingAs($hafizh)->get("/pemeliharaan/{$logPrima->id}");
        $resShow->assertStatus(200);
        $resShow->assertSee($logPrima->log_number, false);
        $resShow->assertSee('PC-TURBIN-TEST-01', false);
        $resShow->assertSee('Pembersihan debu filter intake', false);
        $resShow->assertSee('Bagas Aditya', false);

        // 5b. Pemisahan Akun PM: Teknisi Bagas melihat PM miliknya sendiri (termasuk kerja tim) di tab PM Saya
        $resBagas = $this->actingAs($bagas)->get('/pemeliharaan?tab=my');
        $resBagas->assertStatus(200);
        $resBagas->assertSee('Pemeliharaan Saya', false);
        $resBagas->assertSee('PC-TURBIN-TEST-01', false); // Kerja tim bersama Hafizh
        $resBagas->assertSee('Anda', false); // Badge penanda kepemilikan Anda

        // Admin dapat melihat seluruh tim dan memfilter berdasarkan teknisi
        $resAdminFilter = $this->actingAs($admin)->get("/pemeliharaan?tab=all&teknisi={$hafizh->id}");
        $resAdminFilter->assertStatus(200);
        $resAdminFilter->assertSee('PC-TURBIN-TEST-01', false);

        // 6. Cetak laporan & ekspor data (Standar Perusahaan PT S2P: Cetak Langsung & Unduh PDF)
        $resCetak = $this->actingAs($admin)->get('/pemeliharaan/cetak');
        $resCetak->assertStatus(200);
        $resCetak->assertSee('PT SUMBER SEGARA PRIMADAYA', false);
        $resCetak->assertSee('LAPORAN PELAKSANAAN PREVENTIVE MAINTENANCE', false);
        $resCetak->assertSee('FORM-IT-PM-REV.02', false);
        $resCetak->assertSee('Unduh File PDF', false);
        $resCetak->assertSee('Cetak Langsung', false);

        // Ekspor PDF langsung via DomPDF
        $resPdf = $this->actingAs($admin)->get('/pemeliharaan/cetak?download=pdf');
        $resPdf->assertStatus(200);
        $resPdf->assertHeader('content-type', 'application/pdf');

        // Teknisi juga dapat mengakses pratinjau cetak dan unduh PDF
        $resCetakTeknisi = $this->actingAs($hafizh)->get('/pemeliharaan/cetak');
        $resCetakTeknisi->assertStatus(200);
        $resCetakTeknisi->assertSee('FORM-IT-PM-REV.02', false);

        $resPdfTeknisi = $this->actingAs($hafizh)->get('/pemeliharaan/cetak?download=pdf');
        $resPdfTeknisi->assertStatus(200);
        $resPdfTeknisi->assertHeader('content-type', 'application/pdf');

        $resExport = $this->actingAs($admin)->get('/pemeliharaan/export');
        $resExport->assertStatus(200);
        $resExport->assertHeader('content-type', 'text/csv; charset=utf-8');

        // 7. Admin dapat membuka halaman form Jadwalkan Pemeliharaan dan menyimpan jadwal baru (+ Jadwalkan)
        $resFormJadwalkan = $this->actingAs($admin)->get('/pemeliharaan/jadwalkan');
        $resFormJadwalkan->assertStatus(200);
        $resFormJadwalkan->assertSee('Jadwalkan Pemeliharaan', false);
        $resFormJadwalkan->assertSee('Pratinjau jadwal', false);
        $resFormJadwalkan->assertSee('Ringkasan', false);
        $this->actingAs($hafizh)->get('/pemeliharaan/jadwalkan')->assertStatus(403);

        $resJadwalkan = $this->actingAs($admin)->post('/pemeliharaan/jadwalkan', [
            'tanggal_cek' => now()->addDays(2)->toDateString(),
            'jam_mulai' => '09.00',
            'nama_perangkat' => 'Server Aplikasi Database ERP',
            'kode_perangkat' => 'SRV-ERP-01',
            'lokasi_ruangan' => 'Server Room Lt. 2',
            'frekuensi' => 'Bulanan',
            'kegiatan_list' => [
                ['item' => 'Backup database PostgreSQL dan vacuum optimize', 'checked' => '1'],
                ['item' => 'Cek suhu dan kipas', 'checked' => '1'],
            ],
            'tim' => 'Tim IT Support',
            'teknisi_id' => $hafizh->id,
            'catatan_teknisi' => 'Koordinasi sebelum restart service',
        ]);
        $resJadwalkan->assertRedirect('/pemeliharaan');
        $this->assertDatabaseHas('maintenance_logs', [
            'nama_perangkat' => 'Server Aplikasi Database ERP',
            'status' => 'Terjadwal',
        ]);
        $this->assertDatabaseHas('maintenance_schedules', [
            'nama_aset' => 'Server Aplikasi Database ERP',
            'frekuensi' => 'Setiap bulan',
        ]);

        // 8. Admin dapat mengatur master jadwal rutin (+ Tambah Jadwal Rutin)
        $resRecurring = $this->actingAs($admin)->post('/pemeliharaan/schedules', [
            'nama_aset' => 'Genset & Backup Inverter SCADA',
            'lokasi_ruangan' => 'Power House Lt. 1',
            'frekuensi' => 'Setiap bulan',
            'tanggal_berikutnya' => now()->addMonth()->toDateString(),
            'tim' => 'Tim IT Lapangan',
            'teknisi_id' => $bagas->id,
        ]);
        $resRecurring->assertRedirect();
        $this->assertDatabaseHas('maintenance_schedules', [
            'nama_aset' => 'Genset & Backup Inverter SCADA',
            'frekuensi' => 'Setiap bulan',
        ]);

        // 8b. Tindak lanjut langsung dari Detail Log Pemeliharaan (Perlu Perhatian -> Buat Tiket Perbaikan & Jadwalkan PM Tindak Lanjut)
        $logPerluTindak = \App\Models\MaintenanceLog::create([
            'log_number' => 'PM-TEST-TL-01',
            'tanggal_cek' => now()->toDateString(),
            'periode_bulan' => now()->format('Y-m'),
            'kode_perangkat' => 'PC-CCR-99',
            'nama_perangkat' => 'Workstation Control Room Unit 1',
            'jenis_perangkat' => 'Komputer PC',
            'divisi' => 'Operasi',
            'lokasi_ruangan' => 'Control Room Unit 1',
            'user_id' => $bagas->id,
            'teknisi_nama' => $bagas->name,
            'kondisi' => 'Perlu Perhatian',
            'status' => 'Selesai',
            'tindakan' => 'Suhu CPU terdeteksi 72C idle akibat thermal paste kering.',
            'catatan_rekomendasi' => 'Segera jadwalkan repasting thermal paste dalam 3 hari ke depan.',
            'status_tindak_lanjut' => 'Perlu Pantauan',
        ]);

        $resDetailTL = $this->actingAs($admin)->get("/pemeliharaan/{$logPerluTindak->id}");
        $resDetailTL->assertStatus(200);
        $resDetailTL->assertSee('Buat Tiket Perbaikan', false);
        $resDetailTL->assertSee('Jadwalkan PM Tindak Lanjut', false);

        // Teknisi pemilik log (Bagas) boleh melihat detail lognya, sedangkan teknisi lain (Hafizh) diblokir 403
        $this->actingAs($bagas)->get("/pemeliharaan/{$logPerluTindak->id}")->assertStatus(200);
        $this->actingAs($hafizh)->get("/pemeliharaan/{$logPerluTindak->id}")->assertStatus(403);

        $resPrefillSchedule = $this->actingAs($admin)->get("/pemeliharaan/jadwalkan?from_log={$logPerluTindak->id}");
        $resPrefillSchedule->assertStatus(200);
        $resPrefillSchedule->assertSee('repasting thermal paste', false);

        $resCreateTicketTL = $this->actingAs($admin)->post("/pemeliharaan/{$logPerluTindak->id}/tindak-lanjut-tiket");
        $resCreateTicketTL->assertRedirect("/pemeliharaan/{$logPerluTindak->id}");
        $logPerluTindak->refresh();
        $this->assertNotNull($logPerluTindak->service_id);
        $this->assertSame('Eskalasi Tiket', $logPerluTindak->status_tindak_lanjut);
        $this->assertDatabaseHas('services', [
            'id' => $logPerluTindak->service_id,
            'status' => 'Baru',
            'prioritas' => 'Tinggi',
        ]);

        // 9. Admin dapat menghapus log pemeliharaan
        $resDelete = $this->actingAs($admin)->delete("/pemeliharaan/{$logPrima->id}");
        $resDelete->assertRedirect('/pemeliharaan');
        $this->assertNull(\App\Models\MaintenanceLog::find($logPrima->id));
    }

    public function test_admin_data_master_assets_and_locations_sync_with_pm_forms(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        // 1. Admin membuka Data Master dan melihat bagian Data Aset IT
        $this->actingAs($admin)->get('/admin/master')
            ->assertStatus(200)
            ->assertSee('Data Aset IT', false);

        // 2. Admin menambahkan Area Lokasi baru & Aset IT baru di Data Master
        $this->actingAs($admin)->post('/admin/lokasi', [
            'nama' => 'Ruang Panel Turbin Unit 3',
            'deskripsi' => 'Area kontrol turbin baru',
        ])->assertRedirect();

        $this->actingAs($admin)->post('/admin/aset', [
            'nama' => 'Server Historian Unit 3',
            'kode_perangkat' => 'SRV-HIST-03',
            'jenis_perangkat' => 'Server / UPS',
            'lokasi_ruangan' => 'Ruang Panel Turbin Unit 3',
        ])->assertRedirect();

        $asset = \App\Models\Asset::where('kode_perangkat', 'SRV-HIST-03')->firstOrFail();
        $location = \App\Models\Location::where('nama', 'Ruang Panel Turbin Unit 3')->firstOrFail();

        // 3. Form "+ Tambah jadwal" otomatis memuat Aset & Lokasi baru dari Data Master
        $this->actingAs($admin)->get('/pemeliharaan/schedules/create')
            ->assertStatus(200)
            ->assertSee('Server Historian Unit 3', false)
            ->assertSee('SRV-HIST-03', false)
            ->assertSee('Ruang Panel Turbin Unit 3', false);

        // 4. Admin membuat Jadwal Rutin memakai aset tsb -> otomatis muncul di "+ Jadwalkan" header
        $this->actingAs($admin)->post('/pemeliharaan/schedules', [
            'nama_aset' => 'Server Historian Unit 3',
            'kode_perangkat' => 'SRV-HIST-03',
            'lokasi_ruangan' => 'Ruang Panel Turbin Unit 3',
            'frekuensi' => 'Bulanan',
            'tanggal_berikutnya' => now()->addMonth()->toDateString(),
            'jam_mulai' => '08.30',
            'tim' => 'Tim IT Lapangan',
        ])->assertRedirect();

        $this->actingAs($admin)->get('/pemeliharaan/jadwalkan')
            ->assertStatus(200)
            ->assertSee('Server Historian Unit 3', false)
            ->assertSee('SRV-HIST-03', false)
            ->assertSee('Ruang Panel Turbin Unit 3', false);

        // 5. Admin mengganti nama lokasi di Data Master -> ikut tersinkron ke Master Aset & Jadwal Rutin
        $this->actingAs($admin)->put("/admin/lokasi/{$location->id}", [
            'nama' => 'Ruang Panel Turbin Unit 3A',
            'deskripsi' => 'Area kontrol turbin baru',
        ])->assertRedirect();

        $this->assertSame('Ruang Panel Turbin Unit 3A', $asset->fresh()->lokasi_ruangan);
        $this->assertDatabaseHas('maintenance_schedules', [
            'kode_perangkat' => 'SRV-HIST-03',
            'lokasi_ruangan' => 'Ruang Panel Turbin Unit 3A',
        ]);

        // 6. Admin menonaktifkan aset di Data Master -> hilang dari dropdown "+ Tambah jadwal"
        $this->actingAs($admin)->post("/admin/aset/{$asset->id}/toggle")->assertRedirect();
        $this->actingAs($admin)->get('/pemeliharaan/schedules/create')
            ->assertStatus(200)
            ->assertDontSee('SRV-HIST-03', false);

        // 7. Admin menambah aset tanpa mengisi Kode Aset -> kode otomatis ter-generate sesuai nama & lokasi
        $this->actingAs($admin)->post('/admin/aset', [
            'nama' => 'Kabel LAN',
            'kode_perangkat' => '',
            'lokasi_ruangan' => 'Ruang Kontrol Boiler',
            'jenis_perangkat' => 'Kabel LAN / Jaringan',
        ])->assertRedirect();

        $autoAsset = \App\Models\Asset::where('nama', 'Kabel LAN')->firstOrFail();
        $this->assertMatchesRegularExpression('/^LAN-BLR-\d{2}$/', $autoAsset->kode_perangkat);
    }

    public function test_technician_availability_leave_status_and_task_reassignment(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $hafizh = User::where('email', 'hafizh@s2p.co.id')->firstOrFail();
        $bagas = User::where('email', 'bagas@s2p.co.id')->firstOrFail();
        $giska = User::where('email', 'giska@s2p.co.id')->firstOrFail();
        $pegawai = User::where('role', 'pegawai')->firstOrFail();

        // Set waktu deterministik di jam operasional kerja (09:00 WIB)
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::create(2026, 11, 18, 9, 0, 0, 'Asia/Jakarta'));

        try {
            // 0. Halaman Pengaturan Profil sudah bersih (tidak bercampur kartu status) & /status-kerja khusus Teknisi
            $this->actingAs($hafizh)->get('/profil')
                ->assertStatus(200)
                ->assertDontSee('Status Ketersediaan', false);

            $this->actingAs($admin)->get('/status-kerja')->assertStatus(403);
            $this->actingAs($pegawai)->get('/status-kerja')->assertStatus(403);

            $this->actingAs($hafizh)->get('/status-kerja')
                ->assertStatus(200)
                ->assertSee('Status Kerja', false)
                ->assertSee('Available', false)
                ->assertSee('On Task', false);

            // 1. Check-in pagi: Giska mengaktifkan status Available -> ambil tiket -> otomatis On Task -> selesai -> otomatis Available
            // Pastikan tidak ada tiket aktif sebelumnya milik Giska
            Service::ownedByTeknisi($giska)->whereIn('status', ['Diproses', 'Menunggu sparepart'])->update(['status' => 'Selesai', 'tanggal_selesai' => now()]);

            $this->actingAs($giska->fresh())->post('/status-kerja', [
                'status_ketersediaan' => 'available',
            ])->assertRedirect();
            $this->assertTrue($giska->fresh()->isTersedia());

            $syncTicket = Service::create([
                'ticket_number' => 'TKT-SYNC-01',
                'user_id' => $pegawai->id,
                'nama_pelapor' => $pegawai->name,
                'divisi' => 'Operasi',
                'lokasi_ruangan' => 'Control Room',
                'jenis_perangkat' => 'Komputer PC',
                'keluhan' => 'Monitor berkedip',
                'prioritas' => 'Sedang',
                'status' => 'Baru',
            ]);

            // Klaim tiket -> otomatis On Task
            $this->actingAs($giska)->post("/services/{$syncTicket->id}/claim")->assertRedirect();
            $this->assertTrue($giska->fresh()->isOnTask());

            // Admin Dashboard & halaman khusus Status Teknisi (/admin/status-teknisi) menampilkan status & aktivitas live
            $this->actingAs($admin)->get('/admin')
                ->assertStatus(200)
                ->assertSee('On Task', false)
                ->assertSee('Beban kerja teknisi', false);

            $this->actingAs($admin)->get('/admin/status-teknisi')
                ->assertStatus(200)
                ->assertSee('Status Teknisi', false)
                ->assertSee('Pantauan Live', false)
                ->assertSee($giska->name, false)
                ->assertSee('TKT-SYNC-01', false)
                ->assertSee('Monitor berkedip', false);

            $this->actingAs($admin)->get('/admin/status-teknisi?status=on_task')
                ->assertStatus(200)
                ->assertSee($giska->name, false);

            $this->actingAs($admin)->get("/admin/status-teknisi/{$giska->id}")
                ->assertStatus(200)
                ->assertSee('Detail Teknisi', false)
                ->assertSee($giska->name, false)
                ->assertSee('Ringkasan hari ini', false)
                ->assertSee('Aktivitas hari ini', false)
                ->assertSee('Riwayat 7 hari', false)
                ->assertSee('Online hari ini', false);

            $this->actingAs($giska)->get("/admin/status-teknisi/{$giska->id}")->assertStatus(403);
            $this->actingAs($pegawai)->get("/admin/status-teknisi/{$giska->id}")->assertStatus(403);

            $this->actingAs($giska)->get('/admin/status-teknisi')->assertStatus(403);
            $this->actingAs($pegawai)->get('/admin/status-teknisi')->assertStatus(403);

            // Selesaikan tiket -> otomatis kembali Available
            $this->actingAs($giska)->post("/services/{$syncTicket->id}/done")->assertRedirect();
            $this->assertTrue($giska->fresh()->isTersedia());

            // 2. Jam pulang 17:05 WIB -> otomatis Offline dan tidak bisa aktifkan Available di luar jam kerja
            \Carbon\Carbon::setTestNow(\Carbon\Carbon::create(2026, 11, 18, 17, 5, 0, 'Asia/Jakarta'));
            $this->assertTrue($giska->fresh()->isOffline());
            $this->assertSame('Offline (Luar jam kerja)', $giska->fresh()->availability_short_note);

            $this->actingAs($giska)->post('/status-kerja', [
                'status_ketersediaan' => 'available',
            ])->assertSessionHas('error');

            // 2b. Keesokan pagi sebelum jam 08:00 WIB (07:45 WIB) -> di luar jam operasional, status tetap Offline
            \Carbon\Carbon::setTestNow(\Carbon\Carbon::create(2026, 11, 19, 7, 45, 0, 'Asia/Jakarta'));
            $this->assertFalse(User::isWithinWorkHours());
            $this->assertTrue($giska->fresh()->isOffline());
            $this->actingAs($giska)->post('/status-kerja', [
                'status_ketersediaan' => 'available',
            ])->assertSessionHas('error');

            // 3. Masuk jam kerja pagi (08:05 WIB) -> otomatis reset Offline (Belum aktif) sebelum teknisi check-in hari ini
            \Carbon\Carbon::setTestNow(\Carbon\Carbon::create(2026, 11, 19, 8, 5, 0, 'Asia/Jakarta'));
            $this->assertTrue(User::isWithinWorkHours());
            $this->assertTrue($giska->fresh()->isOffline());
            $this->assertSame('Offline (Belum aktif)', $giska->fresh()->availability_short_note);

            // Setelah check-in pagi, kembali Available
            $this->actingAs($giska->fresh())->post('/status-kerja', [
                'status_ketersediaan' => 'available',
            ])->assertRedirect();
            $this->assertTrue($giska->fresh()->isTersedia());

            // 4. Teknisi (Hafizh) mengajukan Cuti via /status-kerja + lepas jadwal PM ke tim
            $pmHafizh = \App\Models\MaintenanceLog::create([
                'log_number' => 'PM-CUTI-TEST-01',
                'tanggal_cek' => now()->addDays(2)->toDateString(),
                'periode_bulan' => now()->format('Y-m'),
                'kode_perangkat' => 'UPS-TEST-01',
                'nama_perangkat' => 'UPS Ruang Server',
                'jenis_perangkat' => 'Server / UPS',
                'divisi' => 'IT Support',
                'lokasi_ruangan' => 'Server Room',
                'user_id' => $hafizh->id,
                'teknisi_nama' => $hafizh->name,
                'status' => 'Terjadwal',
                'kondisi' => 'Prima',
                'tindakan' => 'Jadwal PM rutin',
                'status_tindak_lanjut' => 'Selesai',
            ]);

            $this->actingAs($hafizh)->post('/status-kerja', [
                'status_ketersediaan' => 'cuti',
                'cuti_mulai' => now()->toDateString(),
                'cuti_selesai' => now()->addDays(3)->toDateString(),
                'catatan_ketersediaan' => 'Cuti tahunan keluarga',
                'release_pm_to_team' => '1',
            ])->assertRedirect();

            $hafizh->refresh();
            $this->assertTrue($hafizh->isCuti());
            $this->assertNull($pmHafizh->fresh()->user_id);
            $this->assertSame('Belum ada', $pmHafizh->fresh()->teknisi_nama);

            // 5. Admin langsung menerima notifikasi cuti Hafizh dan tidak bisa menugaskan tiket baru ke Hafizh
            $adminNotifs = \App\Services\NotificationCenterService::getForUser($admin, 20, false);
            $hasLeaveNotif = collect($adminNotifs['items'])->contains(fn ($it) => str_contains($it['title'], $hafizh->name) && str_contains($it['title'], 'Sedang Cuti'));
            $this->assertTrue($hasLeaveNotif);

            $newTicket = Service::create([
                'ticket_number' => 'TKT-CUTI-01',
                'user_id' => $pegawai->id,
                'nama_pelapor' => $pegawai->name,
                'divisi' => 'Operasi',
                'lokasi_ruangan' => 'Control Room',
                'jenis_perangkat' => 'Komputer PC',
                'keluhan' => 'PC tidak menyala',
                'prioritas' => 'Tinggi',
                'status' => 'Baru',
            ]);

            $this->actingAs($admin)->post('/admin/assign-ticket', [
                'service_id' => $newTicket->id,
                'teknisi_id' => $hafizh->id,
            ])->assertSessionHas('error');
            $this->assertNull($newTicket->fresh()->teknisi_id);

            // 6. Admin mengatur Bagas Cuti & mengalihkan tiket aktif + jadwal PM Bagas ke Giska dalam 1 klik
            $bagasTicket = Service::create([
                'ticket_number' => 'TKT-CUTI-02',
                'user_id' => $pegawai->id,
                'nama_pelapor' => $pegawai->name,
                'divisi' => 'Operasi',
                'lokasi_ruangan' => 'Control Room',
                'jenis_perangkat' => 'Printer',
                'keluhan' => 'Printer macet',
                'prioritas' => 'Sedang',
                'status' => 'Diproses',
                'teknisi_id' => $bagas->id,
                'teknisi_nama' => $bagas->name,
            ]);

            $bagasPm = \App\Models\MaintenanceLog::create([
                'log_number' => 'PM-CUTI-TEST-02',
                'tanggal_cek' => now()->addDays(1)->toDateString(),
                'periode_bulan' => now()->format('Y-m'),
                'kode_perangkat' => 'PRN-TEST-02',
                'nama_perangkat' => 'Printer Control Room',
                'jenis_perangkat' => 'Printer',
                'divisi' => 'Operasi',
                'lokasi_ruangan' => 'Control Room',
                'user_id' => $bagas->id,
                'teknisi_nama' => $bagas->name,
                'status' => 'Terjadwal',
                'kondisi' => 'Prima',
                'tindakan' => 'Jadwal PM printer',
                'status_tindak_lanjut' => 'Selesai',
            ]);

            $this->actingAs($admin)->post("/admin/users/{$bagas->id}/availability", [
                'status_ketersediaan' => 'cuti',
                'cuti_mulai' => now()->toDateString(),
                'cuti_selesai' => now()->addDays(2)->toDateString(),
                'catatan_ketersediaan' => 'Izin dinas luar',
                'reassign_mode' => 'technician',
                'replacement_teknisi_id' => $giska->id,
            ])->assertRedirect();

            $this->assertTrue($bagas->fresh()->isCuti());
            $this->assertSame($giska->id, $bagasTicket->fresh()->teknisi_id);
            $this->assertSame($giska->id, $bagasPm->fresh()->user_id);
            $this->assertTrue($giska->fresh()->isOnTask());

            // 7. Jika tanggal selesai cuti sudah terlewat saat jam kerja, status efektif otomatis kembali Available
            $hafizh->update(['cuti_selesai' => now()->subDay()->toDateString()]);
            $this->assertTrue($hafizh->fresh()->isTersedia());
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    }

    public function test_teknisi_status_kerja_view_renders_compact_responsive_team_grid(): void
    {
        \Carbon\Carbon::setTestNow('2026-11-19 10:00:00');
        try {
            $teknisi = User::where('role', 'teknisi')->where('name', '!=', 'Tim IT Support')->firstOrFail();

            $response = $this->actingAs($teknisi)->get('/status-kerja');
            $response->assertStatus(200);
            $response->assertSee('Status Kerja');
            $response->assertSee('Pilih Status Kerja Anda');
            $response->assertSee('Beban Anda');
            $response->assertSee('Status Tim IT Hari Ini');
            $response->assertSee('team-cards-grid');
            $response->assertSee('team-filter-tabs');
            $response->assertSee('team-search-input');
            $response->assertSee('max-h-[380px]');
            $response->assertSee('Simpan Status Kerja');

            // Post update availability ke On Task
            $postResp = $this->actingAs($teknisi)->post('/status-kerja', [
                'status_ketersediaan' => 'on_task',
                'catatan_ketersediaan' => 'Pengecekan jaringan switch control room',
            ]);
            $postResp->assertRedirect();
            $this->assertSame('on_task', $teknisi->fresh()->status_ketersediaan);
            $this->assertSame('Pengecekan jaringan switch control room', $teknisi->fresh()->catatan_ketersediaan);
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    }

    public function test_maintenance_cetak_preview_and_pdf_download(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['name' => 'Admin Test', 'role' => 'admin']);
        $teknisi = User::where('role', 'teknisi')->where('name', '!=', 'Tim IT Support')->first()
            ?? User::factory()->create(['name' => 'Hafizh Test', 'role' => 'teknisi']);

        // Pastikan ada sampel data pemeliharaan
        $log = \App\Models\MaintenanceLog::create([
            'log_number' => 'PM-CETAK-TEST-01',
            'tanggal_cek' => now()->toDateString(),
            'periode_bulan' => now()->format('Y-m'),
            'kode_perangkat' => 'SRV-TEST-PDF-01',
            'nama_perangkat' => 'Server Core Database Turbin',
            'jenis_perangkat' => 'Server',
            'divisi' => 'Operasi Turbin',
            'lokasi_ruangan' => 'Ruang Server Turbin LT 2',
            'kondisi' => 'Prima',
            'kegiatan' => 'Pengecekan backup rutin & suhu storage',
            'tindakan' => 'Pembersihan heatsink, kalibrasi suhu, dan update patch',
            'status' => 'Selesai',
            'user_id' => $teknisi->id,
            'teknisi_nama' => $teknisi->name,
        ]);

        // 1. Pratinjau Web Cetak Langsung (Admin & Teknisi)
        $respAdmin = $this->actingAs($admin)->get('/pemeliharaan/cetak');
        $respAdmin->assertStatus(200);
        $respAdmin->assertSee('PT SUMBER SEGARA PRIMADAYA');
        $respAdmin->assertSee('FORM-IT-PM-REV.02');
        $respAdmin->assertSee('LAPORAN PELAKSANAAN PREVENTIVE MAINTENANCE');
        $respAdmin->assertSee('Server Core Database Turbin');
        $respAdmin->assertSee('Cetak Langsung (Ctrl+P)');
        $respAdmin->assertSee('Unduh File PDF');

        $respTeknisi = $this->actingAs($teknisi)->get('/pemeliharaan/cetak?divisi=Operasi+Turbin');
        $respTeknisi->assertStatus(200);
        $respTeknisi->assertSee('Server Core Database Turbin');

        // 2. Unduh PDF Asli via DomPDF
        $respPdf = $this->actingAs($admin)->get('/pemeliharaan/cetak?download=pdf');
        $respPdf->assertStatus(200);
        $respPdf->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('Laporan-PM-Aset-IT-PT-S2P', (string) $respPdf->headers->get('content-disposition'));

        // 3. Tombol Cetak / PDF tampil di halaman utama Log Pemeliharaan
        $respIndexAdmin = $this->actingAs($admin)->get('/pemeliharaan');
        $respIndexAdmin->assertStatus(200);
        $respIndexAdmin->assertSee('Cetak / PDF');

        $respIndexTeknisi = $this->actingAs($teknisi)->get('/pemeliharaan');
        $respIndexTeknisi->assertStatus(200);
        $respIndexTeknisi->assertSee('Cetak / PDF');

        // 4. Cetak Single Log (Berita Acara PM) Preview & PDF Download
        $respSinglePreview = $this->actingAs($teknisi)->get("/pemeliharaan/{$log->id}/cetak");
        $respSinglePreview->assertStatus(200);
        $respSinglePreview->assertSee('BERITA ACARA PREVENTIVE MAINTENANCE');
        $respSinglePreview->assertSee('FORM-IT-PM-BA-REV.02');
        $respSinglePreview->assertSee('Server Core Database Turbin');

        $respSinglePdf = $this->actingAs($teknisi)->get("/pemeliharaan/{$log->id}/cetak?download=pdf");
        $respSinglePdf->assertStatus(200);
        $respSinglePdf->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('Berita-Acara-PM', (string) $respSinglePdf->headers->get('content-disposition'));

        // 5. Tombol Cetak pada Detail Log mengarah ke halaman Cetak Single
        $respShow = $this->actingAs($teknisi)->get("/pemeliharaan/{$log->id}");
        $respShow->assertStatus(200);
        $respShow->assertSee(route('maintenance.cetakSingle', $log));

        $log->delete();
    }

    public function test_admin_status_teknisi_detail_linimasa_dan_jam_on_task_7_hari(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['name' => 'Admin Test', 'role' => 'admin']);
        $hafizh = User::where('email', 'hafizh@s2p.co.id')->first()
            ?? User::where('role', 'teknisi')->where('name', '!=', 'Tim IT Support')->firstOrFail();

        $resp = $this->actingAs($admin)->get("/admin/status-teknisi/{$hafizh->id}");
        $resp->assertStatus(200);
        $resp->assertSee('Aktivitas Teknisi');
        $resp->assertSee('Riwayat status dan pekerjaan satu teknisi dalam sehari');
        $resp->assertSee('Linimasa hari ini');
        $resp->assertSee('Ringkasan hari ini');
        $resp->assertSee('Waktu aktif');
        $resp->assertSee('Waktu On Task');
        $resp->assertSee('Tiket diambil');
        $resp->assertSee('Tiket selesai');
        $resp->assertSee('PM selesai');
        $resp->assertSee('Jam On Task, 7 hari terakhir');
        $resp->assertSee('Sen');
        $resp->assertSee('Sel');
        $resp->assertSee('Rab');
        $resp->assertSee('Kam');
        $resp->assertSee('Jum');
        $resp->assertSee('Sab');
        $resp->assertSee('Min');
    }
}
