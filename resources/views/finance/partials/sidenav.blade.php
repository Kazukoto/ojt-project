<nav class="sidenav">
    <div class="logo">
        <img src="{{ asset('images/sample.jpg') }}" alt="Logo" onerror="console.log('Image failed to load')">
    </div>

    <a href="{{ url('finance/') }}"            class="{{ request()->is('finance') ? 'active' : '' }}">📊 DASHBOARD</a>
    <a href="{{ url('finance/employees') }}"   class="{{ request()->is('finance/employees*') ? 'active' : '' }}">📋 EMPLOYEES</a>
    <a href="{{ url('finance/attendance') }}"  class="{{ request()->is('finance/attendance*') ? 'active' : '' }}">👥 ATTENDANCE</a>
    <a href="{{ url('finance/holiday') }}"     class="{{ request()->is('finance/holiday*') ? 'active' : '' }}">🎅🏻 HOLIDAYS</a>
    <a href="{{ url('finance/cashadvance') }}" class="{{ request()->is('finance/cashadvance*') ? 'active' : '' }}">👌🏻 STATUTORY</a>
    <a href="{{ url('finance/payroll') }}"     class="{{ request()->is('finance/payroll*') ? 'active' : '' }}">💸 PAYROLL</a>
    <a href="{{ url('finance/payslip') }}"     class="{{ request()->is('finance/payslip*') ? 'active' : '' }}">👔 PAYSLIPS</a>
    <!--a href="{{ url('finance/statutory') }}"    class="{{ request()->is('finance/statutory*') ? 'active' : '' }}">👌🏻 STATUTORY</a-->
    <div class="sidenav-logout">
        <form action="{{ route('logout') }}" method="POST" style="margin: 0;" id="logoutForm">
            @csrf
            <button type="button" class="logout-btn" onclick="confirmLogout()">🚪 Logout</button>
        </form>
    </div>
</nav>