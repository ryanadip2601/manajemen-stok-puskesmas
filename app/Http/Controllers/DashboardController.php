<?php

namespace App\Http\Controllers;

use App\Models\PemeliharaanAset;
use App\Models\PermintaanBarang;
use App\Models\StockOutRequest;
use App\Models\User;
use App\Models\UsulanPenerimaanBarang;
use App\Services\StockService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
        $stats = $this->stockService->getDashboardStats(30);
        $user = auth()->user();

        $quickCounts = [
            'pending_pickup' => StockOutRequest::where('status', 'pending')->count(),
            'completed_pickup_today' => StockOutRequest::where('status', 'completed')->whereDate('updated_at', now()->toDateString())->count(),
            'pending_bhp' => PermintaanBarang::where('status', 'pending')->count(),
            'pending_usulan_aset' => UsulanPenerimaanBarang::where('status', 'pending')->count(),
            'pending_pemeliharaan' => PemeliharaanAset::where('status', 'menunggu')->count(),
            'pending_users' => $user->isAdmin() ? User::where('is_approved', false)->count() : 0,
        ];

        $menuSections = $this->buildMenuSectionsByRole($user, $quickCounts);

        $payload = array_merge($stats, [
            'menu_sections' => $menuSections,
            'quick_counts' => $quickCounts,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $payload,
            ]);
        }

        return view('dashboard.index', $payload);
    }

    public function analytics(Request $request)
    {
        $rangeDays = (int) $request->input('range_days', 30);
        if (! in_array($rangeDays, [30, 90, 180], true)) {
            $rangeDays = 30;
        }

        $stats = $this->stockService->getDashboardStats($rangeDays);
        $sevenDaysAgo = now()->subDays(6)->toDateString();

        $popularRequests7Days = StockOutRequest::query()
            ->with(['user:id,name,role', 'item:id,name'])
            ->whereDate('request_date', '>=', $sevenDaysAgo)
            ->whereHas('user', function ($query) {
                $query->where('role', 'staff');
            })
            ->selectRaw('user_id, item_id, SUM(quantity) as total_qty, COUNT(*) as total_requests, MAX(request_date) as latest_request_date')
            ->groupBy('user_id', 'item_id')
            ->orderByDesc('total_qty')
            ->orderByDesc('total_requests')
            ->limit(8)
            ->get();

        $stats['popular_requests_7_days'] = $popularRequests7Days;
        $stats['popular_requests_7_days_total'] = (int) $popularRequests7Days->sum('total_qty');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        }

        return view('dashboard.analytics', $stats);
    }

    private function buildMenuSectionsByRole($user, array $quickCounts): array
    {
        $sections = [];

        $sections[] = [
            'title' => 'Operasional Harian',
            'items' => array_values(array_filter([
                [
                    'label' => 'Data Barang',
                    'desc' => 'Lihat dan kelola data barang utama.',
                    'icon' => 'fas fa-box',
                    'route' => route('items.index'),
                    'badge' => $quickCounts['pending_bhp'] > 0 ? $quickCounts['pending_bhp'] : null,
                ],
                $user->canManageWarehouse() ? [
                    'label' => 'Transaksi Gudang',
                    'desc' => 'Input barang masuk/keluar dan mutasi stok.',
                    'icon' => 'fas fa-right-left',
                    'route' => route('stock-in.index'),
                    'badge' => null,
                ] : [
                    'label' => 'Buat Permintaan Pengambilan',
                    'desc' => 'Ajukan barang yang dibutuhkan unit kerja.',
                    'icon' => 'fas fa-plus-circle',
                    'route' => route('stock-request.staff.create'),
                    'badge' => null,
                ],
                [
                    'label' => 'Riwayat Pengambilan',
                    'desc' => 'Lacak proses pengambilan barang.',
                    'icon' => 'fas fa-clipboard-list',
                    'route' => $user->canManageWarehouse() ? route('checklist.index', ['status' => 'pending']) : route('stock-request.staff.index'),
                    'badge' => $quickCounts['pending_pickup'] > 0 ? $quickCounts['pending_pickup'] : null,
                ],
            ])),
        ];

        $sections[] = [
            'title' => 'Permintaan & Persetujuan',
            'items' => array_values(array_filter([
                [
                    'label' => 'Permintaan BHP',
                    'desc' => 'Permintaan barang habis pakai.',
                    'icon' => 'fas fa-hand-holding-medical',
                    'route' => $user->canManageWarehouse() ? route('permintaan-barang.admin.index') : route('permintaan-barang.index'),
                    'badge' => $quickCounts['pending_bhp'] > 0 ? $quickCounts['pending_bhp'] : null,
                ],
                [
                    'label' => 'Usulan Aset',
                    'desc' => 'Kelola usulan kebutuhan barang aset.',
                    'icon' => 'fas fa-file-signature',
                    'route' => $user->canManageWarehouse() ? route('usulan-penerimaan.admin.index') : route('usulan-penerimaan.index'),
                    'badge' => $quickCounts['pending_usulan_aset'] > 0 ? $quickCounts['pending_usulan_aset'] : null,
                ],
                $user->canManageWarehouse() ? [
                    'label' => 'Checklist Permintaan',
                    'desc' => 'Verifikasi permintaan unit dengan cepat.',
                    'icon' => 'fas fa-list-check',
                    'route' => route('checklist.index', ['status' => 'pending']),
                    'badge' => $quickCounts['pending_pickup'] > 0 ? $quickCounts['pending_pickup'] : null,
                ] : null,
            ])),
        ];

        $sections[] = [
            'title' => 'Aset & Pemeliharaan',
            'items' => array_values(array_filter([
                [
                    'label' => 'Inventaris Aset',
                    'desc' => 'Data aset per ruangan dan kondisi aset.',
                    'icon' => 'fas fa-building',
                    'route' => $user->canManageWarehouse() ? route('barang-aset.index') : route('staff.barang-aset.index'),
                    'badge' => null,
                ],
                [
                    'label' => 'Pemeliharaan',
                    'desc' => 'Tiket perbaikan dan progres teknisi.',
                    'icon' => 'fas fa-screwdriver-wrench',
                    'route' => $user->canManageWarehouse() ? route('pemeliharaan.index') : route('staff.pemeliharaan.index'),
                    'badge' => $quickCounts['pending_pemeliharaan'] > 0 ? $quickCounts['pending_pemeliharaan'] : null,
                ],
                $user->canManageWarehouse() ? [
                    'label' => 'Penyusutan Aset',
                    'desc' => 'Analisis penyusutan dan laporan nilai aset.',
                    'icon' => 'fas fa-chart-area',
                    'route' => route('penyusutan.index'),
                    'badge' => null,
                ] : null,
            ])),
        ];

        $sections[] = [
            'title' => 'Monitoring & Laporan',
            'items' => array_values(array_filter([
                [
                    'label' => 'Analitik Grafik',
                    'desc' => 'Pantau tren stok, kategori, dan performa.',
                    'icon' => 'fas fa-chart-line',
                    'route' => route('analytics.index'),
                    'badge' => null,
                ],
                $user->isAdmin() ? [
                    'label' => 'Laporan Lengkap',
                    'desc' => 'Akses semua laporan operasional.',
                    'icon' => 'fas fa-file-lines',
                    'route' => route('reports.index'),
                    'badge' => null,
                ] : null,
                $user->isAdmin() ? [
                    'label' => 'Manajemen User',
                    'desc' => 'Kelola akun, role, dan approval user.',
                    'icon' => 'fas fa-users-gear',
                    'route' => route('users.index'),
                    'badge' => $quickCounts['pending_users'] > 0 ? $quickCounts['pending_users'] : null,
                ] : null,
            ])),
        ];

        return $sections;
    }
}
