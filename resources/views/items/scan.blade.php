@extends('layouts.app')

@section('title', 'Scan Barcode')
@section('header', 'Scan Barcode')
@section('breadcrumb', 'Cari barang dengan scan barcode atau input manual')

@section('content')
<div class="max-w-2xl mx-auto">
    <!-- Scanner Card -->
    <div class="card-hover bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700/50 overflow-hidden mb-6">
        <div class="p-6 border-b border-slate-700/50">
            <h3 class="text-lg font-bold text-white flex items-center">
                <div class="w-10 h-10 rounded-xl bg-yellow-500/20 flex items-center justify-center mr-3">
                    <i class="fas fa-barcode text-yellow-400"></i>
                </div>
                Scanner Barcode
            </h3>
        </div>
        
        <div class="p-6">
            <!-- Camera Scanner -->
            <div class="mb-6">
                <div id="scanner-container" class="relative bg-slate-900 rounded-xl overflow-hidden aspect-video flex items-center justify-center">
                    <video id="scanner-video" class="w-full h-full object-cover hidden"></video>
                    <div id="scanner-placeholder" class="text-center p-8">
                        <div class="w-20 h-20 bg-slate-700/50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-camera text-3xl text-slate-500"></i>
                        </div>
                        <p class="text-slate-400 mb-4">Klik tombol di bawah untuk memulai scan</p>
                        <button id="start-scanner" class="bg-gradient-to-r from-yellow-500 to-orange-500 text-white px-6 py-3 rounded-xl font-bold hover:from-yellow-600 hover:to-orange-600 transition-all">
                            <i class="fas fa-camera mr-2"></i>Mulai Scanner
                        </button>
                    </div>
                    <div id="scanner-overlay" class="absolute inset-0 hidden">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="w-64 h-40 border-2 border-yellow-400 rounded-lg relative">
                                <div class="absolute -top-1 -left-1 w-6 h-6 border-t-4 border-l-4 border-yellow-400 rounded-tl-lg"></div>
                                <div class="absolute -top-1 -right-1 w-6 h-6 border-t-4 border-r-4 border-yellow-400 rounded-tr-lg"></div>
                                <div class="absolute -bottom-1 -left-1 w-6 h-6 border-b-4 border-l-4 border-yellow-400 rounded-bl-lg"></div>
                                <div class="absolute -bottom-1 -right-1 w-6 h-6 border-b-4 border-r-4 border-yellow-400 rounded-br-lg"></div>
                                <div class="absolute top-1/2 left-0 right-0 h-0.5 bg-red-500 animate-pulse"></div>
                            </div>
                        </div>
                        <button id="stop-scanner" class="absolute bottom-4 right-4 bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">
                            <i class="fas fa-stop mr-2"></i>Stop
                        </button>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="flex items-center my-6">
                <div class="flex-1 border-t border-slate-700"></div>
                <span class="px-4 text-slate-500 text-sm">atau</span>
                <div class="flex-1 border-t border-slate-700"></div>
            </div>

            <!-- Manual Input -->
            <form action="{{ route('items.scan.search') }}" method="POST">
                @csrf
                <label class="block text-slate-400 text-sm font-medium mb-2">
                    <i class="fas fa-keyboard mr-2"></i>Input Manual
                </label>
                <div class="flex gap-3">
                    <input 
                        type="text" 
                        name="barcode" 
                        id="barcode-input"
                        class="flex-1 bg-slate-700/50 border border-slate-600 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-yellow-500"
                        placeholder="Masukkan kode barcode atau kode barang..."
                        required
                    >
                    <button type="submit" class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white px-6 py-3 rounded-xl font-bold hover:from-emerald-600 hover:to-teal-700 transition-all">
                        <i class="fas fa-search mr-2"></i>Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Result Card (will be shown via JavaScript) -->
    <div id="result-card" class="hidden card-hover bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-emerald-500/50 overflow-hidden">
        <div class="p-6 border-b border-slate-700/50 bg-emerald-500/10">
            <h3 class="text-lg font-bold text-white flex items-center">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center mr-3">
                    <i class="fas fa-check-circle text-emerald-400"></i>
                </div>
                Barang Ditemukan
            </h3>
        </div>
        <div class="p-6" id="result-content">
            <!-- Content will be inserted by JavaScript -->
        </div>
    </div>

    <!-- Info Card -->
    <div class="mt-6 bg-blue-500/10 border border-blue-500/30 rounded-xl p-4">
        <div class="flex items-start">
            <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center mr-4 flex-shrink-0">
                <i class="fas fa-info-circle text-blue-400"></i>
            </div>
            <div>
                <h4 class="font-semibold text-blue-300 mb-1">Tips Penggunaan</h4>
                <ul class="text-sm text-blue-200/70 space-y-1">
                    <li><i class="fas fa-check mr-2"></i>Pastikan barcode terlihat jelas di dalam area scanner</li>
                    <li><i class="fas fa-check mr-2"></i>Gunakan pencahayaan yang cukup untuk hasil terbaik</li>
                    <li><i class="fas fa-check mr-2"></i>Anda juga bisa memasukkan kode barang secara manual</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/@zxing/library@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startBtn = document.getElementById('start-scanner');
    const stopBtn = document.getElementById('stop-scanner');
    const video = document.getElementById('scanner-video');
    const placeholder = document.getElementById('scanner-placeholder');
    const overlay = document.getElementById('scanner-overlay');
    const barcodeInput = document.getElementById('barcode-input');
    const resultCard = document.getElementById('result-card');
    const resultContent = document.getElementById('result-content');
    
    let codeReader = null;

    startBtn.addEventListener('click', async function() {
        try {
            codeReader = new ZXing.BrowserMultiFormatReader();
            
            placeholder.classList.add('hidden');
            video.classList.remove('hidden');
            overlay.classList.remove('hidden');
            
            const videoInputDevices = await ZXing.BrowserMultiFormatReader.listVideoInputDevices();
            const selectedDeviceId = videoInputDevices[0].deviceId;
            
            codeReader.decodeFromVideoDevice(selectedDeviceId, 'scanner-video', (result, err) => {
                if (result) {
                    barcodeInput.value = result.getText();
                    stopScanner();
                    searchBarcode(result.getText());
                }
            });
        } catch (err) {
            console.error(err);
            alert('Tidak dapat mengakses kamera. Pastikan Anda memberikan izin akses kamera.');
        }
    });

    stopBtn.addEventListener('click', stopScanner);

    function stopScanner() {
        if (codeReader) {
            codeReader.reset();
            codeReader = null;
        }
        video.classList.add('hidden');
        overlay.classList.add('hidden');
        placeholder.classList.remove('hidden');
    }

    async function searchBarcode(barcode) {
        try {
            const response = await fetch('{{ route("items.scan.search") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ barcode: barcode })
            });

            const data = await response.json();

            if (data.success) {
                showResult(data.data);
            } else {
                resultCard.classList.add('hidden');
                alert('Barang tidak ditemukan');
            }
        } catch (err) {
            console.error(err);
            alert('Terjadi kesalahan saat mencari barang');
        }
    }

    function showResult(item) {
        resultContent.innerHTML = `
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Kode</span>
                    <span class="font-mono bg-slate-700/50 px-3 py-1 rounded text-white">${item.code}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Nama Barang</span>
                    <span class="font-semibold text-white">${item.name}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Kategori</span>
                    <span class="text-slate-300">${item.category?.name || '-'}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Stok</span>
                    <span class="bg-emerald-500/20 text-emerald-400 px-3 py-1 rounded-lg font-bold">${item.stock} ${item.unit?.symbol || ''}</span>
                </div>
                <div class="pt-4 border-t border-slate-700">
                    <a href="/items/${item.id}" class="w-full inline-flex items-center justify-center bg-gradient-to-r from-blue-500 to-blue-600 text-white px-4 py-3 rounded-xl font-bold hover:from-blue-600 hover:to-blue-700 transition-all">
                        <i class="fas fa-eye mr-2"></i>Lihat Detail
                    </a>
                </div>
            </div>
        `;
        resultCard.classList.remove('hidden');
    }
});
</script>
@endpush
