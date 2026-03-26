<nav class="sidenav">
    <div class="logo">
        <img src="{{ asset('images/sample.jpg') }}" alt="Logo" onerror="console.log('Image failed to load')">
    </div>

    <a href="{{ url('timekeeper/index') }}" class="{{ request()->is('timekeeper') || request()->is('timekeeper/index') ? 'active' : '' }}">📊 DASHBOARD</a>
    <a href="{{ url('timekeeper/attendance') }}" class="{{ request()->is('timekeeper/attendance*') ? 'active' : '' }}">👥 ATTENDANCE</a>
    <a href="{{ url('timekeeper/holiday') }}" class="{{ request()->is('timekeeper/holiday*') ? 'active' : '' }}">🎅🏻 HOLIDAYS</a>
    <a href="{{ url('timekeeper/employees') }}" class="{{ request()->is('timekeeper/employees*') ? 'active' : '' }}">📋 EMPLOYEES</a>

    <div class="sidenav-logout">
        <form action="{{ route('logout') }}" method="POST" style="margin: 0;" id="logoutForm">
            @csrf
            <button type="button" class="logout-btn" onclick="confirmLogout()">🚪 Logout</button>
        </form>
    </div>
</nav>