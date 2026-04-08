<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Employee Archive</title>
    <link rel="stylesheet" href="{{ asset('css/superadmin/archive.css') }}">
    <link rel="stylesheet" href="{{ asset('css/partials/sidenav.css') }}">
    <style>
        .status-badge         { display:inline-block; padding:3px 10px; border-radius:12px; font-size:11px; font-weight:700; }
        .status-terminated    { background:#dc3545; color:#fff; }
        .status-inactive      { background:#ffc107; color:#000; }

        .col-check    { width:40px; text-align:center; }
        .row-checkbox { width:16px; height:16px; cursor:pointer; accent-color:#6366f1; }
        tr.row-selected td { background:#f5f3ff !important; }

        #bulkBar {
            display:none; align-items:center; gap:10px;
            background:#ede9fe; border:1.5px solid #c4b5fd;
            border-radius:10px; padding:10px 16px; margin-bottom:14px;
            animation: fadeInUp .25s ease both;
        }
        @keyframes fadeInUp { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
        #bulkBar .bulk-count { font-size:13px; font-weight:700; color:#4f46e5; flex:1; }
        .btn-bulk-restore { padding:7px 16px; background:#198754; color:#fff; border:none; border-radius:7px; font-size:13px; font-weight:700; cursor:pointer; }
        .btn-bulk-restore:hover { background:#146c43; }
        .btn-bulk-delete  { padding:7px 16px; background:#dc3545; color:#fff; border:none; border-radius:7px; font-size:13px; font-weight:700; cursor:pointer; }
        .btn-bulk-delete:hover  { background:#b91c1c; }
        .btn-bulk-cancel  { padding:7px 14px; background:#fff; color:#6b7280; border:1px solid #d1d5db; border-radius:7px; font-size:13px; font-weight:600; cursor:pointer; }
        .btn-bulk-cancel:hover  { background:#f3f4f6; }

        .bulk-confirm-overlay {
            display:none; position:fixed; inset:0;
            background:rgba(0,0,0,.45); z-index:9998;
            align-items:center; justify-content:center;
        }
        .bulk-confirm-box {
            background:#fff; border-radius:14px; padding:28px 32px;
            max-width:480px; width:90%; box-shadow:0 8px 32px rgba(0,0,0,.18);
        }
        .bulk-confirm-box h2 { margin:0 0 8px; font-size:18px; color:#1e293b; }
        .bulk-confirm-box p  { font-size:13px; color:#64748b; margin-bottom:14px; }
        .bulk-names-list {
            background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;
            padding:10px 14px; font-size:13px; color:#374151;
            max-height:120px; overflow-y:auto; margin-bottom:16px; line-height:1.8;
        }
        .bulk-progress { display:none; margin-top:12px; }
        .bulk-progress-track { background:#e2e8f0; border-radius:6px; overflow:hidden; height:8px; }
        .bulk-progress-fill  { height:100%; background:#6366f1; width:0%; transition:width .3s; }
        .bulk-progress-text  { font-size:12px; color:#64748b; margin-top:6px; text-align:center; }
        .bulk-err {
            display:none; background:#fee2e2; color:#991b1b;
            border:1px solid #fca5a5; border-radius:6px;
            padding:8px 12px; font-size:13px; margin-top:10px;
        }
        .bulk-footer { display:flex; justify-content:flex-end; gap:8px; margin-top:20px; }
    </style>
</head>
<body>

@if(Session::has('user_id') && Session::get('role_id') == 2)

    @include('admin.partials.sidenav')

    <div class="header">
        <h1>🗄️ Employee Archive</h1>
    </div>

    <div class="container">

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <!-- STATS -->
        <div class="stats-bar">
            <div class="stat-card">
                <div class="number" id="statTotal">{{ $archivedEmployees->count() }}</div>
                <div class="label">Total Archived</div>
            </div>
            <div class="stat-card">
                <div class="number" id="statTerminated">{{ $archivedEmployees->where('status','terminated')->count() }}</div>
                <div class="label">Terminated</div>
            </div>
            <div class="stat-card">
                <div class="number" id="statInactive">{{ $archivedEmployees->where('status','inactive')->count() }}</div>
                <div class="label">Inactive</div>
            </div>
        </div>

        <!-- CONTROLS -->
        <div class="controls-bar">
            <input type="text" id="searchInput" class="search-input" placeholder="🔍 Search by name...">
            <select id="positionFilter" class="position-select">
                <option value="">All Positions</option>
                @foreach($archivedEmployees->pluck('position')->filter()->unique()->sort() as $pos)
                    <option value="{{ strtolower($pos) }}">{{ $pos }}</option>
                @endforeach
            </select>
            <select id="statusFilter" class="position-select">
                <option value="">All Statuses</option>
                <option value="terminated">Terminated</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <!-- BULK ACTION BAR -->
        <div id="bulkBar">
            <span class="bulk-count" id="bulkCount">0 selected</span>
            <button type="button" class="btn-bulk-restore" onclick="openBulkConfirm('restore')">♻️ Restore Selected</button>
            <button type="button" class="btn-bulk-delete"  onclick="openBulkConfirm('delete')">🗑️ Delete Selected</button>
            <button type="button" class="btn-bulk-cancel"  onclick="clearSelection()">✕ Cancel</button>
        </div>

        <div class="section-title">🗄️ Archived Employees (<span id="visibleCount">{{ $archivedEmployees->count() }}</span>)</div>

        <!-- TABLE -->
        <table>
            <thead>
                <tr>
                    <th class="col-check">
                        <input type="checkbox" id="selectAll" class="row-checkbox" onclick="toggleSelectAll(this)">
                    </th>
                    <th>Archive ID</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Position</th>
                    <th>Gender</th>
                    <th>Rating</th>
                    <th>Date Archived</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="archiveTableBody">
                @forelse($archivedEmployees as $emp)
                <tr id="archive-row-{{ $emp->id }}"
                    data-name="{{ strtolower($emp->first_name . ' ' . $emp->last_name) }}"
                    data-position="{{ strtolower($emp->position ?? '') }}"
                    data-status="{{ strtolower($emp->status ?? 'terminated') }}">
                    <td class="col-check" onclick="event.stopPropagation()">
                        <input type="checkbox" class="row-checkbox arc-checkbox"
                               value="{{ $emp->id }}"
                               data-name="{{ $emp->first_name }} {{ $emp->last_name }}"
                               onchange="onCheckboxChange()">
                    </td>
                    <td>{{ $emp->id }}</td>
                    <td>{{ $emp->first_name }} {{ $emp->middle_name ? substr($emp->middle_name,0,1).'. ' : '' }}{{ $emp->last_name }} {{ $emp->suffixes }}</td>
                    <td>{{ $emp->username }}</td>
                    <td>{{ $emp->position ?? 'N/A' }}</td>
                    <td>{{ $emp->gender ?? 'N/A' }}</td>
                    <td>{{ $emp->rating ?? 'n/a' }}</td>
                    <td>{{ \Carbon\Carbon::parse($emp->updated_at)->format('M d, Y') }}</td>
                    <td>
                        @php $status = strtolower($emp->status ?? 'terminated'); @endphp
                        <span class="status-badge status-{{ $status }}">{{ ucfirst($status) }}</span>
                    </td>
                    <td>
                        <div class="action-cell">
                            <button class="btn-restore"
                                onclick="restoreEmployee({{ $emp->id }}, '{{ $emp->first_name }} {{ $emp->last_name }}', this)">
                                ♻️ Restore
                            </button>
                            <button class="btn-delete-perm"
                                onclick="deleteEmployee({{ $emp->id }}, '{{ $emp->first_name }} {{ $emp->last_name }}', this)">
                                🗑️ Delete
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                    <tr id="emptyRow"><td colspan="10" class="no-records">No archived employees yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- BULK CONFIRM MODAL -->
    <div class="bulk-confirm-overlay" id="bulkConfirmOverlay">
        <div class="bulk-confirm-box">
            <h2 id="bulkConfirmTitle"></h2>
            <p id="bulkConfirmDesc"></p>
            <div class="bulk-names-list" id="bulkConfirmNames"></div>
            <div class="bulk-progress" id="bulkProgress">
                <div class="bulk-progress-track">
                    <div class="bulk-progress-fill" id="bulkProgressFill"></div>
                </div>
                <div class="bulk-progress-text" id="bulkProgressText"></div>
            </div>
            <div class="bulk-err" id="bulkErr"></div>
            <div class="bulk-footer">
                <button type="button" id="bulkCancelBtn"
                        style="padding:8px 18px;background:#fff;color:#6b7280;border:1px solid #d1d5db;border-radius:7px;font-size:13px;cursor:pointer;"
                        onclick="closeBulkConfirm()">✕ Cancel</button>
                <button type="button" id="bulkConfirmBtn"
                        style="padding:8px 20px;border:none;border-radius:7px;font-size:13px;font-weight:700;cursor:pointer;color:#fff;"
                        onclick="executeBulkAction()"></button>
            </div>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <script>
        const CSRF      = document.querySelector('meta[name="csrf-token"]').content;
        let _bulkAction = null; // 'restore' | 'delete'

        // ═══════════ MULTISELECT ═══════════════════════════════

        function getChecked() {
            return [...document.querySelectorAll('.arc-checkbox:checked')];
        }

        function onCheckboxChange() {
            const checked = getChecked();
            const total   = [...document.querySelectorAll('.arc-checkbox')]
                                .filter(cb => cb.closest('tr')?.style.display !== 'none').length;
            const bar     = document.getElementById('bulkBar');

            document.querySelectorAll('.arc-checkbox').forEach(cb => {
                const row = cb.closest('tr');
                if (row) row.classList.toggle('row-selected', cb.checked);
            });

            const sa = document.getElementById('selectAll');
            if (sa) {
                sa.checked       = checked.length === total && total > 0;
                sa.indeterminate = checked.length > 0 && checked.length < total;
            }

            if (checked.length > 0) {
                bar.style.display = 'flex';
                document.getElementById('bulkCount').textContent =
                    `${checked.length} employee${checked.length > 1 ? 's' : ''} selected`;
            } else {
                bar.style.display = 'none';
            }
        }

        function toggleSelectAll(masterCb) {
            document.querySelectorAll('.arc-checkbox').forEach(cb => {
                const row = cb.closest('tr');
                if (row && row.style.display !== 'none') {
                    cb.checked = masterCb.checked;
                }
            });
            onCheckboxChange();
        }

        function clearSelection() {
            document.querySelectorAll('.arc-checkbox').forEach(cb => cb.checked = false);
            const sa = document.getElementById('selectAll');
            if (sa) { sa.checked = false; sa.indeterminate = false; }
            onCheckboxChange();
        }

        // ═══════════ BULK CONFIRM MODAL ════════════════════════

        function openBulkConfirm(action) {
            const checked = getChecked();
            if (!checked.length) return;

            _bulkAction = action;
            const isRestore = action === 'restore';

            document.getElementById('bulkConfirmTitle').textContent =
                isRestore ? '♻️ Restore Employees' : '🗑️ Permanently Delete Employees';
            document.getElementById('bulkConfirmDesc').textContent =
                isRestore
                    ? `You are about to restore ${checked.length} employee(s) back to active.`
                    : `⚠️ You are about to permanently delete ${checked.length} employee(s). This cannot be undone.`;
            document.getElementById('bulkConfirmNames').innerHTML =
                checked.map(cb => `• ${cb.dataset.name}`).join('<br>');

            const btn = document.getElementById('bulkConfirmBtn');
            btn.textContent      = isRestore ? '♻️ Restore All' : '🗑️ Delete All';
            btn.style.background = isRestore ? '#198754' : '#dc3545';
            btn.disabled         = false;

            document.getElementById('bulkCancelBtn').disabled      = false;
            document.getElementById('bulkProgress').style.display  = 'none';
            document.getElementById('bulkErr').style.display       = 'none';
            document.getElementById('bulkProgressFill').style.width = '0%';

            document.getElementById('bulkConfirmOverlay').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeBulkConfirm() {
            document.getElementById('bulkConfirmOverlay').style.display = 'none';
            document.body.style.overflow = 'auto';
            _bulkAction = null;
        }

        async function executeBulkAction() {
            const checked   = getChecked();
            const btn       = document.getElementById('bulkConfirmBtn');
            const cancelBtn = document.getElementById('bulkCancelBtn');
            const errBox    = document.getElementById('bulkErr');
            const progWrap  = document.getElementById('bulkProgress');
            const progFill  = document.getElementById('bulkProgressFill');
            const progText  = document.getElementById('bulkProgressText');

            btn.disabled       = true;
            cancelBtn.disabled = true;
            errBox.style.display  = 'none';
            progWrap.style.display = 'block';

            const ids    = checked.map(cb => parseInt(cb.value));
            const failed = [];
            let done     = 0;

            for (const id of ids) {
                try {
                    // ✅ Use real DELETE method — JSON body _method spoofing doesn't work
                    const url    = _bulkAction === 'restore'
                        ? `/admin/archive/${id}/restore`
                        : `/admin/archive/${id}`;
                    const method = _bulkAction === 'restore' ? 'POST' : 'DELETE';

                    const res  = await fetch(url, {
                        method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF,
                            'Accept':       'application/json',
                        },
                        body: JSON.stringify({}),
                    });
                    const data = await res.json();

                    if (data.success) {
                        removeRow(id);
                    } else {
                        failed.push(id);
                    }
                } catch {
                    failed.push(id);
                }

                done++;
                const pct = Math.round((done / ids.length) * 100);
                progFill.style.width = pct + '%';
                progText.textContent = `${done} / ${ids.length} processed...`;
            }

            btn.disabled       = false;
            cancelBtn.disabled = false;

            if (failed.length === 0) {
                closeBulkConfirm();
                clearSelection();
                const verb = _bulkAction === 'restore' ? 'restored' : 'deleted';
                showToast(`${ids.length} employee${ids.length > 1 ? 's' : ''} ${verb} successfully.`, 'success');
            } else {
                errBox.textContent   = `${failed.length} record(s) failed. Others were processed successfully.`;
                errBox.style.display = 'block';
                clearSelection();
            }
        }

        // ═══════════ SINGLE ROW ACTIONS ════════════════════════

        function removeRow(rowId) {
            const row = document.getElementById('archive-row-' + rowId);
            if (!row) return;
            row.style.transition = 'opacity 0.4s, transform 0.4s';
            row.style.opacity    = '0';
            row.style.transform  = 'translateX(20px)';
            setTimeout(() => {
                row.remove();
                const remaining = document.querySelectorAll('#archiveTableBody tr[data-name]').length;
                document.getElementById('visibleCount').textContent = remaining;
                document.getElementById('statTotal').textContent    = remaining;
                if (remaining === 0 && !document.getElementById('emptyRow')) {
                    const tr     = document.createElement('tr');
                    tr.id        = 'emptyRow';
                    tr.innerHTML = `<td colspan="10" class="no-records">No archived employees yet.</td>`;
                    document.getElementById('archiveTableBody').appendChild(tr);
                }
            }, 420);
        }

        function restoreEmployee(id, name, btn) {
            if (!confirm(`Restore ${name} back to employees?`)) return;
            btn.disabled = true; btn.textContent = '⏳...';
            fetch(`/admin/archive/${id}/restore`, {
                method: 'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json' },
                body: JSON.stringify({}),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    removeRow(id);
                    showToast(data.message ?? `${name} restored.`, 'success');
                } else {
                    btn.disabled = false; btn.textContent = '♻️ Restore';
                    showToast(data.message ?? 'Something went wrong.', 'error');
                }
            })
            .catch(() => {
                btn.disabled = false; btn.textContent = '♻️ Restore';
                showToast('Network error.', 'error');
            });
        }

        function deleteEmployee(id, name, btn) {
            if (!confirm(`Permanently delete ${name}? This cannot be undone!`)) return;
            btn.disabled = true; btn.textContent = '⏳...';
            // ✅ Real DELETE method — no _method spoofing in JSON
            fetch(`/admin/archive/${id}`, {
                method: 'DELETE',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json' },
                body: JSON.stringify({}),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    removeRow(id);
                    showToast(data.message ?? `${name} permanently deleted.`, 'success');
                } else {
                    btn.disabled = false; btn.textContent = '🗑️ Delete';
                    showToast(data.message ?? 'Something went wrong.', 'error');
                }
            })
            .catch(() => {
                btn.disabled = false; btn.textContent = '🗑️ Delete';
                showToast('Network error.', 'error');
            });
        }

        // ═══════════ TOAST ══════════════════════════════════════

        function showToast(message, type = 'success') {
            const t = document.createElement('div');
            t.textContent = (type === 'success' ? '✅ ' : '❌ ') + message;
            Object.assign(t.style, {
                position:'fixed', bottom:'24px', right:'24px',
                background: type === 'success' ? '#198754' : '#dc3545',
                color:'#fff', padding:'12px 20px', borderRadius:'8px',
                fontSize:'14px', fontWeight:'600', zIndex:'99999',
                boxShadow:'0 4px 16px rgba(0,0,0,.2)', transition:'opacity .5s',
            });
            document.body.appendChild(t);
            setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 500); }, 3500);
        }

        // ═══════════ FILTER ═════════════════════════════════════

        function filterTable() {
            const search   = document.getElementById('searchInput').value.toLowerCase();
            const position = document.getElementById('positionFilter').value.toLowerCase();
            const status   = document.getElementById('statusFilter').value.toLowerCase();
            const rows     = document.querySelectorAll('#archiveTableBody tr[data-name]');

            let visible = 0;
            rows.forEach(row => {
                const match =
                    (row.dataset.name     ?? '').includes(search) &&
                    (position === '' || (row.dataset.position ?? '') === position) &&
                    (status   === '' || (row.dataset.status   ?? '') === status);
                row.style.display = match ? '' : 'none';
                if (match) visible++;
            });

            document.getElementById('visibleCount').textContent = visible;

            // Deselect hidden rows
            document.querySelectorAll('.arc-checkbox').forEach(cb => {
                const row = cb.closest('tr');
                if (row && row.style.display === 'none') cb.checked = false;
            });
            onCheckboxChange();

            const noRecord = document.getElementById('noRecordRow');
            if (visible === 0) {
                if (!noRecord) {
                    const tr     = document.createElement('tr');
                    tr.id        = 'noRecordRow';
                    tr.innerHTML = `<td colspan="10" class="no-records">No matching records found.</td>`;
                    document.getElementById('archiveTableBody').appendChild(tr);
                }
            } else {
                if (noRecord) noRecord.remove();
            }
        }

        document.getElementById('searchInput').addEventListener('input', filterTable);
        document.getElementById('positionFilter').addEventListener('change', filterTable);
        document.getElementById('statusFilter').addEventListener('change', filterTable);

        document.getElementById('bulkConfirmOverlay').addEventListener('click', function(e) {
            if (e.target === this) closeBulkConfirm();
        });

    </script>

@else
    <script>window.location.href = "{{ route('login') }}";</script>
@endif
</body>
</html>