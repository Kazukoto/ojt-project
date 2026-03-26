<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslips</title>
    <link rel="stylesheet" href="{{ asset('css/partials/sidenav.css') }}">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f2f5;
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 200px;
            flex: 1;
            display: grid;
            grid-template-columns: 340px 1fr;
            grid-template-rows: auto 1fr;
            gap: 0;
            min-height: 100vh;
        }

        .page-title {
            grid-column: 1 / -1;
            padding: 24px 28px 16px;
            font-size: 22px;
            font-weight: 700;
            color: #1e293b;
            border-bottom: 1px solid #e2e8f0;
            background: #fff;
        }

        /* ── LEFT PANEL ── */
        .left-panel { background: #fff; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; overflow: hidden; }
        .left-panel-inner { flex: 1; display: flex; flex-direction: column; padding: 18px 16px; overflow: hidden; }
        .search-row { display: flex; gap: 8px; margin-bottom: 14px; }
        .search-box { flex: 1; padding: 9px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 13px; background: #f8fafc; color: #1e293b; outline: none; transition: border-color .2s; }
        .search-box:focus { border-color: #6366f1; background: #fff; }
        .role-select { padding: 9px 10px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 13px; background: #f8fafc; color: #374151; outline: none; cursor: pointer; min-width: 110px; }
        .role-select:focus { border-color: #6366f1; }

        .employee-list { flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 6px; }
        .employee-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px; cursor: pointer; border: 1.5px solid transparent; transition: all .18s; }
        .employee-item:hover { background: #f0efff; border-color: #c4b5fd; }
        .employee-item.active { background: #ede9fe; border-color: #6366f1; }
        .emp-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #7c3aed); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; flex-shrink: 0; }
        .emp-info { flex: 1; min-width: 0; }
        .emp-name { font-size: 13px; font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .emp-role { font-size: 11px; color: #94a3b8; margin-top: 1px; }

        .pagination-bar { display: flex; justify-content: center; align-items: center; gap: 4px; padding: 12px 0 4px; flex-shrink: 0; }
        .pagination-bar a, .pagination-bar span { padding: 5px 10px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 12px; color: #374151; text-decoration: none; transition: all .15s; }
        .pagination-bar a:hover { background: #6366f1; color: #fff; border-color: #6366f1; }
        .pagination-bar .pg-active { background: #6366f1; color: #fff; border-color: #6366f1; font-weight: 700; }
        .pagination-bar .pg-disabled { color: #cbd5e1; pointer-events: none; background: #f8fafc; }

        /* ── RIGHT PANEL ── */
        .right-panel { background: #f0f2f5; overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 16px; }
        .export-bar { display: flex; justify-content: flex-end; }
        .btn-export { display: inline-flex; align-items: center; gap: 6px; padding: 9px 20px; background: linear-gradient(135deg, #10b981, #059669); color: #fff; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; transition: opacity .2s; box-shadow: 0 2px 8px rgba(16,185,129,.25); }
        .btn-export:hover { opacity: .88; }

        .date-strip input[type="date"] { padding: 7px 8px; border: 1.5px solid #e2e8f0; border-radius: 7px; font-size: 12px; color: #1e293b; outline: none; }
        .date-strip input[type="date"]:focus { border-color: #6366f1; }
        .btn-apply { padding: 7px 14px; background: #6366f1; color: #fff; border: none; border-radius: 7px; font-size: 12px; font-weight: 700; cursor: pointer; }
        .btn-apply:hover { background: #4f46e5; }
        .btn-preset { padding: 7px 10px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 7px; font-size: 11px; font-weight: 600; cursor: pointer; }
        .btn-preset:hover { background: #e2e8f0; }

        /* ── PAYSLIP CARD ── */
        .payslip-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.07); }

        /* Purple gradient header */
        .slip-header { background: linear-gradient(135deg, #6366f1 0%, #7c3aed 100%); padding: 20px 24px; color: #fff; }
        .slip-name   { font-size: 20px; font-weight: 700; letter-spacing: .02em; }
        .slip-role   { font-size: 13px; opacity: .8; margin-top: 3px; }
        .slip-badges { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 14px; }
        .slip-badge  { padding: 5px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; letter-spacing: .03em; }
        .badge-green  { background: rgba(16,185,129,.3);  border: 1px solid rgba(16,185,129,.6); }
        .badge-red    { background: rgba(244,63,94,.3);   border: 1px solid rgba(244,63,94,.6); }
        .badge-orange { background: rgba(251,146,60,.3);  border: 1px solid rgba(251,146,60,.6); }
        .badge-purple { background: rgba(168,85,247,.3);  border: 1px solid rgba(168,85,247,.6); }
        .badge-blue   { background: rgba(59,130,246,.3);  border: 1px solid rgba(59,130,246,.6); }

        /* Period row */
        .slip-period { background: #f8fafc; padding: 8px 24px; border-bottom: 1px solid #f1f5f9; font-size: 12px; color: #64748b; display: flex; gap: 24px; }
        .slip-period strong { color: #374151; }

        /* Table */
        .slip-table { width: 100%; border-collapse: collapse; }
        .slip-table thead th { background: #6c63ff; color: #fff; padding: 10px 16px; text-align: left; font-size: 11px; font-weight: 700; letter-spacing: .04em; }
        .slip-table tbody tr { border-bottom: 1px solid #f0f0f0; }
        .slip-table tbody tr:hover { background: #fafbff; }
        .slip-table td { padding: 10px 16px; font-size: 13px; vertical-align: middle; }

        .td-label  { font-weight: 600; color: #374151; width: 28%; }
        .td-value  { color: #475569; width: 22%; }
        .td-label2 { font-weight: 600; color: #374151; width: 28%; border-left: 1px solid #f0f0f0; }
        .td-value2 { color: #475569; width: 22%; }
        .td-sm     { font-size: 11px; color: #94a3b8; font-style: italic; font-weight: 400; }

        /* Special row types */
        .row-holiday td          { background: #fdf4ff; }
        .row-holiday .td-label   { color: #7e22ce; font-weight: 700; }
        .row-holiday .td-value   { color: #7e22ce; font-weight: 700; }
        .row-holiday .td-label2  { color: #c2410c; font-weight: 700; border-left: 1px solid #e9d5ff; }
        .row-holiday .td-value2  { color: #c2410c; font-weight: 700; }

        .row-restday td          { background: #fff7ed; }
        .row-restday .td-label   { color: #c2410c; font-weight: 700; }
        .row-restday .td-value   { color: #c2410c; font-weight: 700; }
        .row-restday .td-label2  { color: #c2410c; font-weight: 700; border-left: 1px solid #fed7aa; }
        .row-restday .td-value2  { color: #c2410c; font-weight: 700; }

        .row-section-yellow td  { background: #fef9c3; font-weight: 700; font-size: 12px; color: #92400e; padding: 8px 16px; }
        .row-section-red td     { background: #fee2e2; font-weight: 700; font-size: 12px; color: #991b1b; padding: 8px 16px; }
        .row-section-green td   { background: #dcfce7; font-weight: 700; font-size: 12px; color: #166534; padding: 8px 16px; }

        .row-gross td { background: #f0fdf4; font-weight: 700; font-size: 15px; color: #16a34a; padding: 12px 16px; border-top: 2px solid #22c55e; }

        .sig-cell { text-align: center; border-left: 1px solid #f0f0f0; }
        .sig-cell strong { display: block; font-size: 13px; color: #1e293b; margin-bottom: 4px; }
        .sig-cell small  { font-size: 10px; color: #94a3b8; line-height: 1.5; }

        .highlight-green { background: #dcfce7; color: #166534; font-weight: 700; padding: 6px 10px; display: block; }

        /* Empty state */
        .empty-state { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 60px 20px; color: #94a3b8; }
        .empty-icon  { font-size: 52px; margin-bottom: 16px; }

        #mainForm { display: none; }
    </style>
</head>

<body>
@if(Session::has('user_id') && Session::get('role_id') == 4 )

    @include('finance.partials.sidenav')

    <div class="main-content">

        <div class="page-title">💸 Employee Payslips</div>

        <!-- ══ LEFT PANEL ══ -->
        <div class="left-panel">
            <div class="left-panel-inner">

                <div class="search-row">
                    <input type="text" id="employeeSearch" class="search-box" placeholder="🔍 Search employee..." value="{{ request('search') }}">
                    <select id="roleFilter" class="role-select" onchange="filterList()">
                        <option value="">All Roles</option>
                        @foreach($employees->pluck('position')->filter()->unique()->sort() as $pos)
                            <option value="{{ strtolower($pos) }}" {{ request('position') == $pos ? 'selected' : '' }}>{{ $pos }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="employee-list" id="employeeList">
                    @forelse($employees as $emp)
                        <div class="employee-item {{ request('employee_id') == $emp->id ? 'active' : '' }}"
                             data-name="{{ strtolower($emp->first_name . ' ' . $emp->last_name) }}"
                             data-role="{{ strtolower($emp->position ?? '') }}"
                             onclick="selectEmployee({{ $emp->id }})">
                            <div class="emp-avatar">{{ strtoupper(substr($emp->first_name, 0, 1)) }}</div>
                            <div class="emp-info">
                                <div class="emp-name">{{ $emp->first_name }} {{ $emp->last_name }}</div>
                                <div class="emp-role">{{ $emp->position ?? 'No position' }}</div>
                            </div>
                        </div>
                    @empty
                        <p style="text-align:center;color:#94a3b8;font-size:13px;padding:20px;">No employees found.</p>
                    @endforelse
                </div>

                @if($employees->hasPages())
                <div class="pagination-bar">
                    @if($employees->onFirstPage()) <span class="pg-disabled">«</span>
                    @else <a href="{{ $employees->previousPageUrl() . '&' . http_build_query(request()->except('page')) }}">«</a>
                    @endif
                    @php $cur=$employees->currentPage(); $last=$employees->lastPage(); $from=max(1,$cur-2); $to=min($last,$cur+2); @endphp
                    @for($p=$from;$p<=$to;$p++)
                        @if($p==$cur) <span class="pg-active">{{ $p }}</span>
                        @else <a href="{{ $employees->url($p) . '&' . http_build_query(request()->except('page')) }}">{{ $p }}</a>
                        @endif
                    @endfor
                    @if($employees->hasMorePages()) <a href="{{ $employees->nextPageUrl() . '&' . http_build_query(request()->except('page')) }}">»</a>
                    @else <span class="pg-disabled">»</span>
                    @endif
                </div>
                @endif

            </div>
        </div>

        <!-- ══ RIGHT PANEL ══ -->
        <div class="right-panel">

            <!-- Date period strip -->
            <div class="date-strip" style="background:#fff;border-radius:10px;padding:14px 18px;box-shadow:0 2px 8px rgba(0,0,0,.06);">
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">📅 Payroll Period</div>
                <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                    <input type="date" id="filterStart" value="{{ request('start_date', now()->startOfMonth()->toDateString()) }}">
                    <input type="date" id="filterEnd"   value="{{ request('end_date', now()->startOfMonth()->addDays(14)->toDateString()) }}">
                    <button type="button" class="btn-apply" onclick="applyDateFilter()">Apply</button>
                    <button type="button" class="btn-preset" onclick="setPreset('first')">1st–15th</button>
                    <button type="button" class="btn-preset" onclick="setPreset('second')">16th–End</button>
                    <span style="margin-left:auto;font-size:12px;color:#6366f1;font-weight:700;white-space:nowrap;">
                        {{ \Carbon\Carbon::parse(request('start_date', now()->startOfMonth()->toDateString()))->format('M d') }}
                        —
                        {{ \Carbon\Carbon::parse(request('end_date', now()->startOfMonth()->addDays(14)->toDateString()))->format('M d, Y') }}
                    </span>
                </div>
            </div>

            @if($payslipData)

            @php
                $hasRestDay   = isset($payslipData['rest_day_pay'])    && $payslipData['rest_day_pay']    !== '0.00';
                $hasRestDayOT = isset($payslipData['rest_day_ot_pay']) && $payslipData['rest_day_ot_pay'] !== '0.00';
                $hasRegHol    = isset($payslipData['reg_holiday_pay']) && $payslipData['reg_holiday_pay'] !== '0.00';
                $hasSpecHol   = isset($payslipData['spec_holiday_pay'])&& $payslipData['spec_holiday_pay']!== '0.00';
                $regHolDays   = isset($payslipData['reg_holiday_hours'])  && $payslipData['reg_holiday_hours']  > 0 ? round($payslipData['reg_holiday_hours']  / 8, 2) : 0;
                $specHolDays  = isset($payslipData['spec_holiday_hours']) && $payslipData['spec_holiday_hours'] > 0 ? round($payslipData['spec_holiday_hours'] / 8, 2) : 0;
            @endphp

                <div class="export-bar">
                    <a href="{{ route('finance.payslip.export', [
                        'employee_id' => request('employee_id'),
                        'start_date'  => request('start_date', now()->startOfMonth()->toDateString()),
                        'end_date'    => request('end_date',   now()->startOfMonth()->addDays(14)->toDateString()),
                    ]) }}" class="btn-export" target="_blank">📄 Export PDF</a>
                </div>

                <div class="payslip-card">

                    <!-- Header -->
                    <div class="slip-header">
                        <div class="slip-name">{{ strtoupper($payslipData['employee_name']) }}</div>
                        <div class="slip-role">{{ strtoupper($payslipData['position']) }} &nbsp;|&nbsp; #{{ $payslipData['employee_no'] }}</div>
                        <div class="slip-badges">
                            <span class="slip-badge badge-green">DAYS WORKED: {{ $payslipData['days_worked'] }} DAY/S</span>
                            @if($payslipData['total_ot'] !== '0.00')
                                <span class="slip-badge badge-red">OT: {{ $payslipData['total_ot'] }} HRS</span>
                            @endif
                            @if($hasRestDay)
                                <span class="slip-badge badge-orange">REST DAYS: {{ $payslipData['rest_days_worked'] }} DAY/S</span>
                            @endif
                            @if($hasRegHol)
                                <span class="slip-badge badge-purple">REG. HOLIDAY: {{ $regHolDays > 0 ? $regHolDays . ' DAY/S' : 'YES' }}</span>
                            @endif
                            @if($hasSpecHol)
                                <span class="slip-badge badge-blue">SPECIAL HOLIDAY: {{ $specHolDays > 0 ? $specHolDays . ' DAY/S' : 'YES' }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Period -->
                    <div class="slip-period">
                        <div>PERIOD: <strong>{{ date('M d, Y', strtotime($payslipData['start_date'])) }} — {{ date('M d, Y', strtotime($payslipData['end_date'])) }}</strong></div>
                        <div>MODE: <strong>CASH / CHEQUE / BANK DEPOSIT</strong></div>
                    </div>

                    <!-- Table -->
                    <table class="slip-table">
                        <thead>
                            <tr>
                                <th class="td-label">ITEM</th>
                                <th class="td-value">AMOUNT</th>
                                <th class="td-label2">ITEM</th>
                                <th class="td-value2">AMOUNT</th>
                            </tr>
                        </thead>
                        <tbody>

                            <tr>
                                <td class="td-label">ALLOWANCE PER DAY</td>
                                <td class="td-value">₱ {{ $payslipData['allowance_per_day'] }}</td>
                                <td class="td-label2">Other Additional Payments</td>
                                <td class="td-value2">—</td>
                            </tr>
                            <tr>
                                <td class="td-label">TOTAL ALLOWANCE</td>
                                <td class="td-value">₱ {{ $payslipData['total_allowance'] }}</td>
                                <td class="td-label2 td-sm">(Breakdown shown below)</td>
                                <td class="td-value2"></td>
                            </tr>

                            <tr>
                                <td class="td-label">BASIC RATE PER DAY</td>
                                <td class="td-value">₱ {{ $payslipData['daily_rate'] }}</td>
                                <td class="td-label2">NSD RENDERED HOURS</td>
                                <td class="td-value2">{{ $payslipData['total_nsd'] != '0.00' ? $payslipData['total_nsd'] . ' hrs' : '—' }}</td>
                            </tr>
                            <tr>
                                <td class="td-label">TOTAL BASIC PAY</td>
                                <td class="td-value">₱ {{ $payslipData['basic_pay'] }}</td>
                                <td class="td-label2">NSD PAY</td>
                                <td class="td-value2">{{ $payslipData['nsd_pay'] != '0.00' ? '₱ ' . $payslipData['nsd_pay'] : '—' }}</td>
                            </tr>

                            <tr>
                                <td class="td-label">OVERTIME RATE PER HOUR</td>
                                <td class="td-value">₱ {{ $payslipData['overtime_rate_per_hour'] }}</td>
                                <td class="td-label2">REST DAY RATE PER HOUR <span style="font-size:10px;color:#94a3b8;font-weight:400;">(169%)</span></td>
                                <td class="td-value2">{{ $hasRestDay ? '₱ ' . $payslipData['rest_day_rate_per_hour'] : '—' }}</td>
                            </tr>
                            <tr>
                                <td class="td-label">TOTAL OVERTIME PAY</td>
                                <td class="td-value">₱ {{ $payslipData['total_overtime_pay'] }}</td>
                                <td class="td-label2">REST DAY OT RATE PER HOUR</td>
                                <td class="td-value2">{{ $hasRestDayOT ? '₱ ' . $payslipData['rest_day_ot_rate'] : '—' }}</td>
                            </tr>

                            {{-- Rest Day rows --}}
                            <tr class="{{ $hasRestDay ? 'row-restday' : '' }}">
                                <td class="td-label">REST DAY HOURS RENDERED</td>
                                <td class="td-value">{{ $hasRestDay ? ($payslipData['rest_day_hours'] . ' hrs') : '—' }}</td>
                                <td class="td-label2">REST DAY PAY</td>
                                <td class="td-value2">{{ $hasRestDay ? '₱ ' . $payslipData['rest_day_pay'] : '—' }}</td>
                            </tr>
                            <tr class="{{ $hasRestDayOT ? 'row-restday' : '' }}">
                                <td class="td-label">REST DAY OT HOURS</td>
                                <td class="td-value">{{ $hasRestDayOT ? ($payslipData['rest_day_ot_hours'] . ' hrs') : '—' }}</td>
                                <td class="td-label2">REST DAY OT PAY</td>
                                <td class="td-value2">{{ $hasRestDayOT ? '₱ ' . $payslipData['rest_day_ot_pay'] : '—' }}</td>
                            </tr>

                            {{-- Holiday row --}}
                            <tr class="{{ ($hasRegHol || $hasSpecHol) ? 'row-holiday' : '' }}">
                                <td class="td-label">REGULAR HOLIDAY PAY (200%)</td>
                                <td class="td-value">{{ $hasRegHol ? '₱ ' . $payslipData['reg_holiday_pay'] : '—' }}</td>
                                <td class="td-label2">SPECIAL HOLIDAY PAY (130%)</td>
                                <td class="td-value2">{{ $hasSpecHol ? '₱ ' . $payslipData['spec_holiday_pay'] : '—' }}</td>
                            </tr>

                            {{-- Deductions header --}}
                            <tr class="row-section-yellow">
                                <td colspan="2">TOTAL DEDUCTIONS</td>
                                <td colspan="2" style="background:#fee2e2;color:#991b1b;">EMPLOYEES STATUTORIES</td>
                            </tr>

                            {{-- SSS | Net Pay --}}
                            <tr>
                                <td class="td-label">SSS</td>
                                <td class="td-value">{{ $payslipData['sss'] != '0.00' ? '₱ ' . $payslipData['sss'] : '—' }}</td>
                                <td class="td-label2">NET PAY (GRAND TOTAL)</td>
                                <td class="td-value2" style="font-weight:700;color:#166534;">₱ {{ $payslipData['grand_total'] }}</td>
                            </tr>

                            {{-- PhilHealth | Employer CRF --}}
                            <tr>
                                <td class="td-label">Phil-Health</td>
                                <td class="td-value">{{ $payslipData['philhealth'] != '0.00' ? '₱ ' . $payslipData['philhealth'] : '—' }}</td>
                                <td colspan="2" style="padding:0;border-left:1px solid #f0f0f0;">
                                    <span class="highlight-green">EMPLOYER'S CRF CONTRIBUTION</span>
                                </td>
                            </tr>

                            {{-- Pag-IBIG | Signature --}}
                            <tr>
                                <td class="td-label">Pag-IBIG</td>
                                <td class="td-value">{{ $payslipData['pagibig'] != '0.00' ? '₱ ' . $payslipData['pagibig'] : '—' }}</td>
                                <td class="sig-cell" colspan="2" rowspan="2">
                                    <strong>{{ strtoupper($payslipData['employee_name']) }}</strong>
                                    <small>SIGNATURE OVER PRINTED NAME<br>(EMPLOYEE'S ACKNOWLEDGMENT)</small>
                                </td>
                            </tr>

                            {{-- Cash Advance --}}
                            <tr>
                                <td class="td-label">Cash Advance</td>
                                <td class="td-value">{{ $payslipData['cash_advance'] != '0.00' ? '₱ ' . $payslipData['cash_advance'] : '—' }}</td>
                            </tr>

                            {{-- Gross Pay --}}
                            <tr class="row-gross">
                                <td colspan="2">GROSS PAY &nbsp;&nbsp; ₱ {{ $payslipData['gross_pay'] }}</td>
                                <td colspan="2" style="border-left:1px solid #bbf7d0;border-top:2px solid #22c55e;"></td>
                            </tr>

                        </tbody>
                    </table>
                </div>

            @else
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <p>Select an employee from the left to view their payslip</p>
                </div>
            @endif

        </div>
    </div>

    <form id="mainForm" method="GET" action="{{ route('finance.payslip') }}">
        <input type="hidden" name="employee_id" id="hiddenEmployeeId" value="{{ request('employee_id') }}">
        <input type="hidden" name="start_date"  id="hiddenStart"      value="{{ request('start_date', now()->startOfMonth()->toDateString()) }}">
        <input type="hidden" name="end_date"    id="hiddenEnd"        value="{{ request('end_date', now()->startOfMonth()->addDays(14)->toDateString()) }}">
    </form>

    <script>
        function selectEmployee(id) {
            document.getElementById('hiddenEmployeeId').value = id;
            syncAndSubmit();
        }
        function syncAndSubmit() {
            document.getElementById('hiddenStart').value = document.getElementById('filterStart').value;
            document.getElementById('hiddenEnd').value   = document.getElementById('filterEnd').value;
            document.getElementById('mainForm').submit();
        }
        function applyDateFilter() {
            const existingId = "{{ request('employee_id') }}";
            if (existingId) document.getElementById('hiddenEmployeeId').value = existingId;
            syncAndSubmit();
        }
        function setPreset(half) {
            const now   = new Date();
            const year  = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            if (half === 'first') {
                document.getElementById('filterStart').value = `${year}-${month}-01`;
                document.getElementById('filterEnd').value   = `${year}-${month}-15`;
            } else {
                const lastDay = new Date(year, now.getMonth() + 1, 0).getDate();
                document.getElementById('filterStart').value = `${year}-${month}-16`;
                document.getElementById('filterEnd').value   = `${year}-${month}-${lastDay}`;
            }
        }
        function filterList() {
            const search = document.getElementById('employeeSearch').value.toLowerCase();
            const role   = document.getElementById('roleFilter').value.toLowerCase();
            document.querySelectorAll('#employeeList .employee-item').forEach(item => {
                const name    = item.dataset.name ?? '';
                const empRole = item.dataset.role ?? '';
                item.style.display = (name.includes(search) && (role === '' || empRole === role)) ? '' : 'none';
            });
        }
        document.getElementById('employeeSearch').addEventListener('input', filterList);
        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) document.getElementById('logoutForm').submit();
        }
    </script>

@else
    <script>window.location.href = "{{ route('login') }}";</script>
@endif
</body>
</html>