@extends('layouts.app')

@section('title', 'Ubah Role - ' . $user->name)
@section('header', 'Ubah Role User')
@section('breadcrumb', 'User / Ubah Role / ' . $user->name)

@section('content')
<div class="max-w-xl mx-auto">
    <div class="bg-slate-800/50 backdrop-blur-xl rounded-2xl border border-slate-700/50 overflow-hidden">
        <div class="p-6 border-b border-slate-700/50">
            <h3 class="text-xl font-bold text-white flex items-center">
                <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center mr-3">
                    <i class="fas fa-user-cog text-white"></i>
                </div>
                Ubah Role User
            </h3>
        </div>

        <!-- User Info -->
        <div class="p-6 border-b border-slate-700/50">
            <div class="flex items-center">
                <div class="w-16 h-16 {{ $user->getRoleBadgeClass() }} rounded-full flex items-center justify-center mr-4">
                    <span class="text-white font-bold text-2xl">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                </div>
                <div>
                    <h4 class="text-white text-xl font-bold">{{ $user->name }}</h4>
                    <p class="text-slate-400">{{ $user->email }}</p>
                    <p class="text-slate-500 text-sm">Role saat ini: <span class="text-amber-400 font-medium">{{ $user->getRoleName() }}</span></p>
                </div>
            </div>
        </div>

        <form action="{{ route('users.update-role', $user->id) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Role Selection -->
            <div>
                <label class="block text-slate-300 font-medium mb-4">
                    <i class="fas fa-shield-alt mr-2 text-amber-400"></i>Pilih Role Baru
                </label>
                
                <div class="space-y-3">
                    <!-- Admin -->
                    <label class="flex items-center p-4 bg-slate-700/30 rounded-xl cursor-pointer hover:bg-slate-700/50 transition border-2 {{ $user->role === 'admin' ? 'border-red-500' : 'border-transparent' }}">
                        <input type="radio" name="role" value="admin" class="hidden peer" {{ $user->role === 'admin' ? 'checked' : '' }}>
                        <div class="w-12 h-12 bg-red-500 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-crown text-white text-lg"></i>
                        </div>
                        <div class="flex-1">
                            <h5 class="text-white font-bold">Administrator</h5>
                            <p class="text-slate-400 text-sm">Akses penuh ke semua fitur, termasuk manajemen user dan laporan</p>
                        </div>
                        <div class="w-6 h-6 rounded-full border-2 border-slate-500 peer-checked:border-red-500 peer-checked:bg-red-500 flex items-center justify-center">
                            <i class="fas fa-check text-white text-xs hidden peer-checked:block"></i>
                        </div>
                    </label>

                    <!-- Admin Gudang -->
                    <label class="flex items-center p-4 bg-slate-700/30 rounded-xl cursor-pointer hover:bg-slate-700/50 transition border-2 {{ $user->role === 'admin_gudang' ? 'border-blue-500' : 'border-transparent' }}">
                        <input type="radio" name="role" value="admin_gudang" class="hidden peer" {{ $user->role === 'admin_gudang' ? 'checked' : '' }}>
                        <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-warehouse text-white text-lg"></i>
                        </div>
                        <div class="flex-1">
                            <h5 class="text-white font-bold">Admin Gudang</h5>
                            <p class="text-slate-400 text-sm">Kelola barang, kategori, transaksi masuk/keluar, dan checklist permintaan</p>
                        </div>
                        <div class="w-6 h-6 rounded-full border-2 border-slate-500 peer-checked:border-blue-500 peer-checked:bg-blue-500 flex items-center justify-center">
                            <i class="fas fa-check text-white text-xs hidden peer-checked:block"></i>
                        </div>
                    </label>

                    <!-- Staff -->
                    <label class="flex items-center p-4 bg-slate-700/30 rounded-xl cursor-pointer hover:bg-slate-700/50 transition border-2 {{ $user->role === 'staff' ? 'border-green-500' : 'border-transparent' }}">
                        <input type="radio" name="role" value="staff" class="hidden peer" {{ $user->role === 'staff' ? 'checked' : '' }}>
                        <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-user text-white text-lg"></i>
                        </div>
                        <div class="flex-1">
                            <h5 class="text-white font-bold">Pegawai</h5>
                            <p class="text-slate-400 text-sm">Hanya bisa melihat barang dan mengajukan permintaan pengambilan</p>
                        </div>
                        <div class="w-6 h-6 rounded-full border-2 border-slate-500 peer-checked:border-green-500 peer-checked:bg-green-500 flex items-center justify-center">
                            <i class="fas fa-check text-white text-xs hidden peer-checked:block"></i>
                        </div>
                    </label>
                </div>

                @error('role')
                    <p class="text-red-400 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-slate-700/50">
                <a href="{{ route('users.show', $user->id) }}" class="px-6 py-3 bg-slate-700 text-white rounded-xl hover:bg-slate-600 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Batal
                </a>
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-xl font-semibold hover:from-amber-600 hover:to-orange-700 transition-all shadow-lg shadow-amber-500/20">
                    <i class="fas fa-save mr-2"></i>Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    <!-- Warning Box -->
    <div class="mt-6 bg-amber-500/10 border border-amber-500/30 rounded-xl p-4">
        <div class="flex items-start">
            <i class="fas fa-exclamation-triangle text-amber-400 mt-0.5 mr-3"></i>
            <div class="text-sm text-amber-300">
                <p class="font-medium mb-1">Perhatian:</p>
                <ul class="list-disc list-inside text-amber-200/80 space-y-1">
                    <li>Perubahan role akan langsung berlaku</li>
                    <li>User akan kehilangan akses ke fitur yang tidak sesuai dengan role barunya</li>
                    <li>Aktivitas ini akan dicatat dalam log sistem</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('input[name="role"]').forEach(input => {
        input.addEventListener('change', function() {
            document.querySelectorAll('label').forEach(label => {
                label.classList.remove('border-red-500', 'border-blue-500', 'border-green-500');
                label.classList.add('border-transparent');
            });
            
            const colors = {
                'admin': 'border-red-500',
                'admin_gudang': 'border-blue-500',
                'staff': 'border-green-500'
            };
            
            this.closest('label').classList.remove('border-transparent');
            this.closest('label').classList.add(colors[this.value]);
        });
    });
</script>
@endpush
@endsection
