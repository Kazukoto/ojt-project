    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Users</title>
        <link rel="stylesheet" href="{{ asset('css/partials/sidenav.css') }}">
        <link rel="stylesheet" href="{{ asset('css/finance/user.css') }}">
    </head>
    <body>
    
@if(Session::has('user_id') && Session::get('role_id') == 4)

        @include('finance.partials.sidenav')

        <div class="main-content">

            <div class="page-header">
                <h1>👩🏻‍🚒 Users</h1>
            </div>

            <!-- CONTROLS -->
            <form method="GET" action="{{ url('finance/users') }}" class="controls-bar">
                <input type="text" name="search" class="search-input"
                    placeholder="🔍 Search by Name" value="{{ request('search') }}">

                <select name="position" class="filter-select" onchange="this.form.submit()">
                    <option value="">Filter by Position</option>
                    @foreach($positions as $pos)
                        <option value="{{ $pos }}" {{ request('position') == $pos ? 'selected' : '' }}>
                            {{ $pos }}
                        </option>
                    @endforeach
                </select>

                <input type="date" name="date" class="date-input"
                    value="{{ request('date', now()->toDateString()) }}">

                <div class="spacer"></div>

                <button type="button" class="btn-add" onclick="openModal()">➕ Add Person</button>
                <button type="button" class="btn-export" onclick="alert('Export functionality')">📤 Export</button>
            </form>

            <!-- TABLE -->
            <table>
                <thead>
                    <tr>
                        <th>Employee No.</th>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Morning</th>
                        <th>Afternoon</th>
                        <th>OT</th>
                    </tr>
                </thead>
                <tbody id="employeeTableBody">
                    @include('finance.partials.user', ['users' => $users])
                </tbody>
            </table>

            <!-- PAGINATION -->
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

        <!-- VIEW USER MODAL -->
        <div id="viewUserModal" class="modal-overlay">
            <div class="modal-box">
                <button class="modal-close" onclick="closeViewUserModal()">✕</button>
                <h1>👤 Employee Details</h1>
                <hr>

                <div class="modal-section-title">Personal Info</div>
                <table class="details-table">
                    <tr><th>Full Name</th><td id="fullName"></td></tr>
                    <tr><th>Birthdate</th><td id="birthdate"></td></tr>
                    <tr><th>Gender</th><td id="gender"></td></tr>
                    <tr><th>Position</th><td id="position"></td></tr>
                    <tr><th>Contact Number</th><td id="contactNumber"></td></tr>
                    <tr><th>Username</th><td id="username"></td></tr>
                    <tr><th>Address</th><td id="address"></td></tr>
                </table>

                <div class="modal-section-title">Emergency Contact</div>
                <table class="details-table">
                    <tr><th>Full Name</th><td id="ecFullName"></td></tr>
                    <tr><th>Contact Number</th><td id="ecContact"></td></tr>
                    <tr><th>Email</th><td id="ecEmail"></td></tr>
                    <tr><th>Address</th><td id="ecAddress"></td></tr>
                </table>
            </div>
        </div>

        <!-- REGISTER MODAL -->
        <div id="modalOverlay" class="modal-overlay">
            <div class="modal-box" style="max-width: 900px;">
                <button class="modal-close" onclick="closeModal()">✕</button>
                <h1>➕ Register Employee</h1>
                <hr>

                <form action="{{ route('finance.store') }}" method="POST">
                    @csrf

                    <div class="modal-section-title">Personal Info</div>
                    <div class="form-row">
                        <div class="form-group"><label>Last Name <a>*</a></label><input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Last Name"></div>
                        <div class="form-group"><label>First Name <a>*</a></label><input type="text" name="first_name" value="{{ old('first_name') }}" placeholder="First Name"></div>
                        <div class="form-group"><label>Middle Name</label><input type="text" name="middle_name" value="{{ old('middle_name') }}" placeholder="Middle Name"></div>
                        <div class="form-group"><label>Suffix</label><input type="text" name="suffixes" value="{{ old('suffixes') }}" placeholder="Suffix"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Contact Number <a>*</a></label><input type="text" name="contact_number" value="{{ old('contact_number') }}"></div>
                        <div class="form-group"><label>Birthdate <a>*</a></label><input type="date" name="birthdate" value="{{ old('birthdate') }}"></div>
                        <div class="form-group"><label>Username <a>*</a></label><input type="text" name="username" value="{{ old('username') }}"></div>
                        <div class="form-group"><label>Password <a>*</a></label><input type="password" name="password" value="{{ old('password') }}"></div>
                        <div class="form-group"><label>Confirm Password <a>*</a></label><input type="password" name="password_confirmation" value="{{ old('password_confirmation') }}"></div>
                    
                        <div class="form-group">
                            <label>Gender <a>*</a></label>
                            <select name="gender">
                                <option value="">Select Gender</option>
                                <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                            </select>
                            <div class="form-group">
                            <label>Project <a>*</a></label>
                            <select name="project_id">
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        </div>
                        <div class="form-group">
                            <label>Position <a>*</a></label>
                            <select name="position">
                                <option value="">Select Position</option>
                                <option value="Engineer" {{ old('position') == 'Engineer' ? 'selected' : '' }}>Engineer</option>
                                <option value="Foreman" {{ old('position') == 'Foreman' ? 'selected' : '' }}>Foreman</option>
                                <option value="Timekeeper" {{ old('position') == 'Timekeeper' ? 'selected' : '' }}>Timekeeper</option>
                                <option value="Finance" {{ old('position') == 'Finance' ? 'selected' : '' }}>Finance</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-section-title">Address</div>
                    <div class="form-row">
                        <div class="form-group"><label>House Number</label><input type="text" name="house_number" value="{{ old('house_number') }}"></div>
                        <div class="form-group"><label>Purok <a>*</a></label><input type="text" name="purok" value="{{ old('purok') }}"></div>
                        <div class="form-group"><label>Barangay <a>*</a></label><input type="text" name="barangay" value="{{ old('barangay') }}"></div>
                        <div class="form-group"><label>City <a>*</a></label><input type="text" name="city" value="{{ old('city') }}"></div>
                        <div class="form-group"><label>Province <a>*</a></label><input type="text" name="province" value="{{ old('province') }}"></div>
                    </div>

                    <div class="modal-section-title">Government IDs <span style="font-weight:400; color:#999;">(optional)</span></div>
                    <div class="form-row">
                        <div class="form-group"><label>SSS</label><input type="text" name="sss" value="{{ old('sss') }}"></div>
                        <div class="form-group"><label>Philhealth</label><input type="text" name="philhealth" value="{{ old('philhealth') }}"></div>
                        <div class="form-group"><label>Pagibig</label><input type="text" name="pagibig" value="{{ old('pagibig') }}"></div>
                    </div>

                    <div class="modal-section-title">Emergency Contact</div>
                    <div class="form-row">
                        <div class="form-group"><label>Last Name <a>*</a></label><input type="text" name="last_name_ec" value="{{ old('last_name_ec') }}"></div>
                        <div class="form-group"><label>First Name <a>*</a></label><input type="text" name="first_name_ec" value="{{ old('first_name_ec') }}"></div>
                        <div class="form-group"><label>Middle Name</label><input type="text" name="middle_name_ec" value="{{ old('middle_name_ec') }}"></div>
                        <div class="form-group"><label>Email</label><input type="email" name="email_ec" value="{{ old('email_ec') }}"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Contact Number <a>*</a></label><input type="text" name="contact_number_ec" value="{{ old('contact_number_ec') }}"></div>
                        <div class="form-group"><label>House Number</label><input type="text" name="house_number_ec" value="{{ old('house_number_ec') }}"></div>
                        <div class="form-group"><label>Purok <a>*</a></label><input type="text" name="purok_ec" value="{{ old('purok_ec') }}"></div>
                        <div class="form-group"><label>Barangay <a>*</a></label><input type="text" name="barangay_ec" value="{{ old('barangay_ec') }}"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>City <a>*</a></label><input type="text" name="city_ec" value="{{ old('city_ec') }}"></div>
                        <div class="form-group"><label>Province <a>*</a></label><input type="text" name="province_ec" value="{{ old('province_ec') }}"></div>
                        <div class="form-group"><label>Country</label><input type="text" name="country_ec" value="{{ old('country_ec') }}"></div>
                    </div>

                    <div class="form-footer">
                        <button type="button" class="btn-cancel" onclick="closeModal()">✕ Cancel</button>
                        <button type="submit" class="btn-save">💾 Register</button>
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

            function formatDate(dateString) {
                if (!dateString || dateString === 'N/A') return 'N/A';
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            }

            // Clickable rows → view modal
            document.querySelectorAll('.clickable-row').forEach(row => {
                row.addEventListener('click', function () {
                    const user = JSON.parse(this.dataset.user);
                    document.getElementById('fullName').innerText      = `${user.first_name} ${user.middle_name ?? ''} ${user.last_name} ${user.suffixes ?? ''}`;
                    document.getElementById('birthdate').innerText     = formatDate(user.birthdate);
                    document.getElementById('gender').innerText        = user.gender ?? 'N/A';
                    document.getElementById('position').innerText      = user.position ?? 'N/A';
                    document.getElementById('contactNumber').innerText = user.contact_number ?? 'N/A';
                    document.getElementById('username').innerText      = user.username ?? 'N/A';
                    document.getElementById('address').innerText       = `${user.house_number ?? ''} ${user.purok ?? ''}, ${user.barangay ?? ''}, ${user.city ?? ''}, ${user.province ?? ''}`;
                    document.getElementById('ecFullName').innerText    = `${user.first_name_ec ?? ''} ${user.middle_name_ec ?? ''} ${user.last_name_ec ?? ''}`;
                    document.getElementById('ecContact').innerText     = user.contact_number_ec ?? 'N/A';
                    document.getElementById('ecEmail').innerText       = user.email_ec ?? 'N/A';
                    document.getElementById('ecAddress').innerText     = `${user.house_number_ec ?? ''}, ${user.purok_ec ?? ''}, ${user.barangay_ec ?? ''}, ${user.city_ec ?? ''}, ${user.province_ec ?? ''}`;
                    document.getElementById('viewUserModal').style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                });
            });

            // Close on outside click
            document.getElementById('modalOverlay').addEventListener('click', function(e) {
                if (e.target === this) closeModal();
            });
            document.getElementById('viewUserModal').addEventListener('click', function(e) {
                if (e.target === this) closeViewUserModal();
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