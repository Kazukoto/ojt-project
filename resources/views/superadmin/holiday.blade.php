<!DOCTYPE html>
<html>
<head>
    <title>Holiday Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/partials/sidenav.css') }}">
    <link rel="stylesheet" href="{{ asset('css/superadmin/holiday.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

</head>
<body>
@include('superadmin.partials.sidenav')

<div class="page-wrapper">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h4 class="fw-bold mb-0">🗓 Holiday Management</h4>
        <button class="add-btn" onclick="openAddModal()">+ Add Holiday</button>
    </div>

    <div class="d-flex gap-4 mb-3 flex-wrap">
        <span class="legend-item"><span class="legend-dot" style="background:#dc3545"></span>Regular Holiday</span>
        <span class="legend-item"><span class="legend-dot" style="background:#fd7e14"></span>Special Non-Working</span>
        <span class="legend-item"><span class="legend-dot" style="background:#198754"></span>Special Working</span>
    </div>

    <div class="row g-3">

        <div class="col-lg-8 calendar-col">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">📅 Holiday Calendar</div>
                <div class="card-body p-2">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 list-col">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center gap-2">
                    <span class="fw-semibold text-nowrap">Recorded Holidays</span>
                    <select id="filterType" class="form-select form-select-sm" onchange="filterList()">
                        <option value="">All Types</option>
                        <option value="regular_holiday">Regular</option>
                        <option value="special_non_working">Special Non-Working</option>
                        <option value="special_working">Special Working</option>
                    </select>
                </div>
                <div class="card-body p-0">
                    <div id="holidayList" class="holiday-list-wrapper"></div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ADD / EDIT MODAL --}}
<div class="modal-overlay" id="holidayModal">
    <div class="modal-box">
        <div class="modal-hdr">
            <h5 id="modalTitle">Add Holiday</h5>
            <button class="modal-close-btn" onclick="closeModal('holidayModal')">✕</button>
        </div>

        <div class="modal-bdy">
            <input type="hidden" id="editId">
            <input type="hidden" id="formMethod" value="POST">

            {{-- Name + Date --}}
            <div class="form-row">
                <div class="fgroup" style="flex:2">
                    <label>Holiday Name <span class="req">*</span></label>
                    <input type="text" id="holidayName" placeholder="e.g. Christmas Day">
                </div>
                <div class="fgroup" style="flex:1.2">
                    <label>Date <span class="req">*</span></label>
                    <div class="date-selects">
                        <select id="holidayMonth" onchange="updateDefaultRate()">
                            @foreach(range(1,12) as $m)
                                <option value="{{ $m }}">{{ \Carbon\Carbon::create(null,$m)->format('M') }}</option>
                            @endforeach
                        </select>
                        <select id="holidayDay">
                            @foreach(range(1,31) as $d)
                                <option value="{{ $d }}">{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Type --}}
            <div class="fgroup">
                <label>Holiday Type <span class="req">*</span></label>
                <select id="holidayType" onchange="updateDefaultRate()">
                    <option value="regular_holiday">Regular Holiday</option>
                    <option value="special_non_working">Special Non-Working</option>
                    <option value="special_working">Special Working</option>
                </select>
            </div>

            {{-- Rate info (auto-filled, read-only display) --}}
            <div class="rate-info-box" id="rateInfoBox">
                <strong>Standard Pay Rate (if Worked):</strong> <span id="rateInfoText"></span>
            </div>

            {{-- Rate if Worked (fixed, read-only) --}}
            <input type="hidden" id="rateWorked">

            {{-- Description --}}
            <div class="fgroup">
                <label>Description (optional)</label>
                <textarea id="holidayDescription" rows="2" placeholder="Additional notes..."></textarea>
            </div>

            {{-- Active toggle --}}
            <div class="toggle-row">
                <input type="checkbox" id="isActive" checked>
                <label for="isActive" style="margin:0; font-weight:600; cursor:pointer;">Active</label>
            </div>
        </div>

        <div class="modal-ftr">
            <button class="mbtn mbtn-secondary" onclick="closeModal('holidayModal')">Cancel</button>
            <button class="mbtn mbtn-primary" onclick="saveHoliday()">Save Holiday</button>
        </div>
    </div>
</div>

{{-- DELETE CONFIRM MODAL --}}
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box modal-box-sm">
        <div class="modal-hdr danger">
            <h6>Delete Holiday?</h6>
            <button class="modal-close-btn" onclick="closeModal('deleteModal')">✕</button>
        </div>
        <div class="modal-bdy" style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#374151;">
            <p style="margin:0 0 10px;">Are you sure you want to delete:</p>
            <strong id="deleteHolidayName"></strong>
        </div>
        <div class="modal-ftr">
            <button class="mbtn mbtn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
            <button class="mbtn mbtn-danger" onclick="confirmDelete()">Delete</button>
        </div>
    </div>
