<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users</title>
    <link rel="stylesheet" href="{{ asset('css/timekeeper/user.css') }}">
</head>
<body>
    @if(Session::has('user_id') && Session::get('role_id') == 3)

    <!-- Sidebar -->
    <nav class="sidenav">
        <div class="logo">LOGO</div>
        <a href="{{ url('timekeeper/index') }}">📊 DASHBOARD</a>
        <a href="{{ url('timekeeper/users') }}" class="active">👥 USERS</a>
        <a href="{{ url('timekeeper/attendance') }}">📋 ATTENDANCE</a>
        <a href="{{ url('timekeeper/employees') }}">👔 EMPLOYEES</a>

        <div class="sidenav-logout">
            <form action="{{ route('logout') }}" method="POST" style="margin: 0;" id="logoutForm">
            @csrf
            <button type="button" class="logout-btn" onclick="confirmLogout()">🚪 Logout</button>
            </form>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1 class="page-title">
                <span class="icon">👥</span>
                Users
            </h1>
        </div>

        <!-- Controls -->
        <form method="GET" action="{{ url('timekeeper/users') }}" class="controls">
            <input type="text" 
                   name="search" 
                   class="search-box" 
                   placeholder="🔍 Search by name..." 
                   value="{{ request('search') }}"
                   id="searchBox">
            
            <select name="position" class="filter-box" onchange="this.form.submit()">
                <option value="">All Positions</option>
                @foreach($positions as $pos)
                    <option value="{{ $pos }}" {{ request('position') == $pos ? 'selected' : '' }}>
                        {{ $pos }}
                    </option>
                @endforeach
            </select>

            <label for="dateFilter">Date:</label>
            <input type="date" name="date" class="date-input" value="{{ request('date', now()->toDateString()) }}"id="dateFilter" readonly>
            <button type="button" class="btn-add" onclick="openModal()">
                + Add Person
            </button>

            <button type="button" class="btn-export" onclick="alert('Export functionality')">
                📥 Export
            </button>
        </form>

        <!-- Table -->
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee No.</th>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Morning</th>
                        <th>Afternoon</th>
                        <th>OT Hours</th>
                    </tr>
                </thead>
                <tbody id="employeeTableBody">
                    @include('timekeeper.partials.users', ['users' => $users])
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
        <div class="pagination">
            @if ($users->onFirstPage())
                <span class="disabled">««</span>
            @else
                <a href="{{ $users->url(1) }}">««</a>
            @endif

            @if ($users->onFirstPage())
                <span class="disabled">«</span>
            @else
                <a href="{{ $users->previousPageUrl() }}">«</a>
            @endif

            @php
                $currentPage = $users->currentPage();
                $lastPage = $users->lastPage();
                $start = max(1, $currentPage - 2);
                $end = min($lastPage, $currentPage + 2);
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
            @else
                <span class="disabled">»</span>
            @endif

            @if ($users->hasMorePages())
                <a href="{{ $users->url($users->lastPage()) }}">»»</a>
            @else
                <span class="disabled">»»</span>
            @endif
        </div>
        @endif
    </div>

    <!-- View User Details Modal -->
    <div id="viewUserModal" class="modal-overlay">
    <div class="modal-box">
        <button class="modal-close" onclick="closeViewUserModal()">×</button>
        
        <h2 class="modal-title">Employee Details</h2>
        
        <div class="modal-content">

            <!-- ================= PERSONAL INFORMATION ================= -->
            <h3>Personal Information</h3>
            <table class="details-table">
                <tr>
                    <th>Employee ID:</th>
                    <td id="employeeId"></td>
                </tr>
                <tr>
                    <th>Full Name:</th>
                    <td id="fullName"></td>
                </tr>
                <tr>
                    <th>Username:</th>
                    <td id="username"></td>
                </tr>
                <tr>
                    <th>Birthdate:</th>
                    <td id="birthdate"></td>
                </tr>
                <tr>
                    <th>Gender:</th>
                    <td id="gender"></td>
                </tr>
                <tr>
                    <th>Position:</th>
                    <td id="position"></td>
                </tr>
                <tr>
                    <th>Rating:</th>
                    <td id="rating"></td>
                </tr>
                <tr>
                    <th>Contact Number:</th>
                    <td id="contactNumber"></td>
                </tr>
                <tr>
                    <th>Address:</th>
                    <td id="address"></td>
                </tr>
            </table>

            <!-- ================= GOVERNMENT DETAILS ================= -->
            <h3>Government Information</h3>
            <table class="details-table">
                <tr>
                    <th>SSS:</th>
                    <td id="sss"></td>
                </tr>
                <tr>
                    <th>PhilHealth:</th>
                    <td id="philhealth"></td>
                </tr>
                <tr>
                    <th>Pag-IBIG:</th>
                    <td id="pagibig"></td>
                </tr>
            </table>

            <!-- ================= EMERGENCY CONTACT ================= -->
            <h3>Emergency Contact</h3>
            <table class="details-table">
                <tr>
                    <th>Full Name:</th>
                    <td id="ecFullName"></td>
                </tr>
                <tr>
                    <th>Contact Number:</th>
                    <td id="ecContact"></td>
                </tr>
                <tr>
                    <th>Email:</th>
                    <td id="ecEmail"></td>
                </tr>
                <tr>
                    <th>Address:</th>
                    <td id="ecAddress"></td>
                </tr>
            </table>

            <!-- ================= RECORD INFO ================= -->
            <h3>Record Information</h3>
            <table class="details-table">
                <tr>
                    <th>Created At:</th>
                    <td id="createdAt"></td>
                </tr>
                <tr>
                    <th>Last Updated:</th>
                    <td id="updatedAt"></td>
                </tr>
            </table>

        </div>
    </div>
