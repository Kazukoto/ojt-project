<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Users</title>
    <link rel="stylesheet" href="{{ asset('css/partials/sidenav.css') }}">
    <link rel="stylesheet" href="{{ asset('css/superadmin/users.css') }}">
</head>

<body>

@if(Session::has('user_id') && Session::get('role_id') == 1)

    @include('superadmin.partials.sidenav')

    <div class="main-content">

        <div class="page-header">
            <h1>👩🏻‍🚒 Users</h1>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                <span>✅ {{ session('success') }}</span>
                <button class="alert-close" onclick="this.parentElement.remove()">✕</button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-error">
                <div style="flex: 1;">
                    <strong>❌ There were some errors with your submission:</strong>
                    <ul class="error-list">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button class="alert-close" onclick="this.parentElement.remove()">✕</button>
            </div>
        @endif

        <!-- CONTROLS -->
        <div class="controls-bar">
            <input
                type="text"
                id="searchUser"
                class="search-input"
                placeholder="🔍 Search by Name"
                value="{{ request('search') }}"
                autocomplete="off"
            >
            <select id="filterPosition" class="filter-select">
                <option value="">Filter by Position</option>
                @foreach($positions as $pos)
                    <option value="{{ $pos }}" {{ request('position') == $pos ? 'selected' : '' }}>
                        {{ $pos }}
                    </option>
                @endforeach
            </select>
            <label>Date:</label>
            <input type="date" id="filterDate" class="date-input" readonly
                value="{{ request('date', now()->toDateString()) }}">
            <div class="spacer"></div>
            <button type="button" class="btn-add" onclick="openModal()">➕ Add User</button>
            <button type="button" class="btn-export" onclick="alert('Export functionality')">📤 Export</button>
        </div>

        <!-- TABLE -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Username</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="employeeTableBody">
                @include('superadmin.partials.user', ['users' => $users])
            </tbody>
        </table>

        <!-- PAGINATION -->
        <div id="paginationContainer">
            @if($users->hasPages())
                <div class="pagination">
                    @if ($users->onFirstPage())
                        <span class="disabled">««</span>
                        <span class="disabled">«</span>
                    @else
                        <a href="{{ $users->url(1) }}">««</a>
                        <a href="{{ $users->previousPageUrl() }}">«</a>
                    @endif
                    @php
                        $currentPage = $users->currentPage();
                        $lastPage    = $users->lastPage();
                        $start       = max(1, $currentPage - 2);
                        $end         = min($lastPage, $currentPage + 2);
                    @endphp
                    @for ($page = $start; $page <= $end; $page++)
                        @if ($page == $currentPage)
                            <span class="active">{{ $page }}</span>
                        @else
                            <a href="{{ $users->url($page) }}">{{ $page }}</a>
                        @endif
                    @endfor
                    @if($end < $lastPage)
                        <span class="disabled">...</span>
                    @endif
                    @if ($users->hasMorePages())
                        <a href="{{ $users->nextPageUrl() }}">»</a>
                        <a href="{{ $users->url($users->lastPage()) }}">»»</a>
                    @else
                        <span class="disabled">»</span>
                        <span class="disabled">»»</span>
                    @endif
                </div>
            @endif
        </div>

    </div>

    <!-- VIEW USER MODAL -->
    <div id="viewUserModal" class="modal-overlay">
        <div class="modal-box">
            <button class="modal-close" onclick="closeViewUserModal()">✕</button>
            <h1>👤 User Details</h1>
            <hr>
            <div class="modal-section-title">Account Info</div>
            <table class="details-table">
                <tr><th>ID</th>        <td id="vUserId"></td></tr>
                <tr><th>Full Name</th> <td id="vFullName"></td></tr>
                <tr><th>Position</th>  <td id="vPosition"></td></tr>
                <tr><th>Username</th>  <td id="vUsername"></td></tr>
                <tr><th>Role ID</th>   <td id="vRoleId"></td></tr>
                <tr><th>Status</th>    <td id="vStatus"></td></tr>
                <tr><th>Created</th>   <td id="vCreated"></td></tr>
            </table>
        </div>
    </div>

    <!-- EDIT USER MODAL -->
    <div id="editUserModal" class="modal-overlay">
        <div class="modal-box" style="max-width:600px;">
            <button class="modal-close" onclick="closeEditUserModal()">✕</button>
            <h1>✏️ Edit User</h1>
            <hr>
            <form id="editUserForm" method="POST">
                @csrf
                @method('PUT')
                <div class="form-row">
                    <div class="form-group">
                        <label>Last Name <span style="color:red">*</span></label>
                        <input type="text" name="last_name" id="edit_last_name" required>
                    </div>
                    <div class="form-group">
                        <label>First Name <span style="color:red">*</span></label>
                        <input type="text" name="first_name" id="edit_first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" id="edit_middle_name">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Username <span style="color:red">*</span></label>
                        <input type="text" name="username" id="edit_username" required>
                    </div>
                    <div class="form-group">
                        <label>Position</label>
                        <input type="text" name="position" id="edit_position">
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role_id" id="edit_role_id">
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->role_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>New Password <span style="color:#9ca3af;font-weight:400;">(leave blank to keep current)</span></label>
                        <input type="password" name="password" id="edit_password" placeholder="Enter new password">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="password_confirmation" id="edit_password_confirmation" placeholder="Confirm new password">
                    </div>
                </div>
                <div class="form-footer">
                    <button type="button" class="btn-cancel" onclick="closeEditUserModal()">✕ Cancel</button>
                    <button type="submit" class="btn-save">💾 Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ARCHIVE / STATUS MODAL -->
    <div id="archiveUserModal" class="modal-overlay">
        <div class="modal-box" style="max-width:420px;">
            <button class="modal-close" onclick="closeArchiveModal()">✕</button>
            <h1>🚫 Change User Status</h1>
            <hr>
            <p style="margin-bottom:16px;color:#374151;font-size:14px;">
                Select a status for <strong id="archiveUserName"></strong>.<br>
                <span style="color:#dc3545;font-size:13px;">⚠️ Terminated or Inactive users will not be able to log in.</span>
            </p>
            <div class="form-group">
                <label>Status <span style="color:red">*</span></label>
                <select id="archiveStatus" style="width:100%;padding:10px;border:1.5px solid #d1d5db;border-radius:8px;font-size:14px;">
                    <option value="active">✅ Active — Can log in</option>
                    <option value="terminated">🔴 Terminated — Cannot log in</option>
                    <option value="inactive">⚫ Inactive — Cannot log in</option>
                </select>
            </div>
            <div class="form-group" id="archiveReasonGroup" style="display:none;">
                <label>Reason</label>
                <textarea id="archiveReasonText" rows="3" placeholder="Optional reason..."
                    style="width:100%;padding:10px;border:1.5px solid #d1d5db;border-radius:8px;font-size:14px;resize:vertical;"></textarea>
            </div>
            <div id="archiveError" style="display:none;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:6px;padding:8px 12px;font-size:13px;margin-bottom:12px;"></div>
            <div class="form-footer">
                <button type="button" class="btn-cancel" onclick="closeArchiveModal()">✕ Cancel</button>
                <button type="button" id="archiveSubmitBtn" class="btn-save" style="background:#dc3545;" onclick="submitArchive()">🚫 Apply Status</button>
            </div>
        </div>
    </div>

    <!-- REGISTER MODAL -->
    <div id="modalOverlay" class="modal-overlay">
        <div class="modal-box" style="max-width:900px;">
            <button class="modal-close" onclick="closeModal()">✕</button>
            <h1>➕ Register Employee</h1>
            <hr>

            <form action="{{ route('superadmin.store') }}" method="POST" id="registerForm" enctype="multipart/form-data">
                @csrf

                {{-- ── PHOTO AT TOP ── --}}
                <div class="modal-section-title">Profile Photo</div>
                <div class="form-row" style="align-items:center;gap:20px;margin-bottom:20px;">
                    <div id="addUserAvatarPreview" style="width:90px;height:90px;border-radius:50%;flex-shrink:0;background:linear-gradient(135deg,#6366f1,#4f46e5);display:flex;align-items:center;justify-content:center;font-size:36px;font-weight:700;color:#fff;overflow:hidden;box-shadow:0 4px 12px rgba(99,102,241,.3);position:relative;cursor:pointer;" onclick="document.getElementById('addUserPhotoInput').click()">
                        <span id="addUserAvatarInitial" style="pointer-events:none;">👤</span>
                        <img id="addUserAvatarImg" src="" alt="" style="display:none;width:100%;height:100%;object-fit:cover;position:absolute;inset:0;">
                        <div style="position:absolute;inset:0;background:rgba(0,0,0,0.35);display:flex;align-items:center;justify-content:center;border-radius:50%;opacity:0;transition:opacity .2s;" id="addUserAvatarOverlay"><span style="color:#fff;font-size:20px;">📷</span></div>
                    </div>
                    <div>
                        <input type="file" id="addUserPhotoInput" name="photo" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="previewUserPhoto(this)">
                        <button type="button" style="padding:8px 16px;background:#f1f5f9;border:1px solid #cbd5e1;border-radius:8px;font-size:13px;cursor:pointer;font-weight:600;color:#374151;" onclick="document.getElementById('addUserPhotoInput').click()">📁 Choose Photo</button>
                        <p style="font-size:12px;color:#94a3b8;margin-top:6px;">JPG, PNG or WEBP · Max 2MB · Optional</p>
                        <button type="button" id="addUserRemovePhotoBtn" onclick="removeUserPhoto()" style="display:none;margin-top:6px;padding:5px 12px;background:#fee2e2;border:1px solid #fca5a5;border-radius:6px;font-size:12px;cursor:pointer;color:#dc2626;font-weight:600;">🗑️ Remove Photo</button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Project <a>*</a></label>
                    <select name="project_id" required>
                        <option value="">Select Project</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="modal-section-title">Personal Info</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Last Name <a>*</a></label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" required>
                    </div>
                    <div class="form-group">
                        <label>First Name <a>*</a></label>
                        <input type="text" name="first_name" value="{{ old('first_name') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" value="{{ old('middle_name') }}">
                    </div>
                    <div class="form-group">
                        <label>Suffix</label>
                        <input type="text" name="suffixes" value="{{ old('suffixes') }}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Contact Number <a>*</a></label>
                        <input type="text" name="contact_number" value="{{ old('contact_number') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Birthdate <a>*</a></label>
                        <input type="date" name="birthdate" value="{{ old('birthdate') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Username <a>*</a></label>
                        <input type="text" name="username" value="{{ old('username') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Password <a>*</a></label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password <a>*</a></label>
                        <input type="password" name="password_confirmation" required>
                    </div>
                    <div class="form-group">
                        <label>Gender <a>*</a></label>
                        <select name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male"   {{ old('gender') == 'Male'   ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Position/Role <a>*</a></label>
                        <select name="role_id" required>
                            <option value="">Select Role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                    {{ $role->role_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-section-title">Address</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>House Number</label>
                        <input type="text" name="house_number" value="{{ old('house_number') }}">
                    </div>
                    <div class="form-group">
                        <label>Purok <a>*</a></label>
                        <input type="text" name="purok" value="{{ old('purok') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Barangay <a>*</a></label>
                        <input type="text" name="barangay" value="{{ old('barangay') }}" required>
                    </div>
                    <div class="form-group">
                        <label>City <a>*</a></label>
                        <input type="text" name="city" value="{{ old('city') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Province <a>*</a></label>
                        <input type="text" name="province" value="{{ old('province') }}" required>
                    </div>
                </div>

                <div class="modal-section-title">Government IDs <span style="font-weight:400;color:#999;">(optional)</span></div>
                <div class="form-row">
                    <div class="form-group">
                        <label>SSS</label>
                        <input type="text" name="sss" value="{{ old('sss') }}">
                    </div>
                    <div class="form-group">
                        <label>Philhealth</label>
                        <input type="text" name="philhealth" value="{{ old('philhealth') }}">
                    </div>
                    <div class="form-group">
                        <label>Pagibig</label>
                        <input type="text" name="pagibig" value="{{ old('pagibig') }}">
                    </div>
                </div>

                <div class="modal-section-title">Emergency Contact</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Last Name <a>*</a></label>
                        <input type="text" name="last_name_ec" value="{{ old('last_name_ec') }}" required>
                    </div>
                    <div class="form-group">
                        <label>First Name <a>*</a></label>
                        <input type="text" name="first_name_ec" value="{{ old('first_name_ec') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name_ec" value="{{ old('middle_name_ec') }}">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email_ec" value="{{ old('email_ec') }}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Contact Number <a>*</a></label>
                        <input type="text" name="contact_number_ec" value="{{ old('contact_number_ec') }}" required>
                    </div>
                    <div class="form-group">
                        <label>House Number</label>
                        <input type="text" name="house_number_ec" value="{{ old('house_number_ec') }}">
                    </div>
                    <div class="form-group">
                        <label>Purok <a>*</a></label>
                        <input type="text" name="purok_ec" value="{{ old('purok_ec') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Barangay <a>*</a></label>
                        <input type="text" name="barangay_ec" value="{{ old('barangay_ec') }}" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>City <a>*</a></label>
                        <input type="text" name="city_ec" value="{{ old('city_ec') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Province <a>*</a></label>
                        <input type="text" name="province_ec" value="{{ old('province_ec') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Country</label>
                        <input type="text" name="country_ec" value="{{ old('country_ec') }}">
                    </div>
                </div>

                <div class="form-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">✕ Cancel</button>
                    <button type="submit" class="btn-save">💾 Register</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ── ADD USER PHOTO ────────────────────────────────────
        function previewUserPhoto(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            if (file.size > 2 * 1024 * 1024) { alert('Image must be under 2MB.'); input.value = ''; return; }
            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById('addUserAvatarImg').src                = e.target.result;
                document.getElementById('addUserAvatarImg').style.display      = 'block';
                document.getElementById('addUserAvatarInitial').style.display  = 'none';
                document.getElementById('addUserRemovePhotoBtn').style.display = 'inline-block';
            };
            reader.readAsDataURL(file);
        }

        function removeUserPhoto() {
            document.getElementById('addUserPhotoInput').value              = '';
            document.getElementById('addUserAvatarImg').src                 = '';
            document.getElementById('addUserAvatarImg').style.display       = 'none';
            document.getElementById('addUserRemovePhotoBtn').style.display  = 'none';
            document.getElementById('addUserAvatarInitial').style.display   = 'block';
        }

        // ── MODAL HELPERS ─────────────────────────────────────
        function openModal() {
            removeUserPhoto();
            document.getElementById('modalOverlay').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            removeUserPhoto();
            document.getElementById('modalOverlay').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function closeViewUserModal() {
            document.getElementById('viewUserModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function closeEditUserModal() {
            document.getElementById('editUserModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function closeArchiveModal() {
            document.getElementById('archiveUserModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            document.getElementById('archiveError').style.display = 'none';
            document.getElementById('archiveReasonGroup').style.display = 'none';
            document.getElementById('archiveReasonText').value = '';
            document.getElementById('archiveStatus').value = 'active';
            _archiveUserId = null;
        }

        // ── Avatar hover overlay ──────────────────────────────
        const addUserAvatar = document.getElementById('addUserAvatarPreview');
        if (addUserAvatar) {
            const ov = document.getElementById('addUserAvatarOverlay');
            addUserAvatar.addEventListener('mouseenter', () => ov.style.opacity = '1');
            addUserAvatar.addEventListener('mouseleave', () => ov.style.opacity = '0');
        }

        // ── Auto-open register modal on validation error ───────
        @if($errors->any())
            openModal();
        @endif

        // ── Auto-hide alerts ───────────────────────────────────
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(a => {
                a.style.transition = 'opacity 0.5s';
                a.style.opacity = '0';
                setTimeout(() => a.remove(), 500);
            });
        }, 5000);

        // ── Date formatter ─────────────────────────────────────
        function formatDate(d) {
            if (!d || d === 'N/A') return 'N/A';
            return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        }

        // ── Users data for client-side lookup ──────────────────
        let allUsers = @json($users->items());

        // ── VIEW modal ─────────────────────────────────────────
        function openViewModal(userId) {
            const u = allUsers.find(u => u.id == userId);
            if (!u) return;

            document.getElementById('vUserId').innerText   = u.id;
            document.getElementById('vFullName').innerText = [u.first_name, u.middle_name, u.last_name].filter(Boolean).join(' ');
            document.getElementById('vPosition').innerText = u.position ?? 'N/A';
            document.getElementById('vUsername').innerText = u.username ?? 'N/A';
            document.getElementById('vRoleId').innerText   = u.role_id  ?? 'N/A';
            document.getElementById('vCreated').innerText  = formatDate(u.created_at);

            fetch(`/superadmin/users/${userId}/status`)
                .then(r => r.json())
                .then(data => {
                    const color = data.status === 'active' ? '#198754' : data.status === 'terminated' ? '#dc3545' : '#6c757d';
                    const label = data.status ? data.status.charAt(0).toUpperCase() + data.status.slice(1) : 'N/A';
                    document.getElementById('vStatus').innerHTML =
                        `<span style="padding:2px 10px;border-radius:12px;font-size:12px;font-weight:600;color:white;background:${color}">${label}</span>`;
                })
                .catch(() => { document.getElementById('vStatus').innerText = 'N/A'; });

            document.getElementById('viewUserModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        // ── EDIT modal ─────────────────────────────────────────
        function openEditModal(userId) {
            const u = allUsers.find(u => u.id == userId);
            if (!u) return;

            document.getElementById('edit_last_name').value   = u.last_name   ?? '';
            document.getElementById('edit_first_name').value  = u.first_name  ?? '';
            document.getElementById('edit_middle_name').value = u.middle_name ?? '';
            document.getElementById('edit_username').value    = u.username    ?? '';
            document.getElementById('edit_position').value    = u.position    ?? '';
            document.getElementById('edit_role_id').value     = u.role_id     ?? '';
            document.getElementById('edit_password').value    = '';
            document.getElementById('edit_password_confirmation').value = '';

            document.getElementById('editUserForm').action = `/superadmin/users/${userId}`;
            document.getElementById('editUserModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        // ── ARCHIVE/STATUS modal ───────────────────────────────
        let _archiveUserId = null;

        function openArchiveModal(userId, name) {
            _archiveUserId = userId;
            document.getElementById('archiveUserName').innerText = name;
            document.getElementById('archiveError').style.display = 'none';
            document.getElementById('archiveReasonGroup').style.display = 'none';
            document.getElementById('archiveReasonText').value = '';
            document.getElementById('archiveStatus').value = 'active';

            document.getElementById('archiveStatus').onchange = function () {
                document.getElementById('archiveReasonGroup').style.display =
                    this.value !== 'active' ? 'block' : 'none';
            };

            document.getElementById('archiveUserModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function submitArchive() {
            const status  = document.getElementById('archiveStatus').value;
            const reason  = document.getElementById('archiveReasonText').value;
            const btn     = document.getElementById('archiveSubmitBtn');
            const errBox  = document.getElementById('archiveError');
            const savedId = _archiveUserId;

            btn.disabled    = true;
            btn.textContent = '⏳ Processing...';
            errBox.style.display = 'none';

            fetch(`/superadmin/users/${savedId}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept':       'application/json',
                },
                body: JSON.stringify({ _method: 'PATCH', status, archive_reason: reason }),
            })
            .then(r => r.json())
            .then(data => {
                btn.disabled    = false;
                btn.textContent = '🚫 Apply Status';
                if (data.success) {
                    closeArchiveModal();
                    if (status !== 'active') {
                        const row = document.getElementById('user-row-' + savedId);
                        if (row) {
                            row.style.transition = 'opacity 0.4s, transform 0.4s';
                            row.style.opacity    = '0';
                            row.style.transform  = 'translateX(20px)';
                            setTimeout(() => row.remove(), 420);
                        }
                    }
                    showToast(data.message, 'success');
                } else {
                    errBox.textContent   = data.message ?? 'Something went wrong.';
                    errBox.style.display = 'block';
                }
            })
            .catch(() => {
                btn.disabled    = false;
                btn.textContent = '🚫 Apply Status';
                errBox.textContent   = 'Network error. Please try again.';
                errBox.style.display = 'block';
            });
        }

        // ── Toast ──────────────────────────────────────────────
        function showToast(message, type = 'success') {
            const t = document.createElement('div');
            t.textContent = (type === 'success' ? '✅ ' : '❌ ') + message;
            Object.assign(t.style, {
                position: 'fixed', bottom: '24px', right: '24px',
                background: type === 'success' ? '#198754' : '#dc3545',
                color: '#fff', padding: '12px 20px', borderRadius: '8px',
                fontSize: '14px', fontWeight: '600', zIndex: '99999',
                boxShadow: '0 4px 16px rgba(0,0,0,0.2)', transition: 'opacity 0.5s',
            });
            document.body.appendChild(t);
            setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 500); }, 3500);
        }

        // ── Close modals on outside click ──────────────────────
        ['modalOverlay','viewUserModal','editUserModal','archiveUserModal'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('click', function (e) {
                if (e.target === this) { this.style.display = 'none'; document.body.style.overflow = 'auto'; }
            });
        });

        // ── AJAX Search ────────────────────────────────────────
        let searchTimer = null;
        const searchRoute = '{{ route('superadmin.users') }}';

        function doSearch() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                const params = new URLSearchParams({
                    search:   document.getElementById('searchUser').value,
                    position: document.getElementById('filterPosition').value,
                    date:     document.getElementById('filterDate').value,
                });

                fetch(`${searchRoute}?${params.toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    document.getElementById('employeeTableBody').innerHTML = data.table;
                    document.getElementById('paginationContainer').innerHTML = data.pagination;
                    // Update allUsers so view/edit modals still work after search
                    allUsers = data.users ?? allUsers;
                })
                .catch(err => console.error('Search error:', err));
            }, 400);
        }

        document.getElementById('searchUser').addEventListener('input', doSearch);
        document.getElementById('filterPosition').addEventListener('change', doSearch);
    </script>

@else
    <script>window.location.href = "{{ route('login') }}";</script>
@endif

</body>
</html>