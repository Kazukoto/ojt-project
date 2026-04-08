<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="{{ asset('css/partials/sidenav.css') }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f2f8;
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 200px;
            flex: 1;
            padding: 36px 30px;
        }

        .page-heading {
            font-size: 26px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 28px;
        }

        /* ── Stats ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 22px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            border-left: 4px solid transparent;
            transition: transform 0.2s, box-shadow 0.2s;
            animation: fadeUp 0.5s ease both;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.11); }
        .stat-card:nth-child(1) { border-left-color: #6366f1; animation-delay:.05s; }
        .stat-card:nth-child(2) { border-left-color: #f43f5e; animation-delay:.10s; }
        .stat-card:nth-child(3) { border-left-color: #10b981; animation-delay:.15s; }
        .stat-card:nth-child(4) { border-left-color: #f59e0b; animation-delay:.20s; }
        .stat-card:nth-child(5) { border-left-color: #3b82f6; animation-delay:.25s; }
        .stat-card:nth-child(6) { border-left-color: #8b5cf6; animation-delay:.30s; }

        .stat-icon {
            width: 72px; height: 72px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 36px; flex-shrink: 0;
        }
        .stat-card:nth-child(1) .stat-icon { background: #ede9fe; }
        .stat-card:nth-child(2) .stat-icon { background: #ffe4e6; }
        .stat-card:nth-child(3) .stat-icon { background: #d1fae5; }
        .stat-card:nth-child(4) .stat-icon { background: #fef3c7; }
        .stat-card:nth-child(5) .stat-icon { background: #dbeafe; }
        .stat-card:nth-child(6) .stat-icon { background: #ede9fe; }

        .stat-info { flex: 1; }
        .stat-info h3 { font-size: 20px; font-weight: 700; color: #1e293b; line-height: 1.1; }
        .stat-info p  { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: .04em; margin-top: 3px; }
        .stat-info small { font-size: 11px; color: #9ca3af; display: block; margin-top: 2px; }

        /* Payroll card */
        #payrollStatCard { align-items: flex-start; }
        .payroll-body { flex: 1; }
        .cutoff-btns { display: flex; gap: 6px; margin-top: 10px; }
        .cutoff-btn {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            border: 1.5px solid #6366f1;
            background: #fff;
            color: #6366f1;
            transition: all .15s;
        }
        .cutoff-btn.active   { background: #6366f1; color: #fff; }
        .cutoff-btn:disabled { opacity: .5; cursor: not-allowed; }
        #payrollLoading { display: none; font-size: 11px; color: #9ca3af; margin-top: 4px; }
        #payrollLoading.show { display: block; }

        /* ── Bottom layout ── */
        .bottom-section {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 20px;
            align-items: start;
        }

        /* ── Table Card ── */
        .table-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            overflow: hidden;
            animation: fadeUp 0.5s ease .25s both;
        }
        .table-card-header { padding: 18px 20px 14px; border-bottom: 1px solid #f1f5f9; }
        .table-card-header h2 { font-size: 16px; font-weight: 700; color: #1e293b; }

        table { width: 100%; border-collapse: collapse; }
        thead tr { background: linear-gradient(90deg, #6366f1, #7c3aed); }
        thead th {
            padding: 13px 16px; text-align: left;
            font-size: 12px; font-weight: 600; color: #fff;
            letter-spacing: .05em; text-transform: uppercase; white-space: nowrap;
        }
        tbody td { padding: 12px 16px; font-size: 14px; color: #374151; border-bottom: 1px solid #f1f5f9; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr { transition: background .15s; cursor: pointer; }
        tbody tr:hover { background: #f8f7ff; }

        .name-cell { display: flex; align-items: center; gap: 10px; }
        .avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #7c3aed);
            color: #fff; display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 13px; flex-shrink: 0;
        }

        /* ── Dashboard Pagination ── */
        .dash-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 4px;
            padding: 14px 16px;
            border-top: 1px solid #f1f5f9;
        }
        .dash-pagination a,
        .dash-pagination span {
            padding: 6px 11px;
            border: 1.5px solid #e2e8f0;
            border-radius: 6px;
            font-size: 12px;
            color: #374151;
            text-decoration: none;
            background: #fff;
            transition: all .15s;
        }
        .dash-pagination a:hover      { background: #6366f1; color: #fff; border-color: #6366f1; }
        .dash-pagination .pg-active   { background: linear-gradient(135deg,#6366f1,#7c3aed); color: #fff; border-color: #6366f1; font-weight: 700; }
        .dash-pagination .pg-disabled { color: #cbd5e1; background: #f8fafc; pointer-events: none; }

        /* ── Charts column ── */
        .charts-column {
            display: flex; flex-direction: column; gap: 18px;
            animation: fadeUp 0.5s ease .3s both;
        }
        .chart-card {
            background: #fff; border-radius: 14px;
            padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        }
        .chart-card h3 { font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 14px; }

        .chart-wrapper {
            position: relative; height: 180px;
            display: flex; align-items: center; justify-content: center;
        }
        .chart-center-label { position: absolute; text-align: center; pointer-events: none; }
        .chart-center-label .big   { font-size: 22px; font-weight: 800; color: #1e293b; line-height: 1; }
        .chart-center-label .small { font-size: 11px; color: #94a3b8; margin-top: 2px; }

        .chart-legend { display: flex; flex-direction: column; gap: 7px; margin-top: 14px; }
        .legend-item  { display: flex; align-items: center; justify-content: space-between; font-size: 13px; color: #475569; }
        .legend-left  { display: flex; align-items: center; }
        .legend-dot   { width: 10px; height: 10px; border-radius: 50%; margin-right: 7px; flex-shrink: 0; }
        .legend-val   { font-weight: 700; color: #1e293b; }

        /* ── Animations ── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes modalFade {
            from { opacity: 0; transform: translateY(-14px) scale(.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 1100px) {
            .bottom-section { grid-template-columns: 1fr; }
            .charts-column  { flex-direction: row; flex-wrap: wrap; }
            .chart-card     { flex: 1; min-width: 260px; }
        }
        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 20px; }
            .stats-grid   { grid-template-columns: 1fr 1fr; }
            .charts-column { flex-direction: column; }
        }
    </style>
</head>
<body>

@if(Session::has('user_id') && Session::get('role_id') == 1)

    {{-- Sidenav (includes logout modal + script) --}}
    @include('superadmin.partials.sidenav')

    <div class="main-content">

        <h1 class="page-heading">📊 Dashboard</h1>

        <!-- ══ Stats Grid ══ -->
        <div class="stats-grid">

            {{-- Gross Pay (AJAX toggle) --}}
            <div class="stat-card" id="payrollStatCard">
                <div class="stat-icon">💰</div>
                <div class="payroll-body">
                    <h3 id="payrollAmount">₱{{ number_format($totalGrossPay ?? 0, 2) }}</h3>
                    <p>Total Gross Pay</p>
                    <small id="payrollLabel">📅 {{ $cutoffLabel }}</small>
                    <div id="payrollLoading">⏳ Calculating...</div>
                    <div class="cutoff-btns">
                        <button class="cutoff-btn {{ $defaultCutoff === 'first'  ? 'active' : '' }}"
                            id="btnFirst"  onclick="loadPayroll('first')">1st–15th</button>
                        <button class="cutoff-btn {{ $defaultCutoff === 'second' ? 'active' : '' }}"
                            id="btnSecond" onclick="loadPayroll('second')">16th–End</button>
                    </div>
                </div>
            </div>

            {{-- Net Pay --}}
            <div class="stat-card">
                <div class="stat-icon">💵</div>
                <div class="stat-info">
                    <h3>₱{{ number_format($totalNetPay ?? 0, 2) }}</h3>
                    <p>Net Pay This Period</p>
                    <small>After deductions</small>
                </div>
            </div>

            {{-- Total Cash Advanced --}}
            <div class="stat-card">
                <div class="stat-icon">💳</div>
                <div class="stat-info">
                    <h3>₱{{ number_format($totalCashAdvance ?? 0, 2) }}</h3>
                    <p>Total Cash Advanced</p>
                    <small>Approved only</small>
                </div>
            </div>

            {{-- Present Today --}}
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <h3>{{ number_format($totalPresentToday ?? 0) }}</h3>
                    <p>Present Today</p>
                    <small>{{ $totalAbsentToday ?? 0 }} absent</small>
                </div>
            </div>

        </div>

        <!-- ══ Bottom: Table + Charts ══ -->
        <div class="bottom-section">

            <!-- Employee Table -->
            <div class="table-card">
                <div class="table-card-header">
                    <h2>👔 Employee List</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Position</th>
                            <th>Employment Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $employee)
                        <tr class="employee-row"
                            data-id="{{ $employee->id }}"
                            data-name="{{ $employee->last_name }}, {{ $employee->first_name }} {{ $employee->middle_name }} {{ $employee->suffixes }}"
                            data-first-name="{{ $employee->first_name }}"
                            data-contact-number="{{ $employee->contact_number }}"
                            data-gender="{{ $employee->gender }}"
                            data-birthdate="{{ $employee->birthdate }}"
                            data-position="{{ $employee->position }}"
                            data-username="{{ $employee->username }}"
                            data-address="{{ $employee->house_number }} {{ $employee->purok }}, {{ $employee->barangay }}, {{ $employee->city }}, {{ $employee->province }}"
                            data-rating="{{ $employee->rating }}"
                            data-sss="{{ $employee->sss }}"
                            data-philhealth="{{ $employee->philhealth }}"
                            data-pagibig="{{ $employee->pagibig }}"
                            data-ec-name="{{ $employee->first_name_ec }} {{ $employee->last_name_ec }}"
                            data-ec-contact="{{ $employee->contact_number_ec }}"
                            data-ec-email="{{ $employee->email_ec }}"
                            data-ec-address="{{ $employee->house_number_ec }} {{ $employee->purok_ec }}, {{ $employee->barangay_ec }}, {{ $employee->city_ec }}, {{ $employee->province_ec }}">
                            <td>{{ $employee->id }}</td>
                            <td>
                                <div class="name-cell">
                                    <div class="avatar">{{ strtoupper(substr($employee->first_name, 0, 1)) }}</div>
                                    <span>{{ $employee->last_name }}, {{ $employee->first_name }}</span>
                                </div>
                            </td>
                            <td>{{ $employee->gender }}</td>
                            <td>{{ $employee->position }}</td>
                            <td>{{ $employee->created_at->format('M d, Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="text-align:center;padding:30px;color:#94a3b8;">No employees found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

                @if($employees->hasPages())
                <div class="dash-pagination">
                    @if($employees->onFirstPage())
                        <span class="pg-disabled">«</span>
                    @else
                        <a href="{{ $employees->previousPageUrl() }}">«</a>
                    @endif

                    @php
                        $cur  = $employees->currentPage();
                        $last = $employees->lastPage();
                        $from = max(1, $cur - 2);
                        $to   = min($last, $cur + 2);
                    @endphp

                    @for($p = $from; $p <= $to; $p++)
                        @if($p == $cur)
                            <span class="pg-active">{{ $p }}</span>
                        @else
                            <a href="{{ $employees->url($p) }}">{{ $p }}</a>
                        @endif
                    @endfor

                    @if($employees->hasMorePages())
                        <a href="{{ $employees->nextPageUrl() }}">»</a>
                    @else
                        <span class="pg-disabled">»</span>
                    @endif
                </div>
                @endif
            </div>

            <!-- Charts Column -->
            <div class="charts-column">

                <!-- Employees by Position -->
                <div class="chart-card">
                    <h3>🏗️ Employees by Position</h3>
                    <div class="chart-wrapper">
                        <canvas id="positionChart" width="170" height="170"></canvas>
                        <div class="chart-center-label">
                            <div class="big">{{ $totalUsers ?? 0 }}</div>
                            <div class="small">Total</div>
                        </div>
                    </div>
                    <div class="chart-legend">
                        @foreach($positionCounts->take(6) as $pos)
                        <div class="legend-item">
                            <div class="legend-left">
                                <div class="legend-dot" style="background: hsl({{ ($loop->index * 47) % 360 }}, 65%, 58%);"></div>
                                {{ $pos->position }}
                            </div>
                            <span class="legend-val">{{ $pos->total }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Payroll Breakdown -->
                <div class="chart-card">
                    <h3>💼 Payroll Breakdown</h3>
                    <div class="chart-wrapper">
                        <canvas id="payrollChart" width="170" height="170"></canvas>
                        <div class="chart-center-label">
                            <div class="big" style="font-size:14px;">₱{{ number_format(($totalBasicPay ?? 0) + ($totalOTPay ?? 0) + ($totalAllowance ?? 0), 0) }}</div>
                            <div class="small">Gross</div>
                        </div>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <div class="legend-left"><div class="legend-dot" style="background:#6366f1;"></div>Basic Pay</div>
                            <span class="legend-val">₱{{ number_format($totalBasicPay ?? 0, 0) }}</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-left"><div class="legend-dot" style="background:#f59e0b;"></div>OT Pay</div>
                            <span class="legend-val">₱{{ number_format($totalOTPay ?? 0, 0) }}</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-left"><div class="legend-dot" style="background:#10b981;"></div>Allowance</div>
                            <span class="legend-val">₱{{ number_format($totalAllowance ?? 0, 0) }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ── Employee View Modal ── --}}
    <div id="viewUserModal" style="display:none;position:fixed;inset:0;background:rgba(15,10,40,.55);z-index:9999;backdrop-filter:blur(4px);justify-content:center;align-items:center;padding:20px;">
        <div style="max-width:780px;padding:0;overflow:hidden;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.22);width:100%;position:relative;animation:modalFade .22s ease;">
            <button style="position:absolute;top:14px;right:16px;z-index:10;width:32px;height:32px;border:none;border-radius:50%;background:#f1f5f9;color:#475569;font-size:16px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;" onclick="closeViewUserModal()">✕</button>
            <div style="display:flex;align-items:center;gap:24px;padding:28px 32px 20px;border-bottom:1px solid #f0f0f0;">
                <div id="profileAvatar" style="width:90px;height:90px;border-radius:50%;flex-shrink:0;background:linear-gradient(135deg,#6366f1,#4f46e5);display:flex;align-items:center;justify-content:center;font-size:36px;font-weight:700;color:#fff;box-shadow:0 4px 16px rgba(99,102,241,.35);overflow:hidden;">
                    <span id="profileInitial">?</span>
                    <img id="profileImg" src="" alt="" style="display:none;width:100%;height:100%;object-fit:cover;">
                </div>
                <div>
                    <div id="viewFullName"  style="font-size:22px;font-weight:700;color:#1e293b;margin-bottom:4px;"></div>
                    <div id="viewRoleBadge" style="display:inline-block;padding:4px 14px;border-radius:20px;font-size:12px;font-weight:700;letter-spacing:.4px;background:#ede9fe;color:#4f46e5;"></div>
                </div>
            </div>
            <div style="padding:24px 32px;overflow-y:auto;max-height:520px;">
                <div style="font-size:11px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:.8px;margin-bottom:12px;">Personal Information</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px 24px;margin-bottom:24px;">
                    <div><span style="font-size:11px;color:#94a3b8;font-weight:600;display:block;margin-bottom:2px;">BIRTHDATE</span><span id="viewBirthdate" style="font-size:14px;color:#1e293b;font-weight:500;"></span></div>
                    <div><span style="font-size:11px;color:#94a3b8;font-weight:600;display:block;margin-bottom:2px;">GENDER</span><span id="viewGender" style="font-size:14px;color:#1e293b;font-weight:500;"></span></div>
                    <div><span style="font-size:11px;color:#94a3b8;font-weight:600;display:block;margin-bottom:2px;">CONTACT NUMBER</span><span id="viewContact" style="font-size:14px;color:#1e293b;font-weight:500;"></span></div>
                    <div><span style="font-size:11px;color:#94a3b8;font-weight:600;display:block;margin-bottom:2px;">USERNAME</span><span id="viewUsername" style="font-size:14px;color:#1e293b;font-weight:500;"></span></div>
                    <div style="grid-column:1/-1;"><span style="font-size:11px;color:#94a3b8;font-weight:600;display:block;margin-bottom:2px;">ADDRESS</span><span id="viewAddress" style="font-size:14px;color:#1e293b;font-weight:500;"></span></div>
                </div>
                <div style="font-size:11px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:.8px;margin-bottom:12px;">Government IDs</div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px 24px;margin-bottom:24px;">
                    <div><span style="font-size:11px;color:#94a3b8;font-weight:600;display:block;margin-bottom:2px;">SSS</span><span id="viewSss" style="font-size:14px;color:#1e293b;font-weight:500;"></span></div>
                    <div><span style="font-size:11px;color:#94a3b8;font-weight:600;display:block;margin-bottom:2px;">PHILHEALTH</span><span id="viewPhilhealth" style="font-size:14px;color:#1e293b;font-weight:500;"></span></div>
                    <div><span style="font-size:11px;color:#94a3b8;font-weight:600;display:block;margin-bottom:2px;">PAG-IBIG</span><span id="viewPagibig" style="font-size:14px;color:#1e293b;font-weight:500;"></span></div>
                </div>
                <div style="font-size:11px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:.8px;margin-bottom:12px;">Emergency Contact</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px 24px;">
                    <div><span style="font-size:11px;color:#94a3b8;font-weight:600;display:block;margin-bottom:2px;">FULL NAME</span><span id="viewEcName" style="font-size:14px;color:#1e293b;font-weight:500;"></span></div>
                    <div><span style="font-size:11px;color:#94a3b8;font-weight:600;display:block;margin-bottom:2px;">CONTACT NUMBER</span><span id="viewEcContact" style="font-size:14px;color:#1e293b;font-weight:500;"></span></div>
                    <div><span style="font-size:11px;color:#94a3b8;font-weight:600;display:block;margin-bottom:2px;">EMAIL</span><span id="viewEcEmail" style="font-size:14px;color:#1e293b;font-weight:500;"></span></div>
                    <div style="grid-column:1/-1;"><span style="font-size:11px;color:#94a3b8;font-weight:600;display:block;margin-bottom:2px;">ADDRESS</span><span id="viewEcAddress" style="font-size:14px;color:#1e293b;font-weight:500;"></span></div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function () {

        // ── Employee row → view modal ──────────────────────
        document.querySelectorAll(".employee-row").forEach(row => {
            row.addEventListener("click", function () {
                const d = this.dataset;

                document.getElementById('profileInitial').textContent      = (d.firstName || '?').charAt(0).toUpperCase();
                document.getElementById('profileImg').style.display        = 'none';
                document.getElementById('profileInitial').style.display    = '';
                document.getElementById('viewFullName').textContent        = d.name         || 'N/A';
                document.getElementById('viewRoleBadge').textContent       = d.position     || 'N/A';
                document.getElementById('viewBirthdate').textContent       = d.birthdate    || '—';
                document.getElementById('viewGender').textContent          = d.gender       || '—';
                document.getElementById('viewContact').textContent         = d.contactNumber|| '—';
                document.getElementById('viewUsername').textContent        = d.username     || '—';
                document.getElementById('viewAddress').textContent         = d.address      || '—';
                document.getElementById('viewSss').textContent             = d.sss          || '—';
                document.getElementById('viewPhilhealth').textContent      = d.philhealth   || '—';
                document.getElementById('viewPagibig').textContent         = d.pagibig      || '—';
                document.getElementById('viewEcName').textContent          = d.ecName       || '—';
                document.getElementById('viewEcContact').textContent       = d.ecContact    || '—';
                document.getElementById('viewEcEmail').textContent         = d.ecEmail      || '—';
                document.getElementById('viewEcAddress').textContent       = d.ecAddress    || '—';

                document.getElementById('viewUserModal').style.display = 'flex';
            });
        });

        // ── Employees by Position Donut ────────────────────
        @php
            $posLabels = $positionCounts->take(6)->pluck('position')->toJson();
            $posData   = $positionCounts->take(6)->pluck('total')->toJson();
            $posColors = $positionCounts->take(6)->map(function($p, $i) {
                $hues = [240, 210, 160, 40, 280, 340];
                return 'hsl(' . ($hues[$i % count($hues)]) . ', 65%, 58%)';
            })->toJson();
        @endphp

        new Chart(document.getElementById('positionChart'), {
            type: 'doughnut',
            data: {
                labels: {!! $posLabels !!},
                datasets: [{
                    data: {!! $posData !!},
                    backgroundColor: {!! $posColors !!},
                    borderWidth: 2, borderColor: '#fff', hoverOffset: 6,
                }]
            },
            options: {
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed} employees` } }
                },
                animation: { animateRotate: true, duration: 900, delay: 300 }
            }
        });

        // ── Payroll Breakdown Donut ────────────────────────
        const basicPay  = {{ round($totalBasicPay  ?? 0) }};
        const otPay     = {{ round($totalOTPay     ?? 0) }};
        const allowance = {{ round($totalAllowance ?? 0) }};
        const hasPayroll = basicPay + otPay + allowance > 0;

        new Chart(document.getElementById('payrollChart'), {
            type: 'doughnut',
            data: {
                labels: ['Basic Pay', 'OT Pay', 'Allowance'],
                datasets: [{
                    data: hasPayroll ? [basicPay, otPay, allowance] : [1, 0, 0],
                    backgroundColor: hasPayroll ? ['#6366f1', '#f59e0b', '#10b981'] : ['#e2e8f0'],
                    borderWidth: 2, borderColor: '#fff', hoverOffset: 6,
                }]
            },
            options: {
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ₱${ctx.parsed.toLocaleString()}` } }
                },
                animation: { animateRotate: true, duration: 900, delay: 450 }
            }
        });

        // ── Close modals on backdrop click ────────────────
        document.getElementById('viewUserModal').addEventListener('click', function(e) {
            if (e.target === this) closeViewUserModal();
        });
    });

    // ── AJAX payroll cutoff toggle ─────────────────────────
    function loadPayroll(cutoff) {
        const btn1    = document.getElementById('btnFirst');
        const btn2    = document.getElementById('btnSecond');
        const amount  = document.getElementById('payrollAmount');
        const label   = document.getElementById('payrollLabel');
        const loading = document.getElementById('payrollLoading');

        btn1.classList.toggle('active', cutoff === 'first');
        btn2.classList.toggle('active', cutoff === 'second');
        btn1.disabled = btn2.disabled = true;
        amount.style.opacity = '0.35';
        loading.classList.add('show');

        fetch(`{{ route('superadmin.payroll.gross') }}?cutoff=${cutoff}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => { if (!r.ok) throw new Error(); return r.json(); })
        .then(data => {
            amount.textContent   = '₱' + data.total_gross_pay;
            label.textContent    = '📅 ' + data.cutoff_label;
            amount.style.opacity = '1';
            loading.classList.remove('show');
            btn1.disabled = btn2.disabled = false;
        })
        .catch(() => {
            amount.style.opacity = '1';
            loading.classList.remove('show');
            btn1.disabled = btn2.disabled = false;
        });
    }

    function closeViewUserModal() {
        document.getElementById('viewUserModal').style.display = 'none';
    }
    </script>

@else
    <script>window.location.href = "{{ route('login') }}";</script>
@endif

</body>
</html>