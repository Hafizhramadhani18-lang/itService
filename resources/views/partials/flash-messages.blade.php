@if(session('success'))
<div id="flash-success-alert" class="transition-all duration-500 ease-out flex items-center justify-between px-3.5 py-2 rounded-xl bg-teal-50 border border-teal-200/90 text-teal-900 shadow-2xs">
    <div class="flex items-center space-x-2.5 min-w-0">
        <div class="w-5 h-5 rounded-lg bg-teal-700 text-white flex items-center justify-center flex-shrink-0 text-[10px] shadow-xs">
            <i class="fa-solid fa-check"></i>
        </div>
        <span class="text-xs font-semibold text-teal-950 truncate">
            {{ session('success') }}
        </span>
    </div>
    <button type="button" 
            onclick="dismissFlashAlert('flash-success-alert')" 
            class="text-teal-600 hover:text-teal-950 hover:bg-teal-100/70 w-6 h-6 rounded-lg transition flex items-center justify-center ml-3 flex-shrink-0 cursor-pointer" 
            title="Tutup pemberitahuan">
        <i class="fa-solid fa-xmark text-xs"></i>
    </button>
</div>
@endif

@if(session('info'))
<div id="flash-info-alert" class="transition-all duration-500 ease-out flex items-center justify-between px-3.5 py-2 rounded-xl bg-slate-100 border border-slate-200 text-slate-800 shadow-2xs">
    <div class="flex items-center space-x-2.5 min-w-0">
        <div class="w-5 h-5 rounded-lg bg-slate-700 text-white flex items-center justify-center flex-shrink-0 text-[10px] shadow-xs">
            <i class="fa-solid fa-info"></i>
        </div>
        <span class="text-xs font-semibold text-slate-900 truncate">
            {{ session('info') }}
        </span>
    </div>
    <button type="button" 
            onclick="dismissFlashAlert('flash-info-alert')" 
            class="text-slate-500 hover:text-slate-900 hover:bg-slate-200 w-6 h-6 rounded-lg transition flex items-center justify-center ml-3 flex-shrink-0 cursor-pointer" 
            title="Tutup pemberitahuan">
        <i class="fa-solid fa-xmark text-xs"></i>
    </button>
</div>
@endif

@if($errors->any())
<div id="flash-error-alert" class="transition-all duration-500 ease-out flex items-start justify-between px-3.5 py-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 shadow-2xs">
    <div class="flex items-start space-x-2.5 min-w-0">
        <div class="w-5 h-5 rounded-lg bg-rose-600 text-white flex items-center justify-center flex-shrink-0 text-[10px] shadow-xs mt-0.5">
            <i class="fa-solid fa-exclamation"></i>
        </div>
        <div class="text-xs text-rose-950">
            <span class="font-bold">Periksa kembali data Anda:</span>
            <ul class="list-disc list-inside mt-0.5 space-y-0.5 text-[11px]">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    <button type="button" 
            onclick="dismissFlashAlert('flash-error-alert')" 
            class="text-rose-600 hover:text-rose-950 hover:bg-rose-100 w-6 h-6 rounded-lg transition flex items-center justify-center ml-3 flex-shrink-0 cursor-pointer" 
            title="Tutup pemberitahuan">
        <i class="fa-solid fa-xmark text-xs"></i>
    </button>
</div>
@endif

<script>
    function dismissFlashAlert(id) {
        const el = document.getElementById(id);
        if (el) {
            el.classList.add('opacity-0', '-translate-y-2', 'scale-[0.99]', 'pointer-events-none');
            setTimeout(() => {
                el.style.display = 'none';
                el.remove();
            }, 400);
        }
    }

    // Auto-dismiss alert notifikasi sukses & info setelah 4.5 detik
    document.addEventListener('DOMContentLoaded', function() {
        const autoDismissIds = ['flash-success-alert', 'flash-info-alert'];
        autoDismissIds.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                setTimeout(() => {
                    dismissFlashAlert(id);
                }, 4500);
            }
        });
    });
</script>

