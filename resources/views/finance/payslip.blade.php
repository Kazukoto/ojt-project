<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslips</title>
    <link rel="stylesheet" href="{{ asset('css/partials/sidenav.css') }}">
    <link rel="stylesheet" href="{{ asset('css/superadmin/payslip.css') }}">
</head>
<body>
@if(Session::has('user_id') && Session::get('role_id') == 4)

    @include('finance.partials.sidenav')

    <div class="main-content">
        <div class="page-title">💸 Employee Payslips</div>

        <!-- ══ LEFT PANEL ══ -->
        <div class="left-panel">
            <div class="left-panel-inner">

                <div class="search-row">
                    <input type="text" id="employeeSearch" class="search-box"
                           placeholder="🔍 Search employee..."
                           value="{{ request('search') }}"
                           autocomplete="off">
                    <div id="searchSpinner" style="display:none;align-items:center;padding:0 4px;">
                        <div style="width:16px;height:16px;border:2px solid #e2e8f0;border-top-color:#6366f1;border-radius:50%;animation:spin .6s linear infinite;"></div>
                    </div>
                </div>

                <div id="employeeList" class="employee-list">
                    @forelse($employees as $emp)
                        <div class="employee-item {{ request('employee_id') == $emp->id ? 'active' : '' }}"
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

                <div id="paginationBar" class="pagination-bar">
                    @if($employees->hasPages())
                        @if($employees->onFirstPage())
                            <span class="pg-disabled">«</span>
                        @else
                            <a href="javascript:void(0)" onclick="ajaxGoToPage({{ $employees->currentPage() - 1 }})">«</a>
                        @endif
                        @php $cur=$employees->currentPage(); $last=$employees->lastPage(); $from=max(1,$cur-2); $to=min($last,$cur+2); @endphp
                        @for($p=$from; $p<=$to; $p++)
                            @if($p==$cur)
                                <span class="pg-active">{{ $p }}</span>
                            @else
                                <a href="javascript:void(0)" onclick="ajaxGoToPage({{ $p }})">{{ $p }}</a>
                            @endif
                        @endfor
                        @if($employees->hasMorePages())
                            <a href="javascript:void(0)" onclick="ajaxGoToPage({{ $employees->currentPage() + 1 }})">»</a>
                        @else
                            <span class="pg-disabled">»</span>
                        @endif
                    @endif
                </div>

            </div>
        </div>

        <!-- ══ RIGHT PANEL ══ -->
        <div class="right-panel" id="rightPanel">

            {{-- Date strip — always visible, not part of AJAX refresh --}}
            <div class="date-strip" id="dateStrip" style="background:#fff;border-radius:10px;padding:14px 18px;box-shadow:0 2px 8px rgba(0,0,0,.06);">
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">📅 Payroll Period</div>
                <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                    <input type="date" id="filterStart" value="{{ request('start_date', now()->startOfMonth()->toDateString()) }}">
                    <input type="date" id="filterEnd"   value="{{ request('end_date', now()->startOfMonth()->addDays(14)->toDateString()) }}">
                    <button type="button" class="btn-apply" onclick="applyDateFilter()">Apply</button>
                    <button type="button" class="btn-preset" onclick="setPreset('first')">1st–15th</button>
                    <button type="button" class="btn-preset" onclick="setPreset('second')">16th–End</button>
                </div>
            </div>

            {{-- Loading spinner shown during AJAX --}}
            <div class="right-loading" id="rightLoading">
                <div class="spinner"></div>
                <span>Loading payslip...</span>
            </div>

            {{-- Payslip content — replaced by AJAX --}}
            <div id="payslipContent">
                @if($payslipData)
                    @include('admin.partials.payslip_content', ['payslipData' => $payslipData])
                @else
                    <div class="empty-state">
                        <div class="empty-icon">📋</div>
                        <p>Select an employee from the left to view their payslip</p>
                    </div>
                @endif
            </div>

        </div>
    </div>

    <script>
        const PAYSLIP_ROUTE  = "{{ route('admin.payslip') }}";
        let searchTimer      = null;
        let currentPage      = {{ $employees->currentPage() }};
        let currentSearch    = "{{ request('search') }}";
        let activeEmployeeId = "{{ request('employee_id') }}";

        // ── AJAX: load employee list only ──────────────────────
        function ajaxLoadEmployees(search, page) {
            document.getElementById('searchSpinner').style.display = 'flex';

            const params = new URLSearchParams({
                search:      search,
                page:        page,
                ajax_list:   1,
                employee_id: activeEmployeeId,
            });

            fetch(PAYSLIP_ROUTE + '?' + params, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                document.getElementById('searchSpinner').style.display = 'none';
                currentPage   = data.current_page;
                currentSearch = search;

                const list = document.getElementById('employeeList');
                if (!data.employees.length) {
                    list.innerHTML = '<p style="text-align:center;color:#94a3b8;font-size:13px;padding:20px;">No employees found.</p>';
                } else {
                    list.innerHTML = data.employees.map(emp => {
                        const isActive = String(emp.id) === String(activeEmployeeId) ? 'active' : '';
                        return `<div class="employee-item ${isActive}" onclick="selectEmployee(${emp.id})">
                            <div class="emp-avatar">${emp.first_name.charAt(0).toUpperCase()}</div>
                            <div class="emp-info">
                                <div class="emp-name">${emp.first_name} ${emp.last_name}</div>
                                <div class="emp-role">${emp.position || 'No position'}</div>
                            </div>
                        </div>`;
                    }).join('');
                }
                renderPagination(data.current_page, data.last_page);
            })
            .catch(() => { document.getElementById('searchSpinner').style.display = 'none'; });
        }

        // ── AJAX: load payslip content only ───────────────────
        function ajaxLoadPayslip(employeeId) {
            if (!employeeId) return;

            const content = document.getElementById('payslipContent');
            const loading = document.getElementById('rightLoading');

            content.style.display = 'none';
            loading.style.display = 'flex';

            const params = new URLSearchParams({
                employee_id: employeeId,
                start_date:  document.getElementById('filterStart').value,
                end_date:    document.getElementById('filterEnd').value,
                ajax_payslip: 1,
            });

            fetch(PAYSLIP_ROUTE + '?' + params, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => {
                loading.style.display  = 'none';
                content.style.display  = 'block';
                content.innerHTML      = html;
            })
            .catch(() => {
                loading.style.display  = 'none';
                content.style.display  = 'block';
                content.innerHTML      = '<div class="empty-state"><div class="empty-icon">❌</div><p>Failed to load payslip. Please try again.</p></div>';
            });
        }

        // ── Select employee — AJAX right panel only ────────────
        function selectEmployee(id) {
            activeEmployeeId = id;

            // Mark active in list
            document.querySelectorAll('.employee-item').forEach(el => el.classList.remove('active'));
            const rows = document.querySelectorAll('.employee-item');
            rows.forEach(el => {
                if (el.getAttribute('onclick') === `selectEmployee(${id})`) {
                    el.classList.add('active');
                }
            });

            // Load payslip without page reload
            ajaxLoadPayslip(id);
        }

        // ── Apply date filter — reloads payslip if employee selected ──
        function applyDateFilter() {
            if (activeEmployeeId) {
                ajaxLoadPayslip(activeEmployeeId);
            }
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
            // Auto-reload if employee is selected
            if (activeEmployeeId) ajaxLoadPayslip(activeEmployeeId);
        }

        // ── Pagination ─────────────────────────────────────────
        function ajaxGoToPage(page) {
            ajaxLoadEmployees(document.getElementById('employeeSearch').value.trim(), page);
        }

        function renderPagination(cur, last) {
            const bar = document.getElementById('paginationBar');
            if (last <= 1) { bar.innerHTML = ''; return; }
            let html = cur <= 1
                ? '<span class="pg-disabled">«</span>'
                : `<a href="javascript:void(0)" onclick="ajaxGoToPage(${cur - 1})">«</a>`;
            const from = Math.max(1, cur - 2);
            const to   = Math.min(last, cur + 2);
            for (let p = from; p <= to; p++) {
                html += p === cur
                    ? `<span class="pg-active">${p}</span>`
                    : `<a href="javascript:void(0)" onclick="ajaxGoToPage(${p})">${p}</a>`;
            }
            html += cur >= last
                ? '<span class="pg-disabled">»</span>'
                : `<a href="javascript:void(0)" onclick="ajaxGoToPage(${cur + 1})">»</a>`;
            bar.innerHTML = html;
        }

        // ── Live search ────────────────────────────────────────
        document.getElementById('employeeSearch').addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => ajaxLoadEmployees(this.value.trim(), 1), 300);
        });

        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) document.getElementById('logoutForm').submit();
        }
    </script>

@else
    <script>window.location.href = "{{ route('login') }}";</script>
@endif
</body>
</html>