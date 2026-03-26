<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Attendance</title>
    <link rel="stylesheet" href="{{ asset('css/superadmin/attendance.css') }}">
    <link rel="stylesheet" href="{{ asset('css/partials/sidenav.css') }}">
    <style>
        input.time-error { border: 2px solid #ef4444 !important; background: #fee2e2 !important; }
        .error-message   { color:#dc2626; font-size:11px; margin-top:2px; display:block; }
        .alert           { padding:14px 20px; border-radius:8px; margin-bottom:20px; font-size:14px; }
        .alert-success   { background:#d1fae5; border-left:4px solid #10b981; color:#065f46; }
        .alert-error     { background:#fee2e2; border-left:4px solid #ef4444; color:#7f1d1d; }
        .alert ul        { margin:0; padding-left:20px; }

        .user-stamp       { font-size:10px; color:#6366f1; margin-top:3px; display:flex; align-items:center; gap:3px; white-space:nowrap; }
        .stamp-time       { color:#9ca3af; font-size:9px; }
        .user-stamp-empty { color:#d1d5db; font-style:italic; }

        #bulkSaveBar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            margin-bottom: 12px;
            padding: 0;
            background: transparent;
            border: none;
            box-shadow: none;
        }
        #bulkSaveBar h2 { display: none; }
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
        #saveProgressText { font-size: 12px; color: #64748b; white-space: nowrap; }

        #bulkResult {
            display: none;
            font-size: 13px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 6px;
        }
        #bulkResult.ok  { background: #d1fae5; color: #065f46; }
        #bulkResult.err { background: #fee2e2; color: #991b1b; }

        tr.row-saved td { animation: savedFlash .6s ease forwards; }
        @keyframes savedFlash {
            0%   { background: #d1fae5; }
            100% { background: transparent; }
        }

        .pagination { margin-top:10px; padding:20px; display:flex; justify-content:center; gap:5px; flex-wrap:wrap; }
        .pagination a, .pagination span { padding:8px 12px; border:1px solid #ccc; text-decoration:none; color:#333; font-size:14px; border-radius:4px; transition:all .2s; }
        .pagination a:hover { background:linear-gradient(135deg,#667eea,#764ba2); color:white; border-color:#667eea; }
        .pagination .active { background:linear-gradient(135deg,#667eea,#764ba2); color:white; font-weight:bold; border-color:#667eea; }
        .pagination .disabled { color:#999; pointer-events:none; background:#f5f5f5; cursor:not-allowed; }
    </style>
</head>
<body>
@if(Session::has('user_id') && Session::get('role_id') == 4)

    @php
        $selectedDate  = request('date', now()->toDateString());
        $today         = now()->toDateString();
        $defaultStatus = ($selectedDate < $today) ? 'Absent' : 'Unfilled';
    @endphp

    @include('finance.partials.sidenav')

    <div class="main-content">

        <div class="header">
            <h1 class="page-title"><span class="icon">📋</span> Attendance</h1>
        </div>

        @if($holidayInfo['is_holiday'])
            @php
                $bannerColors = [
                    'regular_holiday'     => ['bg'=>'#fff1f2','border'=>'#dc3545','badge'=>'#dc3545','icon'=>'🎌'],
                    'special_non_working' => ['bg'=>'#fff7ed','border'=>'#fd7e14','badge'=>'#fd7e14','icon'=>'📅'],
                    'special_working'     => ['bg'=>'#f0fdf4','border'=>'#198754','badge'=>'#198754','icon'=>'💼'],
                ];
                $bc = $bannerColors[$holidayInfo['holiday_type']] ?? ['bg'=>'#f8f9fa','border'=>'#6c757d','badge'=>'#6c757d','icon'=>'📌'];
            @endphp
            <div style="background:{{ $bc['bg'] }};border-left:5px solid {{ $bc['border'] }};border-radius:8px;padding:14px 18px;margin-bottom:16px;display:flex;align-items:center;gap:12px;">
                <span style="font-size:24px;">{{ $bc['icon'] }}</span>
                <div style="flex:1;">
                    <div style="font-weight:700;font-size:15px;color:#1f2937;">{{ $holidayInfo['holiday_name'] }}</div>
                    <div style="font-size:13px;color:#6b7280;margin-top:2px;">
                        Pay rates: <strong style="color:{{ $bc['badge'] }};">Worked {{ $holidayInfo['rate_worked'] * 100 }}%</strong>
                        &nbsp;·&nbsp;<strong style="color:#6b7280;">Unworked {{ $holidayInfo['rate_unworked'] * 100 }}%</strong>
                    </div>
                </div>
                <span style="background:{{ $bc['badge'] }};color:white;font-size:12px;font-weight:600;padding:4px 10px;border-radius:20px;white-space:nowrap;">{{ $holidayInfo['type_label'] }}</span>
            </div>
        @else
            <div style="background:#f9fafb;border-left:5px solid #d1d5db;border-radius:8px;padding:10px 18px;margin-bottom:16px;font-size:13px;color:#9ca3af;">
                📆 <strong>Regular Day</strong> — Standard pay rate applies (1×)
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="toggle-container">
            <button id="regularBtn" class="toggle-btn active" onclick="showRegular()">☀ Regular</button>
            <button id="nsdBtn"     class="toggle-btn"        onclick="showNsd()">🌙 NSD</button>
        </div>

        <form action="{{ route('finance.attendance') }}" method="GET" class="controls">
            <input type="text" name="search" class="search-box" placeholder="🔍 Search by name..." value="{{ request('search') }}">
            <select name="position" class="filter-box">
                <option value="">All Positions</option>
                @foreach($positions as $pos)
                    <option value="{{ $pos }}" {{ request('position') == $pos ? 'selected' : '' }}>{{ $pos }}</option>
                @endforeach
            </select>
            <input type="date" name="date" class="date-input" value="{{ $selectedDate }}">
            <button type="submit" class="btn-filter">Filter</button>
        </form>

        {{-- BULK SAVE BAR — REGULAR --}}
        <div id="bulkSaveBar">
            <div id="saveProgressWrap">
                <div id="saveProgressTrack"><div id="saveProgressFill"></div></div>
                <span id="saveProgressText">0 / 0</span>
            </div>
            <div id="bulkResult"></div>
            <button type="button" class="btn-bulk-save" id="btnBulkSave" onclick="saveAllRegular()">
                💾 Save All Attendance
            </button>
        </div>

        {{-- REGULAR TABLE --}}
        <div id="regularTable" class="table-wrapper">
            <table class="data-table" id="regularDataTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Position</th>
                        <th>AM In (8-12)</th>
                        <th>AM Out (8-12)</th>
                        <th>AM Status</th>
                        <th>PM In (1-5)</th>
                        <th>PM Out (1-5)</th>
                        <th>PM Status</th>
                        <th>OT Hours (6-9PM)</th>
                        <th>Day Type</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                    <tr data-employee-id="{{ $employee->id }}">
                        <td class="employee-name">
                            <div class="name-cell">
                                <div class="avatar">{{ strtoupper(substr($employee->first_name, 0, 1)) }}</div>
                                <div>
                                    <span>{{ $employee->first_name }} {{ $employee->last_name }}</span>
                                    @if(isset($attendances[$employee->id]) && $attendances[$employee->id]->updated_by)
                                        <div class="user-stamp">
                                            ✏️ {{ $attendances[$employee->id]->updated_by }}
                                            <span class="stamp-time">{{ \Carbon\Carbon::parse($attendances[$employee->id]->updated_at)->format('M d, g:i A') }}</span>
                                        </div>
                                    @else
                                        <div class="user-stamp user-stamp-empty">— not yet saved</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $employee->position }}</td>

                        <!-- AM In -->
                        <td>
                            <input type="time" class="time-input time-in-am reg-time-in-am"
                                   data-employee="{{ $employee->id }}"
                                   min="08:00" max="12:00"
                                   value="{{ isset($attendances[$employee->id]) ? $attendances[$employee->id]->time_in : '' }}"
                                   onchange="validateTimeRange(this,'08:00','12:00','AM In'); updateMorningStatus(this);">
                        </td>
                        <!-- AM Out -->
                        <td>
                            <input type="time" class="time-input reg-time-out-am"
                                   data-employee="{{ $employee->id }}"
                                   min="08:00" max="12:00"
                                   value="{{ isset($attendances[$employee->id]) ? $attendances[$employee->id]->time_out : '' }}"
                                   onchange="validateTimeRange(this,'08:00','12:00','AM Out')">
                        </td>
                        <!-- AM Status -->
                        @php
                            $morningVal = $attendances[$employee->id]->morning_status ?? null;
                            if (!$morningVal) {
                                $morningVal = ($attendances[$employee->id]->time_in ?? null) ? 'Present' : $defaultStatus;
                            }
                        @endphp
                        <td>
                            <input type="text"
                                   class="status-display status-morning reg-morning-status status-{{ strtolower(str_replace(' ','-',$morningVal)) }}"
                                   data-employee="{{ $employee->id }}"
                                   value="{{ $morningVal }}"
                                   readonly>
                        </td>
                        <!-- PM In -->
                        <td>
                            <input type="time" class="time-input time-in-pm reg-time-in-pm"
                                   data-employee="{{ $employee->id }}"
                                   min="13:00" max="17:00"
                                   value="{{ isset($attendances[$employee->id]) ? $attendances[$employee->id]->time_in_af : '' }}"
                                   onchange="validateTimeRange(this,'13:00','17:00','PM In'); updateAfternoonStatus(this);">
                        </td>
                        <!-- PM Out -->
                        <td>
                            <input type="time" class="time-input time-out-pm reg-time-out-pm"
                                   data-employee="{{ $employee->id }}"
                                   min="13:00" max="17:00"
                                   value="{{ isset($attendances[$employee->id]) ? $attendances[$employee->id]->time_out_af : '' }}"
                                   onchange="validateTimeRange(this,'13:00','17:00','PM Out'); updateAfternoonStatus(this);">
                        </td>
                        <!-- PM Status -->
                        @php
                            $afternoonVal = $attendances[$employee->id]->afternoon_status ?? null;
                            if (!$afternoonVal) {
                                $afternoonVal = ($attendances[$employee->id]->time_in_af ?? null) ? 'Present' : $defaultStatus;
                            }
                        @endphp
                        <td>
                            <input type="text"
                                   class="status-display status-afternoon reg-afternoon-status status-{{ strtolower(str_replace(' ','-',$afternoonVal)) }}"
                                   data-employee="{{ $employee->id }}"
                                   value="{{ $afternoonVal }}"
                                   readonly>
                        </td>
                        <!-- OT -->
                        <td>
                            <input type="number" step="0.01" class="ot-input reg-ot"
                                   data-employee="{{ $employee->id }}"
                                   placeholder="0.00" min="0" max="3"
                                   value="{{ isset($attendances[$employee->id]) ? $attendances[$employee->id]->overtime_hours : '' }}"
                                   onchange="validateOTHours(this)"
                                   title="OT: 6 PM - 9 PM (Max 3 hours)">
                        </td>
                        <!-- Day Type -->
                        <td>
                            @php
                                $att        = $attendances[$employee->id] ?? null;
                                $hType      = $att->holiday_type ?? $holidayInfo['holiday_type'];
                                $hName      = $att->holiday_name ?? $holidayInfo['holiday_name'];
                                $badgeColor = match($hType) {
                                    'regular_holiday'     => '#dc3545',
                                    'special_non_working' => '#fd7e14',
                                    'special_working'     => '#198754',
                                    default               => '#6c757d',
                                };
                                $hLabel = match($hType) {
                                    'regular_holiday'     => 'Reg. Holiday',
                                    'special_non_working' => 'Sp. Non-Work',
                                    'special_working'     => 'Sp. Working',
                                    default               => 'Regular',
                                };
                            @endphp
                            <span style="background:{{ $badgeColor }};color:white;font-size:11px;padding:3px 8px;border-radius:12px;white-space:nowrap;font-weight:600;">{{ $hLabel }}</span>
                            @if($hName)<div style="font-size:10px;color:#6b7280;margin-top:2px;">{{ $hName }}</div>@endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="no-data">No employees found for this date.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- NSD BULK SAVE BAR --}}
        <div id="nsdSaveBar" style="display:none;align-items:center;justify-content:flex-end;gap:12px;margin-bottom:12px;padding:0;background:transparent;border:none;">
            <div id="nsdProgressWrap" style="display:none;align-items:center;gap:10px;flex:1;">
                <div style="flex:1;background:#e2e8f0;border-radius:6px;height:8px;overflow:hidden;">
                    <div id="nsdProgressFill" style="height:100%;background:linear-gradient(90deg,#6366f1,#4f46e5);width:0%;transition:width .3s;"></div>
                </div>
                <span id="nsdProgressText" style="font-size:12px;color:#64748b;white-space:nowrap;"></span>
            </div>
            <div id="nsdBulkResult" style="display:none;font-size:13px;font-weight:600;padding:4px 12px;border-radius:6px;"></div>
            <button type="button" class="btn-bulk-save" id="btnBulkNsd" onclick="saveAllNsd()"
                    style="background:linear-gradient(135deg,#0ea5e9,#0284c7);">
                💾 Save All NSD
            </button>
        </div>

        {{-- NSD TABLE --}}
        <div id="nsdTable" class="table-wrapper" style="display:none;">
            <table class="data-table" id="nsdDataTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Position</th>
                        <th>NSD Time In (9PM)</th>
                        <th>NSD Time Out (6AM)</th>
                        <th>NSD Hours (Auto)</th>
                        <th>Day Type</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                    <tr data-employee-id="{{ $employee->id }}">
                        <td class="employee-name">
                            <div class="name-cell">
                                <div class="avatar">{{ strtoupper(substr($employee->first_name, 0, 1)) }}</div>
                                <div>
                                    <span>{{ $employee->first_name }} {{ $employee->last_name }}</span>
                                    @if(isset($attendances[$employee->id]) && $attendances[$employee->id]->nsd_updated_by)
                                        <div class="user-stamp">
                                            ✏️ {{ $attendances[$employee->id]->nsd_updated_by }}
                                            <span class="stamp-time">{{ \Carbon\Carbon::parse($attendances[$employee->id]->updated_at)->format('M d, g:i A') }}</span>
                                        </div>
                                    @else
                                        <div class="user-stamp user-stamp-empty">— not yet saved</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $employee->position }}</td>
                        <!-- NSD In -->
                        <td>
                            <input type="time" class="time-input nsd-time-in"
                                   data-employee="{{ $employee->id }}"
                                   min="21:00"
                                   value="{{ isset($attendances[$employee->id]) ? $attendances[$employee->id]->nsd_time_in : '' }}"
                                   onchange="validateNSDTimeIn(this); calculateNSDHours({{ $employee->id }})">
                        </td>
                        <!-- NSD Out -->
                        <td>
                            <input type="time" class="time-input nsd-time-out"
                                   data-employee="{{ $employee->id }}"
                                   max="06:00"
                                   value="{{ isset($attendances[$employee->id]) ? $attendances[$employee->id]->nsd_time_out : '' }}"
                                   onchange="validateNSDTimeOut(this); calculateNSDHours({{ $employee->id }})">
                        </td>
                        <!-- NSD Hours -->
                        <td>
                            <input type="number" step="0.01" class="ot-input nsd-hours"
                                   data-employee="{{ $employee->id }}"
                                   placeholder="0.00"
                                   value="{{ isset($attendances[$employee->id]) ? $attendances[$employee->id]->nsd_hours : '' }}"
                                   readonly style="background:#f3f4f6;">
                        </td>
                        <!-- Day Type -->
                        <td>
                            @php
                                $att        = $attendances[$employee->id] ?? null;
                                $hType      = $att->holiday_type ?? $holidayInfo['holiday_type'];
                                $hName      = $att->holiday_name ?? $holidayInfo['holiday_name'];
                                $badgeColor = match($hType) {
                                    'regular_holiday'     => '#dc3545',
                                    'special_non_working' => '#fd7e14',
                                    'special_working'     => '#198754',
                                    default               => '#6c757d',
                                };
                                $hLabel = match($hType) {
                                    'regular_holiday'     => 'Reg. Holiday',
                                    'special_non_working' => 'Sp. Non-Work',
                                    'special_working'     => 'Sp. Working',
                                    default               => 'Regular',
                                };
                            @endphp
                            <span style="background:{{ $badgeColor }};color:white;font-size:11px;padding:3px 8px;border-radius:12px;white-space:nowrap;font-weight:600;">{{ $hLabel }}</span>
                            @if($hName)<div style="font-size:10px;color:#6b7280;margin-top:2px;">{{ $hName }}</div>@endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="no-data">No employees found for this date.</td></tr>
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
                    <a href="{{ $employees->appends(['date'=>request('date'),'search'=>request('search'),'position'=>request('position')])->url(1) }}">««</a>
                    <a href="{{ $employees->appends(['date'=>request('date'),'search'=>request('search'),'position'=>request('position')])->previousPageUrl() }}">«</a>
                @endif
                @php $currentPage=$employees->currentPage(); $lastPage=$employees->lastPage(); $start=max(1,$currentPage-2); $end=min($lastPage,$currentPage+2); @endphp
                @for($page=$start;$page<=$end;$page++)
                    @if($page==$currentPage)<span class="active">{{ $page }}</span>
                    @else<a href="{{ $employees->appends(['date'=>request('date'),'search'=>request('search'),'position'=>request('position')])->url($page) }}">{{ $page }}</a>@endif
                @endfor
                @if($end<$lastPage)<span class="disabled">...</span>@endif
                @if($employees->hasMorePages())
                    <a href="{{ $employees->appends(['date'=>request('date'),'search'=>request('search'),'position'=>request('position')])->nextPageUrl() }}">»</a>
                    <a href="{{ $employees->appends(['date'=>request('date'),'search'=>request('search'),'position'=>request('position')])->url($employees->lastPage()) }}">»»</a>
                @else
                    <span class="disabled">»</span><span class="disabled">»»</span>
                @endif
            </div>
        @endif

    </div><!-- end main-content -->

<script>
    const CSRF          = document.querySelector('meta[name="csrf-token"]').content;
    const STORE_URL     = "{{ route('finance.attendance.store') }}";
    const STORE_NSD     = "{{ route('finance.attendance.storeNsd') }}";
    const SELECTED_DATE = "{{ $selectedDate }}";

    // ═══════════ BULK SAVE — REGULAR ═══════════════════════

    async function saveAllRegular() {
        const rows   = document.querySelectorAll('#regularDataTable tbody tr[data-employee-id]');
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

        const today = new Date().toISOString().split('T')[0];
        let done = 0, failed = 0;

        for (const row of rows) {
            const empId = row.dataset.employeeId;

            const timeInAm  = row.querySelector(`.reg-time-in-am[data-employee="${empId}"]`)?.value  || null;
            const timeOutAm = row.querySelector(`.reg-time-out-am[data-employee="${empId}"]`)?.value || null;
            const timeInPm  = row.querySelector(`.reg-time-in-pm[data-employee="${empId}"]`)?.value  || null;
            const timeOutPm = row.querySelector(`.reg-time-out-pm[data-employee="${empId}"]`)?.value || null;
            const otHours   = parseFloat(row.querySelector(`.reg-ot[data-employee="${empId}"]`)?.value) || 0;

            // ✅ Derive status directly from time inputs — never trust the display field
            const defaultStatus   = SELECTED_DATE < today ? 'Absent' : 'Unfilled';
            const morningStatus   = timeInAm ? 'Present' : defaultStatus;
            const afternoonStatus = timeInPm ? 'Present' : defaultStatus;

            try {
                const res = await fetch(STORE_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'Accept':       'application/json',
                    },
                    body: JSON.stringify({
                        employee_id:      empId,
                        date:             SELECTED_DATE,
                        time_in:          timeInAm,
                        time_out:         timeOutAm,
                        morning_status:   morningStatus,
                        time_in_af:       timeInPm,
                        time_out_af:      timeOutPm,
                        afternoon_status: afternoonStatus,
                        overtime_hours:   otHours,
                    }),
                });

                const contentType = res.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    console.error(`[Row ${empId}] Non-JSON response — status: ${res.status}, url: ${res.url}`);
                    failed++;
                    done++;
                    continue;
                }

                const json = await res.json();

                if (res.ok && json.success) {
                    row.classList.add('row-saved');
                    setTimeout(() => row.classList.remove('row-saved'), 700);
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
        btn.innerHTML      = '💾 Save All Attendance';
        prog.style.display = 'none';

        if (failed === 0) {
            showResult(result, `✅ All ${done} records saved!`, true);
        } else {
            showResult(result, `⚠️ ${done - failed} saved, ${failed} failed. Check console for details.`, false);
        }
    }

    // ═══════════ BULK SAVE — NSD ═══════════════════════════

    async function saveAllNsd() {
        const rows   = document.querySelectorAll('#nsdDataTable tbody tr[data-employee-id]');
        const btn    = document.getElementById('btnBulkNsd');
        const prog   = document.getElementById('nsdProgressWrap');
        const fill   = document.getElementById('nsdProgressFill');
        const text   = document.getElementById('nsdProgressText');
        const result = document.getElementById('nsdBulkResult');

        if (!rows.length) { showResult(result, 'No records to save.', false); return; }

        btn.disabled         = true;
        btn.innerHTML        = '⏳ Saving...';
        prog.style.display   = 'flex';
        result.style.display = 'none';

        let done = 0, failed = 0;

        for (const row of rows) {
            const empId    = row.dataset.employeeId;
            const nsdIn    = row.querySelector(`.nsd-time-in[data-employee="${empId}"]`)?.value  || null;
            const nsdOut   = row.querySelector(`.nsd-time-out[data-employee="${empId}"]`)?.value || null;
            const nsdHours = parseFloat(row.querySelector(`.nsd-hours[data-employee="${empId}"]`)?.value) || 0;

            if (!nsdIn && !nsdOut) { done++; continue; }

            try {
                const res = await fetch(STORE_NSD, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'Accept':       'application/json',
                    },
                    body: JSON.stringify({
                        employee_id:  empId,
                        date:         SELECTED_DATE,
                        nsd_time_in:  nsdIn,
                        nsd_time_out: nsdOut,
                        nsd_hours:    nsdHours,
                    }),
                });

                const contentType = res.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    console.error(`[NSD Row ${empId}] Non-JSON response — status: ${res.status}`);
                    failed++;
                    done++;
                    continue;
                }

                const json = await res.json();

                if (res.ok && json.success) {
                    row.classList.add('row-saved');
                    setTimeout(() => row.classList.remove('row-saved'), 700);
                } else {
                    console.warn(`[NSD Row ${empId}] Save failed:`, json);
                    failed++;
                }

            } catch (err) {
                console.error(`[NSD Row ${empId}] Fetch error:`, err);
                failed++;
            }

            done++;
            const pct = Math.round((done / rows.length) * 100);
            fill.style.width = pct + '%';
            text.textContent = `${done} / ${rows.length}`;
        }

        btn.disabled       = false;
        btn.innerHTML      = '💾 Save All NSD';
        prog.style.display = 'none';

        if (failed === 0) {
            showResult(result, `✅ NSD saved!`, true);
        } else {
            showResult(result, `⚠️ ${failed} failed. Check console for details.`, false);
        }
    }

    // ═══════════ RESULT HELPER ═════════════════════════════

    function showResult(el, msg, ok) {
        el.textContent   = msg;
        el.className     = ok ? 'ok' : 'err';
        el.style.display = 'block';
        setTimeout(() => { el.style.display = 'none'; }, 4000);
    }

    // ═══════════ VALIDATION ════════════════════════════════

    function validateTimeRange(input, minTime, maxTime, fieldName) {
        const time = input.value;
        if (!time) return true;
        if (time < minTime || time > maxTime) {
            input.classList.add('time-error');
            alert(`${fieldName} must be between ${minTime} and ${maxTime}`);
            input.value = '';
            input.classList.remove('time-error');
            return false;
        }
        input.classList.remove('time-error');
        return true;
    }

    function validateOTHours(input) {
        const hours = parseFloat(input.value);
        if (hours > 3) { alert('OT Hours cannot exceed 3 hours (6 PM - 9 PM limit)'); input.value = '3.00'; }
        if (hours < 0) input.value = '0.00';
    }

    function validateNSDTimeIn(input) {
        const time = input.value;
        if (!time) return true;
        if (time < '21:00') {
            input.classList.add('time-error');
            alert('NSD Time In must be 9:00 PM (21:00) or later');
            input.value = '';
            input.classList.remove('time-error');
            return false;
        }
        input.classList.remove('time-error');
        return true;
    }

    function validateNSDTimeOut(input) {
        const time = input.value;
        if (!time) return true;
        if (time > '06:00') {
            input.classList.add('time-error');
            alert('NSD Time Out must be 6:00 AM (06:00) or earlier');
            input.value = '';
            input.classList.remove('time-error');
            return false;
        }
        input.classList.remove('time-error');
        return true;
    }

    function calculateNSDHours(employeeId) {
        const timeIn  = document.querySelector(`.nsd-time-in[data-employee="${employeeId}"]`).value;
        const timeOut = document.querySelector(`.nsd-time-out[data-employee="${employeeId}"]`).value;
        const hoursEl = document.querySelector(`.nsd-hours[data-employee="${employeeId}"]`);
        if (!timeIn || !timeOut) { hoursEl.value = ''; return; }
        const [inH, inM]   = timeIn.split(':').map(Number);
        const [outH, outM] = timeOut.split(':').map(Number);
        let inDate  = new Date(); inDate.setHours(inH, inM, 0);
        let outDate = new Date(); outDate.setHours(outH, outM, 0);
        if (outH < inH) outDate.setDate(outDate.getDate() + 1);
        hoursEl.value = ((outDate - inDate) / 3600000).toFixed(2);
    }

    function setStatus(field, status) {
        const sessionClass = field.classList.contains('status-morning') ? 'status-morning' : 'status-afternoon';
        field.value     = status;
        field.className = `status-display ${sessionClass} status-` + status.toLowerCase().replace(/ /g, '-');
    }

    function updateMorningStatus(input) {
        const empId       = input.dataset.employee;
        const statusField = document.querySelector(`.status-morning[data-employee="${empId}"]`);
        const today       = new Date().toISOString().split('T')[0];
        if (!input.value) { setStatus(statusField, SELECTED_DATE < today ? 'Absent' : 'Unfilled'); return; }
        setStatus(statusField, 'Present');
    }

    function updateAfternoonStatus(input) {
        const empId       = input.dataset.employee;
        const statusField = document.querySelector(`.status-afternoon[data-employee="${empId}"]`);
        const today       = new Date().toISOString().split('T')[0];
        const row         = input.closest('tr');
        const timeInPm    = row.querySelector(`.time-in-pm[data-employee="${empId}"]`).value;
        if (!timeInPm) { setStatus(statusField, SELECTED_DATE < today ? 'Absent' : 'Unfilled'); return; }
        setStatus(statusField, 'Present');
    }

    window.addEventListener('DOMContentLoaded', function () {
        const today = new Date().toISOString().split('T')[0];
        markStatusForDate(SELECTED_DATE < today ? 'Absent' : 'Unfilled');
        document.querySelectorAll('.nsd-time-in').forEach(input => {
            if (input.value) calculateNSDHours(input.dataset.employee);
        });
    });

    function markStatusForDate(defaultStatus) {
        document.querySelectorAll('.time-in-am').forEach(input => {
            const f = document.querySelector(`.status-morning[data-employee="${input.dataset.employee}"]`);
            if (!f) return;
            const cur = f.value;
            if (cur === 'Present' && input.value) return;
            if (cur === 'Late'    && input.value) return;
            if (!input.value) setStatus(f, defaultStatus);
            else updateMorningStatus(input);
        });
        document.querySelectorAll('.time-in-pm').forEach(input => {
            const f = document.querySelector(`.status-afternoon[data-employee="${input.dataset.employee}"]`);
            if (!f) return;
            const cur = f.value;
            if (cur === 'Present' && input.value) return;
            if (cur === 'Late'    && input.value) return;
            if (!input.value) setStatus(f, defaultStatus);
            else updateAfternoonStatus(input);
        });
    }

    // ═══════════ TAB TOGGLE ════════════════════════════════

    function showRegular() {
        document.getElementById('regularTable').style.display  = 'block';
        document.getElementById('bulkSaveBar').style.display   = 'flex';
        document.getElementById('nsdTable').style.display      = 'none';
        document.getElementById('nsdSaveBar').style.display    = 'none';
        document.getElementById('regularBtn').classList.add('active');
        document.getElementById('nsdBtn').classList.remove('active');
    }

    function showNsd() {
        document.getElementById('regularTable').style.display  = 'none';
        document.getElementById('bulkSaveBar').style.display   = 'none';
        document.getElementById('nsdTable').style.display      = 'block';
        document.getElementById('nsdSaveBar').style.display    = 'flex';
        document.getElementById('nsdBtn').classList.add('active');
        document.getElementById('regularBtn').classList.remove('active');
    }

    function confirmLogout() {
        if (confirm('Are you sure you want to logout?')) document.getElementById('logoutForm').submit();
    }
</script>

@else
    <script>window.location.href = "{{ route('login') }}";</script>
@endif
</body>
</html>