<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Statutory</title>
    <link rel="stylesheet" href="{{ asset('css/superadmin/attendance.css') }}">
    <link rel="stylesheet" href="{{ asset('css/partials/sidenav.css') }}">
    <style>
        .alert           { padding:14px 20px; border-radius:8px; margin-bottom:20px; font-size:14px; }
        .alert-success   { background:#d1fae5; border-left:4px solid #10b981; color:#065f46; }
        .alert-error     { background:#fee2e2; border-left:4px solid #ef4444; color:#7f1d1d; }

        #bulkSaveBar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            margin-bottom: 12px;
        }
        .btn-bulk-save {
            padding: 9px 22px;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: opacity .2s;
        }
        .btn-bulk-save:hover    { opacity: .88; }
        .btn-bulk-save:disabled { opacity: .5; cursor: not-allowed; }

        #saveProgressWrap {
            display: none;
            align-items: center;
            gap: 10px;
            flex: 1;
        }
        #saveProgressTrack {
            flex: 1;
            background: #e2e8f0;
            border-radius: 6px;
            height: 8px;
            overflow: hidden;
        }
        #saveProgressFill {
            height: 100%;
            background: linear-gradient(90deg, #6366f1, #4f46e5);
            width: 0%;
            transition: width .3s;
        }
        #saveProgressText { font-size:12px; color:#64748b; white-space:nowrap; }

        #bulkResult {
            display: none;
            font-size: 13px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 6px;
        }
        #bulkResult.ok  { background:#d1fae5; color:#065f46; }
        #bulkResult.err { background:#fee2e2; color:#991b1b; }

        tr.row-saved td { animation: savedFlash .6s ease forwards; }
        @keyframes savedFlash {
            0%   { background:#d1fae5; }
            100% { background:transparent; }
        }

        .statutory-input {
            width: 110px;
            padding: 6px 8px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 13px;
            text-align: right;
        }
        .statutory-input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 2px #e0e7ff; }

        .badge-saved {
            font-size: 10px;
            color: #10b981;
            margin-top: 3px;
            display: flex;
            align-items: center;
            gap: 3px;
            white-space: nowrap;
        }
        .badge-unsaved {
            font-size: 10px;
            color: #d1d5db;
            font-style: italic;
            margin-top: 3px;
        }

        .total-cell {
            font-weight: 700;
            font-size: 13px;
            color: #4f46e5;
        }

        .pagination { margin-top:10px; padding:20px; display:flex; justify-content:center; gap:5px; flex-wrap:wrap; }
        .pagination a, .pagination span { padding:8px 12px; border:1px solid #ccc; text-decoration:none; color:#333; font-size:14px; border-radius:4px; transition:all .2s; }
        .pagination a:hover { background:linear-gradient(135deg,#667eea,#764ba2); color:white; border-color:#667eea; }
        .pagination .active { background:linear-gradient(135deg,#667eea,#764ba2); color:white; font-weight:bold; border-color:#667eea; }
        .pagination .disabled { color:#999; pointer-events:none; background:#f5f5f5; cursor:not-allowed; }
    </style>
</head>
<body>
@if(Session::has('user_id') && Session::get('role_id') == 1)

    @include('superadmin.partials.sidenav')

    <div class="main-content">

        <div class="header">
            <h1 class="page-title"><span class="icon">🏛️</span> Statutory Contributions</h1>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
    <!-- Search & Filter -->
    <form action="{{ route('superadmin.statutory') }}" method="GET" style="display:flex;align-items:center;gap:8px;flex:1;flex-wrap:wrap;">
        <input type="text" name="search" class="search-box" placeholder="🔍 Search by name..." value="{{ request('search') }}" style="flex:1;min-width:160px;">
        <select name="position" class="filter-box">
            <option value="">All Positions</option>
            @foreach($positions as $pos)
                <option value="{{ $pos }}" {{ request('position') == $pos ? 'selected' : '' }}>{{ $pos }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-filter">Filter</button>
    </form>

    <!-- Progress + Result + Save Button -->
    <div id="saveProgressWrap">
        <div id="saveProgressTrack"><div id="saveProgressFill"></div></div>
        <span id="saveProgressText">0 / 0</span>
    </div>
    <div id="bulkResult"></div>
    <button type="button" class="btn-bulk-save" id="btnBulkSave" onclick="saveAllStatutory()">
        💾 Save All Statutory
    </button>
</div>

        <div class="table-wrapper">
            <table class="data-table" id="statutoryTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Position</th>
                        <th>SSS Amount (₱)</th>
                        <th>PhilHealth Amount (₱)</th>
                        <th>Pagibig Amount (₱)</th>
                        <th>Total Deduction (₱)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                    <tr data-employee-id="{{ $employee->id }}">
                        <td>
                            <div class="name-cell">
                                <div class="avatar">{{ strtoupper(substr($employee->first_name, 0, 1)) }}</div>
                                <div>
                                    <span>{{ $employee->first_name }} {{ $employee->last_name }}</span>
                                    @if($employee->sss_amount > 0 || $employee->philhealth_amount > 0 || $employee->pagibig_amount > 0)
                                        <div class="badge-saved">✔ values set</div>
                                    @else
                                        <div class="badge-unsaved">— not yet set</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $employee->position }}</td>

                        <!-- SSS -->
                        <td>
                            <input type="number" step="0.01" min="0"
                                   class="statutory-input stat-sss"
                                   data-employee="{{ $employee->id }}"
                                   value="{{ number_format($employee->sss_amount, 2, '.', '') }}"
                                   placeholder="0.00"
                                   oninput="updateTotal({{ $employee->id }})">
                        </td>

                        <!-- PhilHealth -->
                        <td>
                            <input type="number" step="0.01" min="0"
                                   class="statutory-input stat-philhealth"
                                   data-employee="{{ $employee->id }}"
                                   value="{{ number_format($employee->philhealth_amount, 2, '.', '') }}"
                                   placeholder="0.00"
                                   oninput="updateTotal({{ $employee->id }})">
                        </td>

                        <!-- Pagibig -->
                        <td>
                            <input type="number" step="0.01" min="0"
                                   class="statutory-input stat-pagibig"
                                   data-employee="{{ $employee->id }}"
                                   value="{{ number_format($employee->pagibig_amount, 2, '.', '') }}"
                                   placeholder="0.00"
                                   oninput="updateTotal({{ $employee->id }})">
                        </td>

                        <!-- Total -->
                        <td>
                            <span class="total-cell" id="total-{{ $employee->id }}">
                                ₱{{ number_format($employee->sss_amount + $employee->philhealth_amount + $employee->pagibig_amount, 2) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="no-data">No employees found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($employees->hasPages())
            <div class="pagination">
                @if ($employees->onFirstPage())
                    <span class="disabled">««</span><span class="disabled">«</span>
                @else
                    <a href="{{ $employees->appends(['search'=>request('search'),'position'=>request('position')])->url(1) }}">««</a>
                    <a href="{{ $employees->appends(['search'=>request('search'),'position'=>request('position')])->previousPageUrl() }}">«</a>
                @endif
                @php $currentPage=$employees->currentPage(); $lastPage=$employees->lastPage(); $start=max(1,$currentPage-2); $end=min($lastPage,$currentPage+2); @endphp
                @for($page=$start;$page<=$end;$page++)
                    @if($page==$currentPage)<span class="active">{{ $page }}</span>
                    @else<a href="{{ $employees->appends(['search'=>request('search'),'position'=>request('position')])->url($page) }}">{{ $page }}</a>@endif
                @endfor
                @if($end<$lastPage)<span class="disabled">...</span>@endif
                @if($employees->hasMorePages())
                    <a href="{{ $employees->appends(['search'=>request('search'),'position'=>request('position')])->nextPageUrl() }}">»</a>
                    <a href="{{ $employees->appends(['search'=>request('search'),'position'=>request('position')])->url($employees->lastPage()) }}">»»</a>
                @else
                    <span class="disabled">»</span><span class="disabled">»»</span>
                @endif
            </div>
        @endif

    </div>

<script>
    const CSRF        = document.querySelector('meta[name="csrf-token"]').content;
    const STORE_URL   = "{{ route('superadmin.statutory.store') }}";

    function updateTotal(empId) {
        const sss        = parseFloat(document.querySelector(`.stat-sss[data-employee="${empId}"]`)?.value)        || 0;
        const philhealth = parseFloat(document.querySelector(`.stat-philhealth[data-employee="${empId}"]`)?.value) || 0;
        const pagibig    = parseFloat(document.querySelector(`.stat-pagibig[data-employee="${empId}"]`)?.value)    || 0;
        const total      = sss + philhealth + pagibig;
        document.getElementById(`total-${empId}`).textContent = '₱' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    async function saveAllStatutory() {
        const rows   = document.querySelectorAll('#statutoryTable tbody tr[data-employee-id]');
        const btn    = document.getElementById('btnBulkSave');
        const prog   = document.getElementById('saveProgressWrap');
        const fill   = document.getElementById('saveProgressFill');
        const text   = document.getElementById('saveProgressText');
        const result = document.getElementById('bulkResult');

        if (!rows.length) { showResult(result, 'No records to save.', false); return; }

        btn.disabled         = true;
        btn.innerHTML        = '⏳ Saving...';
        prog.style.display   = 'flex';
        result.style.display = 'none';

        let done = 0, failed = 0, skipped = 0;

        for (const row of rows) {
            const empId      = row.dataset.employeeId;
            const sss        = parseFloat(row.querySelector(`.stat-sss[data-employee="${empId}"]`)?.value)        || 0;
            const philhealth = parseFloat(row.querySelector(`.stat-philhealth[data-employee="${empId}"]`)?.value) || 0;
            const pagibig    = parseFloat(row.querySelector(`.stat-pagibig[data-employee="${empId}"]`)?.value)    || 0;

            // Skip rows where all values are zero
            if (sss === 0 && philhealth === 0 && pagibig === 0) {
                skipped++;
                done++;
                const pct = Math.round((done / rows.length) * 100);
                fill.style.width = pct + '%';
                text.textContent = `${done} / ${rows.length}`;
                continue;
            }

            try {
                const res = await fetch(STORE_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'Accept':       'application/json',
                    },
                    body: JSON.stringify({
                        employee_id:        empId,
                        sss_amount:         sss,
                        philhealth_amount:  philhealth,
                        pagibig_amount:     pagibig,
                    }),
                });

                const contentType = res.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    console.error(`[Row ${empId}] Non-JSON response`);
                    failed++;
                    done++;
                    continue;
                }

                const json = await res.json();

                if (res.ok && json.success) {
                    row.classList.add('row-saved');
                    setTimeout(() => row.classList.remove('row-saved'), 700);
                    // Update the badge to "values set"
                    const badge = row.querySelector('.badge-unsaved');
                    if (badge) {
                        badge.className = 'badge-saved';
                        badge.textContent = '✔ values set';
                    }
                } else {
                    console.warn(`[Row ${empId}] Save failed:`, json);
                    failed++;
                }

            } catch (err) {
                console.error(`[Row ${empId}] Fetch error:`, err);
                failed++;
            }

            done++;
            const pct = Math.round((done / rows.length) * 100);
            fill.style.width = pct + '%';
            text.textContent = `${done} / ${rows.length}`;
        }

        btn.disabled       = false;
        btn.innerHTML      = '💾 Save All Statutory';
        prog.style.display = 'none';

        const saved = done - failed - skipped;
        if (failed === 0) {
            showResult(result, `✅ ${saved} saved, ${skipped} skipped (all zero).`, true);
        } else {
            showResult(result, `⚠️ ${saved} saved, ${failed} failed, ${skipped} skipped.`, false);
        }
    }

    function showResult(el, msg, ok) {
        el.textContent   = msg;
        el.className     = ok ? 'ok' : 'err';
        el.style.display = 'block';
        setTimeout(() => { el.style.display = 'none'; }, 4000);
    }

    function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                document.getElementById('logoutForm').submit();
            }
        }
</script>

@else
    <script>window.location.href = "{{ route('login') }}";</script>
@endif
</body>
</html>