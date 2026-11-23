<!-- Tanggal Sesuai Standar Korporat -->
<div class="hidden lg:inline-flex items-center space-x-1.5 px-3 py-2 bg-white rounded-xl border border-slate-200/80 text-xs font-semibold text-slate-600 shadow-xs">
    <i class="fa-regular fa-calendar text-teal-700 text-xs"></i>
    <span>{{ \Carbon\Carbon::now()->locale('id')->isoFormat('dddd, D MMMM Y') }}</span>
</div>

@if(Auth::check())
@php
    $notifItems = $headerNotifications ?? [];
    $notifUnreadCount = $headerUnreadNotifCount ?? 0;
    $notifViewAllUrl = $headerNotifViewAllRoute ?? (Auth::user()->isAdmin() ? route('admin.notifikasi') : (Auth::user()->isTeknisi() ? route('notifications.index') : route('services.index')));

    $hasFreshUnread = false;
    foreach ($notifItems as $it) {
        if (!empty($it['is_unread']) && !empty($it['timestamp']) && $it['timestamp'] instanceof \Carbon\Carbon) {
            if ($it['timestamp']->diffInMinutes(now()) <= 3) {
                $hasFreshUnread = true;
                break;
            }
        }
    }
@endphp
<!-- Lonceng Notifikasi Terpadu (Admin & Teknisi) -->
<div class="relative">
    <button type="button" onclick="toggleNotifDropdown()" id="notif-btn" 
            class="relative w-9 h-9 bg-white hover:bg-teal-50/40 text-slate-700 border border-slate-200/80 hover:border-teal-500/50 rounded-xl flex items-center justify-center shadow-xs transition duration-150 focus:outline-none focus:ring-2 focus:ring-teal-600/30 cursor-pointer"
            title="Notifikasi Sistem">
        <i id="header-notif-bell-icon" class="fa-regular fa-bell text-sm text-slate-700 transition"></i>
        <!-- Badge Notifikasi Merah -->
        <span id="header-notif-bell-badge" class="{{ $notifUnreadCount > 0 ? '' : 'hidden' }} absolute -top-1 -right-1 flex h-4 min-w-[16px] px-1 items-center justify-center rounded-full bg-rose-500 text-[10px] font-extrabold text-white shadow-xs">
            {{ $notifUnreadCount }}
        </span>
    </button>

    <!-- Dropdown Menu Notifikasi -->
    <div id="notif-dropdown" class="hidden absolute right-0 mt-2.5 w-[330px] sm:w-[390px] bg-white text-slate-800 text-left rounded-2xl shadow-2xl border border-slate-200/90 overflow-hidden z-50">
        
        <!-- Header Bar -->
        <div class="px-4 pt-3.5 pb-2.5 flex items-center justify-between border-b border-slate-100/80">
            <div class="flex items-center space-x-2">
                <span class="text-sm font-extrabold text-slate-900 tracking-tight">Notifikasi</span>
                @if($notifUnreadCount > 0)
                    <span id="header-notif-pill" class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-teal-50 text-teal-700 border border-teal-200/50">
                        {{ $notifUnreadCount }} baru
                    </span>
                @else
                    <span id="header-notif-pill" class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-500">
                        Semua terbaca
                    </span>
                @endif
            </div>
            
            <div class="flex items-center space-x-2">
                <!-- Tombol Audio Mute/Unmute -->
                <button type="button" onclick="toggleNotifSound(event)" id="btn-toggle-notif-sound"
                        class="px-2 py-1 rounded-lg text-slate-500 hover:text-teal-700 hover:bg-slate-100 transition cursor-pointer flex items-center space-x-1.5"
                        title="Suara Notifikasi: Aktif (Klik untuk Membisukan / Mute)">
                    <i id="icon-notif-sound" class="fa-solid fa-volume-high text-xs text-teal-700"></i>
                    <span id="text-notif-sound" class="text-[11px] font-semibold text-slate-600">Aktif</span>
                </button>

                <!-- Tombol Tandai Semua Dibaca -->
                <div id="header-notif-mark-all-container" class="{{ $notifUnreadCount > 0 ? 'inline-flex items-center' : 'hidden' }}">
                    <span class="text-slate-200 mx-1">|</span>
                    <form action="{{ route('notifications.markAllRead') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-xs text-teal-700 hover:text-teal-900 hover:underline font-bold cursor-pointer">
                            Tandai semua dibaca
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Filter Pills (Semua & Belum dibaca) -->
        <div class="px-4 pt-2.5 pb-2.5 border-b border-slate-100 flex items-center space-x-2 bg-slate-50/50">
            <button type="button" onclick="filterHeaderNotifDropdown('all')" id="header-notif-tab-all"
                    class="px-3.5 py-1 rounded-full text-xs font-bold transition cursor-pointer bg-[#092635] text-white shadow-2xs">
                Semua
            </button>
            <button type="button" onclick="filterHeaderNotifDropdown('unread')" id="header-notif-tab-unread"
                    class="px-3.5 py-1 rounded-full text-xs font-semibold transition cursor-pointer bg-white border border-slate-200 text-slate-600 hover:bg-slate-50">
                Belum dibaca &bull; <span id="header-notif-tab-count">{{ $notifUnreadCount }}</span>
            </button>
        </div>

        <!-- Notification List -->
        <div id="header-notif-list" class="max-h-[360px] overflow-y-auto divide-y divide-slate-100">
            @forelse($notifItems as $item)
            <a href="{{ $item['url'] }}"
               onclick="markHeaderNotifRead(event, '{{ $item['id'] }}')"
               data-unread="{{ $item['is_unread'] ? '1' : '0' }}"
               class="header-notif-row px-4 py-3 flex items-start justify-between gap-3 transition group {{ $item['is_unread'] ? 'bg-teal-50/35 hover:bg-teal-50/65' : 'bg-white hover:bg-slate-50' }}">
                
                <div class="flex items-start space-x-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl {{ $item['icon_bg'] }} flex items-center justify-center shrink-0 mt-0.5">
                        <i class="{{ $item['icon'] }} text-xs"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-slate-900 leading-snug group-hover:text-teal-800 transition">
                            {{ $item['title'] }}
                        </div>
                        <div class="text-[11px] text-slate-500 mt-0.5 leading-relaxed line-clamp-2">
                            {{ $item['subtitle'] }}
                        </div>
                    </div>
                </div>

                <div class="flex flex-col items-end shrink-0 space-y-1.5 text-right pt-0.5">
                    <span class="text-[11px] text-slate-400 font-medium whitespace-nowrap">
                        {{ $item['time_label'] }}
                    </span>
                    @if($item['is_unread'])
                        <span class="w-2 h-2 rounded-full bg-teal-600" title="Belum dibaca"></span>
                    @endif
                </div>
            </a>
            @empty
            <div class="p-6 text-center text-slate-400 text-xs">
                <div class="w-10 h-10 rounded-full bg-teal-50 text-teal-600 flex items-center justify-center mx-auto mb-2 text-sm shadow-2xs">
                    <i class="fa-solid fa-check"></i>
                </div>
                <span class="font-bold text-slate-800 block text-xs">Tidak ada notifikasi baru hari ini</span>
                <span class="text-[11px] text-slate-400 mt-1 block leading-relaxed max-w-[260px] mx-auto">
                    Notifikasi otomatis di-reset setiap hari (00:00 WIB). Riwayat sebelumnya tersimpan rapi di Pusat Notifikasi.
                </span>
            </div>
            @endforelse
        </div>

        <!-- Empty Unread Filter State -->
        <div id="header-notif-empty-unread" class="hidden p-6 text-center text-slate-400 text-xs">
            <div class="w-9 h-9 rounded-full bg-teal-50 text-teal-600 flex items-center justify-center mx-auto mb-2 text-sm">
                <i class="fa-solid fa-check-double"></i>
            </div>
            <span class="font-bold text-slate-800 block">Semua notifikasi telah dibaca</span>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Tidak ada notifikasi belum dibaca saat ini.</span>
        </div>

        <!-- Footer Link -->
        <div class="px-4 py-3 border-t border-slate-100 bg-white text-center">
            <a href="{{ $notifViewAllUrl }}" class="text-xs font-bold text-teal-700 hover:text-teal-900 hover:underline transition flex items-center justify-center space-x-1.5">
                <span>Lihat semua notifikasi</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>
    </div>