</div>

    <!-- Add Employee Modal -->
    <div id="modalOverlay" class="modal-overlay">
        <div class="modal-box large">
            <button class="modal-close" onclick="closeModal()">×</button>
            
            <h2 class="modal-title">Register Employee</h2>
            
            <form action="{{ route('modal.store') }}" method="POST">
                @csrf
                
                <div class="form-section">
                    <h3>Personal Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Last Name <span class="required">*</span></label>
                            <input type="text" name="last_name" required value="{{ old('last_name') }}">
                        </div>
                        <div class="form-group">
                            <label>First Name <span class="required">*</span></label>
                            <input type="text" name="first_name" required value="{{ old('first_name') }}">
                        </div>
                        <div class="form-group">
                            <label>Middle Name <span class="required">*</span></label>
                            <input type="text" name="middle_name" value="{{ old('middle_name') }}">
                        </div>
                        <div class="form-group">
                            <label>Suffixes</label>
                            <input type="text" name="suffixes" value="{{ old('suffixes') }}">
                        </div>
                        <div class="form-group">
                            <label>Contact Number <span class="required">*</span></label>
                            <input type="text" name="contact_number" required value="{{ old('contact_number') }}">
                        </div>
                        <div class="form-group">
                            <label>Birthdate <span class="required">*</span></label>
                            <input type="date" name="birthdate" required value="{{ old('birthdate') }}">
                        </div>
                        <div class="form-group">
                            <label>Gender <span class="required">*</span></label>
                            <select name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Position <span class="required">*</span></label>
                            <select name="position" required>
                                <option value="">Select Position</option>
                                <option value="Engineer" {{ old('position') == 'Engineer' ? 'selected' : '' }}>Engineer</option>
                                <option value="Foreman" {{ old('position') == 'Foreman' ? 'selected' : '' }}>Foreman</option>
                                <option value="Timekeeper" {{ old('position') == 'Timekeeper' ? 'selected' : '' }}>Timekeeper</option>
                                <option value="Finance" {{ old('position') == 'Finance' ? 'selected' : '' }}>Finance</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Address</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>House Number</label>
                            <input type="text" name="house_number" value="{{ old('house_number') }}">
                        </div>
                        <div class="form-group">
                            <label>Purok <span class="required">*</span></label>
                            <input type="text" name="purok" required value="{{ old('purok') }}">
                        </div>
                        <div class="form-group">
                            <label>Barangay <span class="required">*</span></label>
                            <input type="text" name="barangay" required value="{{ old('barangay') }}">
                        </div>
                        <div class="form-group">
                            <label>City <span class="required">*</span></label>
                            <input type="text" name="city" required value="{{ old('city') }}">
                        </div>
                        <div class="form-group">
                            <label>Province <span class="required">*</span></label>
                            <input type="text" name="province" required value="{{ old('province') }}">
                        </div>
                        <div class="form-group">
                            <label>SSS <span class="optional">(optional)</span></label>
                            <input type="text" name="sss" value="{{ old('sss') }}">
                        </div>
                        <div class="form-group">
                            <label>PhilHealth <span class="optional">(optional)</span></label>
                            <input type="text" name="philhealth" value="{{ old('philhealth') }}">
                        </div>
                        <div class="form-group">
                            <label>Pag-IBIG <span class="optional">(optional)</span></label>
                            <input type="text" name="pagibig" value="{{ old('pagibig') }}">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Emergency Contact</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Last Name <span class="required">*</span></label>
                            <input type="text" name="last_name_ec" required value="{{ old('last_name_ec') }}">
                        </div>
                        <div class="form-group">
                            <label>First Name <span class="required">*</span></label>
                            <input type="text" name="first_name_ec" required value="{{ old('first_name_ec') }}">
                        </div>
                        <div class="form-group">
                            <label>Middle Name <span class="required">*</span></label>
                            <input type="text" name="middle_name_ec" value="{{ old('middle_name_ec') }}">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email_ec" value="{{ old('email_ec') }}">
                        </div>
                        <div class="form-group">
                            <label>Contact Number <span class="required">*</span></label>
                            <input type="text" name="contact_number_ec" required value="{{ old('contact_number_ec') }}">
                        </div>
                        <div class="form-group">
                            <label>House Number</label>
                            <input type="text" name="house_number_ec" value="{{ old('house_number_ec') }}">
                        </div>
                        <div class="form-group">
                            <label>Purok <span class="required">*</span></label>
                            <input type="text" name="purok_ec" required value="{{ old('purok_ec') }}">
                        </div>
                        <div class="form-group">
                            <label>Barangay <span class="required">*</span></label>
                            <input type="text" name="barangay_ec" required value="{{ old('barangay_ec') }}">
                        </div>
                        <div class="form-group">
                            <label>City <span class="required">*</span></label>
                            <input type="text" name="city_ec" required value="{{ old('city_ec') }}">
                        </div>
                        <div class="form-group">
                            <label>Province <span class="required">*</span></label>
                            <input type="text" name="province_ec" required value="{{ old('province_ec') }}">
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-save">Register Employee</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('modalOverlay').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            document.getElementById('modalOverlay').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function closeViewUserModal() {
            document.getElementById('viewUserModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modals on outside click
        document.getElementById('modalOverlay').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        document.getElementById('viewUserModal').addEventListener('click', function(e) {
            if (e.target === this) closeViewUserModal();
        });

        // Search functionality
        const searchBox = document.querySelector('input[name="search"]');
        const tableBody = document.getElementById('employeeTableBody');
        let timeout = null;

        searchBox.addEventListener('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const query = searchBox.value;
                fetch(`{{ route('timekeeper.users.search') }}?search=${query}`)
                    .then(response => response.text())
                    .then(html => {
                        tableBody.innerHTML = html;
                    })
                    .catch(err => console.error('Error:', err));
            }, 300);
        });

        // View user details
        document.querySelectorAll('.clickable-row').forEach(row => {
    row.addEventListener('click', function() {
        const user = JSON.parse(this.dataset.user);

        // ================= PERSONAL INFORMATION =================
        document.getElementById('employeeId').innerText = user.id ?? 'N/A';

        document.getElementById('fullName').innerText =
            `${user.first_name ?? ''} ${user.middle_name ?? ''} ${user.last_name ?? ''} ${user.suffixes ?? ''}`.replace(/\s+/g, ' ').trim();

        document.getElementById('username').innerText = user.username ?? 'N/A';
        document.getElementById('birthdate').innerText = user.birthdate ?? 'N/A';
        document.getElementById('gender').innerText = user.gender ?? 'N/A';
        document.getElementById('position').innerText = user.position ?? 'N/A';
        document.getElementById('rating').innerText = user.rating ?? 'N/A';
        document.getElementById('contactNumber').innerText = user.contact_number ?? 'N/A';

        // Full Address (clean formatting)
        document.getElementById('address').innerText =
            [
                user.house_number,
                user.purok,
                user.barangay,
                user.city,
                user.province,
                user.zip_code
            ].filter(Boolean).join(', ') || 'N/A';


        // ================= GOVERNMENT INFORMATION =================
        document.getElementById('sss').innerText = user.sss ?? 'N/A';
        document.getElementById('philhealth').innerText = user.philhealth ?? 'N/A';
        document.getElementById('pagibig').innerText = user.pagibig ?? 'N/A';


        // ================= EMERGENCY CONTACT =================
        document.getElementById('ecFullName').innerText =
            `${user.first_name_ec ?? ''} ${user.middle_name_ec ?? ''} ${user.last_name_ec ?? ''}`
            .replace(/\s+/g, ' ')
            .trim() || 'N/A';

        document.getElementById('ecContact').innerText = user.contact_number_ec ?? 'N/A';
        document.getElementById('ecEmail').innerText = user.email_ec ?? 'N/A';

        document.getElementById('ecAddress').innerText =
            [
                user.house_number_ec,
                user.purok_ec,
                user.barangay_ec,
                user.city_ec,
                user.province_ec,
                user.country_ec,
                user.zip_code_ec
            ].filter(Boolean).join(', ') || 'N/A';


        // ================= RECORD INFORMATION =================
        document.getElementById('createdAt').innerText = user.created_at ?? 'N/A';
        document.getElementById('updatedAt').innerText = user.updated_at ?? 'N/A';


        // Show Modal
        document.getElementById('viewUserModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    });
});

        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
            document.getElementById('logoutForm').submit();
            }
        }   
    </script>

    @else
    <script>
        window.location.href = "{{ route('login') }}";
    </script>
    @endif
</body>
</html>