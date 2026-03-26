<!DOCTYPE html>
<html>
<head>
    <title>Holiday Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/partials/sidenav.css') }}">
    <!-- Bootstrap for layout grid only -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FullCalendar -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

    <style>
        * { box-sizing: border-box; }

        body { background: #f4f6f9; }

        .page-wrapper {
            margin-left: 200px;
            padding: 24px 20px;
            min-height: 100vh;
        }

        /* ===== CARD HEADERS ===== */
        .card-header {
            background: linear-gradient(180deg, #6366f1 0%, #4f46e5 100%);
            color: #fff;
        }
        .card-header .form-select { max-width: 150px; font-size: 13px; }

        /* ===== ADD BUTTON ===== */
        .add-btn {
            background: linear-gradient(180deg, #6366f1 0%, #4f46e5 100%);
            color: #fff;
            border: none;
            padding: 9px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity .2s;
        }
        .add-btn:hover { opacity: .88; }

        /* ===== CALENDAR ===== */
        #calendar { min-height: 600px; }
        .calendar-col, .list-col { display: flex; flex-direction: column; }
        .calendar-col .card, .list-col .card { flex: 1; }

        /* ===== HOLIDAY LIST PANEL ===== */
        .holiday-list-wrapper { height: 680px; overflow-y: auto; }
        .holiday-item {
            font-size: 13px;
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
            transition: background .15s;
        }
        .holiday-item:hover { background: #f8f9fa; }
        .inactive-holiday { opacity: .45; }

        /* ===== BADGE COLORS ===== */
        .badge-regular_holiday     { background: #dc3545; }
        .badge-special_non_working { background: #fd7e14; }
        .badge-special_working     { background: #198754; }

        /* ===== LEGEND ===== */
        .legend-dot {
            width: 12px; height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 4px;
            vertical-align: middle;
        }
        .legend-item { font-size: 13px; display: flex; align-items: center; gap: 4px; }

        .fc-daygrid-event { cursor: pointer; }

        /* =====================================================
           PURE CSS MODAL SYSTEM
        ===================================================== */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.55);
            z-index: 1050;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .modal-overlay.show { display: flex; }

        .modal-box {
            background: #fff;
            border-radius: 12px;
            width: 100%;
            max-width: 660px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
            animation: modalIn .2s ease;
        }
        .modal-box-sm { max-width: 380px; }

        @keyframes modalIn {
            from { transform: translateY(-20px); opacity: 0; }
            to   { transform: translateY(0);     opacity: 1; }
        }

        .modal-hdr {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 24px;
            border-radius: 12px 12px 0 0;
            background: #1f2937;
            color: #fff;
        }
        .modal-hdr.danger { background: #dc3545; }
        .modal-hdr h5, .modal-hdr h6 {
            margin: 0;
            font-size: 17px;
            font-weight: 700;
            font-family: Arial, Helvetica, sans-serif;
        }
        .modal-close-btn {
            background: none;
            border: none;
            color: #fff;
            font-size: 22px;
            cursor: pointer;
            line-height: 1;
            padding: 0 4px;
            opacity: .8;
        }
        .modal-close-btn:hover { opacity: 1; }

        .modal-bdy { padding: 28px 28px 12px; font-family: Arial, Helvetica, sans-serif; }
        .modal-ftr {
            padding: 14px 28px 24px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            font-family: Arial, Helvetica, sans-serif;
        }

        /* ===== FORM LAYOUT ===== */
        .form-row { display: flex; gap: 16px; }
        .form-row .fgroup { flex: 1; }

        .fgroup { margin-bottom: 18px; }
        .fgroup label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 6px;
        }
        .fgroup label .req { color: #dc3545; }

        .fgroup input[type="text"],
        .fgroup input[type="number"],
        .fgroup select,
        .fgroup textarea {
            width: 100%;
            padding: 10px 13px;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            color: #1f2937;
            background: #fff;
            transition: border .2s, box-shadow .2s;
            font-family: Arial, Helvetica, sans-serif;
        }
        .fgroup input:focus,
        .fgroup select:focus,
        .fgroup textarea:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,.15);
        }
        .fgroup textarea { resize: vertical; }

        .input-suffix-wrap {
            display: flex;
            align-items: stretch;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            overflow: hidden;
            transition: border .2s, box-shadow .2s;
        }
        .input-suffix-wrap:focus-within {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,.15);
        }
        .input-suffix-wrap input {
            border: none !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            flex: 1;
        }
        .input-suffix-wrap .suffix {
            padding: 10px 13px;
            background: #f3f4f6;
            color: #6b7280;
            font-size: 14px;
            border-left: 1.5px solid #d1d5db;
            display: flex;
            align-items: center;
        }

        .form-hint { font-size: 11px; color: #9ca3af; margin-top: 5px; }

        .date-selects { display: flex; gap: 8px; }
        .date-selects select { flex: 1; }

        .toggle-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #374151;
            font-family: Arial, Helvetica, sans-serif;
        }
        .toggle-row input[type="checkbox"] {
            width: 38px;
            height: 20px;
            accent-color: #6366f1;
            cursor: pointer;
        }

        /* ===== MODAL BUTTONS ===== */
        .mbtn {
            padding: 10px 22px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: opacity .2s, transform .1s;
            font-family: Arial, Helvetica, sans-serif;
        }
        .mbtn:hover { opacity: .88; }
        .mbtn:active { transform: scale(.97); }
        .mbtn-primary   { background: linear-gradient(180deg, #6366f1 0%, #4f46e5 100%); color: #fff; }
        .mbtn-secondary { background: #e5e7eb; color: #374151; }
        .mbtn-danger    { background: #dc3545; color: #fff; }

        @media (max-width: 768px) {
            .page-wrapper { margin-left: 0; }
            .list-col { margin-top: 16px; }
            .holiday-list-wrapper { height: 400px; }
            .form-row { flex-direction: column; gap: 0; }
        }
    </style>
</head>
<body>
@include('admin.partials.sidenav')

<div class="page-wrapper">

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h4 class="fw-bold mb-0">🗓 Holiday Management</h4>
        <!--button class="add-btn" onclick="openAddModal()">+ Add Holiday</button-->
    </div>

    {{-- Legend --}}
    <div class="d-flex gap-4 mb-3 flex-wrap">
        <span class="legend-item"><span class="legend-dot" style="background:#dc3545"></span>Regular Holiday</span>
        <span class="legend-item"><span class="legend-dot" style="background:#fd7e14"></span>Special Non-Working</span>
        <span class="legend-item"><span class="legend-dot" style="background:#198754"></span>Special Working</span>
    </div>

    <div class="row g-3">

        {{-- Calendar --}}
        <div class="col-lg-8 calendar-col">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">📅 Holiday Calendar</div>
                <div class="card-body p-2">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>

        {{-- Holiday List --}}
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

{{-- ===================== ADD / EDIT MODAL ===================== --}}
<div class="modal-overlay" id="holidayModal">
    <div class="modal-box">

        <div class="modal-hdr">
            <h5 id="modalTitle">Add Holiday</h5>
            <button class="modal-close-btn" onclick="closeModal('holidayModal')">✕</button>
        </div>

        <div class="modal-bdy">
            <input type="hidden" id="editId">
            <input type="hidden" id="formMethod" value="POST">

            {{-- Row 1: Name + Date --}}
            <div class="form-row">
                <div class="fgroup" style="flex:2">
                    <label>Holiday Name <span class="req">*</span></label>
                    <input type="text" id="holidayName" placeholder="e.g. Christmas Day">
                </div>
                <div class="fgroup" style="flex:1.2">
                    <label>Date <span class="req">*</span></label>
                    <div class="date-selects">
                        <select id="holidayMonth" onchange="updateDefaultRates()">
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

            {{-- Row 2: Type --}}
            <div class="fgroup">
                <label>Holiday Type <span class="req">*</span></label>
                <select id="holidayType" onchange="updateDefaultRates()">
                    <option value="regular_holiday">Regular Holiday</option>
                    <option value="special_non_working">Special Non-Working</option>
                    <option value="special_working">Special Working</option>
                </select>
            </div>

            {{-- Row 3: Rates --}}
            <div class="form-row">
                <div class="fgroup">
                    <label>Rate if Worked</label>
                    <div class="input-suffix-wrap">
                        <input type="number" id="rateWorked" step="0.01" min="0" max="9.99">
                        <span class="suffix">×</span>
                    </div>
                    <div class="form-hint" id="rateWorkedHint"></div>
                </div>
                <div class="fgroup">
                    <label>Rate if Unworked</label>
                    <div class="input-suffix-wrap">
                        <input type="number" id="rateUnworked" step="0.01" min="0" max="9.99">
                        <span class="suffix">×</span>
                    </div>
                    <div class="form-hint" id="rateUnworkedHint"></div>
                </div>
            </div>

            {{-- Row 4: Description --}}
            <div class="fgroup">
                <label>Description (optional)</label>
                <textarea id="holidayDescription" rows="2" placeholder="Additional notes..."></textarea>
            </div>

            {{-- Row 5: Active toggle --}}
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

{{-- ===================== DELETE CONFIRM MODAL ===================== --}}
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box modal-box-sm">

        <div class="modal-hdr danger">
            <h6>Delete Holiday?</h6>
            <button class="modal-close-btn" onclick="closeModal('deleteModal')">✕</button>
        </div>

        <div class="modal-bdy" style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151;">
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
<form id="postForm" action="{{ route('admin.holiday.store') }}" method="POST" style="display:none">
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
let allHolidays     = @json($holidays);
let calendarEvents  = @json($events);
let pendingDeleteId = null;
let calendarInstance = null;

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

// -------------------------------------------------------
// Modal open / close
// -------------------------------------------------------
function openModal(id) {
    document.getElementById(id).classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.remove('show');
    document.body.style.overflow = '';
}
// Click outside to close
document.addEventListener('click', function(e) {
    ['holidayModal', 'deleteModal'].forEach(id => {
        if (e.target === document.getElementById(id)) closeModal(id);
    });
});
// Escape key to close
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { closeModal('holidayModal'); closeModal('deleteModal'); }
});

// -------------------------------------------------------
// Calendar
// -------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    calendarInstance = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        height: 650,
        events: calendarEvents,
    
        
    });
    calendarInstance.render();
    renderHolidayList(allHolidays);
});

// -------------------------------------------------------
// Modal helpers
// -------------------------------------------------------
function openAddModal() {
    document.getElementById('modalTitle').textContent       = 'Add Holiday';
    document.getElementById('editId').value                 = '';
    document.getElementById('formMethod').value             = 'POST';
    document.getElementById('holidayName').value            = '';
    document.getElementById('holidayMonth').value           = '1';
    document.getElementById('holidayDay').value             = '1';
    document.getElementById('holidayType').value            = 'regular_holiday';
    document.getElementById('holidayDescription').value     = '';
    document.getElementById('isActive').checked             = true;
    updateDefaultRates();
    openModal('holidayModal');
}

function openEditModal(h) {
    document.getElementById('modalTitle').textContent       = 'Edit Holiday';
    document.getElementById('editId').value                 = h.id;
    document.getElementById('formMethod').value             = 'PUT';
    document.getElementById('holidayName').value            = h.name;
    document.getElementById('holidayMonth').value           = h.month;
    document.getElementById('holidayDay').value             = h.day;
    document.getElementById('holidayType').value            = h.type;
    document.getElementById('rateWorked').value             = h.rate_worked;
    document.getElementById('rateUnworked').value           = h.rate_unworked;
    document.getElementById('holidayDescription').value     = h.description ?? '';
    document.getElementById('isActive').checked             = !!h.is_active;
    updateRateHints();
    openModal('holidayModal');
}

function updateDefaultRates() {
    const type  = document.getElementById('holidayType').value;
    const rates = defaultRates[type];
    document.getElementById('rateWorked').value   = rates.worked;
    document.getElementById('rateUnworked').value = rates.unworked;
    updateRateHints();
}
function updateRateHints() {
    const type  = document.getElementById('holidayType').value;
    const rates = defaultRates[type];
    document.getElementById('rateWorkedHint').textContent   = `Default for ${typeLabels[type]}: ${rates.worked}×`;
    document.getElementById('rateUnworkedHint').textContent = `Default for ${typeLabels[type]}: ${rates.unworked}×`;
}

// -------------------------------------------------------
// Save
// -------------------------------------------------------
function saveHoliday() {
    const name   = document.getElementById('holidayName').value.trim();
    const method = document.getElementById('formMethod').value;
    if (!name) { alert('Please enter a holiday name.'); return; }

    const id           = document.getElementById('editId').value;
    const type         = document.getElementById('holidayType').value;
    const month        = document.getElementById('holidayMonth').value;
    const day          = document.getElementById('holidayDay').value;
    const rateWorked   = document.getElementById('rateWorked').value;
    const rateUnworked = document.getElementById('rateUnworked').value;
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
        form.action = `/admin/holiday/${id}`;
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

// -------------------------------------------------------
// Delete
// -------------------------------------------------------
function openDeleteModal(id, name) {
    pendingDeleteId = id;
    document.getElementById('deleteHolidayName').textContent = name;
    openModal('deleteModal');
}
function confirmDelete() {
    const form = document.getElementById('deleteForm');
    form.action = `/admin/holiday/${pendingDeleteId}`;
    form.submit();
}

// -------------------------------------------------------
// Render holiday list
// -------------------------------------------------------
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
        return `
        <div class="holiday-item ${inactiveCls}" data-type="${h.type}">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div>
                    <strong>${dateStr}</strong>
                    <span style="margin-left:6px;">${h.name}</span>
                    ${!h.is_active ? '<span style="background:#6c757d;color:#fff;font-size:10px;padding:2px 6px;border-radius:10px;margin-left:4px;">Inactive</span>' : ''}
                </div>
            </div>
            <div style="margin-top:6px;">
                <span class="badge ${badgeCls} text-white" style="font-size:11px;padding:3px 8px;border-radius:10px;">${label}</span>
                <small style="color:#9ca3af;margin-left:8px;">Worked: ${h.rate_worked}× &nbsp;|&nbsp; Unworked: ${h.rate_unworked}×</small>
            </div>
            ${h.description ? `<div style="font-size:11px;color:#9ca3af;margin-top:4px;">${h.description}</div>` : ''}
        </div>`;
    }).join('');
}

// -------------------------------------------------------
// Filter
// -------------------------------------------------------
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