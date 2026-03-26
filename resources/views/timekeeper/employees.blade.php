<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/superadmin/employee.css') }}">
    <style>
        /* ── Controls bar ── */
        .controls-bar { display:flex; gap:10px; align-items:center; margin-bottom:20px; flex-wrap:wrap; }
        .search-input  { padding:8px 14px; border:1px solid #ccc; border-radius:20px; font-size:14px; width:220px; outline:none; }
        .search-input:focus { border-color:#6c63ff; }
        .position-select { padding:8px 14px; border:1px solid #ccc; border-radius:6px; font-size:14px; background:#fff; outline:none; cursor:pointer; }

        .btn-add-person {
            padding:9px 20px; border:none; border-radius:6px; font-size:14px; font-weight:700;
            cursor:pointer; background:#28a745; color:#fff; transition:opacity .2s;
        }
        .btn-add-person:hover { opacity:.88; }

        .btn-export {
            padding:9px 20px; border:none; border-radius:6px; font-size:14px; font-weight:700;
            cursor:pointer; background:#dc3545; color:#fff; transition:opacity .2s;
        }
        .btn-export:hover { opacity:.88; }

        /* ── Table ── */
        .table-wrapper { background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.08); }
        table { width:100%; border-collapse:collapse; }
        thead { background:#6c63ff; color:#fff; }
        th { padding:13px 16px; text-align:left; font-size:13px; font-weight:600; }
        td { padding:12px 16px; font-size:14px; color:#444; border-bottom:1px solid #f0f0f0; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:#f5f3ff; cursor:pointer; }

        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:700; }
        .badge-rated   { background:#d1fae5; color:#065f46; }
        .badge-pending { background:#fef3c7; color:#92400e; }

        .edit-btn {
            padding:6px 14px; border:none; border-radius:6px; font-size:13px; font-weight:600;
            cursor:pointer; background:#6c63ff; color:#fff; transition:opacity .2s;
        }
        .edit-btn:hover { opacity:.85; }

        .archive-btn {
            padding:6px 14px; border:none; border-radius:6px; font-size:13px; font-weight:600;
            cursor:pointer; background:#dc3545; color:#fff; transition:opacity .2s;
        }
        .archive-btn:hover { opacity:.85; }

        .no-records { text-align:center; padding:30px; color:#999; font-size:14px; }

        /* ── Pagination ── */
        .pagination { padding:20px; display:flex; justify-content:center; gap:5px; flex-wrap:wrap; background:#fff; border-top:1px solid #f0f0f0; }
        .pagination a, .pagination span { padding:8px 12px; border:1px solid #ccc; text-decoration:none; color:#333; font-size:14px; border-radius:4px; transition:all .2s; }
        .pagination a:hover { background:#6c63ff; color:#fff; border-color:#6c63ff; }
        .pagination .active  { background:#6c63ff; color:#fff; font-weight:bold; border-color:#6c63ff; }
        .pagination .disabled { color:#999; pointer-events:none; background:#f5f5f5; }

        /* ── Modal overlay ── */
        .modal-overlay {
            display:none; position:fixed; inset:0;
            background:rgba(0,0,0,.55); z-index:1050;
            justify-content:center; align-items:flex-start;
            padding:30px 20px; overflow-y:auto;
        }
        .modal-box {
            background:#fff; border-radius:12px; width:100%; max-width:660px;
            max-height:90vh; overflow-y:auto;
            box-shadow:0 20px 60px rgba(0,0,0,.25);
            padding:28px; position:relative;
            animation:modalIn .2s ease; margin:auto;
        }
        .modal-box.large { max-width:860px; }
        @keyframes modalIn { from{transform:translateY(-20px);opacity:0} to{transform:translateY(0);opacity:1} }

        .modal-close { position:absolute;top:14px;right:18px;background:none;border:none;font-size:22px;cursor:pointer;color:#6b7280; }
        .modal-close:hover { color:#111; }
        .modal-title { font-size:18px; font-weight:700; margin:0 0 4px; color:#1f2937; }

        /* ── Archive modal specific ── */
        .archive-option {
            display:flex; align-items:center; padding:14px 16px;
            border:2px solid #e5e7eb; border-radius:10px; margin-bottom:10px;
            cursor:pointer; transition:all .15s;
        }
        .archive-option input[type="radio"] { margin-right:14px; width:18px; height:18px; cursor:pointer; accent-color:#6c63ff; }
        .archive-option.terminated { border-color:#dc3545; }
        .archive-option.terminated:hover { background:#fff5f5; }
        .archive-option.inactive   { border-color:#ffc107; }
        .archive-option.inactive:hover   { background:#fffbf0; }
        .archive-label strong { display:block; font-size:15px; margin-bottom:3px; }
        .archive-label small  { color:#6b7280; font-size:13px; }

        textarea.archive-reason {
            width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px;
            font-size:14px; resize:vertical; min-height:80px; font-family:inherit;
        }
        textarea.archive-reason:focus { border-color:#6c63ff; outline:none; }

        /* ── Form grid (shared add/edit) ── */
        .form-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:14px; margin-bottom:8px; }
        .form-group { display:flex; flex-direction:column; gap:5px; }
        .form-group label { font-size:13px; font-weight:600; color:#374151; }
        .form-group input, .form-group select {
            padding:9px 12px; border:1.5px solid #d1d5db; border-radius:8px;
            font-size:14px; color:#1f2937; background:#fff; transition:border .2s;
        }
        .form-group input:focus, .form-group select:focus { outline:none; border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.15); }

        .modal-section-title { font-weight:700; color:#374151; margin:16px 0 12px; font-size:13px; text-transform:uppercase; letter-spacing:.5px; }

        .modal-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:20px; padding-top:16px; border-top:1px solid #e5e7eb; }
        .btn-cancel { padding:9px 20px; border-radius:8px; border:none; cursor:pointer; background:#e5e7eb; color:#374151; font-size:14px; font-weight:600; }
        .btn-save   { padding:9px 20px; border-radius:8px; border:none; cursor:pointer; background:linear-gradient(180deg,#6366f1 0%,#4f46e5 100%); color:#fff; font-size:14px; font-weight:600; }
        .btn-cancel:hover, .btn-save:hover { opacity:.88; }

        /* ── Toast ── */
        #toastNotif { position:fixed;bottom:24px;right:24px;z-index:9999;padding:12px 20px;border-radius:8px;font-size:14px;font-weight:600;color:#fff;opacity:0;transition:opacity .3s;pointer-events:none; }
        #toastNotif.show { opacity:1; }
        #toastNotif.success { background:#16a34a; }
        #toastNotif.error   { background:#dc2626; }
    </style>
</head>

<body>
@if(Session::has('user_id') && Session::get('role_id') == 3)
    <link rel="stylesheet" href="{{ asset('css/partials/sidenav.css') }}">
    
    @include('timekeeper.partials.sidenav')
    <div class="main-content">
        <div class="header">
            <h1 class="page-title"><span class="icon">👔</span> Employees</h1>
        </div>

        @if(session('success'))
            <div style="background:#d1fae5;border-left:4px solid #10b981;color:#065f46;padding:12px 18px;border-radius:8px;margin-bottom:16px;font-size:14px;">
                ✅ {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div style="background:#fee2e2;border-left:4px solid #ef4444;color:#7f1d1d;padding:12px 18px;border-radius:8px;margin-bottom:16px;font-size:14px;">
                ❌ {{ $errors->first() }}
            </div>
        @endif

        {{-- Controls bar matching screenshot --}}
        <div class="controls-bar">
            <input type="text" id="searchInput" class="search-input"
                   placeholder="🔍 Search by Name" value="{{ request('search') }}">

            <select id="positionFilter" class="position-select">
                <option value="">Filter by Position</option>
                @foreach($positions as $pos)
                    <option value="{{ strtolower($pos) }}" {{ request('position') == $pos ? 'selected' : '' }}>
                        {{ $pos }}
                    </option>
                @endforeach
            </select>

            <button type="button" class="btn-add-person" onclick="openAddModal()">➕ Add Person</button>
            <button type="button" class="btn-export">📤 Export</button>
        </div>

        {{-- Table --}}
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Position</th>
                        <th>Gender</th>
                        <th>Rating</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="employeeTableBody">
                    @forelse($employees as $employee)
                        <tr id="emp-row-{{ $employee->id }}"
                            class="clickable-row"
                            data-user='@json($employee)'
                            data-name="{{ strtolower($employee->first_name . ' ' . $employee->last_name) }}"
                            data-position="{{ strtolower($employee->position ?? '') }}">
                            <td>{{ $employee->id }}</td>
                            <td>{{ $employee->first_name }} {{ $employee->last_name }}</td>
                            <td>{{ $employee->position ?? 'N/A' }}</td>
                            <td>{{ $employee->gender ?? 'N/A' }}</td>
                            <td>
                                @if(!empty($employee->rating) && $employee->rating !== 'n/a')
                                    <span class="badge badge-rated">{{ $employee->rating }}</span>
                                @else
                                    <span class="badge badge-pending">n/a</span>
                                @endif
                            </td>
                            <td style="display:flex; gap:6px;">
                                <button class="edit-btn"
                                    onclick="event.stopPropagation(); editEmployee({{ $employee->id }})">
                                    ✏️ Edit
                                </button>
                                <button class="archive-btn"
                                    onclick="event.stopPropagation(); openArchiveModal({{ $employee->id }}, '{{ $employee->first_name }}', '{{ $employee->last_name }}')">
                                    🗄️ Archive
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr id="empty-row">
                            <td colspan="6" class="no-records">No employees found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($employees->hasPages())
                <div class="pagination">
                    @if ($employees->onFirstPage())
                        <span class="disabled">««</span>
                        <span class="disabled">«</span>
                    @else
                        <a href="{{ $employees->url(1) }}">««</a>
                        <a href="{{ $employees->previousPageUrl() }}">«</a>
                    @endif

                    @php
                        $currentPage = $employees->currentPage();
                        $lastPage    = $employees->lastPage();
                        $start       = max(1, $currentPage - 2);
                        $end         = min($lastPage, $currentPage + 2);
                    @endphp

                    @for ($page = $start; $page <= $end; $page++)
                        @if ($page == $currentPage)
                            <span class="active">{{ $page }}</span>
                        @else
                            <a href="{{ $employees->url($page) }}">{{ $page }}</a>
                        @endif
                    @endfor

                    @if($end < $lastPage)
                        <span class="disabled">...</span>
                    @endif

                    @if ($employees->hasMorePages())
                        <a href="{{ $employees->nextPageUrl() }}">»</a>
                        <a href="{{ $employees->url($employees->lastPage()) }}">»»</a>
                    @else
                        <span class="disabled">»</span>
                        <span class="disabled">»»</span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- ════ VIEW DETAILS MODAL ════ --}}
    <div id="viewUserModal" class="modal-overlay">
        <div class="modal-box">
            <button class="modal-close" onclick="closeViewUserModal()">×</button>
            <h2 class="modal-title">Employee Details</h2>
            <div class="modal-content">
                <h3 style="margin:16px 0 10px;font-size:14px;color:#6366f1;text-transform:uppercase;letter-spacing:.5px;">Personal Information</h3>
                <table style="width:100%;border-collapse:collapse;">
                    <tr><td style="padding:7px 0;font-weight:600;color:#374151;width:170px;">Full Name</td>     <td id="fullName"></td></tr>
                    <tr><td style="padding:7px 0;font-weight:600;color:#374151;">Birthdate</td>     <td id="birthdate"></td></tr>
                    <tr><td style="padding:7px 0;font-weight:600;color:#374151;">Gender</td>        <td id="gender"></td></tr>
                    <tr><td style="padding:7px 0;font-weight:600;color:#374151;">Position</td>      <td id="position"></td></tr>
                    <tr><td style="padding:7px 0;font-weight:600;color:#374151;">Contact Number</td><td id="contactNumber"></td></tr>
                    <tr><td style="padding:7px 0;font-weight:600;color:#374151;">Address</td>       <td id="address"></td></tr>
                </table>
                <h3 style="margin:16px 0 10px;font-size:14px;color:#6366f1;text-transform:uppercase;letter-spacing:.5px;">Emergency Contact</h3>
                <table style="width:100%;border-collapse:collapse;">
                    <tr><td style="padding:7px 0;font-weight:600;color:#374151;width:170px;">Full Name</td>      <td id="ecFullName"></td></tr>
                    <tr><td style="padding:7px 0;font-weight:600;color:#374151;">Contact Number</td> <td id="ecContact"></td></tr>
                    <tr><td style="padding:7px 0;font-weight:600;color:#374151;">Email</td>          <td id="ecEmail"></td></tr>
                    <tr><td style="padding:7px 0;font-weight:600;color:#374151;">Address</td>        <td id="ecAddress"></td></tr>
                </table>
            </div>
        </div>
    </div>

    {{-- ════ EDIT EMPLOYEE MODAL ════ --}}
    <div id="employeeEditModal" class="modal-overlay">
        <div class="modal-box large">
            <button class="modal-close" onclick="closeEditModal()">×</button>
            <h2 class="modal-title">Edit Employee</h2>
            <form id="employeeEditForm" method="POST">
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <div class="form-group"><label>First Name</label><input type="text" id="editFirstName" name="first_name" required></div>
                    <div class="form-group"><label>Last Name</label><input type="text" id="editLastName" name="last_name" required></div>
                    <div class="form-group"><label>Position</label><input type="text" id="editPosition" name="position" required></div>
                    <div class="form-group"><label>Contact Number</label><input type="text" id="editContact" name="contact_number"></div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select id="editGender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Birthdate</label><input type="date" id="editBirthdate" name="birthdate"></div>
                    <div class="form-group"><label>Purok</label><input type="text" id="editPurok" name="purok"></div>
                    <div class="form-group"><label>Barangay</label><input type="text" id="editBarangay" name="barangay"></div>
                    <div class="form-group"><label>City</label><input type="text" id="editCity" name="city"></div>
                    <div class="form-group"><label>Province</label><input type="text" id="editProvince" name="province"></div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ════ ADD EMPLOYEE MODAL ════ --}}
    <div id="addEmployeeModal" class="modal-overlay">
        <div class="modal-box large">
            <button class="modal-close" onclick="closeAddModal()">×</button>
            <h2 class="modal-title">➕ Add Employee</h2>
            <form id="addEmployeeForm" action="{{ route('timekeeper.employees.store') }}" method="POST">
                @csrf
                <div class="modal-section-title">Personal Info</div>
                <div class="form-grid">
                    <div class="form-group"><label>Last Name <span style="color:red">*</span></label><input type="text" name="last_name" value="{{ old('last_name') }}" required></div>
                    <div class="form-group"><label>First Name <span style="color:red">*</span></label><input type="text" name="first_name" value="{{ old('first_name') }}" required></div>
                    <div class="form-group"><label>Middle Name</label><input type="text" name="middle_name" value="{{ old('middle_name') }}"></div>
                    <div class="form-group"><label>Suffix</label><input type="text" name="suffixes" value="{{ old('suffixes') }}" placeholder="Jr., Sr., III..."></div>
                    <div class="form-group"><label>Birthdate <span style="color:red">*</span></label><input type="date" name="birthdate" value="{{ old('birthdate') }}" required></div>
                    <div class="form-group">
                        <label>Gender <span style="color:red">*</span></label>
                        <select name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male"   {{ old('gender')=='Male'   ?'selected':'' }}>Male</option>
                            <option value="Female" {{ old('gender')=='Female' ?'selected':'' }}>Female</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Contact Number</label><input type="text" name="contact_number" value="{{ old('contact_number') }}"></div>
                    <div class="form-group">
                        <label>Position / Role <span style="color:red">*</span></label>
                        <select name="role_id" required>
                            <option value="">Select Role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('role_id')==$role->id?'selected':'' }}>{{ $role->role_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Project <span style="color:red">*</span></label>
                        <select name="project_id" required>
                            <option value="">Select Project</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ old('project_id')==$project->id?'selected':'' }}>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-section-title">Address</div>
                <div class="form-grid">
                    <div class="form-group"><label>House Number</label><input type="text" name="house_number" value="{{ old('house_number') }}"></div>
                    <div class="form-group"><label>Purok <span style="color:red">*</span></label><input type="text" name="purok" value="{{ old('purok') }}" required></div>
                    <div class="form-group"><label>Barangay <span style="color:red">*</span></label><input type="text" name="barangay" value="{{ old('barangay') }}" required></div>
                    <div class="form-group"><label>City <span style="color:red">*</span></label><input type="text" name="city" value="{{ old('city') }}" required></div>
                    <div class="form-group"><label>Province <span style="color:red">*</span></label><input type="text" name="province" value="{{ old('province') }}" required></div>
                    <div class="form-group"><label>Zip Code</label><input type="text" name="zip_code" value="{{ old('zip_code') }}"></div>
                </div>

                <div class="modal-section-title">Government IDs <span style="font-weight:400;color:#9ca3af;">(optional)</span></div>
                <div class="form-grid">
                    <div class="form-group"><label>SSS</label><input type="text" name="sss" value="{{ old('sss') }}"></div>
                    <div class="form-group"><label>PhilHealth</label><input type="text" name="philhealth" value="{{ old('philhealth') }}"></div>
                    <div class="form-group"><label>Pag-IBIG</label><input type="text" name="pagibig" value="{{ old('pagibig') }}"></div>
                </div>

                <div class="modal-section-title">Emergency Contact</div>
                <div class="form-grid">
                    <div class="form-group"><label>Last Name <span style="color:red">*</span></label><input type="text" name="last_name_ec" value="{{ old('last_name_ec') }}" required></div>
                    <div class="form-group"><label>First Name <span style="color:red">*</span></label><input type="text" name="first_name_ec" value="{{ old('first_name_ec') }}" required></div>
                    <div class="form-group"><label>Middle Name</label><input type="text" name="middle_name_ec" value="{{ old('middle_name_ec') }}"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email_ec" value="{{ old('email_ec') }}"></div>
                    <div class="form-group"><label>Contact Number <span style="color:red">*</span></label><input type="text" name="contact_number_ec" value="{{ old('contact_number_ec') }}" required></div>
                    <div class="form-group"><label>House Number</label><input type="text" name="house_number_ec" value="{{ old('house_number_ec') }}"></div>
                    <div class="form-group"><label>Purok <span style="color:red">*</span></label><input type="text" name="purok_ec" value="{{ old('purok_ec') }}" required></div>
                    <div class="form-group"><label>Barangay <span style="color:red">*</span></label><input type="text" name="barangay_ec" value="{{ old('barangay_ec') }}" required></div>
                    <div class="form-group"><label>City <span style="color:red">*</span></label><input type="text" name="city_ec" value="{{ old('city_ec') }}" required></div>
                    <div class="form-group"><label>Province <span style="color:red">*</span></label><input type="text" name="province_ec" value="{{ old('province_ec') }}" required></div>
                    <div class="form-group"><label>Country</label><input type="text" name="country_ec" value="{{ old('country_ec') }}"></div>
                    <div class="form-group"><label>Zip Code</label><input type="text" name="zip_code_ec" value="{{ old('zip_code_ec') }}"></div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeAddModal()">✕ Cancel</button>
                    <button type="submit" class="btn-save">💾 Add Employee</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ════ ARCHIVE MODAL — matches screenshot design ════ --}}
    <div id="archiveModal" class="modal-overlay">
        <div class="modal-box" style="max-width:500px;">
            <button class="modal-close" onclick="closeArchiveModal()">×</button>
            <h2 class="modal-title">🗄️ Archive Employee</h2>
            <hr style="margin-bottom:16px;border:none;border-top:1px solid #e5e7eb;">

            <p style="margin-bottom:16px;color:#6b7280;font-size:14px;">
                You are about to archive <strong id="archiveEmployeeName" style="color:#1f2937;"></strong>. Please select a status:
            </p>

            <label class="archive-option terminated">
                <input type="radio" name="archiveStatusRadio" value="terminated">
                <div class="archive-label">
                    <strong style="color:#dc3545;">🔴 Terminated</strong>
                    <small>Employee was let go or contract ended</small>
                </div>
            </label>

            <label class="archive-option inactive">
                <input type="radio" name="archiveStatusRadio" value="inactive">
                <div class="archive-label">
                    <strong style="color:#d97706;">🟡 Inactive</strong>
                    <small>Employee is on leave or temporarily inactive</small>
                </div>
            </label>

            <div style="margin-top:16px;">
                <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">
                    Reason / Notes (Optional)
                </label>
                <textarea id="archiveReasonText" class="archive-reason"
                          placeholder="Enter reason for archiving..."></textarea>
            </div>

            <div id="archiveErrorMsg"
                 style="display:none;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;
                        border-radius:6px;padding:8px 12px;font-size:13px;margin-top:12px;">
            </div>

            <div style="margin-top:20px;display:flex;justify-content:flex-end;gap:8px;">
                <button type="button" onclick="closeArchiveModal()" class="btn-cancel">✕ Cancel</button>
                <button type="button" id="archiveSubmitBtn" onclick="submitArchive()"
                        style="padding:9px 20px;background:#dc3545;color:#fff;border:none;border-radius:8px;
                               font-size:14px;font-weight:700;cursor:pointer;">
                    🗄️ Archive Employee
                </button>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div id="toastNotif"></div>

    <script>
        let _archiveEmpId = null;

        // ── Add Modal ──────────────────────────────────────────
        function openAddModal() {
            document.getElementById('addEmployeeModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        function closeAddModal() {
            document.getElementById('addEmployeeModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // ── Edit Employee ──────────────────────────────────────
        function editEmployee(id) {
            fetch(`/timekeeper/employees/${id}/modal`)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('editFirstName').value  = data.first_name     ?? '';
                    document.getElementById('editLastName').value   = data.last_name      ?? '';
                    document.getElementById('editPosition').value   = data.position       ?? '';
                    document.getElementById('editContact').value    = data.contact_number ?? '';
                    document.getElementById('editGender').value     = data.gender         ?? '';
                    document.getElementById('editBirthdate').value  = data.birthdate      ?? '';
                    document.getElementById('editPurok').value      = data.purok          ?? '';
                    document.getElementById('editBarangay').value   = data.barangay       ?? '';
                    document.getElementById('editCity').value       = data.city           ?? '';
                    document.getElementById('editProvince').value   = data.province       ?? '';
                    document.getElementById('employeeEditForm').action = `/timekeeper/employees/${id}`;
                    document.getElementById('employeeEditModal').style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                })
                .catch(err => { console.error(err); alert('Failed to load employee data'); });
        }
        function closeEditModal() {
            document.getElementById('employeeEditModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // ── Archive Modal ──────────────────────────────────────
        function openArchiveModal(id, firstName, lastName) {
            _archiveEmpId = id;
            document.getElementById('archiveEmployeeName').textContent = `${firstName} ${lastName}`;
            document.getElementById('archiveReasonText').value = '';
            document.getElementById('archiveErrorMsg').style.display = 'none';
            document.querySelectorAll('input[name="archiveStatusRadio"]').forEach(r => r.checked = false);
            document.getElementById('archiveModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        function closeArchiveModal() {
            document.getElementById('archiveModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            _archiveEmpId = null;
        }

        function submitArchive() {
            const selected = document.querySelector('input[name="archiveStatusRadio"]:checked');
            const errBox   = document.getElementById('archiveErrorMsg');
            const btn      = document.getElementById('archiveSubmitBtn');

            if (!selected) {
                errBox.textContent   = 'Please select a status (Terminated or Inactive).';
                errBox.style.display = 'block';
                return;
            }

            const savedId = _archiveEmpId; // save BEFORE closeArchiveModal() nulls it
            const status  = selected.value;
            const reason  = document.getElementById('archiveReasonText').value;

            btn.disabled    = true;
            btn.textContent = '⏳ Archiving...';
            errBox.style.display = 'none';

            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(`/timekeeper/employees/${savedId}/archive`, {
                method:  'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrf, 'Accept':'application/json' },
                body:    JSON.stringify({ status, archive_reason: reason }),
            })
            .then(r => r.json())
            .then(data => {
                btn.disabled    = false;
                btn.textContent = '🗄️ Archive Employee';

                if (data.success) {
                    closeArchiveModal();
                    const row = document.getElementById('emp-row-' + savedId);
                    if (row) {
                        row.style.transition = 'opacity 0.4s, transform 0.4s';
                        row.style.opacity    = '0';
                        row.style.transform  = 'translateX(20px)';
                        setTimeout(() => row.remove(), 420);
                    }
                    showToast(data.message ?? 'Employee archived successfully.', 'success');
                } else {
                    errBox.textContent   = data.message ?? 'Something went wrong.';
                    errBox.style.display = 'block';
                }
            })
            .catch(() => {
                btn.disabled    = false;
                btn.textContent = '🗄️ Archive Employee';
                errBox.textContent   = 'Network error. Please try again.';
                errBox.style.display = 'block';
            });
        }

        // ── View Details ───────────────────────────────────────
        function closeViewUserModal() {
            document.getElementById('viewUserModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        document.querySelectorAll('.clickable-row').forEach(row => {
            row.addEventListener('click', function(e) {
                if (e.target.tagName.toLowerCase() === 'button') return;
                const u = JSON.parse(this.dataset.user);
                document.getElementById('fullName').innerText      = [u.first_name, u.middle_name, u.last_name, u.suffixes].filter(Boolean).join(' ');
                document.getElementById('birthdate').innerText     = u.birthdate       ?? 'N/A';
                document.getElementById('gender').innerText        = u.gender          ?? 'N/A';
                document.getElementById('position').innerText      = u.position        ?? 'N/A';
                document.getElementById('contactNumber').innerText = u.contact_number  ?? 'N/A';
                document.getElementById('address').innerText       = [u.house_number, u.purok, u.barangay, u.city, u.province].filter(Boolean).join(', ') || 'N/A';
                document.getElementById('ecFullName').innerText    = [u.first_name_ec, u.middle_name_ec, u.last_name_ec].filter(Boolean).join(' ') || 'N/A';
                document.getElementById('ecContact').innerText     = u.contact_number_ec ?? 'N/A';
                document.getElementById('ecEmail').innerText       = u.email_ec          ?? 'N/A';
                document.getElementById('ecAddress').innerText     = [u.house_number_ec, u.purok_ec, u.barangay_ec, u.city_ec, u.province_ec, u.country_ec].filter(Boolean).join(', ') || 'N/A';
                document.getElementById('viewUserModal').style.display = 'flex';
                document.body.style.overflow = 'hidden';
            });
        });

        // ── Close on outside click ─────────────────────────────
        ['viewUserModal','employeeEditModal','addEmployeeModal','archiveModal'].forEach(id => {
            document.getElementById(id).addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                    document.body.style.overflow = 'auto';
                    if (id === 'archiveModal') _archiveEmpId = null;
                }
            });
        });

        // ── Auto-open add modal on validation error ────────────
        @if($errors->any())
            openAddModal();
        @endif

        // ── Live search ────────────────────────────────────────
        const searchInput = document.getElementById('searchInput');
        let timeout = null;
        searchInput.addEventListener('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                fetch(`{{ route('timekeeper.employees.search') }}?search=${encodeURIComponent(searchInput.value)}`)
                    .then(r => r.text())
                    .then(html => { document.getElementById('employeeTableBody').innerHTML = html; })
                    .catch(err => console.error('Search error:', err));
            }, 300);
        });

        // ── Position filter ────────────────────────────────────
        document.getElementById('positionFilter').addEventListener('change', function() {
            window.location.href = `{{ route('timekeeper.employees') }}?position=${encodeURIComponent(this.value)}`;
        });

        // ── Toast ──────────────────────────────────────────────
        function showToast(msg, type = 'success') {
            const t = document.getElementById('toastNotif');
            t.textContent = msg;
            t.className   = `show ${type}`;
            setTimeout(() => { t.className = type; }, 3000);
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