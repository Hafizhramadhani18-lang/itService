<!-- ============================================================== -->
<!-- WIDGET FLOATING: WHATSAPP AI CHATBOX & VIRTUAL IT SUPPORT     -->
<!-- Standar Layanan IT Support PT S2P PLTU Karangkandri (24/7)     -->
<!-- ============================================================== -->

<div id="ai-chatbox-container" class="relative">

    <!-- FLOATING TRIGGER BUTTON (Pojok Kanan Bawah - Desain Elegan, Kompak & Korporat) -->
    <button type="button" 
            id="btn-open-ai-chat" 
            onclick="toggleAIChatbox()" 
            class="fixed bottom-6 right-6 z-50 flex items-center space-x-2.5 px-3.5 py-2.5 rounded-full bg-[#0e1a2b] hover:bg-[#152438] text-white border border-slate-700/80 hover:border-emerald-500/80 shadow-xl hover:shadow-2xl hover:shadow-teal-950/30 transition-all duration-300 group cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
            title="Buka Asisten AI IT Support & WhatsApp Helpdesk (24/7)">
        
        <!-- Icon Avatar Bot with Online Pulse -->
        <div class="relative flex-shrink-0">
            <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center text-white shadow-xs">
                <i class="fa-solid fa-robot text-xs"></i>
            </div>
            <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-400 border-2 border-[#0e1a2b] rounded-full"></span>
        </div>

        <!-- Label Singkat & Padat -->
        <div class="text-left leading-tight hidden sm:block pr-1">
            <div class="text-[11px] font-extrabold text-white flex items-center space-x-1.5">
                <span>Tanya AI Support</span>
                <span class="px-1.5 py-0.2 rounded text-[8px] font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">24/7</span>
            </div>
            <div class="text-[9.5px] text-slate-300 font-medium">Bantuan Cepat & WhatsApp</div>
        </div>

        <!-- WhatsApp Mini Icon -->
        <i class="fa-brands fa-whatsapp text-emerald-400 text-sm group-hover:scale-110 transition-transform"></i>
    </button>

    <!-- CHATBOX WINDOW POPUP (Modern Compact Floating Card) -->
    <div id="ai-chatbox-window" 
         class="fixed bottom-20 right-4 sm:right-6 w-[calc(100vw-2rem)] sm:w-[385px] max-w-[395px] h-[525px] max-h-[calc(100vh-6.5rem)] bg-white rounded-2xl shadow-2xl border border-slate-200/90 z-50 flex flex-col overflow-hidden transition-all duration-300 opacity-0 pointer-events-none translate-y-3">
        
        <!-- HEADER CHAT (Navy S2P Corporate Theme) -->
        <div class="bg-[#0e1a2b] text-white px-4 py-3 border-b border-slate-800 flex items-center justify-between select-none flex-shrink-0">
            <div class="flex items-center space-x-2.5 min-w-0">
                <div class="relative flex-shrink-0">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center text-white shadow-xs font-bold text-xs">
                        <i class="fa-solid fa-robot text-sm"></i>
                    </div>
                    <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-400 border-2 border-[#0e1a2b] rounded-full"></span>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center space-x-1.5">
                        <h3 class="font-extrabold text-xs text-white truncate">Virtual IT Support</h3>
                        <span class="px-1.5 py-0.2 rounded-full text-[8px] font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">AI</span>
                    </div>
                    <p class="text-[9.5px] text-slate-300 truncate">PT S2P PLTU Karangkandri &bull; Siaga 24/7</p>
                </div>
            </div>

            <!-- Header Action Buttons -->
            <div class="flex items-center space-x-1">
                <!-- Tombol Chat WA Helpdesk Langsung -->
                <a href="https://wa.me/6281234567890?text=Halo%20IT%20Support%20PT%20S2P%20Karangkandri,%20saya%20butuh%20bantuan%20teknisi." 
                   target="_blank" 
                   class="p-1.5 rounded-lg text-emerald-400 hover:text-white hover:bg-emerald-900/40 transition" 
                   title="Buka WhatsApp Helpdesk">
                    <i class="fa-brands fa-whatsapp text-sm"></i>
                </a>

                <!-- Tombol Email Helpdesk -->
                <button type="button" 
                        onclick="triggerQuickEmailModal()" 
                        class="p-1.5 rounded-lg text-teal-400 hover:text-white hover:bg-teal-900/40 transition cursor-pointer" 
                        title="Format Laporan Email">
                    <i class="fa-solid fa-envelope text-xs"></i>
                </button>

                <!-- Tombol Tutup Window -->
                <button type="button" 
                        onclick="toggleAIChatbox()" 
                        class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer" 
                        title="Tutup Chat">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
        </div>

        <!-- NOTIFICATION BANNER INFO -->
        <div class="bg-slate-50 border-b border-slate-100 px-3.5 py-1.5 flex items-center justify-between text-[10.5px] text-slate-600 flex-shrink-0">
            <div class="flex items-center space-x-1.5 truncate">
                <i class="fa-solid fa-shield-halved text-teal-600 text-[11px] flex-shrink-0"></i>
                <span class="truncate font-medium text-slate-700">Didukung SOP IT Resmi PLTU Cilacap</span>
            </div>
            <a href="{{ route('knowledge.base') }}" class="font-bold text-teal-700 hover:text-teal-900 underline flex-shrink-0 ml-2">Panduan &rarr;</a>
        </div>

        <!-- MESSAGES CONTAINER AREA -->
        <div id="ai-chat-messages" class="flex-1 overflow-y-auto p-3.5 space-y-3 bg-[#f8fafc] text-xs">
            
            <!-- Bot Initial Welcome Bubble -->
            <div class="flex items-start space-x-2.5 animate-fade-in">
                <div class="w-6 h-6 rounded-lg bg-teal-800 text-white flex items-center justify-center text-[10px] flex-shrink-0 mt-0.5 shadow-2xs">
                    <i class="fa-solid fa-headset text-[10px]"></i>
                </div>
                <div class="space-y-2 max-w-[88%]">
                    <div class="bg-white p-3 rounded-xl rounded-tl-sm border border-slate-200/90 shadow-2xs text-slate-800 leading-relaxed space-y-1">
                        <p class="font-bold text-slate-900 text-xs">Halo {{ Auth::user()->name ?? 'Bapak/Ibu' }}! 👋</p>
                        <p class="text-slate-600 text-[11px]">
                            Saya <strong>Asisten Virtual IT Support PT S2P</strong>. Silakan pilih topik kendala atau ketik keluhan Anda untuk diagnosa mandiri cepat sesuai SOP PLTU Karangkandri:
                        </p>
                    </div>

                    <!-- Quick Topic Chips (Rapi & Seimbang) -->
                    <div class="flex flex-wrap gap-1.5" id="ai-quick-chips">
                        <button type="button" onclick="sendQuickPrompt('Printer tidak bisa mencetak / offline')" 
                                class="px-2.5 py-1 rounded-lg bg-white hover:bg-teal-50 text-slate-700 hover:text-teal-900 border border-slate-200 hover:border-teal-400 text-[10px] font-bold transition shadow-2xs cursor-pointer flex items-center space-x-1.5">
                            <i class="fa-solid fa-print text-teal-600 text-[9px]"></i>
                            <span>Printer Error</span>
                        </button>

                        <button type="button" onclick="sendQuickPrompt('Jaringan LAN atau Wi-Fi putus / limited connection')" 
                                class="px-2.5 py-1 rounded-lg bg-white hover:bg-teal-50 text-slate-700 hover:text-teal-900 border border-slate-200 hover:border-teal-400 text-[10px] font-bold transition shadow-2xs cursor-pointer flex items-center space-x-1.5">
                            <i class="fa-solid fa-network-wired text-sky-600 text-[9px]"></i>
                            <span>Jaringan Putus</span>
                        </button>

                        <button type="button" onclick="sendQuickPrompt('Komputer PC lambat / hang / bluescreen')" 
                                class="px-2.5 py-1 rounded-lg bg-white hover:bg-teal-50 text-slate-700 hover:text-teal-900 border border-slate-200 hover:border-teal-400 text-[10px] font-bold transition shadow-2xs cursor-pointer flex items-center space-x-1.5">
                            <i class="fa-solid fa-desktop text-indigo-600 text-[9px]"></i>
                            <span>PC Lambat / Freeze</span>
                        </button>

                        <button type="button" onclick="sendQuickPrompt('Lupa kata sandi / reset akun domain Windows PLTU')" 
                                class="px-2.5 py-1 rounded-lg bg-white hover:bg-teal-50 text-slate-700 hover:text-teal-900 border border-slate-200 hover:border-teal-400 text-[10px] font-bold transition shadow-2xs cursor-pointer flex items-center space-x-1.5">
                            <i class="fa-solid fa-key text-amber-600 text-[9px]"></i>
                            <span>Reset Password</span>
                        </button>

                        <button type="button" onclick="sendQuickPrompt('Email Outlook / Zimbra tidak bisa kirim terima')" 
                                class="px-2.5 py-1 rounded-lg bg-white hover:bg-teal-50 text-slate-700 hover:text-teal-900 border border-slate-200 hover:border-teal-400 text-[10px] font-bold transition shadow-2xs cursor-pointer flex items-center space-x-1.5">
                            <i class="fa-solid fa-envelope text-rose-600 text-[9px]"></i>
                            <span>Email Outlook</span>
                        </button>

                        <button type="button" onclick="sendQuickPrompt('Bagaimana cara menggunakan rumus VLOOKUP di Excel?')" 
                                class="px-2.5 py-1 rounded-lg bg-white hover:bg-teal-50 text-slate-700 hover:text-teal-900 border border-slate-200 hover:border-teal-400 text-[10px] font-bold transition shadow-2xs cursor-pointer flex items-center space-x-1.5">
                            <i class="fa-solid fa-file-excel text-emerald-600 text-[9px]"></i>
                            <span>Rumus Excel</span>
                        </button>

                        <button type="button" onclick="sendQuickPrompt('Aplikasi SAP / Maximo PLTU tidak bisa login atau lambat')" 
                                class="px-2.5 py-1 rounded-lg bg-white hover:bg-teal-50 text-slate-700 hover:text-teal-900 border border-slate-200 hover:border-teal-400 text-[10px] font-bold transition shadow-2xs cursor-pointer flex items-center space-x-1.5">
                            <i class="fa-solid fa-laptop-code text-teal-600 text-[9px]"></i>
                            <span>SAP / Maximo ERP</span>
                        </button>
                    </div>

                    <span class="text-[9px] text-slate-400 block pl-0.5">Baru saja</span>
                </div>
            </div>

        </div>

        <!-- TYPING INDICATOR (Hidden by default) -->
        <div id="ai-typing-indicator" class="hidden px-3.5 py-2 bg-slate-50 text-[10.5px] text-slate-500 flex items-center space-x-2 border-t border-slate-100 flex-shrink-0">
            <span class="inline-flex space-x-1">
                <span class="w-1.5 h-1.5 bg-teal-600 rounded-full animate-bounce"></span>
                <span class="w-1.5 h-1.5 bg-teal-600 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                <span class="w-1.5 h-1.5 bg-teal-600 rounded-full animate-bounce [animation-delay:0.4s]"></span>
            </span>
            <span class="font-medium text-slate-600">AI Support sedang menyusun respon...</span>
        </div>

        <!-- BOTTOM INPUT & DISPATCH ACTIONS (Form Rapi, Bersih & Standar Perusahaan) -->
        <div class="p-2.5 bg-white border-t border-slate-200/90 space-y-1.5 flex-shrink-0">
            <form onsubmit="handleAIChatSubmit(event)" class="flex items-center space-x-1.5 bg-slate-50/80 hover:bg-white focus-within:bg-white rounded-xl border border-slate-200 focus-within:border-teal-600 focus-within:ring-2 focus-within:ring-teal-500/20 px-2.5 py-1 transition-all duration-200 shadow-2xs">
                <input type="text" 
                       id="ai-chat-input" 
                       placeholder="Ketik kendala Anda (mis: printer offline)..." 
                       autocomplete="off"
                       class="flex-1 text-xs bg-transparent border-0 outline-none placeholder:text-slate-400 text-slate-800 py-1">
                <button type="submit" 
                        class="w-7 h-7 rounded-lg bg-teal-700 hover:bg-teal-800 active:scale-95 text-white flex items-center justify-center transition shadow-2xs flex-shrink-0 cursor-pointer"
                        title="Kirim Pesan (Enter)">
                    <i class="fa-solid fa-paper-plane text-[10px]"></i>
                </button>
            </form>

            <div class="flex items-center justify-between text-[9.5px] text-slate-400 px-1 pt-0.5">
                <div class="flex items-center space-x-1 text-slate-500">
                    <i class="fa-solid fa-phone text-teal-600 text-[8px]"></i>
                    <span>Ext. 102 &bull; WA: 0812-3456-7890</span>
                </div>
                <button type="button" onclick="triggerQuickEmailModal()" class="text-teal-700 hover:text-teal-900 font-bold underline cursor-pointer flex items-center space-x-1">
                    <i class="fa-regular fa-envelope text-[9px]"></i>
                    <span>Lapor via Email &rarr;</span>
                </button>
            </div>
        </div>

    </div>

</div>

<!-- ============================================================== -->
<!-- MODAL: FORMAT DISPATCH RESMI VIA EMAIL (itsupport@s2p.co.id)   -->
<!-- ============================================================== -->
<div id="modal-email-dispatch" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 animate-fade-in space-y-4">
        
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center space-x-2.5">
                <div class="w-9 h-9 rounded-xl bg-teal-50 border border-teal-200/80 text-teal-700 flex items-center justify-center text-sm font-bold">
                    <i class="fa-solid fa-envelope-open-text"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-sm text-slate-900">Format Pelaporan Resmi via Email</h3>
                    <p class="text-[11px] text-slate-400">Unit IT Support & Infrastructure &bull; PT S2P</p>
                </div>
            </div>
            <button onclick="closeModalEmailDispatch()" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="space-y-3 text-xs">
            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-2 text-slate-700 font-mono text-[11px]">
                <div class="flex justify-between border-b border-slate-200/60 pb-1">
                    <span class="font-bold text-slate-500">Kepada:</span>
                    <span class="font-bold text-teal-800">itsupport@s2p.co.id</span>
                </div>
                <div class="flex justify-between border-b border-slate-200/60 pb-1">
                    <span class="font-bold text-slate-500">Subjek:</span>
                    <span class="font-semibold text-slate-900" id="email-preview-subject">[IT SERVICE] Laporan Gangguan - {{ Auth::user()->divisi ?? 'Operasional PLTU' }}</span>
                </div>
                <div>
                    <span class="font-bold text-slate-500 block mb-1">Isi Pesan:</span>
                    <textarea id="email-preview-body" rows="6" readonly class="w-full bg-white border border-slate-200 rounded-xl p-2.5 font-mono text-[10.5px] leading-relaxed text-slate-800 focus:outline-none">Yth. Tim IT Support PT S2P PLTU Karangkandri,

Mohon bantuan penanganan kendala teknis berikut:
- Nama Pelapor: {{ Auth::user()->name }}
- NIP / Akun: {{ Auth::user()->username ?? Auth::user()->nip ?? '-' }}
- Divisi / Unit: {{ Auth::user()->divisi ?? 'Operasional PLTU' }}
- Lokasi Ruangan: Gedung PLTU Karangkandri
- Deskripsi Kendala: [Jelaskan kendala perangkat / jaringan di sini]
- Waktu Terjadi: {{ now()->translatedFormat('d F Y, H:i') }} WIB

Demikian laporan ini kami sampaikan untuk dapat ditindaklanjuti. Terima kasih.</textarea>
                </div>
            </div>

            <p class="text-[11px] text-slate-500">
                <i class="fa-solid fa-circle-info text-teal-600 mr-1"></i>
                Klik tombol di bawah untuk langsung membuka aplikasi email default Anda (Outlook / Thunderbird / Webmail) dengan draft yang sudah otomatis terisi.
            </p>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
            <button type="button" onclick="copyEmailBody()" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center space-x-1.5 cursor-pointer">
                <i class="fa-regular fa-copy text-xs"></i>
                <span id="btn-copy-email-label">Salin Format Teks</span>
            </button>

            <div class="flex items-center space-x-2">
                <button type="button" onclick="closeModalEmailDispatch()" class="px-4 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-600 border border-slate-300 text-xs font-bold transition cursor-pointer">
                    Tutup
                </button>
                <a id="btn-open-mailto" 
                   href="mailto:itsupport@s2p.co.id?subject=%5BIT%20SERVICE%5D%20Laporan%20Gangguan%20-%20{{ urlencode(Auth::user()->divisi ?? 'Operasional') }}&body={{ urlencode("Yth. Tim IT Support PT S2P,\n\nMohon bantuan penanganan kendala:\n- Pelapor: " . (Auth::user()->name ?? 'Karyawan') . "\n- Divisi: " . (Auth::user()->divisi ?? 'PLTU') . "\n- Lokasi: Ruang Kerja\n- Kendala: ") }}" 
                   class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-[#0f766e] hover:bg-[#115e59] text-white text-xs font-extrabold transition shadow-xs cursor-pointer">
                    <i class="fa-solid fa-paper-plane text-xs"></i>
                    <span>Buka di Email</span>
                </a>
            </div>
        </div>

    </div>
</div>

<!-- ============================================================== -->
<!-- JAVASCRIPT KNOWLEDGE BASE AI & CHAT INTERACTION ENGINE         -->
<!-- ============================================================== -->
<script>
    // Knowledge Base SOP Troubleshooting IT PT S2P PLTU Karangkandri
    const aiKnowledgeBase = [
        {
            keywords: ['printer', 'cetak', 'print', 'kertas', 'spooler', 'tinta', 'toner', 'roller'],
            title: 'SOP Troubleshooting Printer & Perangkat Cetak',
            diagnosa: 'Kemungkinan antrian print spooler macet (stuck), kabel USB/LAN kendur, atau IP Printer offline.',
            steps: [
                'Pastikan lampu indikator printer berwarna hijau (Ready) dan kabel daya / kabel LAN terpasang erat.',
                'Restart layanan Print Spooler: Buka Command Prompt & ketik: <code>net stop spooler && net start spooler</code>.',
                'Periksa apakah ada kertas macet (paper jam) pada tray atau roller penarik.',
                'Jika printer jaringan (LAN), pastikan ping ke IP printer berhasil (misal: ping 192.168.10.x).'
            ],
            waTemplate: 'Halo Teknisi IT Support, printer di ruangan saya tidak bisa mencetak / offline. Saya sudah cek kabel dan restart spooler tapi masih berkendala. Mohon bantuan teknisi lapangan.'
        },
        {
            keywords: ['jaringan', 'lan', 'wifi', 'wi-fi', 'internet', 'kabel', 'rto', 'ip', 'disconnected', 'putus'],
            title: 'SOP Penanganan Gangguan Jaringan LAN & Wi-Fi',
            diagnosa: 'Koneksi ke switch access terputus, kabel LAN UTP longgar, atau konfigurasi DHCP IP release.',
            steps: [
                'Periksa kabel LAN di belakang CPU dan port wall-plate: pastikan lampu LED indikator menyala hijau/oranye kedip.',
                'Coba lepas kabel LAN, tunggu 5 detik, lalu tancapkan kembali hingga berbunyi "klik".',
                'Buka CMD lalu ketik: <code>ipconfig /release</code> dilanjutkan <code>ipconfig /renew</code>.',
                'Coba tes ping ke gateway lokal: <code>ping 192.168.1.1 -t</code>. Jika RTO, kemungkinan port switch drop.'
            ],
            waTemplate: 'Halo Teknisi IT Support, koneksi jaringan LAN / Wi-Fi di ruangan saya terputus (unidentified network). Sudah coba lepas-pasang kabel dan ipconfig renew belum berhasil. Mohon bantuan pengecekan switch/jaringan.'
        },
        {
            keywords: ['komputer', 'pc', 'lambat', 'lemot', 'hang', 'freeze', 'bluescreen', 'bsod', 'restart'],
            title: 'SOP Penanganan Komputer PC Lemot / Hang / Blue Screen',
            diagnosa: 'Beban memori RAM penuh, harddisk bad sector/100% usage, atau sistem OS korup.',
            steps: [
                'Buka Task Manager (Ctrl + Shift + Esc) untuk memeriksa aplikasi dengan utilisasi CPU atau Memory tinggi.',
                'Tutup tab browser yang berlebihan dan matikan aplikasi background yang tidak diperlukan.',
                'Lakukan restart komputer untuk mereset cache memory Windows.',
                'Jika muncul Blue Screen (BSOD), catat kode STOP ERROR dan segera hubungi teknisi IT Support.'
            ],
            waTemplate: 'Halo Teknisi IT Support, komputer kerja saya mengalami kendala lambat parah / sering hang. Sudah dicoba restart masih berkendala. Mohon bantuan pengecekan fisik perangkat.'
        },
        {
            keywords: ['password', 'sandi', 'lupa', 'reset', 'akun', 'login', 'terkunci', 'domain', 'windows'],
            title: 'SOP Permohonan Reset Password Akun & Domain PLTU',
            diagnosa: 'Akun terblokir akibat salah input password lebih dari 3 kali atau password periodik telah kedaluwarsa.',
            steps: [
                'Pastikan tombol Caps Lock pada keyboard tidak aktif saat mengetik kata sandi.',
                'Siapkan informasi NIP Karyawan, nama lengkap, dan divisi pemohon.',
                'Permohonan reset password domain Windows / Active Directory harus diverifikasi oleh staf IT resmi.',
                'Hubungi IT Support via Telepon Ext. 102 atau klik tombol WhatsApp untuk reset instan.'
            ],
            waTemplate: 'Halo IT Support PT S2P, saya ingin mengajukan permohonan reset password akun domain Windows kerja saya. Mohon bantuan reset.'
        },
        {
            keywords: ['email', 'outlook', 'zimbra', 'surat', 'inbox', 'kirim', 'terima', 'smtp', 'pop3', 'imap'],
            title: 'SOP Konfigurasi & Gangguan Email Korporat Outlook',
            diagnosa: 'Kapasitas mailbox (PST/OST) melebihi kuota kuota atau koneksi ke server email terputus.',
            steps: [
                'Periksa status koneksi di pojok kanan bawah Outlook (harus bertuliskan "Connected to Exchange").',
                'Periksa ukuran folder Deleted Items dan Spam, lakukan pengosongan jika mailbox penuh.',
                'Coba akses email alternatif melalui Webmail browser di https://mail.s2p.co.id.',
                'Jika sertifikat SSL pop-up muncul, klik "Trust Certificate" atau hubungi IT Support.'
            ],
            waTemplate: 'Halo Teknisi IT Support, aplikasi email Outlook saya tidak bisa sinkronisasi kirim/terima email. Mohon panduan atau pengecekan server email.'
        },
        {
            keywords: ['sap', 'maximo', 'erp', 'aplikasi', 'sistem', 'database', 'transaksi'],
            title: 'SOP Kendala Akses Aplikasi Operasional PLTU (SAP / Maximo)',
            diagnosa: 'Server ERP sedang proses batching, sesi login aktif terkunci (lock session), atau koneksi intranet putus.',
            steps: [
                'Pastikan PC terhubung ke jaringan LAN internal PLTU (bukan koneksi internet publik / hotspot HP).',
                'Jika akun terkunci karena multi-login, tunggu 15 menit agar sesi otomatis terminate.',
                'Hapus cache temporary aplikasi melalui menu Options / Settings.',
                'Jika masalah berlanjut, laporkan kode error transaksi SAP/Maximo ke Helpdesk IT.'
            ],
            waTemplate: 'Halo IT Support PT S2P, saya mengalami error saat membuka aplikasi SAP / Maximo PLTU. Mohon bantuan unlock sesi atau pengecekan server ERP.'
        }
    ];

    function toggleAIChatbox() {
        const win = document.getElementById('ai-chatbox-window');
        if (!win) return;
        const isHidden = win.classList.contains('pointer-events-none');
        if (isHidden) {
            win.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-4', 'translate-y-3');
            win.classList.add('opacity-100', 'pointer-events-auto', 'translate-y-0');
            const inp = document.getElementById('ai-chat-input');
            if (inp) setTimeout(() => inp.focus(), 200);
        } else {
            win.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
            win.classList.add('opacity-0', 'pointer-events-none', 'translate-y-3');
        }
    }

    function sendQuickPrompt(promptText) {
        const inp = document.getElementById('ai-chat-input');
        if (inp) {
            inp.value = promptText;
            processUserChat(promptText);
            inp.value = '';
        }
    }

    function handleAIChatSubmit(e) {
        e.preventDefault();
        const inp = document.getElementById('ai-chat-input');
        if (!inp) return;
        const query = inp.value.trim();
        if (!query) return;
        inp.value = '';
        processUserChat(query);
    }

    function processUserChat(userText) {
        const container = document.getElementById('ai-chat-messages');
        const indicator = document.getElementById('ai-typing-indicator');
        if (!container) return;

        // 1. Append User Message
        const timeNow = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const userBubble = document.createElement('div');
        userBubble.className = 'flex items-start justify-end space-x-2 animate-fade-in';
        userBubble.innerHTML = `
            <div class="space-y-1 max-w-[85%] text-right">
                <div class="bg-gradient-to-r from-emerald-600 to-teal-700 text-white p-3 rounded-2xl rounded-tr-sm shadow-2xs leading-relaxed text-left">
                    <p>${escapeHtml(userText)}</p>
                </div>
                <span class="text-[9px] text-slate-400 block pr-1">${timeNow}</span>
            </div>
        `;
        container.appendChild(userBubble);
        container.scrollTop = container.scrollHeight;

        // Show typing indicator
        if (indicator) indicator.classList.remove('hidden');

        // 2. Kirim ke Endpoint AI Backend (Google Gemini AI + Database SOP RAG)
        fetch('{{ route("ai.chat") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ message: userText })
        })
        .then(res => {
            if (!res.ok) throw new Error('Network error');
            return res.json();
        })
        .then(data => {
            if (indicator) indicator.classList.add('hidden');
            renderBotResponse(data, userText, timeNow);
        })
        .catch(err => {
            console.warn('AI API fallback to local:', err);
            if (indicator) indicator.classList.add('hidden');
            renderLocalFallbackResponse(userText, timeNow);
        });
    }

    function renderBotResponse(data, userText, timeNow) {
        const container = document.getElementById('ai-chat-messages');
        if (!container) return;

        const botBubble = document.createElement('div');
        botBubble.className = 'flex items-start space-x-2.5 animate-fade-in';

        // Badges
        let badgeHtml = '';
        if (data.source === 'gemini_ai') {
            badgeHtml += `<span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-emerald-50 text-emerald-800 border border-emerald-300 flex items-center space-x-1"><i class="fa-solid fa-wand-magic-sparkles text-[8px] text-amber-500"></i><span>Gemini AI</span></span>`;
        }
        if (data.sop_title) {
            badgeHtml += `<span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-teal-50 text-teal-700 border border-teal-200 truncate max-w-[200px]" title="${escapeHtml(data.sop_title)}">SOP: ${escapeHtml(data.sop_title)}</span>`;
        }

        // Parse formatted reply (markdown bold & line breaks)
        let formattedReply = escapeHtml(data.reply || '')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/\n/g, '<br>');

        // Steps list
        let stepsHtml = '';
        if (data.steps && Array.isArray(data.steps) && data.steps.length > 0) {
            const stepItems = data.steps.map((s, idx) => `
                <li class="flex items-start space-x-2">
                    <span class="w-4 h-4 rounded-full bg-teal-100 text-teal-800 text-[10px] font-black flex items-center justify-center flex-shrink-0 mt-0.5">${idx + 1}</span>
                    <span class="text-slate-700">${escapeHtml(s)}</span>
                </li>
            `).join('');

            stepsHtml = `
                <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-200/80 my-2">
                    <span class="font-bold text-[10px] text-slate-500 uppercase tracking-wider block mb-1.5">Langkah Panduan Mandiri:</span>
                    <ul class="space-y-1.5 text-[11px]">
                        ${stepItems}
                    </ul>
                </div>
            `;
        }

        // Command Box
        let cmdHtml = '';
        if (data.cmd) {
            cmdHtml = `
                <div class="bg-slate-900 text-slate-100 p-2.5 rounded-xl font-mono text-[10px] space-y-1.5 my-2">
                    <div class="flex items-center justify-between text-slate-400 border-b border-slate-800 pb-1">
                        <span class="text-[9px]">${escapeHtml(data.cmd_title || 'Perintah Terminal / CMD')}</span>
                        <button type="button" onclick="copySnippetText(this)" data-code="${escapeHtml(data.cmd)}" class="text-teal-400 hover:text-white transition cursor-pointer">
                            <i class="fa-regular fa-copy text-[10px]"></i> Salin
                        </button>
                    </div>
                    <pre class="overflow-x-auto whitespace-pre leading-relaxed text-teal-300">${escapeHtml(data.cmd)}</pre>
                </div>
            `;
        }

        // Warning Box
        let warningHtml = '';
        if (data.warning) {
            warningHtml = `
                <div class="p-2 rounded-xl bg-amber-50 border border-amber-200/80 text-[10.5px] text-amber-800 flex items-start space-x-1.5 my-1.5">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 text-xs flex-shrink-0 mt-0.5"></i>
                    <span><strong>Perhatian:</strong> ${escapeHtml(data.warning)}</span>
                </div>
            `;
        }

        const waUrl = data.wa_url || 'https://wa.me/6281234567890';
        const ticketUrl = data.ticket_url || '{{ route("services.create") }}';

        botBubble.innerHTML = `
            <div class="w-7 h-7 rounded-xl bg-teal-800 text-white flex items-center justify-center text-xs flex-shrink-0 mt-0.5 shadow-2xs">
                <i class="fa-solid fa-robot text-[11px]"></i>
            </div>
            <div class="space-y-2 max-w-[88%]">
                <div class="bg-white p-3.5 rounded-2xl rounded-tl-sm border border-slate-200/90 shadow-2xs text-slate-800 space-y-2 leading-relaxed">
                    ${badgeHtml ? `<div class="flex flex-wrap items-center gap-1.5 pb-2 border-b border-slate-100">${badgeHtml}</div>` : ''}
                    
                    <div class="text-[11px] text-slate-700 leading-relaxed">${formattedReply}</div>

                    ${stepsHtml}
                    ${cmdHtml}
                    ${warningHtml}

                    <div class="pt-2 border-t border-slate-100">
                        <p class="text-[10px] text-slate-400 italic mb-2">Masih terkendala atau butuh teknisi lapangan?</p>
                        <div class="flex flex-col sm:flex-row gap-1.5">
                            <a href="${waUrl}" target="_blank" 
                               class="flex-1 inline-flex items-center justify-center space-x-1 px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold transition shadow-2xs">
                                <i class="fa-brands fa-whatsapp text-xs"></i>
                                <span>Chat WA Teknisi</span>
                            </a>
                            <a href="${ticketUrl}" 
                               class="inline-flex items-center justify-center space-x-1 px-3 py-2 rounded-xl bg-[#0e1a2b] hover:bg-[#152438] text-white text-[11px] font-bold transition shadow-2xs">
                                <i class="fa-solid fa-ticket text-xs"></i>
                                <span>Buat Tiket</span>
                            </a>
                        </div>
                    </div>
                </div>
                <span class="text-[9px] text-slate-400 block pl-1">${timeNow}</span>
            </div>
        `;

        container.appendChild(botBubble);
        container.scrollTop = container.scrollHeight;
    }

    function renderLocalFallbackResponse(userText, timeNow) {
        const lower = userText.toLowerCase();
        let matched = null;
        let highestScore = 0;

        for (const item of aiKnowledgeBase) {
            let score = 0;
            for (const kw of item.keywords) {
                if (lower.includes(kw)) score++;
            }
            if (score > highestScore) {
                highestScore = score;
                matched = item;
            }
        }

        const fallbackData = {
            source: 'local_sop_database',
            reply: matched ? `Berdasarkan SOP **${matched.title}**, berikut diagnosa dan langkah penanganan mandiri:` : `Terima kasih atas laporan Anda. Untuk kendala *"${userText}"*, tim teknisi IT Support siap membantu Anda langsung.`,
            sop_title: matched ? matched.title : null,
            steps: matched ? matched.steps : ['Periksa sambungan kabel dan daya perangkat.', 'Hubungi Helpdesk IT Support via Telepon Ext. 102 atau WhatsApp siaga.'],
            wa_url: "https://wa.me/6281234567890?text=" + encodeURIComponent("Halo IT Support PT S2P, saya butuh penanganan kendala: " + userText),
            ticket_url: "{{ route('services.create') }}?kendala=" + encodeURIComponent(userText)
        };

        renderBotResponse(fallbackData, userText, timeNow);
    }

    function copySnippetText(btn) {
        const code = btn.getAttribute('data-code');
        if (!code) return;
        navigator.clipboard.writeText(code).then(() => {
            const old = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check text-[10px] text-emerald-400"></i> Tersalin!';
            setTimeout(() => btn.innerHTML = old, 2000);
        });
    }

    function triggerQuickEmailModal() {
        const modal = document.getElementById('modal-email-dispatch');
        if (modal) modal.classList.remove('hidden');
    }

    function closeModalEmailDispatch() {
        const modal = document.getElementById('modal-email-dispatch');
        if (modal) modal.classList.add('hidden');
    }

    function copyEmailBody() {
        const ta = document.getElementById('email-preview-body');
        const lbl = document.getElementById('btn-copy-email-label');
        if (!ta) return;
        navigator.clipboard.writeText(ta.value).then(() => {
            if (lbl) {
                const old = lbl.innerText;
                lbl.innerText = 'Tersalin!';
                setTimeout(() => lbl.innerText = old, 2000);
            }
        });
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
</script>
