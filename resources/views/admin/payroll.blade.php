<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll</title>
    <link rel="stylesheet" href="{{ asset('css/partials/sidenav.css') }}">
    <link rel="stylesheet" href="{{ asset('css/superadmin/payroll.css') }}">
    <style>
        /* ── Sortable column headers ── */
        thead th.sortable {
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
        }
        thead th.sortable:hover { background: #5a54d4; }
        thead th .sort-icon {
            display: inline-block;
            margin-left: 5px;
            font-size: 11px;
            opacity: 0.5;
            vertical-align: middle;
        }
        thead th.sort-asc .sort-icon,
        thead th.sort-desc .sort-icon { opacity: 1; }
        thead th.sort-asc .sort-icon::after  { content: ' ▲'; }
        thead th.sort-desc .sort-icon::after { content: ' ▼'; }
        thead th:not(.sort-asc):not(.sort-desc) .sort-icon::after { content: ' ⇅'; }

        /* ── Stats cards ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
            border-left: 4px solid #6366f1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .stat-card.green  { border-left-color: #10b981; }
        .stat-card.blue   { border-left-color: #3b82f6; }
        .stat-card.orange { border-left-color: #f59e0b; }
        .stat-card.red    { border-left-color: #ef4444; }
        .stat-card.purple { border-left-color: #8b5cf6; }
        .stat-card.gray   { border-left-color: #6b7280; }

        .stat-icon  { font-size: 20px; margin-bottom: 2px; }
        .stat-label { font-size: 11px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
        .stat-value { font-size: 20px; font-weight: 800; color: #1f2937; line-height: 1.1; }
        .stat-sub   { font-size: 11px; color: #9ca3af; margin-top: 2px; }
    </style>
</head>
<body>

@if(Session::has('user_id') && Session::get('role_id') == 2)

    @include('admin.partials.sidenav')

    <div class="main-content">

        <div class="page-header">
            <h1>💸 Payroll</h1>
        </div>

        <div class="date-range-bar">
            📅 Payroll Period:
            <span>{{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }}</span>
            —
            <span>{{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</span>
        </div>

        {{-- ── STATS CARDS ── --}}
        <div class="stats-grid">
            <div class="stat-card green">
                <div class="stat-icon">💰</div>
                <div class="stat-label">Total Gross Pay</div>
                <div class="stat-value">₱{{ number_format($stats['total_gross'], 2) }}</div>
                <div class="stat-sub">All earnings before deductions</div>
            </div>
            <div class="stat-card blue">
                <div class="stat-icon">🧾</div>
                <div class="stat-label">Total Basic Pay</div>
                <div class="stat-value">₱{{ number_format($stats['total_basic'], 2) }}</div>
                <div class="stat-sub">Regular hours pay</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon">⏰</div>
                <div class="stat-label">Total OT Pay</div>
                <div class="stat-value">₱{{ number_format($stats['total_ot'], 2) }}</div>
                <div class="stat-sub">Overtime at 1.25×</div>
            </div>
            <div class="stat-card purple">
                <div class="stat-icon">🎒</div>
                <div class="stat-label">Total Allowance</div>
                <div class="stat-value">₱{{ number_format($stats['total_allowance'], 2) }}</div>
                <div class="stat-sub">All position allowances</div>
            </div>
            <div class="stat-card red">
                <div class="stat-icon">📉</div>
                <div class="stat-label">Total Deductions</div>
                <div class="stat-value">₱{{ number_format($stats['total_deductions'], 2) }}</div>
                <div class="stat-sub">SSS, PhilHealth, Pag-IBIG, CA</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-label">Total Net Pay</div>
                <div class="stat-value">₱{{ number_format($stats['total_net'], 2) }}</div>
                <div class="stat-sub">{{ $stats['employee_count'] }} employee{{ $stats['employee_count'] != 1 ? 's' : '' }}</div>
            </div>
        </div>

        {{-- FILTER FORM --}}
        <form method="GET" action="{{ route('admin.payroll') }}" class="controls-bar" id="filterForm">

            <input type="hidden" name="sort_by"  id="sortByField"  value="{{ request('sort_by') }}">
            <input type="hidden" name="sort_dir" id="sortDirField" value="{{ request('sort_dir', 'asc') }}">

            <input type="text"
                   name="search"
                   class="search-input"
                   placeholder="🔍 Search by Name"
                   value="{{ request('search') }}">

            <select name="position" class="filter-select" onchange="this.form.submit()">
                <option value="">Filter by Position</option>
                @foreach($positions as $position)
                    <option value="{{ $position }}" {{ request('position') == $position ? 'selected' : '' }}>
                        {{ $position }}
                    </option>
                @endforeach
            </select>

            <span class="date-label">From</span>
            <input type="date" name="start_date" class="date-input" value="{{ $startDate }}">

            <span class="date-label">To</span>
            <input type="date" name="end_date" class="date-input" value="{{ $endDate }}">

            <button type="submit" class="filter-btn">🔍 Filter</button>

            <button type="button" class="export-btn" onclick="openExportModal()">
                📄 Export PDF
            </button>

        </form>

        {{-- TABLE --}}
        <div class="table-wrapper">
            <table id="payrollTable">
                <thead>
                    <tr>
                        <th class="sortable {{ request('sort_by') == 'name' ? (request('sort_dir','asc') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}"
                            data-col="name" onclick="sortBy('name')">
                            Name <span class="sort-icon"></span>
                        </th>
                        <th class="sortable {{ request('sort_by') == 'allowance_total' ? (request('sort_dir','asc') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}"
                            data-col="allowance_total" onclick="sortBy('allowance_total')">
                            Sub-Total Allowance <span class="sort-icon"></span>
                        </th>
                        <th class="sortable {{ request('sort_by') == 'basic_total' ? (request('sort_dir','asc') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}"
                            data-col="basic_total" onclick="sortBy('basic_total')">
                            Sub-Total Basic Pay <span class="sort-icon"></span>
                        </th>
                        <th class="sortable {{ request('sort_by') == 'ot_total' ? (request('sort_dir','asc') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}"
                            data-col="ot_total" onclick="sortBy('ot_total')">
                            Total OT <span class="sort-icon"></span>
                        </th>
                        <th class="sortable {{ request('sort_by') == 'grand_total' ? (request('sort_dir','asc') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}"
                            data-col="grand_total" onclick="sortBy('grand_total')">
                            Grand Total <span class="sort-icon"></span>
                        </th>
                        <th class="sortable {{ request('sort_by') == 'cash_advance' ? (request('sort_dir','asc') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}"
                            data-col="cash_advance" onclick="sortBy('cash_advance')">
                            Cash Advance <span class="sort-icon"></span>
                        </th>
                        <th class="sortable {{ request('sort_by') == 'net_pay' ? (request('sort_dir','asc') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}"
                            data-col="gross_pay" onclick="sortBy('net_pay')">
                            Gross Pay <span class="sort-icon"></span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $emp)
                    <tr onclick="openEmployeeModal(
                        '{{ $emp->first_name }} {{ $emp->last_name }}',
                        '{{ $emp->position }}',
                        '{{ $emp->basic_rate      ?? "—" }}',
                        '{{ $emp->days            ?? "—" }}',
                        '{{ $emp->allowance_total ?? "—" }}',
                        '{{ $emp->basic_total     ?? "—" }}',
                        '{{ $emp->nsd_total       ?? "—" }}',
                        '{{ $emp->ot_total        ?? "—" }}',
                        '{{ $emp->ot_25           ?? "—" }}',
                        '{{ $emp->ot_regular      ?? "—" }}',
                        '{{ $emp->rest_day        ?? "—" }}',
                        '{{ $emp->nsd_110         ?? "—" }}',
                        '{{ $emp->reg_holiday     ?? "—" }}',
                        '{{ $emp->rest_ot         ?? "—" }}',
                        '{{ $emp->gross_pay     ?? "—" }}',
                        '{{ $emp->special_holiday ?? "—" }}',
                        '{{ $emp->philhealth      ?? "—" }}',
                        '{{ $emp->sss             ?? "—" }}',
                        '{{ $emp->cash_advance    ?? "—" }}',
                        '{{ $emp->pagibig         ?? "—" }}',
                        '{{ $emp->net_pay       ?? "—" }}'
                    )">
                        <td>{{ $emp->last_name }}, {{ $emp->first_name }}</td>
                        <td>{{ $emp->allowance_total ?? '—' }}</td>
                        <td>{{ $emp->basic_total     ?? '—' }}</td>
                        <td>{{ $emp->ot_total        ?? '—' }}</td>
                        <td>{{ $emp->gross_pay     ?? '—' }}</td>
                        <td>{{ $emp->cash_advance    ?? '—' }}</td>
                        <td>{{ $emp->net_pay      ?? '—' }}</td>
                    </tr>
                    @empty
                        <tr><td colspan="7" class="no-records">No payroll records found.</td></tr>
                    @endforelse
                </tbody>
            </table>

            @if($employees->hasPages())
                <div class="pagination">
                    @if($employees->onFirstPage())
                        <span class="disabled">««</span>
                        <span class="disabled">«</span>
                    @else
                        <a href="{{ $employees->appends(request()->query())->url(1) }}">««</a>
                        <a href="{{ $employees->appends(request()->query())->previousPageUrl() }}">«</a>
                    @endif

                    @php
                        $currentPage = $employees->currentPage();
                        $lastPage    = $employees->lastPage();
                        $start       = max(1, $currentPage - 2);
                        $end         = min($lastPage, $currentPage + 2);
                    @endphp

                    @for($page = $start; $page <= $end; $page++)
                        @if($page == $currentPage)
                            <span class="active">{{ $page }}</span>
                        @else
                            <a href="{{ $employees->appends(request()->query())->url($page) }}">{{ $page }}</a>
                        @endif
                    @endfor

                    @if($end < $lastPage)
                        <span class="disabled">...</span>
                    @endif

                    @if($employees->hasMorePages())
                        <a href="{{ $employees->appends(request()->query())->nextPageUrl() }}">»</a>
                        <a href="{{ $employees->appends(request()->query())->url($employees->lastPage()) }}">»»</a>
                    @else
                        <span class="disabled">»</span>
                        <span class="disabled">»»</span>
                    @endif
                </div>
            @endif
        </div>

    </div>

    {{-- PAYROLL DETAIL MODAL --}}
    <div id="employeeModal" class="modal-overlay">
        <div class="modal-box">
            <h2>💸 Employee Payroll Details</h2>
            <hr>
            <div class="modal-section-title">Employee Info</div>
            <table>
                <tr><td>Name</td>     <td id="empName"></td></tr>
                <tr><td>Position</td> <td id="empPosition"></td></tr>
            </table>
            <div class="modal-section-title">Pay Breakdown</div>
            <table>
                <tr><td>Basic Rate (Daily)</td>  <td id="basicRate"></td></tr>
                <tr><td>Days Worked</td>          <td id="days"></td></tr>
                <tr><td>Sub-Total Allowance</td>  <td id="allowanceTotal"></td></tr>
                <tr><td>Sub-Total Basic Pay</td>  <td id="basicTotal"></td></tr>
                <tr><td>Total NSD Hours</td>      <td id="nsdTotal"></td></tr>
                <tr><td>Total OT Hours</td>       <td id="otTotal"></td></tr>
                <tr><td>OT Rate /hr</td>          <td id="ot25"></td></tr>
                <tr><td>OT Regular</td>           <td id="otRegular"></td></tr>
                <tr><td>Rest Day</td>             <td id="restDay"></td></tr>
                <tr><td>NSD Pay</td>             <td id="nsd110"></td></tr>
                <tr><td>Regular Holiday(x2.0)</td>      <td id="regHoliday"></td></tr>
                <tr><td>Rest Day OT (x1.69)</td>          <td id="restOt"></td></tr>
                <tr><td>Special Holiday</td>      <td id="specialHoliday"></td></tr>
            </table>
            <div class="modal-section-title">Deductions</div>
            <table>
                <tr><td>PhilHealth</td>    <td id="philhealth"></td></tr>
                <tr><td>SSS</td>           <td id="sss"></td></tr>
                <tr><td>Pag-IBIG</td>      <td id="pagibig"></td></tr>
                <tr><td>Cash Advance</td>  <td id="cashAdvance"></td></tr>
            </table>
            <div class="modal-section-title">Totals</div>
            <table>
                <tr><td>Gross Pay</td> <td id="grossPay"></td></tr>
                <tr><td>Net Pay</td>   <td id="netPay" class="gross-pay-value"></td></tr>
            </table>
            <div style="margin-top:20px; text-align:right;">
                <button class="close-btn" onclick="closeEmployeeModal()">✕ Close</button>
            </div>
        </div>
    </div>

    <script>
        function sortBy(col) {
            const currentCol = document.getElementById('sortByField').value;
            const currentDir = document.getElementById('sortDirField').value;
            const numericCols = ['allowance_total','basic_total','ot_total','grand_total','cash_advance','gross_pay'];
            let newDir;
            if (currentCol === col) {
                newDir = currentDir === 'asc' ? 'desc' : 'asc';
            } else {
                newDir = numericCols.includes(col) ? 'desc' : 'asc';
            }
            document.getElementById('sortByField').value  = col;
            document.getElementById('sortDirField').value = newDir;
            document.getElementById('filterForm').submit();
        }

        function openEmployeeModal(
            name, position, basicRate, days, allowanceTotal, basicTotal,
            nsdTotal, otTotal, ot25, otRegular, restDay, nsd110,
            regHoliday, restOt, grossPay, specialHoliday,
            philhealth, sss, cashAdvance, pagibig, netPay
        ) {
            document.getElementById('empName').textContent        = name;
            document.getElementById('empPosition').textContent    = position;
            document.getElementById('basicRate').textContent      = basicRate;
            document.getElementById('days').textContent           = days;
            document.getElementById('allowanceTotal').textContent = allowanceTotal;
            document.getElementById('basicTotal').textContent     = basicTotal;
            document.getElementById('nsdTotal').textContent       = nsdTotal;
            document.getElementById('otTotal').textContent        = otTotal;
            document.getElementById('ot25').textContent           = ot25;
            document.getElementById('otRegular').textContent      = otRegular;
            document.getElementById('restDay').textContent        = restDay;
            document.getElementById('nsd110').textContent         = nsd110;
            document.getElementById('regHoliday').textContent     = regHoliday;
            document.getElementById('restOt').textContent         = restOt;
            document.getElementById('specialHoliday').textContent = specialHoliday;
            document.getElementById('philhealth').textContent     = philhealth;
            document.getElementById('sss').textContent            = sss;
            document.getElementById('pagibig').textContent        = pagibig;
            document.getElementById('cashAdvance').textContent    = cashAdvance;
            document.getElementById('grossPay').textContent     = grossPay;
            document.getElementById('netPay').textContent         = netPay;
            document.getElementById('employeeModal').style.display = 'flex';
        }

        function closeEmployeeModal() {
            document.getElementById('employeeModal').style.display = 'none';
        }

        document.getElementById('employeeModal').addEventListener('click', function(e) {
            if (e.target === this) closeEmployeeModal();
        });
    </script>

@else
    <script>window.location.href = "{{ route('login') }}";</script>
@endif

    {{-- EXPORT FILTER MODAL --}}
    <div id="exportModal" class="modal-overlay">
        <div class="modal-box">
            <h2>📄 Export Payroll PDF</h2>
            <hr style="margin-bottom:16px;border:none;border-top:1px solid #eee;">
            <p style="font-size:13px;color:#6b7280;margin-bottom:16px;">
                Choose which employees to include in the exported PDF.
            </p>
            <div class="modal-row">
                <span class="modal-label">Position</span>
                <select id="exportPosition" class="modal-select">
                    <option value="">All Positions</option>
                    @foreach($positions as $pos)
                        <option value="{{ $pos }}">{{ $pos }}</option>
                    @endforeach
                </select>
            </div>
            <div class="modal-row">
                <span class="modal-label">From</span>
                <input type="date" id="exportStart" class="modal-input" value="{{ $startDate }}">
            </div>
            <div class="modal-row">
                <span class="modal-label">To</span>
                <input type="date" id="exportEnd" class="modal-input" value="{{ $endDate }}">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel-modal" onclick="closeExportModal()">✕ Cancel</button>
                <button type="button" class="btn-export-modal" onclick="doExport()">📄 Export PDF</button>
            </div>
        </div>
    </div>

    <script>
        function openExportModal() {
            const urlParams = new URLSearchParams(window.location.search);
            const pos = urlParams.get('position') || '';
            if (pos) {
                const sel = document.getElementById('exportPosition');
                for (let i = 0; i < sel.options.length; i++) {
                    if (sel.options[i].value === pos) { sel.selectedIndex = i; break; }
                }
            }
            document.getElementById('exportModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeExportModal() {
            document.getElementById('exportModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function doExport() {
            const position = document.getElementById('exportPosition').value;
            const start    = document.getElementById('exportStart').value;
            const end      = document.getElementById('exportEnd').value;
            if (!start || !end) { alert('Please select a date range.'); return; }
            if (new Date(start) > new Date(end)) { alert('Start date cannot be after end date.'); return; }
            const params = new URLSearchParams({ start_date: start, end_date: end });
            if (position) params.append('position', position);
            window.open('{{ route("superadmin.payroll_pdf") }}?' + params.toString(), '_blank');
            closeExportModal();
        }

        document.getElementById('exportModal').addEventListener('click', function(e) {
            if (e.target === this) closeExportModal();
        });
    </script>

</body>
</html>