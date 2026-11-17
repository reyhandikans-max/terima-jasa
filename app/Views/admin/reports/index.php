<!-- ============================================================
     ADMIN REPORTS & ANALYTICS VIEW — Terima Jasa
     ============================================================ -->

<!-- Chart.js CDN (Lightweight Chart Library) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- Page Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= url('admin/dashboard') ?>" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Laporan Sistem</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold mb-0">Laporan & Analitik Operasional</h4>
            <span class="badge bg-danger text-white rounded-pill px-2 py-1" style="font-size: .75rem;">LIVE REPORT</span>
        </div>
        <p class="text-muted small mb-0">Ringkasan performa pemesanan servis, performa mitra teknisi, dan layanan terpopuler.</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
            <i class="bi bi-printer"></i> Cetak / Print Laporan
        </button>
        <a href="<?= url('admin/orders') ?>" class="btn btn-danger d-inline-flex align-items-center gap-2">
            <i class="bi bi-receipt"></i> Kelola Order
        </a>
    </div>
</div>

<!-- ============================================================
     TOP KPI METRICS: TOTAL ORDER & ESTIMASI OMSET
     ============================================================ -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase">Total Seluruh Order</span>
                <div class="p-2 bg-primary-subtle text-primary rounded-3"><i class="bi bi-receipt"></i></div>
            </div>
            <h3 class="fw-bold mb-1 text-dark"><?= number_format($totalOrders) ?></h3>
            <small class="text-muted">Total transaksi sejak awal</small>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase">Order Selesai</span>
                <div class="p-2 bg-success-subtle text-success rounded-3"><i class="bi bi-check2-all"></i></div>
            </div>
            <h3 class="fw-bold mb-1 text-success"><?= number_format($statusCounts['completed']) ?></h3>
            <small class="text-success fw-semibold">
                <?= $totalOrders > 0 ? round(($statusCounts['completed'] / $totalOrders) * 100, 1) : 0 ?>% tingkat penyelesaian
            </small>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase">Order Berjalan / Pending</span>
                <div class="p-2 bg-warning-subtle text-warning rounded-3"><i class="bi bi-hourglass-split"></i></div>
            </div>
            <h3 class="fw-bold mb-1 text-warning"><?= number_format($statusCounts['pending'] + $statusCounts['accepted'] + $statusCounts['in_progress']) ?></h3>
            <small class="text-muted">Dalam proses tindak lanjut</small>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase">Estimasi Omset Selesai</span>
                <div class="p-2 bg-info-subtle text-info rounded-3"><i class="bi bi-cash-stack"></i></div>
            </div>
            <h4 class="fw-bold mb-1 text-primary font-monospace">Rp <?= number_format($totalRevenue, 0, ',', '.') ?></h4>
            <small class="text-muted">Dari pesanan berstatus completed</small>
        </div>
    </div>
</div>

<!-- ============================================================
     CHARTS SECTION
     ============================================================ -->