</div>

<script>
    function filterHeaderNotifDropdown(mode) {
        const tabAll = document.getElementById('header-notif-tab-all');
        const tabUnread = document.getElementById('header-notif-tab-unread');
        const emptyBox = document.getElementById('header-notif-empty-unread');
        const listBox = document.getElementById('header-notif-list');

        if (mode === 'all') {
            if (tabAll) tabAll.className = 'px-3.5 py-1 rounded-full text-xs font-bold transition cursor-pointer bg-[#092635] text-white shadow-2xs';
            if (tabUnread) tabUnread.className = 'px-3.5 py-1 rounded-full text-xs font-semibold transition cursor-pointer bg-white border border-slate-200 text-slate-600 hover:bg-slate-50';
        } else {
            if (tabUnread) tabUnread.className = 'px-3.5 py-1 rounded-full text-xs font-bold transition cursor-pointer bg-[#092635] text-white shadow-2xs';
            if (tabAll) tabAll.className = 'px-3.5 py-1 rounded-full text-xs font-semibold transition cursor-pointer bg-white border border-slate-200 text-slate-600 hover:bg-slate-50';
        }

        let visibleRows = 0;
        document.querySelectorAll('.header-notif-row').forEach(row => {
            const isUnread = row.getAttribute('data-unread') === '1';
            if (mode === 'all' || (mode === 'unread' && isUnread)) {
                row.classList.remove('hidden');
                visibleRows++;
            } else {
                row.classList.add('hidden');
            }
        });

        if (emptyBox && listBox) {
            if (mode === 'unread' && visibleRows === 0) {
                emptyBox.classList.remove('hidden');
                listBox.classList.add('hidden');
            } else {
                emptyBox.classList.add('hidden');
                listBox.classList.remove('hidden');
            }
        }
    }

    function markHeaderNotifRead(e, notifId) {
        if (navigator.sendBeacon) {
            const data = new FormData();
            data.append('_token', '{{ csrf_token() }}');
            data.append('notif_id', notifId);
            navigator.sendBeacon('{{ route('notifications.markRead') }}', data);
        } else {
            fetch('{{ route('notifications.markRead') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ notif_id: notifId }),
                keepalive: true
            });
        }
    }

    // ========================================================
    // SUARA NOTIFIKASI DUAL-ENGINE (HTML5 WAV & WEB AUDIO API)
    // ========================================================
    let notifAudioEl = null;

    function getAudioElement() {
        if (!notifAudioEl) {
            notifAudioEl = new Audio('{{ asset('sounds/notif.wav') }}?v=iphone');
            notifAudioEl.preload = 'auto';
        }
        return notifAudioEl;
    }

    let notifAudioCtx = null;
    function getNotifAudioContext() {
        if (!notifAudioCtx) {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (AudioContextClass) {
                notifAudioCtx = new AudioContextClass();
            }
        }
        return notifAudioCtx;
    }

    function playWebAudioChimeFallback() {
        try {
            const ctx = getNotifAudioContext();
            if (!ctx) return;

            const trigger = function() {
                const now = ctx.currentTime;
                // Melodi Tri-tone Khas iPhone: 1 - 5 - 8 (C5 -> G5 -> C6)
                const notes = [
                    { f: 523.25, time: now, dur: 0.22, vol: 0.35 },          // Nada 1: C5
                    { f: 783.99, time: now + 0.135, dur: 0.22, vol: 0.40 },  // Nada 2: G5
                    { f: 1046.50, time: now + 0.270, dur: 0.70, vol: 0.48 }  // Nada 3: C6 (Oktaf)
                ];

                notes.forEach(function(n) {
                    // Nada Utama (Pure Sine)
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(n.f, n.time);
                    gain.gain.setValueAtTime(0, n.time);
                    gain.gain.linearRampToValueAtTime(n.vol, n.time + 0.003);
                    gain.gain.exponentialRampToValueAtTime(0.001, n.time + n.dur);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(n.time);
                    osc.stop(n.time + n.dur + 0.05);

                    // Karakter Ketukan Marimba / Mallet (Harmonik Cepat)
                    const oscClick = ctx.createOscillator();
                    const gainClick = ctx.createGain();
                    oscClick.type = 'triangle';
                    oscClick.frequency.setValueAtTime(n.f * 3.9, n.time);
                    gainClick.gain.setValueAtTime(0, n.time);
                    gainClick.gain.linearRampToValueAtTime(n.vol * 0.32, n.time + 0.002);
                    gainClick.gain.exponentialRampToValueAtTime(0.001, n.time + 0.035);
                    oscClick.connect(gainClick);
                    gainClick.connect(ctx.destination);
                    oscClick.start(n.time);
                    oscClick.stop(n.time + 0.04);
                });
            };

            if (ctx.state === 'suspended') {
                ctx.resume().then(trigger).catch(trigger);
            } else {
                trigger();
            }
        } catch (e) {
            console.warn('Web Audio chime fallback error:', e);
        }
    }

    function playNotificationChime(forcePlay = false) {
        const soundEnabled = localStorage.getItem('it_notif_sound_enabled') !== '0';
        if (!soundEnabled && !forcePlay) return;

        try {
            const audio = getAudioElement();
            audio.currentTime = 0;
            const promise = audio.play();
            if (promise !== undefined) {
                promise.catch(function(err) {
                    playWebAudioChimeFallback();
                });
            }
        } catch (e) {
            playWebAudioChimeFallback();
        }
    }

    function toggleNotifSound(e) {
        if (e) e.stopPropagation();
        const current = localStorage.getItem('it_notif_sound_enabled') !== '0';
        const next = !current;
        localStorage.setItem('it_notif_sound_enabled', next ? '1' : '0');
        updateSoundButtonUI(next);

        if (next) {
            playNotificationChime(true);
        }
    }

    function updateSoundButtonUI(enabled) {
        const icon = document.getElementById('icon-notif-sound');
        const text = document.getElementById('text-notif-sound');
        const btn = document.getElementById('btn-toggle-notif-sound');
        if (icon && btn) {
            if (enabled) {
                icon.className = 'fa-solid fa-volume-high text-xs text-teal-700';
                if (text) text.textContent = 'Aktif';
                btn.title = 'Suara Notifikasi: Aktif (Klik untuk Membisukan / Mute)';
            } else {
                icon.className = 'fa-solid fa-volume-xmark text-xs text-slate-400';
                if (text) text.textContent = 'Senyap';
                btn.title = 'Suara Notifikasi: Senyap (Klik untuk Mengaktifkan Suara)';
            }
        }
    }

    // ========================================================
    // FLOATING LIVE TOAST ALERT & REAL-TIME POLLING
    // ========================================================
    let lastPollUnread = {{ (int) $notifUnreadCount }};
    let lastPollLatestId = '{{ !empty($notifItems) ? $notifItems[0]['id'] : '' }}';

    function showLiveToast(title, ticketNumber, isCancelled = false) {
        const toast = document.getElementById('live-ticket-toast');
        const desc = document.getElementById('live-toast-desc');
        const headerTitle = document.getElementById('live-toast-title');
        const badge = document.getElementById('live-toast-badge');
        const icon = document.getElementById('live-toast-icon');
        const iconWrapper = document.getElementById('live-toast-icon-wrapper');
        const btnLink = document.getElementById('live-toast-link');
        if (!toast) return;

        if (isCancelled) {
            if (headerTitle) headerTitle.textContent = 'Tiket Dibatalkan Pelapor';
            if (badge) {
                badge.textContent = 'BATAL';
                badge.className = 'inline-flex items-center px-1.5 py-0.2 rounded-full text-[9px] font-extrabold bg-rose-500/20 text-rose-300 border border-rose-500/30';
            }
            if (icon) icon.className = 'fa-solid fa-ban text-base text-rose-300';
            if (iconWrapper) iconWrapper.className = 'w-10 h-10 rounded-xl bg-rose-500/20 text-rose-300 flex items-center justify-center flex-shrink-0 shadow-inner';
            if (desc) desc.textContent = (ticketNumber ? ticketNumber + ' · ' : '') + 'Dibatalkan oleh ' + (title || 'pelapor');
            if (btnLink) btnLink.textContent = 'Lihat';
        } else {
            if (headerTitle) headerTitle.textContent = 'Tiket Baru Masuk!';
            if (badge) {
                badge.textContent = 'BARU';
                badge.className = 'inline-flex items-center px-1.5 py-0.2 rounded-full text-[9px] font-extrabold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 animate-pulse';
            }
            if (icon) icon.className = 'fa-solid fa-bell text-base text-teal-300 animate-bounce';
            if (iconWrapper) iconWrapper.className = 'w-10 h-10 rounded-xl bg-teal-500/20 text-teal-300 flex items-center justify-center flex-shrink-0 shadow-inner';
            if (desc) desc.textContent = (ticketNumber ? ticketNumber + ' · ' : '') + (title || 'Tiket baru siap ditangani');
            if (btnLink) btnLink.textContent = 'Buka';
        }

        toast.classList.remove('hidden');
        setTimeout(function() {
            toast.classList.remove('translate-y-3', 'opacity-0');
        }, 20);

        setTimeout(function() {
            toast.classList.add('translate-y-3', 'opacity-0');
            setTimeout(function() {
                toast.classList.add('hidden');
            }, 350);
        }, 6000);
    }

    function initNotificationSoundAndPolling() {
        const savedSound = localStorage.getItem('it_notif_sound_enabled');
        if (savedSound === null) {
            localStorage.setItem('it_notif_sound_enabled', '1');
        }
        updateSoundButtonUI(localStorage.getItem('it_notif_sound_enabled') !== '0');

        // Jika baru saja ada tiket masuk dalam 3 menit terakhir dan belum dibunyikan pada sesi ini
        const hasFreshUnread = {{ $hasFreshUnread ? 'true' : 'false' }};
        if (hasFreshUnread && lastPollLatestId) {
            const chimedKey = 'pltu_chimed_' + lastPollLatestId;
            if (!sessionStorage.getItem(chimedKey)) {
                sessionStorage.setItem(chimedKey, '1');
                setTimeout(function() {
                    playNotificationChime();
                }, 500);
            }
        }

        // Cross-tab broadcast listener (ketika pegawai buat aduan tiket atau membatalkan tiket di tab lain)
        try {
            if (window.BroadcastChannel) {
                const ch = new BroadcastChannel('pltu_ticket_channel');
                ch.onmessage = function(ev) {
                    if (ev && ev.data) {
                        if (ev.data.event === 'new_ticket') {
                            pollNewNotifications(true, ev.data.ticket_number);
                        } else if (ev.data.event === 'cancelled_ticket') {
                            showLiveToast(ev.data.pelapor, ev.data.ticket_number, true);
                            pollNewNotifications(false);
                        }
                    }
                };
            }
            window.addEventListener('storage', function(ev) {
                if (ev.key === 'pltu_new_ticket_broadcast' && ev.newValue) {
                    try {
                        const parsed = JSON.parse(ev.newValue);
                        pollNewNotifications(true, parsed.ticket_number);
                    } catch(err) {
                        pollNewNotifications(true);
                    }
                } else if (ev.key === 'pltu_cancelled_ticket_broadcast' && ev.newValue) {
                    try {
                        const parsed = JSON.parse(ev.newValue);
                        showLiveToast(parsed.pelapor, parsed.ticket_number, true);
                        pollNewNotifications(false);
                    } catch(err) {
                        pollNewNotifications(false);
                    }
                }
            });
        } catch(e) {}

        // Polling background setiap 5 detik (responsif & instan)
        setInterval(function() {
            pollNewNotifications(false);
        }, 5000);
    }

    function pollNewNotifications(forceChime, ticketNum) {
        fetch('{{ route('notifications.poll') }}', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function(res) {
            if (!res.ok) throw new Error('Polling status ' + res.status);
            return res.json();
        })
        .then(function(data) {
            if (typeof data.unread_count === 'number') {
                const isNewTicketOrAlert = forceChime || (data.unread_count > lastPollUnread) ||
                    (data.latest_id && data.latest_id !== lastPollLatestId && data.unread_count > 0);

                if (isNewTicketOrAlert) {
                    playNotificationChime();
                    showLiveToast(data.latest_title, ticketNum);

                    // Animasi getar lonceng lembut
                    const bellIcon = document.getElementById('header-notif-bell-icon');
                    if (bellIcon) {
                        bellIcon.classList.add('text-rose-500', 'animate-bounce');
                        setTimeout(function() {
                            bellIcon.classList.remove('text-rose-500', 'animate-bounce');
                        }, 1800);
                    }
                }

                lastPollUnread = data.unread_count;
                if (data.latest_id) {
                    lastPollLatestId = data.latest_id;
                }

                // Update badge merah di lonceng
                const badge = document.getElementById('header-notif-bell-badge');
                if (badge) {
                    if (data.unread_count > 0) {
                        badge.textContent = data.unread_count;
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                }

                // Update teks status di header dropdown
                const pill = document.getElementById('header-notif-pill');
                if (pill) {
                    if (data.unread_count > 0) {
                        pill.className = 'px-2 py-0.5 rounded-full text-[11px] font-bold bg-teal-50 text-teal-700 border border-teal-200/50';
                        pill.textContent = data.unread_count + ' baru';
                    } else {
                        pill.className = 'px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-500';
                        pill.textContent = 'Semua terbaca';
                    }
                }

                // Update tombol Tandai Semua Dibaca
                const markAllContainer = document.getElementById('header-notif-mark-all-container');
                if (markAllContainer) {
                    if (data.unread_count > 0) {
                        markAllContainer.classList.remove('hidden');
                        markAllContainer.classList.add('inline-flex', 'items-center');
                    } else {
                        markAllContainer.classList.add('hidden');
                        markAllContainer.classList.remove('inline-flex', 'items-center');
                    }
                }

                // Update angka pada filter tab
                const tabCount = document.getElementById('header-notif-tab-count');
                if (tabCount) {
                    tabCount.textContent = data.unread_count;
                }
            }
        })
        .catch(function(err) {
            // Abaikan kesalahan koneksi sementara saat polling
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNotificationSoundAndPolling);
    } else {
        initNotificationSoundAndPolling();
    }
</script>

<!-- Live Floating Toast Alert Notifikasi -->
<div id="live-ticket-toast" class="hidden fixed bottom-6 right-6 z-[9999] bg-[#092635] text-white px-4 py-3.5 rounded-2xl shadow-2xl border border-teal-500/40 flex items-center space-x-3.5 transition-all duration-300 transform translate-y-3 opacity-0 pointer-events-auto">
    <div id="live-toast-icon-wrapper" class="w-10 h-10 rounded-xl bg-teal-500/20 text-teal-300 flex items-center justify-center flex-shrink-0 shadow-inner">
        <i id="live-toast-icon" class="fa-solid fa-bell text-base text-teal-300 animate-bounce"></i>
    </div>
    <div class="min-w-0 pr-1">
        <div class="text-xs font-bold text-white flex items-center space-x-2">
            <span id="live-toast-title">Tiket Baru Masuk!</span>
            <span id="live-toast-badge" class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[9px] font-extrabold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 animate-pulse">BARU</span>
        </div>
        <div id="live-toast-desc" class="text-[11px] text-slate-300 truncate max-w-[240px] mt-0.5">
            Tiket baru siap ditangani
        </div>
    </div>
    <a id="live-toast-link" href="{{ route('services.index') }}" class="px-3 py-1.5 rounded-xl bg-teal-600 hover:bg-teal-500 text-white text-xs font-bold shadow-xs transition flex-shrink-0">
        Buka
    </a>
</div>
@endif
