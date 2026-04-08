<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Employees</title>
    <link rel="stylesheet" href="{{ asset('css/partials/sidenav.css') }}">
    <link rel="stylesheet" href="{{ asset('css/superadmin/employee.css') }}">
    <style>
        /* ── Checkbox column ── */
        .col-check { width: 40px; text-align: center; }
        .row-checkbox { width: 16px; height: 16px; cursor: pointer; accent-color: #6366f1; }

        /* ── Bulk action bar ── */
        #bulkBar {
            display: none;
            align-items: center;
            gap: 10px;
            background: #ede9fe;
            border: 1.5px solid #c4b5fd;
            border-radius: 10px;
            padding: 10px 16px;
            margin-bottom: 14px;
            animation: fadeInUp .25s ease both;
        }
        @keyframes fadeInUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
        #bulkBar .bulk-count {
            font-size: 13px; font-weight: 700; color: #4f46e5; flex: 1;
        }
        .btn-bulk-archive {
            padding: 7px 16px; background: #dc3545; color: #fff;
            border: none; border-radius: 7px; font-size: 13px;
            font-weight: 700; cursor: pointer;
        }
        .btn-bulk-archive:hover { background: #b91c1c; }
        .btn-bulk-cancel {
            padding: 7px 14px; background: #fff; color: #6b7280;
            border: 1px solid #d1d5db; border-radius: 7px; font-size: 13px;
            font-weight: 600; cursor: pointer;
        }
        .btn-bulk-cancel:hover { background: #f3f4f6; }

        /* Highlight selected rows */
        tr.row-selected td { background: #f5f3ff !important; }
    </style>
</head>
<body>

@if(Session::has('user_id') && Session::get('role_id') == 1)

    @include('superadmin.partials.sidenav')

    <div class="main-content">

        <div class="header">
            <h1>📋 Employees</h1>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <!-- CONTROLS -->
        <form method="GET" action="{{ route('superadmin.employees') }}" class="controls-bar" id="filterForm">
            <input type="text" name="search" id="searchInput" class="search-input" placeholder="🔍 Search by Name"
                   value="{{ request('search') }}">
            <select name="position" id="positionFilter" class="position-select" onchange="this.form.submit()">
                <option value="">Filter by Position</option>
                @foreach($positions as $pos)
                    <option value="{{ $pos }}" {{ request('position') == $pos ? 'selected' : '' }}>
                        {{ $pos }}
                    </option>
                @endforeach
            </select>
            <button type="submit" style="display:none"></button>
            <button type="button" class="btn-add" onclick="openModal()">➕ Add Person</button>
            <button type="button" class="btn-export" onclick="alert('Export functionality')">📤 Export</button>
        </form>

        <!-- BULK ACTION BAR -->
        <div id="bulkBar">
            <span class="bulk-count" id="bulkCount">0 selected</span>
            <button type="button" class="btn-bulk-archive" onclick="openBulkArchiveModal()">🗄️ Archive Selected</button>
            <button type="button" class="btn-bulk-cancel" onclick="clearSelection()">✕ Cancel</button>
        </div>

        <!-- TABLE -->
        <table>
            <thead>
                <tr>
                    <th class="col-check">
                        <input type="checkbox" id="selectAll" class="row-checkbox" onclick="toggleSelectAll(this)">
                    </th>
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
                    <tr id="emp-row-{{ $employee->id }}" class="clickable-row"
                        data-user='@json($employee)'
                        data-name="{{ strtolower($employee->first_name . ' ' . $employee->last_name) }}"
                        data-position="{{ strtolower($employee->position ?? '') }}">
                        <td class="col-check" onclick="event.stopPropagation()">
                            <input type="checkbox" class="row-checkbox emp-checkbox"
                                   value="{{ $employee->id }}"
                                   data-name="{{ $employee->first_name }} {{ $employee->last_name }}"
                                   onchange="onCheckboxChange()">
                        </td>
                        <td>{{ $employee->id }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:34px;height:34px;border-radius:50%;flex-shrink:0;
                                            background:linear-gradient(135deg,#6366f1,#4f46e5);
                                            display:flex;align-items:center;justify-content:center;
                                            font-weight:700;font-size:13px;color:#fff;overflow:hidden;">
                                    @if($employee->photo)
                                        <img src="{{ asset('storage/' . $employee->photo) }}"
                                             style="width:100%;height:100%;object-fit:cover;"
                                             onerror="this.style.display='none';this.parentElement.textContent='{{ strtoupper(substr($employee->first_name,0,1)) }}'">
                                    @else
                                        {{ strtoupper(substr($employee->first_name, 0, 1)) }}
                                    @endif
                                </div>
                                <span>{{ $employee->first_name }} {{ $employee->last_name }}</span>
                            </div>
                        </td>
                        <td>{{ $employee->position ?? 'N/A' }}</td>
                        <td>{{ $employee->gender ?? 'N/A' }}</td>
                        <td>
                            @if($employee->rating && $employee->rating !== 'n/a')
                                <span class="badge badge-rated">{{ $employee->rating }}</span>
                            @else
                                <span class="badge badge-pending">n/a</span>
                            @endif
                        </td>
                        <td style="display:flex;gap:6px;">
                            <button class="edit-btn" onclick="event.stopPropagation(); editEmployee({{ $employee->id }})">✏️ Edit</button>
                            <button class="delete-btn" onclick="event.stopPropagation(); openArchiveModal({{ $employee->id }}, '{{ $employee->first_name }}', '{{ $employee->last_name }}')">🗑️ Archive</button>
                        </td>
                    </tr>
                @empty
                    <tr id="empty-row">
                        <td colspan="7" class="no-records">No employees found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- PAGINATION -->
        <div id="paginationWrapper">
            <div class="pagination">
                @php $employees->appends(request()->query()); @endphp
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
                @if ($employees->hasMorePages())
                    <a href="{{ $employees->nextPageUrl() }}">»</a>
                    <a href="{{ $employees->url($employees->lastPage()) }}">»»</a>
                @else
                    <span class="disabled">»</span>
                    <span class="disabled">»»</span>
                @endif
            </div>
        </div>
    </div>

    <!-- VIEW EMPLOYEE MODAL -->
    <div id="viewUserModal" class="modal-overlay">
        <div class="modal-box" style="max-width:780px;padding:0;overflow:hidden;">
            <button class="modal-close" style="position:absolute;top:14px;right:16px;z-index:10;" onclick="closeViewUserModal()">✕</button>
            <div style="display:flex;align-items:center;gap:24px;padding:28px 32px 20px;border-bottom:1px solid #f0f0f0;">
                <div id="profileAvatar" style="width:90px;height:90px;border-radius:50%;flex-shrink:0;background:linear-gradient(135deg,#6366f1,#4f46e5);display:flex;align-items:center;justify-content:center;font-size:36px;font-weight:700;color:#fff;box-shadow:0 4px 16px rgba(99,102,241,.35);overflow:hidden;position:relative;">
                    <span id="profileInitial">?</span>
                    <img id="profileImg" src="" alt="" style="display:none;width:100%;height:100%;object-fit:cover;position:absolute;inset:0;">
                </div>
                <div>
                    <div id="viewFullName" style="font-size:22px;font-weight:700;color:#1e293b;margin-bottom:4px;"></div>
                    <div id="viewRoleBadge" style="display:inline-block;padding:4px 14px;border-radius:20px;font-size:12px;font-weight:700;letter-spacing:.4px;background:#ede9fe;color:#4f46e5;"></div>
                </div>
            </div>
            <div style="padding:24px 32px;overflow-y:auto;max-height:520px;">
                <div style="font-size:11px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:.8px;margin-bottom:12px;">Personal Information</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px 24px;margin-bottom:24px;">
                    <div><span style="font-size:11px;color:#94a3b8;font-weight:600;display:block;margin-bottom:2px;">BIRTHDATE</span><span id="viewBirthdate" style="font-size:14px;color:#1e293b;font-weight:500;"></span></div>
                    <div><span style="font-size:11px;color:#94a3b8;font-weight:600;display:block;margin-bottom:2px;">GENDER</span><span id="viewGender" style="font-size:14px;color:#1e293b;font-weight:500;"></span></div>
                    <div><span style="font-size:11px;color:#94a3b8;font-weight:600;display:block;margin-bottom:2px;">CONTACT NUMBER</span><span id="viewContact" style="font-size:14px;color:#1e293b;font-weight:500;"></span></div>
                    <div><span style="font-size:11px;color:#94a3b8;font-weight:600;display:block;margin-bottom:2px;">USERNAME</span><span id="viewUsername" style="font-size:14px;color:#1e293b;font-weight:500;"></span></div>
                    <div style="grid-column:1/-1;"><span style="font-size:11px;color:#94a3b8;font-weight:600;display:block;margin-bottom:2px;">ADDRESS</span><span id="viewAddress" style="font-size:14px;color:#1e293b;font-weight:500;"></span></div>
                </div>
                <div style="font-size:11px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:.8px;margin-bottom:12px;">Government IDs</div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px 24px;margin-bottom:24px;">
                    <div><span style="font-size:11px;color:#94a3b8;font-weight:600;display:block;margin-bottom:2px;">SSS</span><span id="viewSss" style="font-size:14px;color:#1e293b;font-weight:500;"></span></div>
                    <div><span style="font-size:11px;color:#94a3b8;font-weight:600;display:block;margin-bottom:2px;">PHILHEALTH</span><span id="viewPhilhealth" style="font-size:14px;color:#1e293b;font-weight:500;"></span></div>
                    <div><span style="font-size:11px;color:#94a3b8;font-weight:600;display:block;margin-bottom:2px;">PAG-IBIG</span><span id="viewPagibig" style="font-size:14px;color:#1e293b;font-weight:500;"></span></div>
                </div>
                <div style="font-size:11px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:.8px;margin-bottom:12px;">Emergency Contact</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px 24px;">
                    <div><span style="font-size:11px;color:#94a3b8;font-weight:600;display:block;margin-bottom:2px;">FULL NAME</span><span id="viewEcName" style="font-size:14px;color:#1e293b;font-weight:500;"></span></div>
                    <div><span style="font-size:11px;color:#94a3b8;font-weight:600;display:block;margin-bottom:2px;">CONTACT NUMBER</span><span id="viewEcContact" style="font-size:14px;color:#1e293b;font-weight:500;"></span></div>
                    <div><span style="font-size:11px;color:#94a3b8;font-weight:600;display:block;margin-bottom:2px;">EMAIL</span><span id="viewEcEmail" style="font-size:14px;color:#1e293b;font-weight:500;"></span></div>
                    <div style="grid-column:1/-1;"><span style="font-size:11px;color:#94a3b8;font-weight:600;display:block;margin-bottom:2px;">ADDRESS</span><span id="viewEcAddress" style="font-size:14px;color:#1e293b;font-weight:500;"></span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- EDIT EMPLOYEE MODAL -->
    <div id="employeeEditModal" class="modal-overlay">
        <div class="modal-box" style="max-width:860px;">
            <button class="modal-close" onclick="closeEditModal()">✕</button>
            <h2>✏️ Edit Employee</h2>
            <hr>
            <form id="employeeEditForm" method="POST" action="" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" id="editRemovePhotoFlag" name="remove_photo" value="0">
                <div class="modal-section-title">Profile Photo</div>
                <div class="form-row" style="align-items:center;gap:20px;margin-bottom:20px;">
                    <div id="editAvatarPreview" style="width:90px;height:90px;border-radius:50%;flex-shrink:0;background:linear-gradient(135deg,#6366f1,#4f46e5);display:flex;align-items:center;justify-content:center;font-size:36px;font-weight:700;color:#fff;overflow:hidden;box-shadow:0 4px 12px rgba(99,102,241,.3);position:relative;cursor:pointer;" onclick="document.getElementById('editPhotoInput').click()">
                        <span id="editAvatarInitial" style="pointer-events:none;"></span>
                        <img id="editAvatarImg" src="" alt="" style="display:none;width:100%;height:100%;object-fit:cover;position:absolute;inset:0;">
                        <div style="position:absolute;inset:0;background:rgba(0,0,0,0.35);display:flex;align-items:center;justify-content:center;border-radius:50%;opacity:0;transition:opacity .2s;" id="editAvatarOverlay"><span style="color:#fff;font-size:20px;">📷</span></div>
                    </div>
                    <div>
                        <input type="file" id="editPhotoInput" name="photo" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="previewEditPhoto(this)">
                        <button type="button" style="padding:8px 16px;background:#f1f5f9;border:1px solid #cbd5e1;border-radius:8px;font-size:13px;cursor:pointer;font-weight:600;color:#374151;" onclick="document.getElementById('editPhotoInput').click()">📁 Choose Photo</button>
                        <p style="font-size:12px;color:#94a3b8;margin-top:6px;">JPG, PNG or WEBP · Max 2MB</p>
                        <button type="button" id="editRemovePhotoBtn" onclick="removeEditPhoto()" style="display:none;margin-top:6px;padding:5px 12px;background:#fee2e2;border:1px solid #fca5a5;border-radius:6px;font-size:12px;cursor:pointer;color:#dc2626;font-weight:600;">🗑️ Remove Photo</button>
                    </div>
                </div>
                <div class="modal-section-title">Personal Info</div>
                <div class="form-row">
                    <div class="form-group"><label>Last Name <a>*</a></label><input type="text" id="editLastName" name="last_name" required></div>
                    <div class="form-group"><label>First Name <a>*</a></label><input type="text" id="editFirstName" name="first_name" required></div>
                    <div class="form-group"><label>Middle Name</label><input type="text" id="editMiddleName" name="middle_name"></div>
                    <div class="form-group"><label>Suffix</label><input type="text" id="editSuffixes" name="suffixes" placeholder="Jr., Sr., III..."></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Birthdate</label><input type="date" id="editBirthdate" name="birthdate"></div>
                    <div class="form-group"><label>Gender <a>*</a></label>
                        <select id="editGender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Contact Number</label><input type="text" id="editContact" name="contact_number"></div>
                    <div class="form-group"><label>Position</label><input type="text" id="editPosition" name="position"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Username</label><input type="text" id="editUsername" name="username"></div>
                    <div class="form-group"><label>New Password <span style="font-weight:400;color:#9ca3af;font-size:11px;">(leave blank to keep)</span></label><input type="password" id="editPassword" name="password" placeholder="••••••••"></div>
                </div>
                <div class="modal-section-title">Address</div>
                <div class="form-row">
                    <div class="form-group"><label>House Number</label><input type="text" id="editHouseNumber" name="house_number"></div>
                    <div class="form-group"><label>Purok</label><input type="text" id="editPurok" name="purok"></div>
                    <div class="form-group"><label>Barangay</label><input type="text" id="editBarangay" name="barangay"></div>
                    <div class="form-group"><label>City</label><input type="text" id="editCity" name="city"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Province</label><input type="text" id="editProvince" name="province"></div>
                    <div class="form-group"><label>Zip Code</label><input type="text" id="editZipCode" name="zip_code"></div>
                </div>
                <div class="modal-section-title">Government IDs <span style="font-weight:400;color:#9ca3af;font-size:12px;">(optional)</span></div>
                <div class="form-row">
                    <div class="form-group"><label>SSS</label><input type="text" id="editSss" name="sss"></div>
                    <div class="form-group"><label>PhilHealth</label><input type="text" id="editPhilhealth" name="philhealth"></div>
                    <div class="form-group"><label>Pag-IBIG</label><input type="text" id="editPagibig" name="pagibig"></div>
                </div>
                <div class="modal-section-title">Emergency Contact</div>
                <div class="form-row">
                    <div class="form-group"><label>Last Name</label><input type="text" id="editLastNameEc" name="last_name_ec"></div>
                    <div class="form-group"><label>First Name</label><input type="text" id="editFirstNameEc" name="first_name_ec"></div>
                    <div class="form-group"><label>Middle Name</label><input type="text" id="editMiddleNameEc" name="middle_name_ec"></div>
                    <div class="form-group"><label>Email</label><input type="email" id="editEmailEc" name="email_ec"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Contact Number</label><input type="text" id="editContactEc" name="contact_number_ec"></div>
                    <div class="form-group"><label>House Number</label><input type="text" id="editHouseNumberEc" name="house_number_ec"></div>
                    <div class="form-group"><label>Purok</label><input type="text" id="editPurokEc" name="purok_ec"></div>
                    <div class="form-group"><label>Barangay</label><input type="text" id="editBarangayEc" name="barangay_ec"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>City</label><input type="text" id="editCityEc" name="city_ec"></div>
                    <div class="form-group"><label>Province</label><input type="text" id="editProvinceEc" name="province_ec"></div>
                    <div class="form-group"><label>Country</label><input type="text" id="editCountryEc" name="country_ec"></div>
                    <div class="form-group"><label>Zip Code</label><input type="text" id="editZipCodeEc" name="zip_code_ec"></div>
                </div>
                <div style="margin-top:20px;display:flex;justify-content:flex-end;gap:8px;padding-top:16px;border-top:1px solid #e5e7eb;">
                    <button type="button" onclick="closeEditModal()" style="padding:8px 18px;background:#fff;color:#6b7280;border:1px solid #ccc;border-radius:6px;cursor:pointer;font-size:13px;">✕ Cancel</button>
                    <button type="submit" class="save-btn">💾 Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SINGLE ARCHIVE MODAL -->
    <div id="archiveModal" class="modal-overlay">
        <div class="modal-box" style="max-width:500px;">
            <h2>🗄️ Archive Employee</h2>
            <hr style="margin-bottom:16px;">
            <p style="margin-bottom:16px;color:#666;">
                You are about to archive <strong id="archiveEmployeeName"></strong>. Please select a status:
            </p>
            <div id="archivePrecheckWarnings" style="display:none;margin-bottom:16px;">
                <div id="archiveUserAccountWarn" style="display:none;background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:10px 14px;margin-bottom:10px;font-size:13px;color:#92400e;">
                    <strong>⚠️ Active User Account</strong><br>
                    This employee has a login account (<span id="archiveUsername" style="font-weight:700;"></span>). Archiving will remove their access.
                </div>
                <div id="archiveCashAdvanceWarn" style="display:none;background:#fff1f2;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;margin-bottom:10px;font-size:13px;color:#991b1b;">
                    <strong>🚨 Pending / Approved Cash Advances</strong><br>
                    This employee has <span id="archiveCaCount" style="font-weight:700;"></span> cash advance(s) totalling <strong>₱<span id="archiveCaTotal"></span></strong>.
                </div>
            </div>
            <div id="archivePrecheckLoading" style="text-align:center;padding:12px;color:#94a3b8;font-size:13px;">🔍 Checking records...</div>
            <div id="archiveStatusSection" style="display:none;">
                <label class="archive-option terminated">
                    <input type="radio" name="archiveStatusRadio" value="terminated">
                    <div class="archive-label"><strong style="color:#dc3545;">🔴 Terminated</strong><small>Employee was let go or contract ended</small></div>
                </label>
                <label class="archive-option inactive">
                    <input type="radio" name="archiveStatusRadio" value="inactive">
                    <div class="archive-label"><strong style="color:#ffc107;">🟡 Inactive</strong><small>Employee is on leave or temporarily inactive</small></div>
                </label>
                <div class="form-group" style="margin-top:16px;">
                    <label>Reason / Notes (Optional)</label>
                    <textarea id="archiveReasonText" class="archive-reason" placeholder="Enter reason for archiving..."></textarea>
                </div>
            </div>
            <div id="archiveErrorMsg" style="display:none;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:6px;padding:8px 12px;font-size:13px;margin-top:12px;"></div>
            <div style="margin-top:20px;display:flex;justify-content:flex-end;gap:8px;">
                <button type="button" onclick="closeArchiveModal()" style="padding:8px 18px;background:#fff;color:#808080;border:none;border-radius:6px;cursor:pointer;font-size:13px;">✕ Cancel</button>
                <button type="button" id="archiveSubmitBtn" onclick="submitArchive()" class="save-btn" style="background:#dc3545;display:none;">🗄️ Archive Employee</button>
            </div>
        </div>
    </div>

    <!-- BULK ARCHIVE MODAL -->
    <div id="bulkArchiveModal" class="modal-overlay">
        <div class="modal-box" style="max-width:500px;">
            <h2>🗄️ Bulk Archive Employees</h2>
            <hr style="margin-bottom:16px;">
            <p style="margin-bottom:12px;color:#666;">
                You are about to archive <strong id="bulkArchiveCount"></strong> employee(s). Select a status:
            </p>
            <div id="bulkArchiveNames" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;font-size:13px;color:#374151;max-height:120px;overflow-y:auto;margin-bottom:16px;line-height:1.8;"></div>
            <label class="archive-option terminated">
                <input type="radio" name="bulkStatusRadio" value="terminated">
                <div class="archive-label"><strong style="color:#dc3545;">🔴 Terminated</strong><small>Employee was let go or contract ended</small></div>
            </label>
            <label class="archive-option inactive">
                <input type="radio" name="bulkStatusRadio" value="inactive">
                <div class="archive-label"><strong style="color:#ffc107;">🟡 Inactive</strong><small>Employee is on leave or temporarily inactive</small></div>
            </label>
            <div class="form-group" style="margin-top:16px;">
                <label>Reason / Notes (Optional)</label>
                <textarea id="bulkArchiveReason" class="archive-reason" placeholder="Enter reason for archiving..."></textarea>
            </div>
            <div id="bulkArchiveProgress" style="display:none;margin-top:12px;">
                <div style="background:#e2e8f0;border-radius:6px;overflow:hidden;height:8px;">
                    <div id="bulkProgressBar" style="height:100%;background:#6366f1;width:0%;transition:width .3s;"></div>
                </div>
                <div id="bulkProgressText" style="font-size:12px;color:#64748b;margin-top:6px;text-align:center;"></div>
            </div>
            <div id="bulkArchiveError" style="display:none;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:6px;padding:8px 12px;font-size:13px;margin-top:12px;"></div>
            <div style="margin-top:20px;display:flex;justify-content:flex-end;gap:8px;">
                <button type="button" onclick="closeBulkArchiveModal()" id="bulkCancelBtn" style="padding:8px 18px;background:#fff;color:#808080;border:none;border-radius:6px;cursor:pointer;font-size:13px;">✕ Cancel</button>
                <button type="button" id="bulkArchiveSubmitBtn" onclick="submitBulkArchive()" class="save-btn" style="background:#dc3545;">🗄️ Archive All</button>
            </div>
        </div>
    </div>

    <!-- ADD EMPLOYEE MODAL -->
    <div id="employeeAddModal" class="modal-overlay">
        <div class="modal-box" style="max-width:860px;">
            <button class="modal-close" onclick="closeModal()">✕</button>
            <h2>➕ Add Employee</h2>
            <hr>
            <form id="addEmployeeForm" action="{{ route('superadmin.employees.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-section-title">Profile Photo</div>
                <div class="form-row" style="align-items:center;gap:20px;margin-bottom:20px;">
                    <div id="addAvatarPreview" style="width:90px;height:90px;border-radius:50%;flex-shrink:0;background:linear-gradient(135deg,#6366f1,#4f46e5);display:flex;align-items:center;justify-content:center;font-size:36px;font-weight:700;color:#fff;overflow:hidden;box-shadow:0 4px 12px rgba(99,102,241,.3);position:relative;cursor:pointer;" onclick="document.getElementById('addPhotoInput').click()">
                        <span id="addAvatarInitial" style="pointer-events:none;">👤</span>
                        <img id="addAvatarImg" src="" alt="" style="display:none;width:100%;height:100%;object-fit:cover;position:absolute;inset:0;">
                        <div style="position:absolute;inset:0;background:rgba(0,0,0,0.35);display:flex;align-items:center;justify-content:center;border-radius:50%;opacity:0;transition:opacity .2s;" id="addAvatarOverlay"><span style="color:#fff;font-size:20px;">📷</span></div>
                    </div>
                    <div>
                        <input type="file" id="addPhotoInput" name="photo" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="previewAddPhoto(this)">
                        <button type="button" style="padding:8px 16px;background:#f1f5f9;border:1px solid #cbd5e1;border-radius:8px;font-size:13px;cursor:pointer;font-weight:600;color:#374151;" onclick="document.getElementById('addPhotoInput').click()">📁 Choose Photo</button>
                        <p style="font-size:12px;color:#94a3b8;margin-top:6px;">JPG, PNG or WEBP · Max 2MB · Optional</p>
                        <button type="button" id="addRemovePhotoBtn" onclick="removeAddPhoto()" style="display:none;margin-top:6px;padding:5px 12px;background:#fee2e2;border:1px solid #fca5a5;border-radius:6px;font-size:12px;cursor:pointer;color:#dc2626;font-weight:600;">🗑️ Remove Photo</button>
                    </div>
                </div>
                <div class="modal-section-title">Personal Info</div>
                <div class="form-row">
                    <div class="form-group"><label>Last Name <a>*</a></label><input type="text" name="last_name" value="{{ old('last_name') }}" required></div>
                    <div class="form-group"><label>First Name <a>*</a></label><input type="text" name="first_name" value="{{ old('first_name') }}" required></div>
                    <div class="form-group"><label>Middle Name</label><input type="text" name="middle_name" value="{{ old('middle_name') }}"></div>
                    <div class="form-group"><label>Suffix</label><input type="text" name="suffixes" value="{{ old('suffixes') }}" placeholder="Jr., Sr., III..."></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Birthdate <a>*</a></label><input type="date" name="birthdate" value="{{ old('birthdate') }}" required></div>
                    <div class="form-group"><label>Gender <a>*</a></label>
                        <select name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male"   {{ old('gender') == 'Male'   ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Contact Number</label><input type="text" name="contact_number" value="{{ old('contact_number') }}"></div>
                    <div class="form-group"><label>Position / Role <a>*</a></label>
                        <select name="role_id" required>
                            <option value="">Select Role</option>
                            @foreach($fieldRoles as $role)
                                <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>{{ $role->role_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Project</label>
                        <select name="project_id">
                            <option value="">Select Project</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-section-title">Address</div>
                <div class="form-row">
                    <div class="form-group"><label>House Number</label><input type="text" name="house_number" value="{{ old('house_number') }}"></div>
                    <div class="form-group"><label>Purok <a>*</a></label><input type="text" name="purok" value="{{ old('purok') }}" required></div>
                    <div class="form-group"><label>Barangay <a>*</a></label><input type="text" name="barangay" value="{{ old('barangay') }}" required></div>
                    <div class="form-group"><label>City <a>*</a></label><input type="text" name="city" value="{{ old('city') }}" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Province <a>*</a></label><input type="text" name="province" value="{{ old('province') }}" required></div>
                    <div class="form-group"><label>Zip Code</label><input type="text" name="zip_code" value="{{ old('zip_code') }}"></div>
                </div>
                <div class="modal-section-title">Government IDs <span style="font-weight:400;color:#9ca3af;font-size:12px;">(optional)</span></div>
                <div class="form-row">
                    <div class="form-group"><label>SSS</label><input type="text" name="sss" value="{{ old('sss') }}"></div>
                    <div class="form-group"><label>PhilHealth</label><input type="text" name="philhealth" value="{{ old('philhealth') }}"></div>
                    <div class="form-group"><label>Pag-IBIG</label><input type="text" name="pagibig" value="{{ old('pagibig') }}"></div>
                </div>
                <div class="modal-section-title">Emergency Contact</div>
                <div class="form-row">
                    <div class="form-group"><label>Last Name <a>*</a></label><input type="text" name="last_name_ec" value="{{ old('last_name_ec') }}" required></div>
                    <div class="form-group"><label>First Name <a>*</a></label><input type="text" name="first_name_ec" value="{{ old('first_name_ec') }}" required></div>
                    <div class="form-group"><label>Middle Name</label><input type="text" name="middle_name_ec" value="{{ old('middle_name_ec') }}"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email_ec" value="{{ old('email_ec') }}"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Contact Number <a>*</a></label><input type="text" name="contact_number_ec" value="{{ old('contact_number_ec') }}" required></div>
                    <div class="form-group"><label>House Number</label><input type="text" name="house_number_ec" value="{{ old('house_number_ec') }}"></div>
                    <div class="form-group"><label>Purok <a>*</a></label><input type="text" name="purok_ec" value="{{ old('purok_ec') }}" required></div>
                    <div class="form-group"><label>Barangay <a>*</a></label><input type="text" name="barangay_ec" value="{{ old('barangay_ec') }}" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>City <a>*</a></label><input type="text" name="city_ec" value="{{ old('city_ec') }}" required></div>
                    <div class="form-group"><label>Province <a>*</a></label><input type="text" name="province_ec" value="{{ old('province_ec') }}" required></div>
                    <div class="form-group"><label>Country</label><input type="text" name="country_ec" value="{{ old('country_ec') }}"></div>
                    <div class="form-group"><label>Zip Code</label><input type="text" name="zip_code_ec" value="{{ old('zip_code_ec') }}"></div>
                </div>
                <div style="margin-top:20px;display:flex;justify-content:flex-end;gap:8px;padding-top:16px;border-top:1px solid #e5e7eb;">
                    <button type="button" onclick="closeModal()" style="padding:8px 18px;background:#fff;color:#6b7280;border:1px solid #ccc;border-radius:6px;cursor:pointer;font-size:13px;">✕ Cancel</button>
                    <button type="submit" class="save-btn">💾 Add Employee</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let _archiveEmpId = null;
        const roleNames   = { 1: 'Super Admin', 2: 'Admin', 3: 'Timekeeper', 4: 'Finance' };
        const CSRF        = document.querySelector('meta[name="csrf-token"]').content;

        // ═══════════ MULTISELECT ═══════════════════════════════

        function getChecked() {
            return [...document.querySelectorAll('.emp-checkbox:checked')];
        }

        function onCheckboxChange() {
            const checked = getChecked();
            const total   = document.querySelectorAll('.emp-checkbox').length;
            const bar     = document.getElementById('bulkBar');

            // Highlight rows
            document.querySelectorAll('.emp-checkbox').forEach(cb => {
                const row = cb.closest('tr');
                if (row) row.classList.toggle('row-selected', cb.checked);
            });

            // Update select-all state
            const selectAll = document.getElementById('selectAll');
            if (selectAll) {
                selectAll.checked       = checked.length === total && total > 0;
                selectAll.indeterminate = checked.length > 0 && checked.length < total;
            }

            // Show/hide bulk bar
            if (checked.length > 0) {
                bar.style.display = 'flex';
                document.getElementById('bulkCount').textContent = `${checked.length} employee${checked.length > 1 ? 's' : ''} selected`;
            } else {
                bar.style.display = 'none';
            }
        }

        function toggleSelectAll(masterCb) {
            document.querySelectorAll('.emp-checkbox').forEach(cb => {
                cb.checked = masterCb.checked;
            });
            onCheckboxChange();
        }

        function clearSelection() {
            document.querySelectorAll('.emp-checkbox').forEach(cb => cb.checked = false);
            const sa = document.getElementById('selectAll');
            if (sa) { sa.checked = false; sa.indeterminate = false; }
            onCheckboxChange();
        }

        // ═══════════ BULK ARCHIVE MODAL ════════════════════════

        function openBulkArchiveModal() {
            const checked = getChecked();
            if (!checked.length) return;

            document.getElementById('bulkArchiveCount').textContent = checked.length;
            document.getElementById('bulkArchiveNames').innerHTML =
                checked.map(cb => `• ${cb.dataset.name}`).join('<br>');
            document.getElementById('bulkArchiveReason').value = '';
            document.getElementById('bulkArchiveError').style.display   = 'none';
            document.getElementById('bulkArchiveProgress').style.display = 'none';
            document.querySelectorAll('input[name="bulkStatusRadio"]').forEach(r => r.checked = false);
            document.getElementById('bulkArchiveSubmitBtn').disabled = false;
            document.getElementById('bulkArchiveSubmitBtn').textContent = '🗄️ Archive All';
            document.getElementById('bulkCancelBtn').disabled = false;

            document.getElementById('bulkArchiveModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeBulkArchiveModal() {
            document.getElementById('bulkArchiveModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        async function submitBulkArchive() {
            const selected = document.querySelector('input[name="bulkStatusRadio"]:checked');
            const errBox   = document.getElementById('bulkArchiveError');
            const btn      = document.getElementById('bulkArchiveSubmitBtn');
            const cancelBtn = document.getElementById('bulkCancelBtn');

            if (!selected) {
                errBox.textContent   = 'Please select a status (Terminated or Inactive).';
                errBox.style.display = 'block';
                return;
            }

            const ids    = getChecked().map(cb => parseInt(cb.value));
            const status = selected.value;
            const reason = document.getElementById('bulkArchiveReason').value;

            btn.disabled      = true;
            cancelBtn.disabled = true;
            errBox.style.display = 'none';

            // Show progress bar
            const progressWrap = document.getElementById('bulkArchiveProgress');
            const progressBar  = document.getElementById('bulkProgressBar');
            const progressText = document.getElementById('bulkProgressText');
            progressWrap.style.display = 'block';

            let done = 0;
            const failed = [];

            for (const id of ids) {
                try {
                    const res = await fetch(`/superadmin/employees/${id}/archive`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                        body: JSON.stringify({ status, archive_reason: reason }),
                    });
                    const data = await res.json();
                    if (data.success) {
                        // Animate row out
                        const row = document.getElementById('emp-row-' + id);
                        if (row) {
                            row.style.transition = 'opacity 0.3s, transform 0.3s';
                            row.style.opacity    = '0';
                            row.style.transform  = 'translateX(20px)';
                            setTimeout(() => row.remove(), 320);
                        }
                    } else {
                        failed.push(id);
                    }
                } catch {
                    failed.push(id);
                }

                done++;
                const pct = Math.round((done / ids.length) * 100);
                progressBar.style.width  = pct + '%';
                progressText.textContent = `${done} / ${ids.length} processed...`;
            }

            btn.disabled       = false;
            cancelBtn.disabled = false;

            if (failed.length === 0) {
                closeBulkArchiveModal();
                clearSelection();
                showToast(`${ids.length} employee${ids.length > 1 ? 's' : ''} archived successfully.`, 'success');
            } else {
                errBox.textContent   = `${failed.length} employee(s) failed to archive. Others were successful.`;
                errBox.style.display = 'block';
                btn.textContent      = '🗄️ Archive All';
                clearSelection();
            }
        }

        // ═══════════ ADD PHOTO ═════════════════════════════════
        function previewAddPhoto(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            if (file.size > 2 * 1024 * 1024) { alert('Image must be under 2MB.'); input.value = ''; return; }
            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById('addAvatarImg').src                = e.target.result;
                document.getElementById('addAvatarImg').style.display      = 'block';
                document.getElementById('addAvatarInitial').style.display  = 'none';
                document.getElementById('addRemovePhotoBtn').style.display = 'inline-block';
            };
            reader.readAsDataURL(file);
        }

        function removeAddPhoto() {
            const inp = document.getElementById('addPhotoInput');
            if (inp) inp.value = '';
            const img = document.getElementById('addAvatarImg');
            if (img) { img.src = ''; img.style.display = 'none'; }
            const btn = document.getElementById('addRemovePhotoBtn');
            if (btn) btn.style.display = 'none';
            const init = document.getElementById('addAvatarInitial');
            if (init) init.style.display = 'block';
        }

        function openModal() {
            removeAddPhoto();
            document.getElementById('employeeAddModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            removeAddPhoto();
            document.getElementById('employeeAddModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // ═══════════ EDIT PHOTO ════════════════════════════════
        function previewEditPhoto(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            if (file.size > 2 * 1024 * 1024) { alert('Image must be under 2MB.'); input.value = ''; return; }
            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById('editAvatarImg').src                = e.target.result;
                document.getElementById('editAvatarImg').style.display      = 'block';
                document.getElementById('editAvatarInitial').style.display  = 'none';
                document.getElementById('editRemovePhotoBtn').style.display = 'inline-block';
                document.getElementById('editRemovePhotoFlag').value        = '0';
            };
            reader.readAsDataURL(file);
        }

        function removeEditPhoto() {
            document.getElementById('editPhotoInput').value             = '';
            document.getElementById('editAvatarImg').src                = '';
            document.getElementById('editAvatarImg').style.display      = 'none';
            document.getElementById('editRemovePhotoBtn').style.display = 'none';
            document.getElementById('editRemovePhotoFlag').value        = '1';
            const firstName = document.getElementById('editFirstName').value;
            const initial   = document.getElementById('editAvatarInitial');
            initial.textContent   = (firstName || '?').charAt(0).toUpperCase();
            initial.style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('employeeEditModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // ═══════════ AVATAR HELPER ═════════════════════════════
        function setAvatar(imgEl, initialEl, photoPath, firstName) {
            if (photoPath) {
                imgEl.src             = '/storage/' + photoPath;
                imgEl.style.display   = 'block';
                initialEl.style.display = 'none';
                imgEl.onerror = () => { imgEl.style.display = 'none'; initialEl.style.display = 'block'; };
            } else {
                imgEl.style.display     = 'none';
                initialEl.textContent   = (firstName || '?').charAt(0).toUpperCase();
                initialEl.style.display = 'block';
            }
        }

        // ═══════════ VIEW MODAL ════════════════════════════════
        function openViewModal(u) {
            const fullName = [u.first_name, u.middle_name, u.last_name, u.suffixes].filter(Boolean).join(' ');
            document.getElementById('viewFullName').textContent   = fullName || 'N/A';
            document.getElementById('profileInitial').textContent = (u.first_name || '?').charAt(0).toUpperCase();
            document.getElementById('viewRoleBadge').textContent  = `${roleNames[u.role_id] ?? 'Employee'} | #${u.id}`;
            document.getElementById('viewBirthdate').textContent  = u.birthdate
                ? new Date(u.birthdate).toLocaleDateString('en-US', { year:'numeric', month:'long', day:'numeric' })
                : 'N/A';
            document.getElementById('viewGender').textContent   = u.gender         ?? 'N/A';
            document.getElementById('viewContact').textContent  = u.contact_number ?? 'N/A';
            document.getElementById('viewUsername').textContent = u.username        ?? 'N/A';
            document.getElementById('viewAddress').textContent  =
                [u.house_number, u.purok, u.barangay, u.city, u.province, u.zip_code].filter(Boolean).join(', ') || 'N/A';
            document.getElementById('viewSss').textContent        = u.sss        ?? 'N/A';
            document.getElementById('viewPhilhealth').textContent = u.philhealth ?? 'N/A';
            document.getElementById('viewPagibig').textContent    = u.pagibig    ?? 'N/A';
            document.getElementById('viewEcName').textContent     =
                [u.first_name_ec, u.middle_name_ec, u.last_name_ec].filter(Boolean).join(' ') || 'N/A';
            document.getElementById('viewEcContact').textContent = u.contact_number_ec ?? 'N/A';
            document.getElementById('viewEcEmail').textContent   = u.email_ec          ?? 'N/A';
            document.getElementById('viewEcAddress').textContent =
                [u.house_number_ec, u.purok_ec, u.barangay_ec, u.city_ec, u.province_ec, u.country_ec, u.zip_code_ec].filter(Boolean).join(', ') || 'N/A';
            setAvatar(document.getElementById('profileImg'), document.getElementById('profileInitial'), u.photo, u.first_name);
            document.getElementById('viewUserModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeViewUserModal() {
            document.getElementById('viewUserModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // ═══════════ ROW LISTENERS ═════════════════════════════
        function attachRowListeners() {
            document.querySelectorAll('.clickable-row').forEach(row => {
                row.addEventListener('click', function(e) {
                    if (e.target.tagName.toLowerCase() === 'button') return;
                    if (e.target.classList.contains('row-checkbox')) return;
                    openViewModal(JSON.parse(this.dataset.user));
                });
            });
            // Re-attach checkbox listeners after AJAX re-render
            document.querySelectorAll('.emp-checkbox').forEach(cb => {
                cb.addEventListener('change', onCheckboxChange);
            });
        }

        // ═══════════ EDIT EMPLOYEE ═════════════════════════════
        function editEmployee(id) {
            fetch(`/superadmin/employees/${id}/modal`)
                .then(r => r.json())
                .then(d => {
                    document.getElementById('editFirstName').value   = d.first_name      ?? '';
                    document.getElementById('editLastName').value    = d.last_name       ?? '';
                    document.getElementById('editMiddleName').value  = d.middle_name     ?? '';
                    document.getElementById('editSuffixes').value    = d.suffixes        ?? '';
                    document.getElementById('editBirthdate').value   = d.birthdate ? d.birthdate.substring(0,10) : '';
                    document.getElementById('editGender').value      = d.gender          ?? '';
                    document.getElementById('editContact').value     = d.contact_number  ?? '';
                    document.getElementById('editPosition').value    = d.position        ?? '';
                    document.getElementById('editUsername').value    = d.username        ?? '';
                    document.getElementById('editPassword').value    = '';
                    document.getElementById('editRemovePhotoFlag').value = '0';
                    document.getElementById('editPhotoInput').value      = '';
                    setAvatar(document.getElementById('editAvatarImg'), document.getElementById('editAvatarInitial'), d.photo, d.first_name);
                    document.getElementById('editRemovePhotoBtn').style.display = d.photo ? 'inline-block' : 'none';
                    document.getElementById('editHouseNumber').value = d.house_number ?? '';
                    document.getElementById('editPurok').value       = d.purok        ?? '';
                    document.getElementById('editBarangay').value    = d.barangay     ?? '';
                    document.getElementById('editCity').value        = d.city         ?? '';
                    document.getElementById('editProvince').value    = d.province     ?? '';
                    document.getElementById('editZipCode').value     = d.zip_code     ?? '';
                    document.getElementById('editSss').value        = d.sss        ?? '';
                    document.getElementById('editPhilhealth').value = d.philhealth ?? '';
                    document.getElementById('editPagibig').value    = d.pagibig    ?? '';
                    document.getElementById('editFirstNameEc').value   = d.first_name_ec     ?? '';
                    document.getElementById('editLastNameEc').value    = d.last_name_ec      ?? '';
                    document.getElementById('editMiddleNameEc').value  = d.middle_name_ec    ?? '';
                    document.getElementById('editEmailEc').value       = d.email_ec          ?? '';
                    document.getElementById('editContactEc').value     = d.contact_number_ec ?? '';
                    document.getElementById('editHouseNumberEc').value = d.house_number_ec   ?? '';
                    document.getElementById('editPurokEc').value       = d.purok_ec          ?? '';
                    document.getElementById('editBarangayEc').value    = d.barangay_ec       ?? '';
                    document.getElementById('editCityEc').value        = d.city_ec           ?? '';
                    document.getElementById('editProvinceEc').value    = d.province_ec       ?? '';
                    document.getElementById('editCountryEc').value     = d.country_ec        ?? '';
                    document.getElementById('editZipCodeEc').value     = d.zip_code_ec       ?? '';
                    document.getElementById('employeeEditForm').action = `/superadmin/employees/${id}`;
                    document.getElementById('employeeEditModal').style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                })
                .catch(err => alert('Failed to load employee data: ' + err.message));
        }

        // ═══════════ AVATAR HOVER ══════════════════════════════
        const editAvatar = document.getElementById('editAvatarPreview');
        if (editAvatar) {
            const overlay = document.getElementById('editAvatarOverlay');
            editAvatar.addEventListener('mouseenter', () => overlay.style.opacity = '1');
            editAvatar.addEventListener('mouseleave', () => overlay.style.opacity = '0');
        }
        const addAvatar = document.getElementById('addAvatarPreview');
        if (addAvatar) {
            const addOverlay = document.getElementById('addAvatarOverlay');
            addAvatar.addEventListener('mouseenter', () => addOverlay.style.opacity = '1');
            addAvatar.addEventListener('mouseleave', () => addOverlay.style.opacity = '0');
        }

        // ═══════════ SINGLE ARCHIVE MODAL ══════════════════════
        function openArchiveModal(id, firstName, lastName) {
            _archiveEmpId = id;
            document.getElementById('archiveEmployeeName').textContent = `${firstName} ${lastName}`;
            document.getElementById('archiveReasonText').value         = '';
            document.getElementById('archiveErrorMsg').style.display   = 'none';
            document.getElementById('archivePrecheckWarnings').style.display = 'none';
            document.getElementById('archivePrecheckLoading').style.display  = 'block';
            document.getElementById('archiveStatusSection').style.display    = 'none';
            document.getElementById('archiveSubmitBtn').style.display        = 'none';
            document.getElementById('archiveUserAccountWarn').style.display  = 'none';
            document.getElementById('archiveCashAdvanceWarn').style.display  = 'none';
            document.querySelectorAll('input[name="archiveStatusRadio"]').forEach(r => r.checked = false);
            document.getElementById('archiveModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';

            fetch(`/superadmin/employees/${id}/archive-precheck`, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    document.getElementById('archivePrecheckLoading').style.display = 'none';
                    if (data.has_user_account) {
                        document.getElementById('archiveUsername').textContent           = data.username;
                        document.getElementById('archiveUserAccountWarn').style.display  = 'block';
                        document.getElementById('archivePrecheckWarnings').style.display = 'block';
                    }
                    if (data.has_cash_advances) {
                        document.getElementById('archiveCaCount').textContent            = data.cash_advance_count;
                        document.getElementById('archiveCaTotal').textContent            = data.cash_advance_total;
                        document.getElementById('archiveCashAdvanceWarn').style.display  = 'block';
                        document.getElementById('archivePrecheckWarnings').style.display = 'block';
                    }
                    document.getElementById('archiveStatusSection').style.display = 'block';
                    document.getElementById('archiveSubmitBtn').style.display     = 'inline-block';
                })
                .catch(() => {
                    document.getElementById('archivePrecheckLoading').style.display = 'none';
                    document.getElementById('archiveStatusSection').style.display   = 'block';
                    document.getElementById('archiveSubmitBtn').style.display       = 'inline-block';
                });
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
            if (!selected) { errBox.textContent = 'Please select a status.'; errBox.style.display = 'block'; return; }
            const savedId = _archiveEmpId;
            btn.disabled = true; btn.textContent = '⏳ Archiving...'; errBox.style.display = 'none';
            fetch(`/superadmin/employees/${savedId}/archive`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ status: selected.value, archive_reason: document.getElementById('archiveReasonText').value }),
            })
            .then(r => r.json())
            .then(data => {
                btn.disabled = false; btn.textContent = '🗄️ Archive Employee';
                if (data.success) {
                    closeArchiveModal();
                    const row = document.getElementById('emp-row-' + savedId);
                    if (row) { row.style.transition = 'opacity 0.4s,transform 0.4s'; row.style.opacity = '0'; row.style.transform = 'translateX(20px)'; setTimeout(() => row.remove(), 420); }
                    showToast(data.message ?? 'Employee archived.', 'success');
                } else { errBox.textContent = data.message ?? 'Something went wrong.'; errBox.style.display = 'block'; }
            })
            .catch(() => { btn.disabled = false; btn.textContent = '🗄️ Archive Employee'; errBox.textContent = 'Network error.'; errBox.style.display = 'block'; });
        }

        // ═══════════ TOAST ══════════════════════════════════════
        function showToast(message, type = 'success') {
            const t = document.createElement('div');
            t.textContent = (type === 'success' ? '✅ ' : '❌ ') + message;
            Object.assign(t.style, { position:'fixed', bottom:'24px', right:'24px', background: type==='success'?'#198754':'#dc3545', color:'#fff', padding:'12px 20px', borderRadius:'8px', fontSize:'14px', fontWeight:'600', zIndex:'99999', boxShadow:'0 4px 16px rgba(0,0,0,.2)', transition:'opacity .5s' });
            document.body.appendChild(t);
            setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 500); }, 3500);
        }

        // ═══════════ AJAX SEARCH & PAGINATION ══════════════════
        let _searchTimer = null;

        function fetchEmployees(page = 1) {
            const search   = document.getElementById('searchInput').value;
            const position = document.getElementById('positionFilter').value;
            const params   = new URLSearchParams({ search, position, page });

            document.getElementById('employeeTableBody').innerHTML =
                `<tr><td colspan="7" style="text-align:center;padding:24px;color:#94a3b8;">🔍 Searching...</td></tr>`;
            document.getElementById('paginationWrapper').innerHTML = '';
            clearSelection();

            fetch(`{{ route('superadmin.employees') }}?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (!data.employees.length) {
                    document.getElementById('employeeTableBody').innerHTML =
                        `<tr><td colspan="7" class="no-records">No employees found.</td></tr>`;
                } else {
                    document.getElementById('employeeTableBody').innerHTML = data.employees.map(e => {
                        const rating  = (e.rating && e.rating !== 'n/a')
                            ? `<span class="badge badge-rated">${e.rating}</span>`
                            : `<span class="badge badge-pending">n/a</span>`;
                        const userData = JSON.stringify(e).replace(/'/g, "&#39;");
                        const initial  = e.first_name.charAt(0).toUpperCase();
                        const avatar   = e.photo
                            ? `<img src="/storage/${e.photo}" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none';this.parentElement.textContent='${initial}'">`
                            : initial;
                        return `<tr id="emp-row-${e.id}" class="clickable-row"
                                    data-user='${userData}'
                                    data-name="${(e.first_name+' '+e.last_name).toLowerCase()}"
                                    data-position="${(e.position??'').toLowerCase()}">
                                    <td class="col-check" onclick="event.stopPropagation()">
                                        <input type="checkbox" class="row-checkbox emp-checkbox"
                                               value="${e.id}"
                                               data-name="${e.first_name} ${e.last_name}"
                                               onchange="onCheckboxChange()">
                                    </td>
                                    <td>${e.id}</td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:10px;">
                                            <div style="width:34px;height:34px;border-radius:50%;flex-shrink:0;background:linear-gradient(135deg,#6366f1,#4f46e5);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff;overflow:hidden;">${avatar}</div>
                                            <span>${e.first_name} ${e.last_name}</span>
                                        </div>
                                    </td>
                                    <td>${e.position??'N/A'}</td>
                                    <td>${e.gender??'N/A'}</td>
                                    <td>${rating}</td>
                                    <td style="display:flex;gap:6px;">
                                        <button class="edit-btn" onclick="event.stopPropagation();editEmployee(${e.id})">✏️ Edit</button>
                                        <button class="delete-btn" onclick="event.stopPropagation();openArchiveModal(${e.id},'${e.first_name}','${e.last_name}')">🗑️ Archive</button>
                                    </td>
                                </tr>`;
                    }).join('');
                    attachRowListeners();
                }

                const cur  = data.current_page;
                const last = data.last_page;
                let pg = '<div class="pagination">';
                pg += cur <= 1 ? '<span class="disabled">««</span><span class="disabled">«</span>'
                               : `<a href="#" data-page="1">««</a><a href="#" data-page="${cur-1}">«</a>`;
                const s = Math.max(1,cur-2), en = Math.min(last,cur+2);
                for (let i=s;i<=en;i++) pg += i===cur?`<span class="active">${i}</span>`:`<a href="#" data-page="${i}">${i}</a>`;
                pg += cur>=last?'<span class="disabled">»</span><span class="disabled">»»</span>'
                               :`<a href="#" data-page="${cur+1}">»</a><a href="#" data-page="${last}">»»</a>`;
                pg += '</div>';
                document.getElementById('paginationWrapper').innerHTML = pg;
                attachPaginationListeners();
            })
            .catch(() => {
                document.getElementById('employeeTableBody').innerHTML =
                    `<tr><td colspan="7" style="text-align:center;padding:24px;color:#dc3545;">❌ Failed to load.</td></tr>`;
            });
        }

        function attachPaginationListeners() {
            document.querySelectorAll('#paginationWrapper a[data-page]').forEach(link => {
                link.addEventListener('click', function(e) { e.preventDefault(); fetchEmployees(this.dataset.page); });
            });
        }

        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(_searchTimer);
            _searchTimer = setTimeout(() => fetchEmployees(1), 350);
        });
        document.getElementById('positionFilter').addEventListener('change', () => fetchEmployees(1));

        ['viewUserModal','employeeEditModal','archiveModal','bulkArchiveModal','employeeAddModal'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('click', function(e) {
                if (e.target === this) { this.style.display = 'none'; document.body.style.overflow = 'auto'; }
            });
        });

        attachRowListeners();

        

        

        // ═══════════ MUST BE LAST ═══════════════════════════════
        @if($errors->any())
            openModal();
        @endif
    </script>

@else
    <script>window.location.href = "{{ route('login') }}";</script>
@endif

</body>
</html>