<div class="row g-4 mb-4">
    <!-- Chart: Order Berdasarkan Status (Doughnut) -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0">Order Berdasarkan Status</h6>
                <span class="badge bg-light text-dark border">Distribusi Proporsi</span>
            </div>
            <div class="card-body p-4 d-flex flex-column align-items-center justify-content-center">
                <div style="position: relative; width: 100%; max-width: 280px; height: 260px;">
                    <canvas id="statusChart"></canvas>
                </div>
                <div class="row w-100 g-2 mt-3 pt-3 border-top text-center">
                    <div class="col">
                        <span class="badge bg-warning text-dark mb-1 d-block"><?= $statusCounts['pending'] ?></span>
                        <small class="text-muted" style="font-size:.7rem;">Pending</small>
                    </div>
                    <div class="col">
                        <span class="badge bg-info text-dark mb-1 d-block"><?= $statusCounts['accepted'] ?></span>
                        <small class="text-muted" style="font-size:.7rem;">Accepted</small>
                    </div>
                    <div class="col">
                        <span class="badge bg-primary mb-1 d-block"><?= $statusCounts['in_progress'] ?></span>
                        <small class="text-muted" style="font-size:.7rem;">Active</small>
                    </div>
                    <div class="col">
                        <span class="badge bg-success mb-1 d-block"><?= $statusCounts['completed'] ?></span>
                        <small class="text-muted" style="font-size:.7rem;">Done</small>
                    </div>
                    <div class="col">
                        <span class="badge bg-danger mb-1 d-block"><?= $statusCounts['cancelled'] ?></span>
                        <small class="text-muted" style="font-size:.7rem;">Cancel</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart: Top Teknisi & Top Layanan Bar Charts -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0">Grafik Jasa Paling Banyak Dipesan</h6>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Top 5 Jasa</span>
            </div>
            <div class="card-body p-4">
                <div style="position: relative; width: 100%; height: 280px;">
                    <canvas id="serviceBarChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     TABEL DETAIL: TEKNISI DENGAN ORDER TERBANYAK
     ============================================================ -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <div class="p-2 bg-success-subtle text-success rounded-3"><i class="bi bi-trophy-fill"></i></div>
            <div>
                <h6 class="fw-bold mb-0">Peringkat Teknisi dengan Order Terbanyak</h6>
                <small class="text-muted">Mitra teknisi yang paling produktif dan banyak menyelesaikan tugas</small>
            </div>
        </div>
        <a href="<?= url('admin/technicians') ?>" class="btn btn-outline-success btn-sm rounded-pill px-3">
            Kelola Teknisi &rarr;
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                    <th class="ps-4" style="width: 60px;">Rank</th>
                    <th>Nama Teknisi</th>
                    <th>Bidang Keahlian</th>
                    <th>Lokasi</th>
                    <th>Total Order</th>
                    <th>Order Selesai</th>
                    <th>Rating Kepuasan</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($techOrderMap)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted small">Belum ada data teknisi.</td></tr>
                <?php else: ?>
                    <?php foreach ($techOrderMap as $idx => $tItem): ?>
                        <?php
                            $tech = $tItem['user'];
                            $prof = $tItem['profile'];
                            $rank = $idx + 1;
                            $medalClass = match($rank) {
                                1 => 'badge bg-warning text-dark border',
                                2 => 'badge bg-secondary-subtle text-dark border',
                                3 => 'badge bg-warning-subtle text-dark border',
                                default => 'badge bg-light text-muted border'
                            };
                        ?>
                        <tr>
                            <td class="ps-4">
                                <span class="<?= $medalClass ?> rounded-circle p-2" style="width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;">
                                    <?= $rank ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($tech->name) ?></div>
                                <small class="text-muted"><?= e($tech->phone ?: $tech->email) ?></small>
                            </td>
                            <td>
                                <span class="small fw-semibold"><?= e($prof ? $prof->expertise : '-') ?></span>
                            </td>
                            <td>
                                <span class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($prof ? $prof->location : '-') ?></span>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill">
                                    <?= $tItem['total_orders'] ?> Order
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-success-subtle text-success fw-bold px-3 py-2 rounded-pill">
                                    <?= $tItem['completed_count'] ?> Selesai
                                </span>
                            </td>
                            <td>
                                <?php if ($tItem['avg_rating'] > 0): ?>
                                    <span class="text-warning fw-bold small">
                                        <i class="bi bi-star-fill me-1"></i><?= $tItem['avg_rating'] ?> / 5
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">- Belum ada rating -</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <a href="<?= url("admin/technicians/{$tech->id}") ?>" class="btn btn-sm btn-light border rounded-pill px-3">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================================
     TABEL DETAIL: JASA PALING BANYAK DIPESAN
     ============================================================ -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <div class="p-2 bg-primary-subtle text-primary rounded-3"><i class="bi bi-fire"></i></div>
            <div>
                <h6 class="fw-bold mb-0">Peringkat Layanan Jasa Paling Banyak Dipesan</h6>
                <small class="text-muted">Layanan favorit pelanggan berdasarkan frekuensi booking</small>
            </div>
        </div>
        <a href="<?= url('admin/services') ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
            Kelola Jasa &rarr;
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                    <th class="ps-4" style="width: 60px;">Rank</th>
                    <th>Nama Layanan Jasa</th>
                    <th>Kategori</th>
                    <th>Harga Estimasi</th>
                    <th>Frekuensi Pemesanan</th>
                    <th>Total Estimasi Transaksi</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($serviceOrderMap)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted small">Belum ada layanan terdaftar.</td></tr>
                <?php else: ?>
                    <?php foreach ($serviceOrderMap as $idx => $sItem): ?>
                        <?php
                            $srv = $sItem['service'];
                            $cat = $sItem['category'];
                            $rank = $idx + 1;
                        ?>
                        <tr>
                            <td class="ps-4">
                                <span class="badge bg-light text-dark border rounded-circle p-2" style="width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;">
                                    <?= $rank ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($srv->name) ?></div>
                                <small class="text-muted font-monospace">#SRV-<?= str_pad($srv->id, 4, '0', STR_PAD_LEFT) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-tag text-primary me-1"></i><?= e($cat ? $cat->name : 'Uncategorized') ?>
                                </span>
                            </td>
                            <td>
                                <span class="small font-monospace">Rp <?= number_format($srv->estimated_price, 0, ',', '.') ?></span>
                            </td>
                            <td>
                                <span class="badge bg-primary text-white fw-bold px-3 py-2 rounded-pill">
                                    <?= $sItem['order_count'] ?>x Dipesan
                                </span>
                            </td>
                            <td>
                                <span class="fw-bold text-success font-monospace">
                                    Rp <?= number_format($sItem['gross_value'], 0, ',', '.') ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="<?= url("admin/services/{$srv->id}/edit") ?>" class="btn btn-sm btn-light border rounded-pill px-3">
                                    Edit Jasa
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================================
     CHART.JS INITIALIZATION
     ============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Doughnut Chart: Status
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($statusChartData['labels']) ?>,
            datasets: [{
                data: <?= json_encode($statusChartData['data']) ?>,
                backgroundColor: <?= json_encode($statusChartData['colors']) ?>,
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 10,
                        font: { size: 11, family: "'Plus Jakarta Sans', sans-serif" }
                    }
                }
            },
            cutout: '65%'
        }
    });

    // 2. Bar Chart: Top Services
    const serviceCtx = document.getElementById('serviceBarChart').getContext('2d');
    new Chart(serviceCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($serviceChartData['labels']) ?>,
            datasets: [{
                label: 'Jumlah Order Dipesan',
                data: <?= json_encode($serviceChartData['data']) ?>,
                backgroundColor: 'rgba(37, 99, 235, 0.85)',
                borderColor: 'rgba(37, 99, 235, 1)',
                borderWidth: 1,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0, font: { family: "'Plus Jakarta Sans', sans-serif" } }
                },
                x: {
                    ticks: {
                        font: { family: "'Plus Jakarta Sans', sans-serif", size: 10 },
                        callback: function(val, index) {
                            const label = this.getLabelForValue(val);
                            return label.length > 20 ? label.substr(0, 18) + '...' : label;
                        }
                    }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>
