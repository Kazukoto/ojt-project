<nav class="sidenav">
    <div class="logo">
        <img src="{{ asset('images/sample.jpg') }}" alt="Logo" onerror="console.log('Image failed to load')">
    </div>

    <a href="{{ url('superadmin/') }}"            class="{{ request()->is('superadmin') ? 'active' : '' }}">📊 DASHBOARD</a>
    <a href="{{ url('superadmin/users') }}"        class="{{ request()->is('superadmin/users*') ? 'active' : '' }}">👩🏻‍🚒 USERS</a>
    <a href="{{ url('superadmin/payroll') }}"      class="{{ request()->is('superadmin/payroll*') ? 'active' : '' }}">💸 PAYROLL</a>
    <a href="{{ url('superadmin/payslip') }}"      class="{{ request()->is('superadmin/payslip*') ? 'active' : '' }}">👔 PAYSLIPS</a>
    <a href="{{ url('superadmin/cashadvance') }}"  class="{{ request()->is('superadmin/cashadvance*') ? 'active' : '' }}">👌🏻 STATUTORY</a>
    <a href="{{ url('superadmin/attendance') }}"   class="{{ request()->is('superadmin/attendance*') ? 'active' : '' }}">👥 ATTENDANCE</a>
    <a href="{{ url('superadmin/holiday') }}"      class="{{ request()->is('superadmin/holiday*') ? 'active' : '' }}">🎅🏻 HOLIDAYS</a>
    <a href="{{ url('superadmin/employees') }}"    class="{{ request()->is('superadmin/employees*') ? 'active' : '' }}">📋 EMPLOYEES</a>
    <a href="{{ url('superadmin/rateapproval') }}" class="{{ request()->is('superadmin/rateapproval*') ? 'active' : '' }}">⭐ RATE APPROVAL</a>
    <a href="{{ url('superadmin/archive') }}"      class="{{ request()->is('superadmin/archive*') ? 'active' : '' }}">👻 ARCHIVES</a>
    <a href="{{ url('superadmin/rolemanagement') }}" class="{{ request()->is('superadmin/rolemanagement*') ? 'active' : '' }}">🙎🏻‍♀️ ROLES</a>
    <!--a href="{{ url('superadmin/statutory') }}"    class="{{ request()->is('superadmin/statutory*') ? 'active' : '' }}">👌🏻 STATUTORY</a-->

    <div class="sidenav-logout">
        <form action="{{ route('logout') }}" method="POST" style="margin: 0;" id="logoutForm">
            @csrf
            <button type="button" class="logout-btn" onclick="confirmLogout()">🚪 Logout</button>
        </form>
    </div>
</nav>