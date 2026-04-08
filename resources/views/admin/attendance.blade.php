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

        .save-bar {
            display: flex;
            align-items: center;
            gap: 12px;
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
            white-space: nowrap;
        }
        .btn-bulk-save:hover    { opacity: .88; }
        .btn-bulk-save:disabled { opacity: .5; cursor: not-allowed; }

        .progress-wrap {
            display: none;
            align-items: center;
            gap: 10px;
            flex: 1;
        }
        .progress-track { flex:1; background:#e2e8f0; border-radius:6px; height:8px; overflow:hidden; }
        .progress-fill  { height:100%; background:linear-gradient(90deg,#6366f1,#4f46e5); width:0%; transition:width .3s; }
        .progress-text  { font-size:12px; color:#64748b; white-space:nowrap; }

        .bulk-result { display:none; font-size:13px; font-weight:600; padding:4px 12px; border-radius:6px; }
        .bulk-result.ok  { background:#d1fae5; color:#065f46; }
        .bulk-result.err { background:#fee2e2; color:#991b1b; }

        tr.row-saved td { animation: savedFlash .6s ease forwards; }
        @keyframes savedFlash { 0% { background:#d1fae5; } 100% { background:transparent; } }

        .pagination { margin-top:10px; padding:20px; display:flex; justify-content:center; gap:5px; flex-wrap:wrap; }
        .pagination a, .pagination span { padding:8px 12px; border:1px solid #ccc; text-decoration:none; color:#333; font-size:14px; border-radius:4px; transition:all .2s; }
        .pagination a:hover { background:linear-gradient(135deg,#667eea,#764ba2); color:white; border-color:#667eea; }
        .pagination .active { background:linear-gradient(135deg,#667eea,#764ba2); color:white; font-weight:bold; border-color:#667eea; }
        .pagination .disabled { color:#999; pointer-events:none; background:#f5f5f5; cursor:not-allowed; }

        .status-badge { display:inline-block; padding:4px 10px; border-radius:12px; font-size:11px; font-weight:700; white-space:nowrap; }
        .status-badge.present  { background:#d1fae5; color:#065f46; }
        .status-badge.half-day { background:#eff6ff; color:#1e40af; }
        .status-badge.absent   { background:#fee2e2; color:#991b1b; }
        .status-badge.unfilled { background:#f3f4f6; color:#6b7280; }

        /* OT layout toggle buttons */
        .ot-layout-bar { display:flex; align-items:center; gap:8px; margin-bottom:12px; }
        .ot-layout-bar span { font-size:13px; color:#6b7280; margin-right:4px; }
        .btn-layout {
            padding: 5px 14px;
            border: 1.5px solid #d1d5db;
            border-radius: 6px;
            background: #fff;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            color: #374151;
            transition: all .15s;
        }
        .btn-layout.active { background:linear-gradient(135deg,#3b82f6,#2563eb); border-color:#3b82f6; color:#fff; }
        .btn-layout:hover:not(.active) { border-color:#3b82f6; color:#2563eb; }

        /* Dual table dividers */
        #otDualTable .dual-divider  { border-left:3px solid #ffffff; }
        #nsdDualTable .dual-divider { border-left:3px solid #ffffff; }

        /* NSD layout toggle */
        .nsd-layout-bar { display:flex; align-items:center; gap:8px; margin-bottom:12px; }
        .nsd-layout-bar span { font-size:13px; color:#6b7280; margin-right:4px; }
        .btn-nsd-layout {
            padding: 5px 14px;
            border: 1.5px solid #d1d5db;
            border-radius: 6px;
            background: #fff;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            color: #374151;
            transition: all .15s;
        }
        .btn-nsd-layout.active { background:linear-gradient(135deg,#0ea5e9,#0284c7); border-color:#0ea5e9; color:#fff; }
        .btn-nsd-layout:hover:not(.active) { border-color:#0ea5e9; color:#0284c7; }

        /* Combined filter + save bar row */
        .filter-save-row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        .filter-save-row .controls {
            margin-bottom: 0 !important;
            flex: 1;
            min-width: 0;
        }
    </style>
</head>
<body>
@if(Session::has('user_id') && Session::get('role_id') == 2)

    @php
        $selectedDate  = request('date', now()->toDateString());
        $today         = now()->toDateString();
        $defaultStatus = ($selectedDate < $today) ? 'Absent' : 'Unfilled';
    @endphp

    @include('admin.partials.sidenav')

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

        {{-- TAB BUTTONS --}}
        <div class="toggle-container">
            <button id="regularBtn" class="toggle-btn active" onclick="showRegular()">☀ Regular</button>
            <button id="otBtn"      class="toggle-btn"        onclick="showOT()">⏰ Overtime</button>
            <button id="nsdBtn"     class="toggle-btn"        onclick="showNsd()">🌙 NSD</button>
        </div>

        {{-- ══════════ COMBINED FILTER + SAVE BAR ROW ══════════ --}}
        <div class="filter-save-row">

            <form action="{{ route('admin.attendance') }}" method="GET" class="controls">
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

            {{-- Regular save bar --}}
            <div id="regularSaveBar" class="save-bar" style="flex-shrink:0;">
                <div id="regProgressWrap" class="progress-wrap">
                    <div class="progress-track"><div id="regProgressFill" class="progress-fill"></div></div>
                    <span id="regProgressText" class="progress-text">0 / 0</span>
                </div>
                <div id="regBulkResult" class="bulk-result"></div>
                <button type="button" class="btn-bulk-save" id="btnBulkSave" onclick="saveAllRegular()">
                    💾 Save All Attendance
                </button>
            </div>

            {{-- OT save bar --}}
            <div id="otSaveBar" class="save-bar" style="display:none; flex-shrink:0;">
                <div id="otProgressWrap" class="progress-wrap">
                    <div class="progress-track"><div id="otProgressFill" class="progress-fill" style="background:linear-gradient(90deg,#3b82f6,#2563eb);"></div></div>
                    <span id="otProgressText" class="progress-text"></span>
                </div>
                <div id="otBulkResult" class="bulk-result"></div>
                <button type="button" class="btn-bulk-save" id="btnBulkOT" onclick="saveAllOT()" style="background:linear-gradient(135deg,#3b82f6,#2563eb);">
                    💾 Save All OT
                </button>
            </div>

            {{-- NSD save bar --}}
            <div id="nsdSaveBar" class="save-bar" style="display:none; flex-shrink:0;">
                <div id="nsdProgressWrap" class="progress-wrap">
                    <div class="progress-track"><div id="nsdProgressFill" class="progress-fill" style="background:linear-gradient(90deg,#0ea5e9,#0284c7);"></div></div>
                    <span id="nsdProgressText" class="progress-text"></span>
                </div>
                <div id="nsdBulkResult" class="bulk-result"></div>
                <button type="button" class="btn-bulk-save" id="btnBulkNsd" onclick="saveAllNsd()" style="background:linear-gradient(135deg,#0ea5e9,#0284c7);">
                    💾 Save All NSD
                </button>
            </div>

        </div>

        {{-- ══════════ REGULAR ══════════ --}}
        <div id="regularTable" class="table-wrapper">
            <table class="data-table" id="regularDataTable">
                <thead>
                    <tr>
                        <th>Name</th><th>Position</th>
                        <th>AM In (7-12)</th><th>AM Out (7-12)</th>
                        <th>PM In (1-5)</th><th>PM Out (1-5)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                    @php
                        $att          = $attendances[$employee->id] ?? null;
                        $morningVal   = $att->morning_status   ?? null;
                        $afternoonVal = $att->afternoon_status ?? null;
                        if (!$morningVal)   $morningVal   = ($att->time_in    ?? null) ? 'Present' : $defaultStatus;
                        if (!$afternoonVal) $afternoonVal = ($att->time_in_af ?? null) ? 'Present' : $defaultStatus;
                        $bothPresent    = in_array($morningVal,   ['Present','Late']) && in_array($afternoonVal, ['Present','Late']);
                        $nonePresent    = !in_array($morningVal,  ['Present','Late']) && !in_array($afternoonVal, ['Present','Late']);
                        $combinedStatus = $bothPresent ? 'Present' : ($nonePresent ? $defaultStatus : 'Half Day');
                        $badgeClass     = match($combinedStatus) { 'Present'=>'present','Half Day'=>'half-day','Absent'=>'absent',default=>'unfilled' };
                    @endphp
                    <tr data-employee-id="{{ $employee->id }}">
                        <td class="employee-name">
                            <div class="name-cell">
                                <div class="avatar">{{ strtoupper(substr($employee->first_name,0,1)) }}</div>
                                <div>
                                    <span>{{ $employee->first_name }} {{ $employee->last_name }}</span>
                                    @if($att && $att->updated_by)
                                        <div class="user-stamp">✏️ {{ $att->updated_by }} <span class="stamp-time">{{ \Carbon\Carbon::parse($att->updated_at)->format('M d, g:i A') }}</span></div>
                                    @else
                                        <div class="user-stamp user-stamp-empty">— not yet saved</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $employee->position }}</td>
                        <td><input type="time" class="time-input time-in-am reg-time-in-am" data-employee="{{ $employee->id }}" min="07:00" max="12:00" value="{{ $att->time_in ?? '' }}" onchange="validateTimeRange(this,'07:00','12:00','AM In'); updateCombinedStatus({{ $employee->id }});"></td>
                        <td><input type="time" class="time-input reg-time-out-am" data-employee="{{ $employee->id }}" min="07:00" max="12:00" value="{{ $att->time_out ?? '' }}" onchange="validateTimeRange(this,'07:00','12:00','AM Out')"></td>
                        <td><input type="time" class="time-input time-in-pm reg-time-in-pm" data-employee="{{ $employee->id }}" min="13:00" max="17:00" value="{{ $att->time_in_af ?? '' }}" onchange="validateTimeRange(this,'13:00','17:00','PM In'); updateCombinedStatus({{ $employee->id }});"></td>
                        <td><input type="time" class="time-input time-out-pm reg-time-out-pm" data-employee="{{ $employee->id }}" min="13:00" max="17:00" value="{{ $att->time_out_af ?? '' }}" onchange="validateTimeRange(this,'13:00','17:00','PM Out'); updateCombinedStatus({{ $employee->id }});"></td>
                        <td><span class="status-badge {{ $badgeClass }} reg-combined-status" data-employee="{{ $employee->id }}">{{ $combinedStatus }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="no-data">No employees found for this date.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ══════════ NSD ══════════ --}}
        <div id="nsdSection" style="display:none;">
            <div class="nsd-layout-bar">
                <span>Layout:</span>
                <button class="btn-nsd-layout active" id="btnNsdLayoutSingle" onclick="switchNSDLayout('single')">👤 Single</button>
                <button class="btn-nsd-layout"        id="btnNsdLayoutDual"   onclick="switchNSDLayout('dual')">👥 Dual (2/row)</button>
            </div>

            {{-- NSD Single layout --}}
            <div id="nsdSingleTable" class="table-wrapper">
                <table class="data-table" id="nsdDataTable">
                    <thead>
                        <tr>
                            <th>Name</th><th>Position</th>
                            <th>NSD Time In (9PM+)</th><th>NSD Time Out (6AM-)</th><th>NSD Hours (Auto)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employees as $employee)
                        <tr data-employee-id="{{ $employee->id }}">
                            <td class="employee-name">
                                <div class="name-cell">
                                    <div class="avatar">{{ strtoupper(substr($employee->first_name,0,1)) }}</div>
                                    <div>
                                        <span>{{ $employee->first_name }} {{ $employee->last_name }}</span>
                                        @if(isset($attendances[$employee->id]) && $attendances[$employee->id]->nsd_updated_by)
                                            <div class="user-stamp">✏️ {{ $attendances[$employee->id]->nsd_updated_by }} <span class="stamp-time">{{ \Carbon\Carbon::parse($attendances[$employee->id]->updated_at)->format('M d, g:i A') }}</span></div>
                                        @else
                                            <div class="user-stamp user-stamp-empty">— not yet saved</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>{{ $employee->position }}</td>
                            <td><input type="time" class="time-input nsd-time-in" data-employee="{{ $employee->id }}" value="{{ isset($attendances[$employee->id]) ? substr($attendances[$employee->id]->nsd_time_in,0,5) : '' }}" onchange="validateNSDTimeIn(this); calculateNSDHours({{ $employee->id }})"></td>
                            <td><input type="time" class="time-input nsd-time-out" data-employee="{{ $employee->id }}" value="{{ isset($attendances[$employee->id]) ? substr($attendances[$employee->id]->nsd_time_out,0,5) : '' }}" onchange="validateNSDTimeOut(this); calculateNSDHours({{ $employee->id }})"></td>
                            <td><input type="number" step="0.01" class="ot-input nsd-hours" data-employee="{{ $employee->id }}" placeholder="0.00" value="{{ isset($attendances[$employee->id]) ? $attendances[$employee->id]->nsd_hours : '' }}" readonly style="background:#f3f4f6;"></td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="no-data">No employees found for this date.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- NSD Dual layout (2 persons per row) — built by JS --}}
            <div id="nsdDualTable" class="table-wrapper" style="display:none;">
                <table class="data-table" id="nsdDualDataTable">
                    <thead>
                        <tr>
                            <th>Name</th><th>Position</th><th>NSD In</th><th>NSD Out</th><th>NSD Hrs</th>
                            <th class="dual-divider">Name</th><th>Position</th><th>NSD In</th><th>NSD Out</th><th>NSD Hrs</th>
                        </tr>
                    </thead>
                    <tbody id="nsdDualBody"></tbody>
                </table>
            </div>
        </div>

        {{-- ══════════ OVERTIME ══════════ --}}
        <div id="otSection" style="display:none;">
            <div class="ot-layout-bar">
                <span>Layout:</span>
                <button class="btn-layout active" id="btnLayoutSingle" onclick="switchOTLayout('single')">👤 Single</button>
                <button class="btn-layout"        id="btnLayoutDual"   onclick="switchOTLayout('dual')">👥 Dual (2/row)</button>
            </div>

            {{-- Single layout --}}
            <div id="otSingleTable" class="table-wrapper">
                <table class="data-table" id="otDataTable">
                    <thead>
                        <tr>
                            <th>Name</th><th>Position</th>
                            <th>OT In (5-9PM)</th><th>OT Out (5-9PM)</th><th>OT Hours (Auto)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employees as $employee)
                        @php $att = $attendances[$employee->id] ?? null; @endphp
                        <tr data-employee-id="{{ $employee->id }}">
                            <td class="employee-name">
                                <div class="name-cell">
                                    <div class="avatar">{{ strtoupper(substr($employee->first_name,0,1)) }}</div>
                                    <div>
                                        <span>{{ $employee->first_name }} {{ $employee->last_name }}</span>
                                        @if($att && $att->updated_by)
                                            <div class="user-stamp">✏️ {{ $att->updated_by }} <span class="stamp-time">{{ \Carbon\Carbon::parse($att->updated_at)->format('M d, g:i A') }}</span></div>
                                        @else
                                            <div class="user-stamp user-stamp-empty">— not yet saved</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>{{ $employee->position }}</td>
                            <td><input type="time" class="time-input ot-time-in" data-employee="{{ $employee->id }}" value="{{ $att && $att->ot_time_in ? substr($att->ot_time_in,0,5) : '' }}" onchange="validateOTTime(this,'OT In'); calculateOTHours({{ $employee->id }});"></td>
                            <td><input type="time" class="time-input ot-time-out" data-employee="{{ $employee->id }}" value="{{ $att && $att->ot_time_out ? substr($att->ot_time_out,0,5) : '' }}" onchange="validateOTTime(this,'OT Out'); calculateOTHours({{ $employee->id }});"></td>
                            <td><input type="number" step="0.01" class="ot-input ot-hours" data-employee="{{ $employee->id }}" placeholder="0.00" min="0" max="3" value="{{ $att ? $att->overtime_hours : '' }}" readonly style="background:#f3f4f6;" title="Auto-calculated from OT In/Out"></td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="no-data">No employees found for this date.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Dual layout (2 persons per row) — built by JS --}}
            <div id="otDualTable" class="table-wrapper" style="display:none;">
                <table class="data-table" id="otDualDataTable">
                    <thead>
                        <tr>
                            <th>Name</th><th>Position</th><th>OT In</th><th>OT Out</th><th>OT Hrs</th>
                            <th class="dual-divider">Name</th><th>Position</th><th>OT In</th><th>OT Out</th><th>OT Hrs</th>
                        </tr>
                    </thead>
                    <tbody id="otDualBody"></tbody>
                </table>
            </div>
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

    </div>

<script>
    const CSRF          = document.querySelector('meta[name="csrf-token"]').content;
    const STORE_URL     = "{{ route('admin.attendance.store') }}";
    const STORE_NSD     = "{{ route('admin.attendance.storeNsd') }}";
    const SELECTED_DATE = "{{ $selectedDate }}";

    // ═══════════ OT HOURS AUTO-CALCULATE ══════════════════

    function calculateOTHours(employeeId) {
        const otIn    = document.querySelector(`.ot-time-in[data-employee="${employeeId}"]`)?.value;
        const otOut   = document.querySelector(`.ot-time-out[data-employee="${employeeId}"]`)?.value;
        const hoursEl = document.querySelector(`.ot-hours[data-employee="${employeeId}"]`);
        if (!hoursEl) return;
        if (!otIn || !otOut) { hoursEl.value = ''; syncDualHours(employeeId, ''); return; }
        const [inH, inM]   = otIn.split(':').map(Number);
        const [outH, outM] = otOut.split(':').map(Number);
        const inMins  = inH * 60 + inM;
        const outMins = outH * 60 + outM;
        if (outMins <= inMins) { hoursEl.value = ''; syncDualHours(employeeId, ''); return; }
        const hrs = Math.min((outMins - inMins) / 60, 3).toFixed(2);
        hoursEl.value = hrs;
        syncDualHours(employeeId, hrs);
    }

    function syncDualHours(empId, val) {
        const el = document.querySelector(`.ot-dual-hours[data-employee="${empId}"]`);
        if (el) el.value = val;
    }

    // ═══════════ COMBINED STATUS ══════════════════════════

    function updateCombinedStatus(employeeId) {
        const today    = new Date().toISOString().split('T')[0];
        const fallback = SELECTED_DATE < today ? 'Absent' : 'Unfilled';
        const amIn     = document.querySelector(`.reg-time-in-am[data-employee="${employeeId}"]`)?.value;
        const pmIn     = document.querySelector(`.reg-time-in-pm[data-employee="${employeeId}"]`)?.value;
        let status, cls;
        if (amIn && pmIn)         { status = 'Present';  cls = 'present'; }
        else if (!amIn && !pmIn)  { status = fallback;   cls = fallback === 'Absent' ? 'absent' : 'unfilled'; }
        else                      { status = 'Half Day'; cls = 'half-day'; }
        const badge = document.querySelector(`.reg-combined-status[data-employee="${employeeId}"]`);
        if (badge) { badge.textContent = status; badge.className = `status-badge ${cls} reg-combined-status`; badge.dataset.employee = employeeId; }
    }

    // ═══════════ DUAL TABLE BUILD & SYNC ══════════════════

    function buildDualTable() {
        const rows  = [...document.querySelectorAll('#otDataTable tbody tr[data-employee-id]')];
        const tbody = document.getElementById('otDualBody');
        tbody.innerHTML = '';
        for (let i = 0; i < rows.length; i += 2) {
            const tr = document.createElement('tr');
            tr.innerHTML = makeDualCells(rows[i], false) + makeDualCells(rows[i+1] || null, true);
            tbody.appendChild(tr);
        }
    }

    function makeDualCells(row, isDivider) {
        if (!row) {
            return `<td ${isDivider ? 'class="dual-divider"' : ''} colspan="5" style="background:#fafafa;"></td>`;
        }
        const empId = row.dataset.employeeId;
        const name  = row.querySelector('.employee-name span')?.textContent?.trim() || '—';
        const pos   = row.cells[1]?.textContent?.trim() || '—';
        const otIn  = row.querySelector('.ot-time-in')?.value  || '';
        const otOut = row.querySelector('.ot-time-out')?.value || '';
        const otHrs = row.querySelector('.ot-hours')?.value    || '';
        const d     = isDivider ? 'class="dual-divider"' : '';
        return `
            <td ${d} style="font-size:13px;font-weight:600;white-space:nowrap;">${name}</td>
            <td style="font-size:12px;color:#6b7280;">${pos}</td>
            <td><input type="time" class="time-input ot-dual-in" data-employee="${empId}"
                       value="${otIn}" style="width:105px;"
                       onchange="syncFromDual(${empId},'in',this.value);"></td>
            <td><input type="time" class="time-input ot-dual-out" data-employee="${empId}"
                       value="${otOut}" style="width:105px;"
                       onchange="syncFromDual(${empId},'out',this.value);"></td>
            <td><input type="number" step="0.01" class="ot-input ot-dual-hours" data-employee="${empId}"
                       placeholder="0.00" value="${otHrs}" readonly style="background:#f3f4f6;width:65px;"></td>`;
    }

    function syncFromDual(empId, field, val) {
        const sel = field === 'in' ? `.ot-time-in[data-employee="${empId}"]` : `.ot-time-out[data-employee="${empId}"]`;
        const el  = document.querySelector(sel);
        if (el) el.value = val;
        calculateOTHours(empId);
    }

    function switchOTLayout(layout) {
        if (layout === 'single') {
            document.getElementById('otSingleTable').style.display = 'block';
            document.getElementById('otDualTable').style.display   = 'none';
            document.getElementById('btnLayoutSingle').classList.add('active');
            document.getElementById('btnLayoutDual').classList.remove('active');
        } else {
            buildDualTable();
            document.getElementById('otSingleTable').style.display = 'none';
            document.getElementById('otDualTable').style.display   = 'block';
            document.getElementById('btnLayoutDual').classList.add('active');
            document.getElementById('btnLayoutSingle').classList.remove('active');
        }
    }

    // ═══════════ BULK SAVE — REGULAR ═══════════════════════

    async function saveAllRegular() {
        const rows   = document.querySelectorAll('#regularDataTable tbody tr[data-employee-id]');
        const btn    = document.getElementById('btnBulkSave');
        const fill   = document.getElementById('regProgressFill');
        const text   = document.getElementById('regProgressText');
        const prog   = document.getElementById('regProgressWrap');
        const result = document.getElementById('regBulkResult');
        if (!rows.length) { showResult(result,'No records to save.',false); return; }
        btn.disabled = true; btn.innerHTML = '⏳ Saving...';
        prog.style.display = 'flex'; result.style.display = 'none';
        const today = new Date().toISOString().split('T')[0];
        let done = 0, failed = 0, skipped = 0;
        for (const row of rows) {
            const empId     = row.dataset.employeeId;
            const timeInAm  = row.querySelector('.reg-time-in-am')?.value  || null;
            const timeOutAm = row.querySelector('.reg-time-out-am')?.value || null;
            const timeInPm  = row.querySelector('.reg-time-in-pm')?.value  || null;
            const timeOutPm = row.querySelector('.reg-time-out-pm')?.value || null;
            if (!timeInAm && !timeOutAm && !timeInPm && !timeOutPm) {
                skipped++; done++;
                fill.style.width = Math.round((done/rows.length)*100)+'%'; text.textContent=`${done} / ${rows.length}`; continue;
            }
            const def = SELECTED_DATE < today ? 'Absent' : 'Unfilled';
            try {
                const res  = await fetch(STORE_URL, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'}, body:JSON.stringify({ employee_id:empId, date:SELECTED_DATE, time_in:timeInAm, time_out:timeOutAm, morning_status:timeInAm?'Present':def, time_in_af:timeInPm, time_out_af:timeOutPm, afternoon_status:timeInPm?'Present':def }) });
                const json = await res.json();
                if (res.ok && json.success) { row.classList.add('row-saved'); setTimeout(()=>row.classList.remove('row-saved'),700); }
                else { failed++; console.warn(`[Reg ${empId}]`,json); }
            } catch(e) { failed++; console.error(`[Reg ${empId}]`,e); }
            done++; fill.style.width=Math.round((done/rows.length)*100)+'%'; text.textContent=`${done} / ${rows.length}`;
        }
        btn.disabled=false; btn.innerHTML='💾 Save All Attendance'; prog.style.display='none';
        const saved = done-failed-skipped;
        showResult(result, failed===0?`✅ ${saved} saved, ${skipped} skipped.`:`⚠️ ${saved} saved, ${failed} failed, ${skipped} skipped.`, failed===0);
    }

    // ═══════════ BULK SAVE — NSD ═══════════════════════════

    async function saveAllNsd() {
        const rows   = document.querySelectorAll('#nsdDataTable tbody tr[data-employee-id]');
        const btn    = document.getElementById('btnBulkNsd');
        const fill   = document.getElementById('nsdProgressFill');
        const text   = document.getElementById('nsdProgressText');
        const prog   = document.getElementById('nsdProgressWrap');
        const result = document.getElementById('nsdBulkResult');
        if (!rows.length) { showResult(result,'No records to save.',false); return; }
        btn.disabled=true; btn.innerHTML='⏳ Saving...'; prog.style.display='flex'; result.style.display='none';
        let done=0, failed=0, skipped=0;
        for (const row of rows) {
            const empId    = row.dataset.employeeId;
            const nsdIn    = row.querySelector('.nsd-time-in')?.value  || null;
            const nsdOut   = row.querySelector('.nsd-time-out')?.value || null;
            const nsdHours = parseFloat(row.querySelector('.nsd-hours')?.value) || 0;
            if (!nsdIn && !nsdOut) { skipped++; done++; fill.style.width=Math.round((done/rows.length)*100)+'%'; text.textContent=`${done} / ${rows.length}`; continue; }
            try {
                const res  = await fetch(STORE_NSD, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'}, body:JSON.stringify({ employee_id:empId, date:SELECTED_DATE, nsd_time_in:nsdIn, nsd_time_out:nsdOut, nsd_hours:nsdHours }) });
                const json = await res.json();
                if (res.ok && json.success) { row.classList.add('row-saved'); setTimeout(()=>row.classList.remove('row-saved'),700); }
                else { failed++; console.warn(`[NSD ${empId}]`,json); }
            } catch(e) { failed++; console.error(`[NSD ${empId}]`,e); }
            done++; fill.style.width=Math.round((done/rows.length)*100)+'%'; text.textContent=`${done} / ${rows.length}`;
        }
        btn.disabled=false; btn.innerHTML='💾 Save All NSD'; prog.style.display='none';
        const saved=done-failed-skipped;
        showResult(result, failed===0?`✅ ${saved} NSD saved, ${skipped} skipped.`:`⚠️ ${saved} saved, ${failed} failed, ${skipped} skipped.`, failed===0);
    }

    // ═══════════ BULK SAVE — OT ════════════════════════════

    async function saveAllOT() {
        const rows   = document.querySelectorAll('#otDataTable tbody tr[data-employee-id]');
        const btn    = document.getElementById('btnBulkOT');
        const fill   = document.getElementById('otProgressFill');
        const text   = document.getElementById('otProgressText');
        const prog   = document.getElementById('otProgressWrap');
        const result = document.getElementById('otBulkResult');
        if (!rows.length) { showResult(result,'No records to save.',false); return; }
        btn.disabled=true; btn.innerHTML='⏳ Saving...'; prog.style.display='flex'; result.style.display='none';
        let done=0, failed=0, skipped=0;
        for (const row of rows) {
            const empId   = row.dataset.employeeId;
            const otInEl  = row.querySelector('.ot-time-in');
            const otOutEl = row.querySelector('.ot-time-out');
            const otIn    = (otInEl?.value  || otInEl?.getAttribute('value')  || '').trim() || null;
            const otOut   = (otOutEl?.value || otOutEl?.getAttribute('value') || '').trim() || null;
            const otHours = parseFloat(row.querySelector('.ot-hours')?.value) || 0;
            if (!otIn && !otOut) { skipped++; done++; fill.style.width=Math.round((done/rows.length)*100)+'%'; text.textContent=`${done} / ${rows.length}`; continue; }
            try {
                const res  = await fetch(STORE_URL, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'}, body:JSON.stringify({ employee_id:empId, date:SELECTED_DATE, ot_time_in:otIn, ot_time_out:otOut, overtime_hours:otHours }) });
                const json = await res.json();
                if (res.ok && json.success) { row.classList.add('row-saved'); setTimeout(()=>row.classList.remove('row-saved'),700); }
                else { failed++; console.warn(`[OT ${empId}]`,json); }
            } catch(e) { failed++; console.error(`[OT ${empId}]`,e); }
            done++; fill.style.width=Math.round((done/rows.length)*100)+'%'; text.textContent=`${done} / ${rows.length}`;
        }
        btn.disabled=false; btn.innerHTML='💾 Save All OT'; prog.style.display='none';
        const saved=done-failed-skipped;
        showResult(result, failed===0?`✅ ${saved} OT saved, ${skipped} skipped.`:`⚠️ ${saved} saved, ${failed} failed, ${skipped} skipped.`, failed===0);
    }

    // ═══════════ NSD LAYOUT TOGGLE ════════════════════════

    function buildNSDDualTable() {
        const rows  = [...document.querySelectorAll('#nsdDataTable tbody tr[data-employee-id]')];
        const tbody = document.getElementById('nsdDualBody');
        tbody.innerHTML = '';
        for (let i = 0; i < rows.length; i += 2) {
            const tr = document.createElement('tr');
            tr.innerHTML = makeNSDDualCells(rows[i], false) + makeNSDDualCells(rows[i+1] || null, true);
            tbody.appendChild(tr);
        }
    }

    function makeNSDDualCells(row, isDivider) {
        if (!row) {
            return `<td ${isDivider ? 'class="dual-divider"' : ''} colspan="5" style="background:#fafafa;"></td>`;
        }
        const empId  = row.dataset.employeeId;
        const name   = row.querySelector('.employee-name span')?.textContent?.trim() || '—';
        const pos    = row.cells[1]?.textContent?.trim() || '—';
        const nsdIn  = row.querySelector('.nsd-time-in')?.value  || '';
        const nsdOut = row.querySelector('.nsd-time-out')?.value || '';
        const nsdHrs = row.querySelector('.nsd-hours')?.value    || '';
        const d      = isDivider ? 'class="dual-divider"' : '';
        return `
            <td ${d} style="font-size:13px;font-weight:600;white-space:nowrap;">${name}</td>
            <td style="font-size:12px;color:#6b7280;">${pos}</td>
            <td><input type="time" class="time-input nsd-dual-in" data-employee="${empId}"
                       value="${nsdIn}" style="width:105px;"
                       onchange="syncFromNSDDual(${empId},'in',this.value);"></td>
            <td><input type="time" class="time-input nsd-dual-out" data-employee="${empId}"
                       value="${nsdOut}" style="width:105px;"
                       onchange="syncFromNSDDual(${empId},'out',this.value);"></td>
            <td><input type="number" step="0.01" class="ot-input nsd-dual-hours" data-employee="${empId}"
                       placeholder="0.00" value="${nsdHrs}" readonly style="background:#f3f4f6;width:65px;"></td>`;
    }

    function syncFromNSDDual(empId, field, val) {
        const sel = field === 'in' ? `.nsd-time-in[data-employee="${empId}"]` : `.nsd-time-out[data-employee="${empId}"]`;
        const el  = document.querySelector(sel);
        if (el) { el.value = val; calculateNSDHours(empId); }
        const dualHours = document.querySelector(`.nsd-dual-hours[data-employee="${empId}"]`);
        const singleHours = document.querySelector(`.nsd-hours[data-employee="${empId}"]`)?.value || '';
        if (dualHours) dualHours.value = singleHours;
    }

    function switchNSDLayout(layout) {
        if (layout === 'single') {
            document.getElementById('nsdSingleTable').style.display = 'block';
            document.getElementById('nsdDualTable').style.display   = 'none';
            document.getElementById('btnNsdLayoutSingle').classList.add('active');
            document.getElementById('btnNsdLayoutDual').classList.remove('active');
        } else {
            buildNSDDualTable();
            document.getElementById('nsdSingleTable').style.display = 'none';
            document.getElementById('nsdDualTable').style.display   = 'block';
            document.getElementById('btnNsdLayoutDual').classList.add('active');
            document.getElementById('btnNsdLayoutSingle').classList.remove('active');
        }
    }

    // ═══════════ HELPERS ═══════════════════════════════════

    function showResult(el, msg, ok) {
        el.textContent = msg; el.className = ok ? 'bulk-result ok' : 'bulk-result err';
        el.style.display = 'block'; setTimeout(()=>{ el.style.display='none'; }, 4000);
    }

    // ═══════════ VALIDATION ════════════════════════════════

    function validateTimeRange(input, minTime, maxTime, fieldName) {
        const time = input.value; if (!time) return true;
        if (time < minTime || time > maxTime) {
            input.classList.add('time-error'); alert(`${fieldName} must be between ${minTime} and ${maxTime}`);
            input.value=''; input.classList.remove('time-error'); return false;
        }
        input.classList.remove('time-error'); return true;
    }

    function validateOTTime(input, fieldName) {
        const time = input.value; if (!time) return true;
        if (time < '17:00' || time > '21:00') {
            input.classList.add('time-error'); alert(`${fieldName} must be between 5:00 PM (18:00) and 9:00 PM (21:00)`);
            input.value=''; input.classList.remove('time-error'); return false;
        }
        input.classList.remove('time-error'); return true;
    }

    function validateNSDTimeIn(input) {
        const time = input.value; if (!time) return true;
        if (time < '21:00') { input.classList.add('time-error'); alert('NSD Time In must be 9:00 PM (21:00) or later'); input.value=''; input.classList.remove('time-error'); return false; }
        input.classList.remove('time-error'); return true;
    }

    function validateNSDTimeOut(input) {
        const time = input.value; if (!time) return true;
        if (time > '06:00') { input.classList.add('time-error'); alert('NSD Time Out must be 6:00 AM (06:00) or earlier'); input.value=''; input.classList.remove('time-error'); return false; }
        input.classList.remove('time-error'); return true;
    }

    function calculateNSDHours(employeeId) {
        const timeIn  = document.querySelector(`.nsd-time-in[data-employee="${employeeId}"]`).value;
        const timeOut = document.querySelector(`.nsd-time-out[data-employee="${employeeId}"]`).value;
        const hoursEl = document.querySelector(`.nsd-hours[data-employee="${employeeId}"]`);
        if (!timeIn || !timeOut) { hoursEl.value=''; return; }
        const [inH,inM]=[...timeIn.split(':').map(Number)], [outH,outM]=[...timeOut.split(':').map(Number)];
        let inDate=new Date(); inDate.setHours(inH,inM,0);
        let outDate=new Date(); outDate.setHours(outH,outM,0);
        if (outH < inH) outDate.setDate(outDate.getDate()+1);
        hoursEl.value = ((outDate-inDate)/3600000).toFixed(2);
    }

    // ═══════════ ON LOAD ═══════════════════════════════════

    window.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.ot-time-in').forEach(el => { if (el.value) calculateOTHours(el.dataset.employee); });
        document.querySelectorAll('.nsd-time-in').forEach(el => { if (el.value) calculateNSDHours(el.dataset.employee); });
        document.querySelectorAll('.reg-time-in-am').forEach(el => updateCombinedStatus(el.dataset.employee));
    });

    // ═══════════ TAB TOGGLE ════════════════════════════════

    function showRegular() {
        document.getElementById('regularTable').style.display   = 'block';
        document.getElementById('regularSaveBar').style.display = 'flex';
        document.getElementById('nsdSection').style.display     = 'none';
        document.getElementById('nsdSaveBar').style.display     = 'none';
        document.getElementById('otSection').style.display      = 'none';
        document.getElementById('otSaveBar').style.display      = 'none';
        document.getElementById('regularBtn').classList.add('active');
        document.getElementById('nsdBtn').classList.remove('active');
        document.getElementById('otBtn').classList.remove('active');
    }
    function showNsd() {
        document.getElementById('regularTable').style.display   = 'none';
        document.getElementById('regularSaveBar').style.display = 'none';
        document.getElementById('nsdSection').style.display     = 'block';
        document.getElementById('nsdSaveBar').style.display     = 'flex';
        document.getElementById('otSection').style.display      = 'none';
        document.getElementById('otSaveBar').style.display      = 'none';
        document.getElementById('nsdBtn').classList.add('active');
        document.getElementById('regularBtn').classList.remove('active');
        document.getElementById('otBtn').classList.remove('active');
    }
    function showOT() {
        document.getElementById('regularTable').style.display   = 'none';
        document.getElementById('regularSaveBar').style.display = 'none';
        document.getElementById('nsdSection').style.display     = 'none';
        document.getElementById('nsdSaveBar').style.display     = 'none';
        document.getElementById('otSection').style.display      = 'block';
        document.getElementById('otSaveBar').style.display      = 'flex';
        document.getElementById('otBtn').classList.add('active');
        document.getElementById('regularBtn').classList.remove('active');
        document.getElementById('nsdBtn').classList.remove('active');
    }
</script>

@else
    <script>window.location.href = "{{ route('login') }}";</script>
@endif
</body>
</html>