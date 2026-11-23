@extends('layouts.app')

@section('title', 'Pusat Notifikasi - ' . (Auth::user()->isAdmin() ? 'Koordinator IT' : 'Teknisi IT'))

@section('content')
<div class="space-y-6">

    <!-- Top Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2.5">
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Pusat Notifikasi</h1>
                @if($unreadCount > 0)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-teal-50 text-teal-700 border border-teal-200/60">
                        {{ $unreadCount }} belum dibaca
                    </span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600">
                        Semua terbaca
                    </span>
                @endif
            </div>
            <p class="text-xs text-slate-500 mt-1">
                @if(Auth::user()->isAdmin())
                    Riwayat seluruh peringatan tiket mendesak, laporan teknisi, penugasan, dan pengguna baru
                @else
                    Riwayat tiket mendesak, tiket baru masuk tim, pengalihan tugas, dan konfirmasi penyelesaian
                @endif
            </p>
        </div>

        <div class="flex items-center space-x-2.5">
            @if($unreadCount > 0)
            <form action="{{ route('notifications.markAllRead') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs flex items-center space-x-1.5 cursor-pointer">
                    <i class="fa-solid fa-check-double text-[11px]"></i>
                    <span>Tandai semua dibaca</span>
                </button>
            </form>
            @endif

            @include('partials.header-widget')
        </div>
    </div>

    <!-- Alert Flash -->
    @include('partials.flash-messages')

    <!-- Card Container Notifikasi -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        
        <!-- Filter Tabs Bar -->
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-slate-50/40">
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" onclick="filterNotifPage('all')" data-filter="all"
                        class="page-notif-tab px-4 py-1.5 rounded-full text-xs font-bold transition cursor-pointer bg-[#092635] text-white shadow-xs">
                    Semua ({{ count($notifications) }})
                </button>
                <button type="button" onclick="filterNotifPage('today')" data-filter="today"
                        class="page-notif-tab px-4 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer bg-white border border-slate-200 text-slate-600 hover:bg-slate-50">
                    Hari Ini (24 Jam)
                </button>
                <button type="button" onclick="filterNotifPage('unread')" data-filter="unread"
                        class="page-notif-tab px-4 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer bg-white border border-slate-200 text-slate-600 hover:bg-slate-50">
                    Belum dibaca &bull; {{ $unreadCount }}
                </button>
                <button type="button" onclick="filterNotifPage('urgent')" data-filter="urgent"
                        class="page-notif-tab px-4 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer bg-white border border-slate-200 text-slate-600 hover:bg-slate-50">
                    Tiket Mendesak
                </button>
                @if(Auth::user()->isAdmin())
                    <button type="button" onclick="filterNotifPage('report')" data-filter="report"
                            class="page-notif-tab px-4 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer bg-white border border-slate-200 text-slate-600 hover:bg-slate-50">
                        Laporan Teknisi
                    </button>
                @else
                    <button type="button" onclick="filterNotifPage('new_ticket')" data-filter="new_ticket"
                            class="page-notif-tab px-4 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer bg-white border border-slate-200 text-slate-600 hover:bg-slate-50">
                        Tiket Baru
                    </button>
                    <button type="button" onclick="filterNotifPage('assigned')" data-filter="assigned"
                            class="page-notif-tab px-4 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer bg-white border border-slate-200 text-slate-600 hover:bg-slate-50">
                        Penugasan
                    </button>
                @endif
            </div>
        </div>

        <!-- Notification List -->
        <div class="divide-y divide-slate-100" id="notif-page-list">
            @forelse($notifications as $item)
            <a href="{{ $item['url'] }}"
               onclick="markNotifPageItemRead('{{ $item['id'] }}')"
               data-type="{{ $item['type'] }}"
               data-today="{{ !empty($item['is_today']) ? '1' : '0' }}"
               data-unread="{{ $item['is_unread'] ? '1' : '0' }}"
               class="page-notif-item px-5 py-4 flex items-start justify-between gap-4 transition group {{ $item['is_unread'] ? 'bg-teal-50/35 hover:bg-teal-50/60' : 'bg-white hover:bg-slate-50' }}">
                
                <div class="flex items-start space-x-3.5 min-w-0">
                    <div class="w-10 h-10 rounded-xl {{ $item['icon_bg'] }} flex items-center justify-center shrink-0 mt-0.5 shadow-2xs">
                        <i class="{{ $item['icon'] }} text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-teal-800 transition">
                            {{ $item['title'] }}
                        </div>
                        <div class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                            {{ $item['subtitle'] }}
                        </div>
                    </div>
                </div>

                <div class="flex flex-col items-end shrink-0 space-y-2 text-right">
                    <span class="text-xs text-slate-400 font-medium whitespace-nowrap">
                        {{ $item['time_label'] }}
                    </span>
                    @if($item['is_unread'])
                        <span class="w-2.5 h-2.5 rounded-full bg-teal-600" title="Belum dibaca"></span>
                    @endif
                </div>
            </a>
            @empty
            <div class="p-10 text-center text-slate-400 text-xs">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-lg">
                    <i class="fa-regular fa-bell-slash"></i>
                </div>
                <div class="font-bold text-slate-700 text-sm">Belum ada notifikasi aktivitas</div>
                <p class="mt-1">Semua aktivitas sistem akan otomatis dicatat di sini.</p>
            </div>
            @endforelse
        </div>

        <!-- Empty Filtered State -->
        <div id="notif-page-empty" class="hidden p-10 text-center text-slate-400 text-xs">
            <div class="w-12 h-12 rounded-full bg-teal-50 text-teal-600 flex items-center justify-center mx-auto mb-3 text-lg">
                <i class="fa-solid fa-check"></i>
            </div>
            <div class="font-bold text-slate-700 text-sm">Tidak ada notifikasi pada kategori ini</div>
        </div>

    </div>

</div>

@push('scripts')
<script>
    function filterNotifPage(filter) {
        document.querySelectorAll('.page-notif-tab').forEach(btn => {
            if (btn.getAttribute('data-filter') === filter) {
                btn.className = 'page-notif-tab px-4 py-1.5 rounded-full text-xs font-bold transition cursor-pointer bg-[#092635] text-white shadow-xs';
            } else {
                btn.className = 'page-notif-tab px-4 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer bg-white border border-slate-200 text-slate-600 hover:bg-slate-50';
            }
        });

        let visibleCount = 0;
        document.querySelectorAll('.page-notif-item').forEach(item => {
            const type = item.getAttribute('data-type');
            const isUnread = item.getAttribute('data-unread') === '1';

            let show = false;
            if (filter === 'all') show = true;
            else if (filter === 'today') show = (item.getAttribute('data-today') === '1');
            else if (filter === 'unread') show = isUnread;
            else if (filter === 'urgent') show = (type === 'urgent' || type === 'waiting');
            else if (filter === 'report') show = (type === 'report');
            else if (filter === 'new_ticket') show = (type === 'new_ticket');
            else if (filter === 'assigned') show = (type === 'assigned');

            if (show) {
                item.classList.remove('hidden');
                visibleCount++;
            } else {
                item.classList.add('hidden');
            }
        });

        const emptyEl = document.getElementById('notif-page-empty');
        if (emptyEl) {
            emptyEl.classList.toggle('hidden', visibleCount > 0);
        }
    }

    function markNotifPageItemRead(notifId) {
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
</script>
@endpush
@endsection
