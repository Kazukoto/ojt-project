<nav class="sidenav">
    <div class="logo">
                <img src="{{ asset('images/sample.jpg') }}" alt="Logo" onerror="console.log('Image failed to load')">
            </div>
    <a href="{{ url('admin/') }}" class="{{ request()->is('admin') ? 'active' : '' }}">📊 DASHBOARD</a>
    <a href="{{ url('admin/users') }}" class="{{ request()->is('admin/users*') ? 'active' : '' }}">👩🏻‍🚒 USERS</a>
    <a href="{{ url('admin/payroll') }}" class="{{ request()->is('admin/payroll*') ? 'active' : '' }}">💸 PAYROLL</a>
    <a href="{{ url('admin/payslip') }}" class="{{ request()->is('admin/payslip*') ? 'active' : '' }}">👔 PAYSLIPS</a>
    <a href="{{ url('admin/cashadvance') }}" class="{{ request()->is('admin/cashadvance*') ? 'active' : '' }}">👌🏻 STATUTORY</a>
    <a href="{{ url('admin/attendance') }}" class="{{ request()->is('admin/attendance*') ? 'active' : '' }}">👥 ATTENDANCE</a>
    <a href="{{ url('admin/holiday') }}" class="{{ request()->is('admin/holiday*') ? 'active' : '' }}">🎅🏻 HOLIDAYS</a>
    <a href="{{ url('admin/employees') }}" class="{{ request()->is('admin/employees*') ? 'active' : '' }}">📋 EMPLOYEES</a>
    <a href="{{ url('admin/rateapproval') }}" class="{{ request()->is('admin/rateapproval*') ? 'active' : '' }}">⭐ RATE APPROVAL</a>
    <a href="{{ url('admin/archive') }}" class="{{ request()->is('admin/archive*') ? 'active' : '' }}">👻 ARCHIVES</a>
    <a href="{{ url('admin/rolemanagement') }}" class="{{ request()->is('admin/rolemanagement*') ? 'active' : '' }}">🙎🏻‍♀️ ROLES</a>
    <!--a href="{{ url('admin/statutory') }}" class="{{ request()->is('admin/statutory*') ? 'active' : '' }}">👌🏻 STATUTORY</a-->
    <div class="sidenav-logout">
        <form action="{{ route('logout') }}" method="POST" style="margin: 0;" id="logoutForm">
            @csrf
            <button type="button" class="logout-btn" onclick="confirmLogout()">🚪 Logout</button>
        </form>
    </div>
</nav>