@php
    $stats = [
        ['label' => 'Kamar Tersedia', 'value' => '18', 'icon' => 'bi-house-door', 'color' => 'primary'],
        ['label' => 'Check-In Hari Ini', 'value' => '6', 'icon' => 'bi-box-arrow-in-right', 'color' => 'success'],
        ['label' => 'Check-Out Hari Ini', 'value' => '4', 'icon' => 'bi-box-arrow-left', 'color' => 'warning'],
        ['label' => 'Pendapatan', 'value' => 'Rp 24.8Jt', 'icon' => 'bi-cash-stack', 'color' => 'info'],
    ];

    $rooms = [
        ['room_no' => '101', 'type' => 'Deluxe', 'status' => 'Occupied', 'guest' => 'Andi Wijaya', 'check_in' => '2026-10-04', 'check_out' => '2026-10-07', 'payment' => 'DP', 'total' => 'Rp 1.200.000'],
        ['room_no' => '102', 'type' => 'Superior', 'status' => 'Available', 'guest' => '-', 'check_in' => '-', 'check_out' => '-', 'payment' => '-', 'total' => '-'],
        ['room_no' => '103', 'type' => 'Family', 'status' => 'Occupied', 'guest' => 'Rina Sari', 'check_in' => '2026-10-03', 'check_out' => '2026-10-05', 'payment' => 'Lunas', 'total' => 'Rp 1.800.000'],
        ['room_no' => '104', 'type' => 'Standard', 'status' => 'Cleaning', 'guest' => '-', 'check_in' => '-', 'check_out' => '-', 'payment' => '-', 'total' => '-'],
        ['room_no' => '201', 'type' => 'Deluxe', 'status' => 'Available', 'guest' => '-', 'check_in' => '-', 'check_out' => '-', 'payment' => '-', 'total' => '-'],
        ['room_no' => '202', 'type' => 'Suite', 'status' => 'Occupied', 'guest' => 'Budi Hartono', 'check_in' => '2026-10-02', 'check_out' => '2026-10-06', 'payment' => 'DP', 'total' => 'Rp 2.800.000'],
        ['room_no' => '203', 'type' => 'Superior', 'status' => 'Available', 'guest' => '-', 'check_in' => '-', 'check_out' => '-', 'payment' => '-', 'total' => '-'],
        ['room_no' => '204', 'type' => 'Standard', 'status' => 'Occupied', 'guest' => 'Dewi Lestari', 'check_in' => '2026-10-04', 'check_out' => '2026-10-08', 'payment' => 'Lunas', 'total' => 'Rp 1.500.000'],
    ];
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-In & Check-Out Kamar</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: #f5f7fb;
            color: #1f2937;
        }

        .page-header {
            background: linear-gradient(135deg, #0d6efd, #0b3d91);
            color: white;
            border-radius: 18px;
            padding: 26px 28px;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px rgba(13, 110, 253, 0.15);
        }

        .stat-card {
            border: 0;
            border-radius: 16px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .stat-card .card-body {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 22px 20px;
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .card-panel {
            background: white;
            border: 1px solid #edf2f7;
            border-radius: 18px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.04);
        }

        .table thead th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            border-bottom: 1px solid #edf2f7;
        }

        .status-badge {
            font-size: 0.78rem;
            font-weight: 600;
            padding: 0.5rem 0.8rem;
            border-radius: 999px;
        }

        .status-occupied {
            background: #e0f2fe;
            color: #0369a1;
        }

        .status-available {
            background: #dcfce7;
            color: #166534;
        }

        .status-cleaning {
            background: #fef3c7;
            color: #92400e;
        }

        .btn-action {
            border-radius: 10px;
            font-weight: 600;
            padding: 0.65rem 1rem;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            padding: 0.75rem 0.9rem;
            border: 1px solid #dfe7f1;
            background: #fff;
        }

        .form-control:focus,
        .form-select:focus {
            box-shadow: none;
            border-color: #86b7fe;
        }

        .section-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .divider {
            border-top: 1px solid #edf2f7;
            margin: 22px 0;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div class="text-uppercase small opacity-75 mb-1">Hotel Management</div>
                <h2 class="mb-0 fw-bold">Check-In & Check-Out Kamar</h2>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-light btn-action">
                    <i class="bi bi-filter me-2"></i>Filter
                </button>
                <button class="btn btn-outline-light btn-action">
                    <i class="bi bi-download me-2"></i>Export
                </button>
            </div>
        </div>

        <div class="row g-3 mb-4">
            @foreach ($stats as $stat)
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div>
                                <div class="text-muted small">{{ $stat['label'] }}</div>
                                <div class="fs-4 fw-bold mt-1">{{ $stat['value'] }}</div>
                            </div>
                            <div class="stat-icon bg-{{ $stat['color'] }}">
                                <i class="bi {{ $stat['icon'] }}"></i>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card-panel p-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h5 class="section-title mb-0">Daftar Kamar</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary btn-action">
                                <i class="bi bi-plus-circle me-2"></i>Tambah Reservasi
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>No. Kamar</th>
                                    <th>Tipe</th>
                                    <th>Status</th>
                                    <th>Tamu</th>
                                    <th>Check-In</th>
                                    <th>Check-Out</th>
                                    <th>Pembayaran</th>
                                    <th>Total</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rooms as $room)
                                    <tr>
                                        <td class="fw-semibold">{{ $room['room_no'] }}</td>
                                        <td>{{ $room['type'] }}</td>
                                        <td>
                                            @php
                                                $statusClass = match($room['status']) {
                                                    'Occupied' => 'status-occupied',
                                                    'Available' => 'status-available',
                                                    'Cleaning' => 'status-cleaning',
                                                    default => 'status-available'
                                                };
                                            @endphp
                                            <span class="status-badge {{ $statusClass }}">
                                                {{ $room['status'] }}
                                            </span>
                                        </td>
                                        <td>{{ $room['guest'] }}</td>
                                        <td>{{ $room['check_in'] }}</td>
                                        <td>{{ $room['check_out'] }}</td>
                                        <td>{{ $room['payment'] }}</td>
                                        <td>{{ $room['total'] }}</td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-primary" title="Check-In">
                                                    <i class="bi bi-box-arrow-in-right"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning" title="Check-Out">
                                                    <i class="bi bi-box-arrow-left"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-panel p-4 mb-4">
                    <h5 class="section-title">Form Check-In</h5>

                    <form>
                        <div class="mb-3">
                            <label class="form-label">Nama Tamu</label>
                            <input type="text" class="form-control" placeholder="Masukkan nama tamu">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nomor Kamar</label>
                            <select class="form-select">
                                <option selected>Pilih kamar</option>
                                <option>101 - Deluxe</option>
                                <option>102 - Superior</option>
                                <option>201 - Deluxe</option>
                                <option>203 - Superior</option>
                            </select>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label">Check-In</label>
                                <input type="date" class="form-control" value="2026-10-04">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Durasi</label>
                                <select class="form-select">
                                    <option>1 Malam</option>
                                    <option selected>2 Malam</option>
                                    <option>3 Malam</option>
                                    <option>5 Malam</option>
                                    <option>7 Malam</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-3 mb-3">
                            <label class="form-label">Jumlah Tamu</label>
                            <input type="number" class="form-control" value="2" min="1">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Metode Pembayaran</label>
                            <select class="form-select">
                                <option selected>Transfer</option>
                                <option>Cash</option>
                                <option>Debit/Kartu</option>
                                <option>E-Wallet</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">DP Awal</label>
                            <input type="text" class="form-control" value="Rp 600.000">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 btn-action">
                            <i class="bi bi-check-circle me-2"></i>Proses Check-In
                        </button>
                    </form>
                </div>

                <div class="card-panel p-4">
                    <h5 class="section-title">Form Check-Out</h5>

                    <form>
                        <div class="mb-3">
                            <label class="form-label">Nomor Kamar</label>
                            <select class="form-select">
                                <option selected>Pilih kamar</option>
                                <option>101 - Deluxe</option>
                                <option>103 - Family</option>
                                <option>202 - Suite</option>
                                <option>204 - Standard</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tanggal Check-Out</label>
                            <input type="date" class="form-control" value="2026-10-05">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tagihan Tambahan</label>
                            <input type="text" class="form-control" placeholder="Rp 0">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control" rows="3" placeholder="Tambahkan catatan..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-warning w-100 btn-action text-white">
                            <i class="bi bi-box-arrow-left me-2"></i>Proses Check-Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>