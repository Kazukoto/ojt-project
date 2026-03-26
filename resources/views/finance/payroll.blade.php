<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll</title>
    <link rel="stylesheet" href="{{ asset('css/partials/sidenav.css') }}">
    <link rel="stylesheet" href="{{ asset('css/superadmin/payroll.css') }}">
</head>
<body>

@if(Session::has('user_id') && Session::get('role_id') == 4)

    @include('finance.partials.sidenav')

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

        {{-- FILTER FORM --}}
        <form method="GET" action="{{ route('finance.payroll') }}" class="controls-bar">

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

            {{-- ── Export PDF Button — opens export filter modal ── --}}
            <button type="button" class="export-btn" onclick="openExportModal()">
                📄 Export PDF
            </button>

        </form>

        {{-- TABLE --}}
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Sub-Total Allowance</th>
                        <th>Sub-Total Basic Pay</th>
                        <th>Total OT</th>
                        <th>Grand Total</th>
                        <th>Cash Advance</th>
                        <th>Gross Pay</th>
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
                        '{{ $emp->grand_total     ?? "—" }}',
                        '{{ $emp->special_holiday ?? "—" }}',
                        '{{ $emp->philhealth      ?? "—" }}',
                        '{{ $emp->sss             ?? "—" }}',
                        '{{ $emp->cash_advance    ?? "—" }}',
                        '{{ $emp->pagibig         ?? "—" }}',
                        '{{ $emp->gross_pay       ?? "—" }}'
                    )">
                        <td>{{ $emp->last_name }}, {{ $emp->first_name }}</td>
                        <td>{{ $emp->allowance_total ?? '—' }}</td>
                        <td>{{ $emp->basic_total     ?? '—' }}</td>
                        <td>{{ $emp->ot_total        ?? '—' }}</td>
                        <td>{{ $emp->grand_total     ?? '—' }}</td>
                        <td>{{ $emp->cash_advance    ?? '—' }}</td>
                        <td>{{ $emp->gross_pay       ?? '—' }}</td>
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
                <tr><td>OT Rate 25%</td>          <td id="ot25"></td></tr>
                <tr><td>OT Regular</td>           <td id="otRegular"></td></tr>
                <tr><td>Rest Day</td>             <td id="restDay"></td></tr>
                <tr><td>NSD 110%</td>             <td id="nsd110"></td></tr>
                <tr><td>Regular Holiday</td>      <td id="regHoliday"></td></tr>
                <tr><td>Rest Day OT</td>          <td id="restOt"></td></tr>
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
                <tr><td>Grand Total</td> <td id="grandTotal"></td></tr>
                <tr><td>Gross Pay</td>   <td id="grossPay" class="gross-pay-value"></td></tr>
            </table>
            <div style="margin-top:20px; text-align:right;">
                <button class="close-btn" onclick="closeEmployeeModal()">✕ Close</button>
            </div>
        </div>
    </div>

    <script>
        function openEmployeeModal(
            name, position, basicRate, days, allowanceTotal, basicTotal,
            nsdTotal, otTotal, ot25, otRegular, restDay, nsd110,
            regHoliday, restOt, grandTotal, specialHoliday,
            philhealth, sss, cashAdvance, pagibig, grossPay
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
            document.getElementById('grandTotal').textContent     = grandTotal;
            document.getElementById('grossPay').textContent       = grossPay;
            document.getElementById('employeeModal').style.display = 'flex';
        }

        function closeEmployeeModal() {
            document.getElementById('employeeModal').style.display = 'none';
        }

        document.getElementById('employeeModal').addEventListener('click', function(e) {
            if (e.target === this) closeEmployeeModal();
        });

        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                document.getElementById('logoutForm').submit();
            }
        }
    </script>

@else
    <script>window.location.href = "{{ route('login') }}";</script>
@endif


    {{-- ══ EXPORT FILTER MODAL ══════════════════════════════════ --}}
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
            // Pre-fill with whatever is currently filtered on the table
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

            if (!start || !end) {
                alert('Please select a date range.');
                return;
            }
            if (new Date(start) > new Date(end)) {
                alert('Start date cannot be after end date.');
                return;
            }

            const params = new URLSearchParams({ start_date: start, end_date: end });
            if (position) params.append('position', position);

            const url = '{{ route("finance.payroll_pdf") }}?' + params.toString();
            window.open(url, '_blank');
            closeExportModal();
        }

        // Close on outside click
        document.getElementById('exportModal').addEventListener('click', function(e) {
            if (e.target === this) closeExportModal();
        });
    </script>

</body>
</html>