</div>

{{-- Hidden DELETE form --}}
<form id="deleteForm" method="POST" style="display:none">
    @csrf
    @method('DELETE')
</form>

{{-- Hidden PUT form --}}
<form id="putForm" method="POST" style="display:none">
    @csrf
    @method('PUT')
    <input type="hidden" name="name"          id="pf_name">
    <input type="hidden" name="type"          id="pf_type">
    <input type="hidden" name="month"         id="pf_month">
    <input type="hidden" name="day"           id="pf_day">
    <input type="hidden" name="rate_worked"   id="pf_rate_worked">
    <input type="hidden" name="rate_unworked" id="pf_rate_unworked">
    <input type="hidden" name="description"   id="pf_description">
    <input type="hidden" name="is_active"     id="pf_is_active">
</form>

{{-- POST store form --}}
<form id="postForm" action="{{ route('superadmin.holiday.store') }}" method="POST" style="display:none">
    @csrf
    <input type="hidden" name="name"          id="sf_name">
    <input type="hidden" name="type"          id="sf_type">
    <input type="hidden" name="month"         id="sf_month">
    <input type="hidden" name="day"           id="sf_day">
    <input type="hidden" name="rate_worked"   id="sf_rate_worked">
    <input type="hidden" name="rate_unworked" id="sf_rate_unworked">
    <input type="hidden" name="description"   id="sf_description">
    <input type="hidden" name="is_active"     id="sf_is_active" value="1">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let allHolidays      = @json($holidays);
let calendarEvents   = @json($events);
let pendingDeleteId  = null;
let calendarInstance = null;

// Fixed rates — rate_unworked sent to DB automatically based on type
const defaultRates = {
    regular_holiday:     { worked: 2.00, unworked: 1.00 },
    special_non_working: { worked: 1.30, unworked: 0.00 },
    special_working:     { worked: 1.00, unworked: 0.00 },
};
const typeLabels = {
    regular_holiday:     'Regular Holiday',
    special_non_working: 'Special Non-Working',
    special_working:     'Special Working',
};
const rateDescriptions = {
    regular_holiday:     '2.00× — 200% of daily rate',
    special_non_working: '1.30× — 130% of daily rate',
    special_working:     '1.00× — 100% of daily rate',
};

// ── Modal open/close ───────────────────────────────────────
function openModal(id) {
    document.getElementById(id).classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.remove('show');
    document.body.style.overflow = '';
}
document.addEventListener('click', function(e) {
    ['holidayModal','deleteModal'].forEach(id => {
        if (e.target === document.getElementById(id)) closeModal(id);
    });
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { closeModal('holidayModal'); closeModal('deleteModal'); }
});

// ── Calendar ───────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    calendarInstance = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        height: 650,
        events: calendarEvents,
    });
    calendarInstance.render();
    renderHolidayList(allHolidays);
});

// ── Modal helpers ──────────────────────────────────────────
function openAddModal() {
    document.getElementById('modalTitle').textContent   = 'Add Holiday';
    document.getElementById('editId').value             = '';
    document.getElementById('formMethod').value         = 'POST';
    document.getElementById('holidayName').value        = '';
    document.getElementById('holidayMonth').value       = '1';
    document.getElementById('holidayDay').value         = '1';
    document.getElementById('holidayType').value        = 'regular_holiday';
    document.getElementById('holidayDescription').value = '';
    document.getElementById('isActive').checked         = true;
    updateDefaultRate();
    openModal('holidayModal');
}

function openEditModal(h) {
    document.getElementById('modalTitle').textContent   = 'Edit Holiday';
    document.getElementById('editId').value             = h.id;
    document.getElementById('formMethod').value         = 'PUT';
    document.getElementById('holidayName').value        = h.name;
    document.getElementById('holidayMonth').value       = h.month;
    document.getElementById('holidayDay').value         = h.day;
    document.getElementById('holidayType').value        = h.type;
    document.getElementById('rateWorked').value         = h.rate_worked;
    document.getElementById('holidayDescription').value = h.description ?? '';
    document.getElementById('isActive').checked         = !!h.is_active;
    updateRateHint();
    openModal('holidayModal');
}

// Auto-fill rate when type changes
function updateDefaultRate() {
    const type = document.getElementById('holidayType').value;
    document.getElementById('rateWorked').value = defaultRates[type].worked;
    updateRateHint();
}
function updateRateHint() {
    const type = document.getElementById('holidayType').value;
    document.getElementById('rateInfoText').textContent = rateDescriptions[type];
}

