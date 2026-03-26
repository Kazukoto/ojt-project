<link rel="stylesheet" href="{{ asset('css/partials/header.css')}}">

<div class="header-top">
    <div>
        <h2 style="margin: 0; color: #333;">Timekeeper</h2>
    </div>
    <div class="user-profile">
        <div class="user-dropdown" id="userDropdown">
            <button class="dropdown-btn" onclick="toggleDropdown()">
                <div class="user-avatar">{{ strtoupper(substr(Session::get('first_name'), 0, 1)) }}</div>
                <span>{{ Session::get('first_name') }}</span>
                <span style="font-size: 12px; color: black;">▼</span>
            </button>
            <div class="dropdown-content">
                <strong style="display: block; padding: 8px 0;">{{ Session::get('full_name') }}</strong>
                <small style="display: block; color: #666; padding: 0 0 8px 0;">
                    Role ID: {{ Session::get('role_id') }}
                </small>
                <small style="display: block; color: #999; padding: 0 0 8px 0; font-size: 11px;">
                    User ID: {{ Session::get('user_id') }}
                </small>
                <hr>
                <a href="#" onclick="viewProfile(event)">👤 My Profile</a>
                <a href="#" onclick="changePassword(event)">🔐 Change Password</a>
                <hr>
                <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="logout-btn">🚪 Logout</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleDropdown() {
        const dropdown = document.getElementById('userDropdown');
        dropdown.classList.toggle('active');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('userDropdown');
        if (dropdown && !dropdown.contains(event.target)) {
            dropdown.classList.remove('active');
        }
    });

    function viewProfile(event) {
        event.preventDefault();
        alert('View profile - to be implemented');
    }

    function changePassword(event) {
        event.preventDefault();
        alert('Change password - to be implemented');
    }
</script>