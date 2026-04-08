<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslips</title>
    <link rel="stylesheet" href="{{ asset('css/partials/sidenav.css') }}">
    <link rel="stylesheet" href="{{ asset('css/superadmin/payslip.css') }}">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; display: flex; min-height: 100vh; }
        .main-content { margin-left: 200px; flex: 1; display: grid; grid-template-columns: 340px 1fr; grid-template-rows: auto 1fr; gap: 0; min-height: 100vh; }
        .page-title { grid-column: 1 / -1; padding: 24px 28px 16px; font-size: 22px; font-weight: 700; color: #1e293b; border-bottom: 1px solid #e2e8f0; background: #fff; }

        .left-panel { background: #fff; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; overflow: hidden; }
        .left-panel-inner { flex: 1; display: flex; flex-direction: column; padding: 18px 16px; overflow: hidden; }
        .search-row { display: flex; gap: 8px; margin-bottom: 14px; }
        .search-box { flex: 1; padding: 9px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 13px; background: #f8fafc; color: #1e293b; outline: none; transition: border-color .2s; }
        .search-box:focus { border-color: #6366f1; background: #fff; }

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

        .payslip-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.07); }
        .slip-header { background: linear-gradient(135deg, #6366f1 0%, #7c3aed 100%); padding: 20px 24px; color: #fff; }
        .slip-name { font-size: 20px; font-weight: 700; letter-spacing: .02em; }
        .slip-role { font-size: 13px; opacity: .8; margin-top: 3px; }
        .slip-badges { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 14px; }
        .slip-badge { padding: 5px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; letter-spacing: .03em; }
        .badge-green  { background: rgba(16,185,129,.3);  border: 1px solid rgba(16,185,129,.6); }
        .badge-red    { background: rgba(244,63,94,.3);   border: 1px solid rgba(244,63,94,.6); }
        .badge-orange { background: rgba(251,146,60,.3);  border: 1px solid rgba(251,146,60,.6); }
        .badge-purple { background: rgba(168,85,247,.3);  border: 1px solid rgba(168,85,247,.6); }
        .badge-blue   { background: rgba(59,130,246,.3);  border: 1px solid rgba(59,130,246,.6); }

        .slip-period { background: #f8fafc; padding: 8px 24px; border-bottom: 1px solid #f1f5f9; font-size: 12px; color: #64748b; display: flex; gap: 24px; }
        .slip-period strong { color: #374151; }

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
        .row-gross td { background: #f0fdf4; font-weight: 700; font-size: 15px; color: #16a34a; padding: 12px 16px; border-top: 2px solid #22c55e; }
        .sig-cell { text-align: center; border-left: 1px solid #f0f0f0; }
        .sig-cell strong { display: block; font-size: 13px; color: #1e293b; margin-bottom: 4px; }
        .sig-cell small  { font-size: 10px; color: #94a3b8; line-height: 1.5; }
        .highlight-green { background: #dcfce7; color: #166534; font-weight: 700; padding: 6px 10px; display: block; }
        .empty-state { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 60px 20px; color: #94a3b8; }
        .empty-icon  { font-size: 52px; margin-bottom: 16px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .right-loading { display:none; flex-direction:column; align-items:center; justify-content:center; gap:12px; padding:60px 20px; color:#94a3b8; }
        .right-loading .spinner { width:32px; height:32px; border:3px solid #e2e8f0; border-top-color:#6366f1; border-radius:50%; animation:spin .7s linear infinite; }
    </style>
</head>
<body>
@if(Session::has('user_id') && Session::get('role_id') == 2)

    @include('admin.partials.sidenav')

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

    </script>

@else
    <script>window.location.href = "{{ route('login') }}";</script>
@endif
</body>
</html>