// ── Save ───────────────────────────────────────────────────
function saveHoliday() {
    const name = document.getElementById('holidayName').value.trim();
    if (!name) { alert('Please enter a holiday name.'); return; }

    const method       = document.getElementById('formMethod').value;
    const id           = document.getElementById('editId').value;
    const type         = document.getElementById('holidayType').value;
    const month        = document.getElementById('holidayMonth').value;
    const day          = document.getElementById('holidayDay').value;
    const rateWorked   = document.getElementById('rateWorked').value;
    const rateUnworked = defaultRates[type].unworked; // auto from type, hidden from user
    const description  = document.getElementById('holidayDescription').value;
    const isActive     = document.getElementById('isActive').checked ? '1' : '0';

    if (method === 'PUT') {
        document.getElementById('pf_name').value          = name;
        document.getElementById('pf_type').value          = type;
        document.getElementById('pf_month').value         = month;
        document.getElementById('pf_day').value           = day;
        document.getElementById('pf_rate_worked').value   = rateWorked;
        document.getElementById('pf_rate_unworked').value = rateUnworked;
        document.getElementById('pf_description').value   = description;
        document.getElementById('pf_is_active').value     = isActive;
        const form = document.getElementById('putForm');
        form.action = `/superadmin/holiday/${id}`;
        form.submit();
    } else {
        document.getElementById('sf_name').value          = name;
        document.getElementById('sf_type').value          = type;
        document.getElementById('sf_month').value         = month;
        document.getElementById('sf_day').value           = day;
        document.getElementById('sf_rate_worked').value   = rateWorked;
        document.getElementById('sf_rate_unworked').value = rateUnworked;
        document.getElementById('sf_description').value   = description;
        document.getElementById('sf_is_active').value     = isActive;
        document.getElementById('postForm').submit();
    }
}

// ── Delete ─────────────────────────────────────────────────
function openDeleteModal(id, name) {
    pendingDeleteId = id;
    document.getElementById('deleteHolidayName').textContent = name;
    openModal('deleteModal');
}
function confirmDelete() {
    const form = document.getElementById('deleteForm');
    form.action = `/superadmin/holiday/${pendingDeleteId}`;
    form.submit();
}

// ── Render holiday list ────────────────────────────────────
function renderHolidayList(data) {
    const container = document.getElementById('holidayList');
    if (!data.length) {
        container.innerHTML = '<p style="color:#9ca3af;text-align:center;padding:20px;">No holidays recorded.</p>';
        return;
    }
    const sorted = [...data].sort((a,b) => a.month !== b.month ? a.month - b.month : a.day - b.day);
    container.innerHTML = sorted.map(h => {
        const dateStr     = `${String(h.month).padStart(2,'0')}-${String(h.day).padStart(2,'0')}`;
        const badgeCls    = `badge-${h.type}`;
        const inactiveCls = h.is_active ? '' : 'inactive-holiday';
        const label       = typeLabels[h.type] ?? h.type;
        const hJson       = JSON.stringify(h).replace(/'/g, "\\'").replace(/"/g, '&quot;');
        return `
        <div class="holiday-item ${inactiveCls}" data-type="${h.type}">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div>
                    <strong>${dateStr}</strong>
                    <span style="margin-left:6px;">${h.name}</span>
                    ${!h.is_active ? '<span style="background:#6c757d;color:#fff;font-size:10px;padding:2px 6px;border-radius:10px;margin-left:4px;">Inactive</span>' : ''}
                </div>
                <div style="display:flex;gap:6px;flex-shrink:0;">
                    <button onclick='openEditModal(${JSON.stringify(h)})'
                        style="background:#6366f1;color:#fff;border:none;border-radius:6px;padding:3px 10px;font-size:11px;cursor:pointer;font-weight:600;">
                        ✏️ Edit
                    </button>
                    <button onclick="openDeleteModal(${h.id}, '${h.name.replace(/'/g, "\\'")}')"
                        style="background:#dc3545;color:#fff;border:none;border-radius:6px;padding:3px 10px;font-size:11px;cursor:pointer;font-weight:600;">
                        🗑 Delete
                    </button>
                </div>
            </div>
            <div style="margin-top:6px;">
                <span class="badge ${badgeCls} text-white" style="font-size:11px;padding:3px 8px;border-radius:10px;">${label}</span>
                <small style="color:#9ca3af;margin-left:8px;">Rate: <strong style="color:#374151;">${h.rate_worked}×</strong></small>
            </div>
            ${h.description ? `<div style="font-size:11px;color:#9ca3af;margin-top:4px;">${h.description}</div>` : ''}
        </div>`;
    }).join('');
}

// ── Filter ─────────────────────────────────────────────────
function filterList() {
    const type     = document.getElementById('filterType').value;
    const filtered = type ? allHolidays.filter(h => h.type === type) : allHolidays;
    renderHolidayList(filtered);
}

function confirmLogout() {
    if (confirm('Are you sure you want to logout?')) {
        document.getElementById('logoutForm').submit();
    }
}
</script>
</body>
</html>