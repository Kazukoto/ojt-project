<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cash Advance & Statutory</title>
    <link rel="stylesheet" href="{{ asset('css/partials/sidenav.css') }}">
    <link rel="stylesheet" href="{{ asset('css/superadmin/cashadvance.css') }}">
    <style>
        /* ── Alerts ── */
        .alert           { padding:14px 20px; border-radius:8px; margin-bottom:20px; font-size:14px; }
        .alert-success   { background:#d1fae5; border-left:4px solid #10b981; color:#065f46; }
        .alert-error     { background:#fee2e2; border-left:4px solid #ef4444; color:#7f1d1d; }
        .alert ul        { margin:0; padding-left:18px; }

        /* ── Tab Bar (same style as Role Management) ── */
        .tab-bar {
            display: flex;
            gap: 0;
            margin-bottom: 24px;
            border-bottom: 2px solid #e2e8f0;
        }
        .tab-btn {
            padding: 10px 28px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            font-size: 15px;
            font-weight: 600;
            color: #94a3b8;
            cursor: pointer;
            transition: color 0.2s, border-color 0.2s;
        }
        .tab-btn.active          { color: #4f46e5; border-bottom-color: #4f46e5; }
        .tab-btn:hover:not(.active) { color: #475569; }

        .tab-panel         { display: none; }
        .tab-panel.active  { display: block; }

        /* ── Cash Advance status badges ── */
        .status-paid     { background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; border-radius:6px; padding:3px 10px; font-size:12px; font-weight:700; }

        /* ── Statutory-specific styles ── */
        .statutory-input {
            width: 110px;
            padding: 6px 8px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 13px;
            text-align: right;
        }
        .statutory-input:focus { outline:none; border-color:#6366f1; box-shadow:0 0 0 2px #e0e7ff; }

        .badge-saved   { font-size:10px; color:#10b981; margin-top:3px; display:flex; align-items:center; gap:3px; white-space:nowrap; }
        .badge-unsaved { font-size:10px; color:#d1d5db; font-style:italic; margin-top:3px; }

        .total-cell { font-weight:700; font-size:13px; color:#4f46e5; }

        /* ── Statutory toolbar ── */
        #bulkSaveBar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .btn-bulk-save {
            padding: 9px 22px;
            background: linear-gradient(135deg,#6366f1,#4f46e5);
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
        .btn-bulk-save:hover    { opacity:.88; }
        .btn-bulk-save:disabled { opacity:.5; cursor:not-allowed; }

        .btn-filter {
    padding: 9px 18px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s;
    white-space: nowrap;
}

.btn-filter:hover {
    opacity: 0.88;
}

.btn-filter:active {
    transform: scale(0.97);
}

        #saveProgressWrap {
            display: none;
            align-items: center;
            gap: 10px;
            flex: 1;
        }
        #saveProgressTrack { flex:1; background:#e2e8f0; border-radius:6px; height:8px; overflow:hidden; }
        #saveProgressFill  { height:100%; background:linear-gradient(90deg,#6366f1,#4f46e5); width:0%; transition:width .3s; }
        #saveProgressText  { font-size:12px; color:#64748b; white-space:nowrap; }

        #bulkResult { display:none; font-size:13px; font-weight:600; padding:4px 12px; border-radius:6px; }
        #bulkResult.ok  { background:#d1fae5; color:#065f46; }
        #bulkResult.err { background:#fee2e2; color:#991b1b; }

        tr.row-saved td { animation:savedFlash .6s ease forwards; }
        @keyframes savedFlash { 0% { background:#d1fae5; } 100% { background:transparent; } }

        /* ── Shared filter row ── */
        .filter-form-row { margin-bottom:16px; }
        .flex-box        { display:flex; align-items:center; }

        /* ── Pagination ── */
        .pagination { margin-top:10px; padding:20px; display:flex; justify-content:center; gap:5px; flex-wrap:wrap; }
        .pagination a, .pagination span { padding:8px 12px; border:1px solid #ccc; text-decoration:none; color:#333; font-size:14px; border-radius:4px; transition:all .2s; }
        .pagination a:hover   { background:linear-gradient(135deg,#667eea,#764ba2); color:white; border-color:#667eea; }
        .pagination .active   { background:linear-gradient(135deg,#667eea,#764ba2); color:white; font-weight:bold; border-color:#667eea; }
        .pagination .disabled { color:#999; pointer-events:none; background:#f5f5f5; cursor:not-allowed; }
    </style>
</head>
<body>

@if(Session::has('user_id') && Session::get('role_id') == 2)

    @include('admin.partials.sidenav')

    <div class="main-content">

        <!-- ── Alerts ── -->
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- ── Page Header ── -->
        <div class="flex-box" style="margin-bottom:20px;">
            <h1 style="flex:1; margin:0;">💼 Cash Advance & Statutory</h1>
        </div>

        <!-- ── Tab Bar ── -->
        <div class="tab-bar">
            <button class="tab-btn {{ request('tab') !== 'statutory' ? 'active' : '' }}"
                    onclick="switchTab('cashadvance', this)">
                💸 Cash Advance
            </button>
            <button class="tab-btn {{ request('tab') === 'statutory' ? 'active' : '' }}"
                    onclick="switchTab('statutory', this)">
                🏛️ Statutory
            </button>
        </div>


        {{-- ══════════════════════════════════════════
             TAB 1 — CASH ADVANCE
        ══════════════════════════════════════════ --}}
        <div id="tab-cashadvance" class="tab-panel {{ request('tab') !== 'statutory' ? 'active' : '' }}">

            <!-- Filter + Add -->
            <form method="GET" action="{{ route('admin.cashadvance') }}" class="filter-form-row">
                <input type="hidden" name="tab" value="cashadvance">
                <div class="flex-box" style="margin-bottom:12px; gap:10px; flex-wrap:wrap;">
                    <input type="text"
                           name="search"
                           class="search-box"
                           placeholder="Search by Name"
                           value="{{ request('search') }}"
                           style="flex:1; min-width:160px;">

                    <select name="status" class="filter-box" onchange="this.form.submit()">
                        <option value="">All (excl. Paid)</option>
                        <option value="pending"  {{ request('status') == 'pending'  ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="paid"     {{ request('status') == 'paid'     ? 'selected' : '' }}>Paid</option>
                    </select>

                    <button type="button" class="add-btn" onclick="openCashAdvanceModal()">+ Add Cash Advance</button>
                </div>
            </form>

            <!-- Table -->
            <div class="table-container">
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Amount</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Date Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cashAdvances as $cash)
                                <tr>
                                    <td>{{ $cash->id }}</td>
                                    <td>
                                        {{ $cash->employee->first_name ?? '' }}
                                        @if($cash->employee->middle_name ?? false)
                                            {{ strtoupper(substr($cash->employee->middle_name, 0, 1)) }}.
                                        @endif
                                        {{ $cash->employee->last_name ?? '' }}
                                    </td>
                                    <td>₱ {{ number_format($cash->amount, 2) }}</td>
                                    <td>{{ $cash->reason ?? 'N/A' }}</td>
                                    <td>
                                        <span class="status-badge status-{{ strtolower($cash->status) }}">
                                            {{ ucfirst($cash->status) }}
                                        </span>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($cash->created_at)->format('M d, Y | h:i A') }}</td>
                                    <td>
                                        <button class="edit-btn"   onclick="editCashAdvanceModal({{ $cash->id }})">✏️ Edit</button>
                                        <button class="delete-btn" onclick="confirmDelete({{ $cash->id }})">🗑️ Delete</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No Cash Advance Records Found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                @if ($cashAdvances->onFirstPage())
                    <span class="disabled">««</span>
                    <span class="disabled">«</span>
                @else
                    <a href="{{ $cashAdvances->url(1)->appends(array_merge(request()->query(), ['tab'=>'cashadvance'])) }}">««</a>
                    <a href="{{ $cashAdvances->previousPageUrl() . '&tab=cashadvance' }}">«</a>
                @endif

                @php
                    $currentPage = $cashAdvances->currentPage();
                    $lastPage    = $cashAdvances->lastPage();
                    $start       = max(1, $currentPage - 2);
                    $end         = min($lastPage, $currentPage + 2);
                @endphp

                @for ($page = $start; $page <= $end; $page++)
                    @if ($page == $currentPage)
                        <span class="active">{{ $page }}</span>
                    @else
                        <a href="{{ $cashAdvances->url($page) }}&tab=cashadvance">{{ $page }}</a>
                    @endif
                @endfor

                @if ($cashAdvances->hasMorePages())
                    <a href="{{ $cashAdvances->nextPageUrl() }}&tab=cashadvance">»</a>
                    <a href="{{ $cashAdvances->url($cashAdvances->lastPage()) }}&tab=cashadvance">»»</a>
                @else
                    <span class="disabled">»</span>
                    <span class="disabled">»»</span>
                @endif
            </div>

        </div>{{-- end tab-cashadvance --}}


        {{-- ══════════════════════════════════════════
             TAB 2 — STATUTORY
        ══════════════════════════════════════════ --}}
        <div id="tab-statutory" class="tab-panel {{ request('tab') === 'statutory' ? 'active' : '' }}">

            <!-- Filter + Save Bar -->
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap;">
                <form action="{{ route('admin.cashadvance') }}" method="GET"
                      style="display:flex; align-items:center; gap:8px; flex:1; flex-wrap:wrap;">
                    <input type="hidden" name="tab" value="statutory">
                    <input type="text" name="stat_search" class="search-box"
                           placeholder="🔍 Search by name..."
                           value="{{ request('stat_search') }}"
                           style="flex:1; min-width:160px;">
                    <select name="position" class="filter-box">
                        <option value="">All Positions</option>
                        @foreach($positions as $pos)
                            <option value="{{ $pos }}" {{ request('position') == $pos ? 'selected' : '' }}>{{ $pos }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-filter">Filter</button>
                </form>

                <div id="saveProgressWrap">
                    <div id="saveProgressTrack"><div id="saveProgressFill"></div></div>
                    <span id="saveProgressText">0 / 0</span>
                </div>
                <div id="bulkResult"></div>
                <button type="button" class="btn-bulk-save" id="btnBulkSave" onclick="saveAllStatutory()">
                    💾 Save All Statutory
                </button>
            </div>

            <!-- Table -->
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
                            <td>
                                <input type="number" step="0.01" min="0"
                                       class="statutory-input stat-sss"
                                       data-employee="{{ $employee->id }}"
                                       value="{{ number_format($employee->sss_amount, 2, '.', '') }}"
                                       placeholder="0.00"
                                       oninput="updateTotal({{ $employee->id }})">
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0"
                                       class="statutory-input stat-philhealth"
                                       data-employee="{{ $employee->id }}"
                                       value="{{ number_format($employee->philhealth_amount, 2, '.', '') }}"
                                       placeholder="0.00"
                                       oninput="updateTotal({{ $employee->id }})">
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0"
                                       class="statutory-input stat-pagibig"
                                       data-employee="{{ $employee->id }}"
                                       value="{{ number_format($employee->pagibig_amount, 2, '.', '') }}"
                                       placeholder="0.00"
                                       oninput="updateTotal({{ $employee->id }})">
                            </td>
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

            <!-- Pagination -->
            @if($employees->hasPages())
                <div class="pagination">
                    @if ($employees->onFirstPage())
                        <span class="disabled">««</span><span class="disabled">«</span>
                    @else
                        <a href="{{ $employees->appends(['tab'=>'statutory','stat_search'=>request('stat_search'),'position'=>request('position')])->url(1) }}">««</a>
                        <a href="{{ $employees->appends(['tab'=>'statutory','stat_search'=>request('stat_search'),'position'=>request('position')])->previousPageUrl() }}">«</a>
                    @endif
                    @php $cp=$employees->currentPage(); $lp=$employees->lastPage(); $s=max(1,$cp-2); $e=min($lp,$cp+2); @endphp
                    @for($pg=$s;$pg<=$e;$pg++)
                        @if($pg==$cp)<span class="active">{{ $pg }}</span>
                        @else<a href="{{ $employees->appends(['tab'=>'statutory','stat_search'=>request('stat_search'),'position'=>request('position')])->url($pg) }}">{{ $pg }}</a>@endif
                    @endfor
                    @if($employees->hasMorePages())
                        <a href="{{ $employees->appends(['tab'=>'statutory','stat_search'=>request('stat_search'),'position'=>request('position')])->nextPageUrl() }}">»</a>
                        <a href="{{ $employees->appends(['tab'=>'statutory','stat_search'=>request('stat_search'),'position'=>request('position')])->url($employees->lastPage()) }}">»»</a>
                    @else
                        <span class="disabled">»</span><span class="disabled">»»</span>
                    @endif
                </div>
            @endif

        </div>{{-- end tab-statutory --}}

    </div>{{-- end main-content --}}


    {{-- ══════════════════════════════════════════
         CASH ADVANCE MODALS
    ══════════════════════════════════════════ --}}

    <!-- Add Modal -->
    <div id="cashAdvanceModal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <h2>Add Cash Advance</h2>
            <hr>
            <form method="POST" action="{{ route('admin.cashadvance.store') }}" id="cashAdvanceForm">
                @csrf
                <table>
                    <tbody>
                        <tr>
                            <td><strong>Employee:</strong></td>
                            <td>
                                <select name="employee_id" required>
                                    <option value="">Select Employee</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}">
                                            {{ $emp->first_name }}
                                            @if($emp->middle_name) {{ strtoupper(substr($emp->middle_name, 0, 1)) }}. @endif
                                            {{ $emp->last_name }}
                                            @if($emp->suffixes) {{ $emp->suffixes }} @endif
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Amount:</strong></td>
                            <td><input type="number" name="amount" step="0.01" placeholder="0.00" value="{{ old('amount') }}" required></td>
                        </tr>
                        <tr>
                            <td><strong>Reason:</strong></td>
                            <td><textarea name="reason" required placeholder="Enter reason for cash advance" rows="3">{{ old('reason') }}</textarea></td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td>
                                <select name="status">
                                    <option value="">Select Status</option>
                                    <option value="pending"  @selected(old('status') === 'pending')>Pending</option>
                                    <option value="approved" @selected(old('status') === 'approved')>Approved</option>
                                    <option value="rejected" @selected(old('status') === 'rejected')>Rejected</option>
                                    <option value="paid"     @selected(old('status') === 'paid')>Paid</option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div style="margin-top:20px;text-align:right;display:flex;justify-content:flex-end;gap:10px;">
                    <button type="submit" class="save-btn">Save</button>
                    <button type="button" class="modal-close" onclick="closeCashAdvanceModal()">X</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editCashAdvanceModal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <h2>Edit Cash Advance</h2>
            <hr>
            <form id="editCashAdvanceForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_employee_id_hidden" name="employee_id">
                <table>
                    <tbody>
                        <tr>
                            <td><strong>Employee:</strong></td>
                            <td>
                                <select id="edit_employee_id" disabled>
                                    <option value="">Select Employee</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}">
                                            {{ $emp->first_name }}
                                            @if($emp->middle_name) {{ strtoupper(substr($emp->middle_name, 0, 1)) }}. @endif
                                            {{ $emp->last_name }}
                                            @if($emp->suffixes) {{ $emp->suffixes }} @endif
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Amount:</strong></td>
                            <td><input type="number" id="edit_amount" name="amount" step="0.01" required></td>
                        </tr>
                        <tr>
                            <td><strong>Reason:</strong></td>
                            <td><textarea id="edit_reason" name="reason" rows="3" required></textarea></td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td>
                                <select id="edit_status" name="status" required>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="paid">Paid</option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div style="margin-top:20px;text-align:right;display:flex;justify-content:flex-end;gap:10px;">
                    <button type="submit" class="save-btn">Update</button>
                    <button type="button" class="modal-close" onclick="closeEditCashAdvanceModal()">X</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Form -->
    <form id="deleteCashAdvanceForm" method="POST" style="display:none;">
        @csrf
        @method('DELETE')
    </form>


    {{-- ══════════════════════════════════════════
         JAVASCRIPT
    ══════════════════════════════════════════ --}}
    <script>
        // ── Tab Switching ──
        function switchTab(tabName, btn) {
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('tab-' + tabName).classList.add('active');
            btn.classList.add('active');
        }

        // ── Cash Advance Modals ──
        function openCashAdvanceModal() {
            document.getElementById('cashAdvanceModal').style.display = 'flex';
        }
        function closeCashAdvanceModal() {
            document.getElementById('cashAdvanceModal').style.display = 'none';
        }

        function editCashAdvanceModal(id) {
            fetch(`/admin/cashadvance/${id}/modal`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(data => {
                document.getElementById('edit_employee_id').value        = data.employee_id;
                document.getElementById('edit_employee_id_hidden').value = data.employee_id;
                document.getElementById('edit_amount').value             = data.amount;
                document.getElementById('edit_reason').value             = data.reason;

                const status = (data.status || '').toLowerCase();
                const sel = document.getElementById('edit_status');
                for (let i = 0; i < sel.options.length; i++) {
                    if (sel.options[i].value === status) { sel.selectedIndex = i; break; }
                }

                document.getElementById('editCashAdvanceForm').action = `/admin/cashadvance/${id}`;
                document.getElementById('editCashAdvanceModal').style.display = 'flex';
            })
            .catch(err => alert('Failed to load cash advance data: ' + err.message));
        }
        function closeEditCashAdvanceModal() {
            document.getElementById('editCashAdvanceModal').style.display = 'none';
        }

        function confirmDelete(id) {
            if (!confirm('Are you sure you want to delete this cash advance record?')) return;
            const form = document.getElementById('deleteCashAdvanceForm');
            form.action = `/admin/cashadvance/${id}`;
            form.submit();
        }

        // ── Statutory ──
        const CSRF      = document.querySelector('meta[name="csrf-token"]').content;
        const STORE_URL = "{{ route('admin.statutory.store') }}";

        function updateTotal(empId) {
            const sss        = parseFloat(document.querySelector(`.stat-sss[data-employee="${empId}"]`)?.value)        || 0;
            const philhealth = parseFloat(document.querySelector(`.stat-philhealth[data-employee="${empId}"]`)?.value) || 0;
            const pagibig    = parseFloat(document.querySelector(`.stat-pagibig[data-employee="${empId}"]`)?.value)    || 0;
            const total      = sss + philhealth + pagibig;
            document.getElementById(`total-${empId}`).textContent =
                '₱' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        async function saveAllStatutory() {
            const rows   = document.querySelectorAll('#statutoryTable tbody tr[data-employee-id]');
            const btn    = document.getElementById('btnBulkSave');
            const prog   = document.getElementById('saveProgressWrap');
            const fill   = document.getElementById('saveProgressFill');
            const text   = document.getElementById('saveProgressText');
            const result = document.getElementById('bulkResult');

            if (!rows.length) { showResult(result, 'No records to save.', false); return; }

            btn.disabled        = true;
            btn.innerHTML       = '⏳ Saving...';
            prog.style.display  = 'flex';
            result.style.display = 'none';

            let done = 0, failed = 0, skipped = 0;

            for (const row of rows) {
                const empId      = row.dataset.employeeId;
                const sss        = parseFloat(row.querySelector(`.stat-sss[data-employee="${empId}"]`)?.value)        || 0;
                const philhealth = parseFloat(row.querySelector(`.stat-philhealth[data-employee="${empId}"]`)?.value) || 0;
                const pagibig    = parseFloat(row.querySelector(`.stat-pagibig[data-employee="${empId}"]`)?.value)    || 0;

                if (sss === 0 && philhealth === 0 && pagibig === 0) {
                    skipped++; done++;
                    fill.style.width = Math.round((done / rows.length) * 100) + '%';
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
                            employee_id:       empId,
                            sss_amount:        sss,
                            philhealth_amount: philhealth,
                            pagibig_amount:    pagibig,
                        }),
                    });

                    const ct = res.headers.get('content-type');
                    if (!ct || !ct.includes('application/json')) { failed++; done++; continue; }

                    const json = await res.json();

                    if (res.ok && json.success) {
                        row.classList.add('row-saved');
                        setTimeout(() => row.classList.remove('row-saved'), 700);
                        const badge = row.querySelector('.badge-unsaved');
                        if (badge) { badge.className = 'badge-saved'; badge.textContent = '✔ values set'; }
                    } else {
                        failed++;
                    }
                } catch (err) {
                    failed++;
                }

                done++;
                fill.style.width = Math.round((done / rows.length) * 100) + '%';
                text.textContent = `${done} / ${rows.length}`;
            }

            btn.disabled      = false;
            btn.innerHTML     = '💾 Save All Statutory';
            prog.style.display = 'none';

            const saved = done - failed - skipped;
            showResult(
                result,
                failed === 0
                    ? `✅ ${saved} saved, ${skipped} skipped (all zero).`
                    : `⚠️ ${saved} saved, ${failed} failed, ${skipped} skipped.`,
                failed === 0
            );
        }

        function showResult(el, msg, ok) {
            el.textContent   = msg;
            el.className     = ok ? 'ok' : 'err';
            el.style.display = 'block';
            setTimeout(() => { el.style.display = 'none'; }, 4000);
        }

    </script>

@else
    <script>window.location.href = "{{ route('login') }}";</script>
@endif

</body>
</html>