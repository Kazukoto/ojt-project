<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role Management</title>
    <link rel="stylesheet" href="{{ asset('css/superadmin/rolemanagement.css') }}">
    <link rel="stylesheet" href="{{ asset('css/partials/sidenav.css') }}">
</head>
<body>
    <!-- Sidebar -->
    @include('admin.partials.sidenav')

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1 class="page-title">
                <span class="icon">⭐</span>
                Role Management
            </h1>
        </div>

        <!-- Tab Bar -->
        <div class="tab-bar">
            <button class="tab-btn active" onclick="switchTab('roles', this)">Roles</button>
            <button class="tab-btn" onclick="switchTab('projects', this)">Projects</button>
        </div>

        <!-- ───────────── ROLES TAB ───────────── -->
        <div id="tab-roles" class="tab-panel active">
            <div class="controls">
                <div class="search-wrapper">
                    <input type="text"
                           class="search-box"
                           placeholder="🔍 Search by name..."
                           id="searchRolesInput"
                           onkeyup="filterTable('rolesTable', 'searchRolesInput')">
                </div>
                <button class="btn-add" onclick="openAddModal()">+ Add Role</button>
            </div>

            <div class="table-wrapper">
                <table class="data-table" id="rolesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Role Name</th>
                            <th>Description</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $role)
                        <tr>
                            <td>{{ $role->id }}</td>
                            <td class="role-name">{{ $role->role_name }}</td>
                            <td>{{ $role->description ?? 'N/A' }}</td>
                            <td>{{ \Carbon\Carbon::parse($role->created_at)->format('M d, Y') }}</td>
                            <td>
                                <button class="btn-edit"
                                        onclick="openEditModal({{ $role->id }}, '{{ addslashes($role->role_name) }}', '{{ addslashes($role->description) }}', {{ $role->hourly_rate }})">
                                    ✏️ Edit
                                </button>
                                <button class="btn-delete"
                                        onclick="confirmDelete({{ $role->id }}, '{{ addslashes($role->role_name) }}')">
                                    🗑️ Delete
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="no-data">No roles found. Click "Add Role" to create one.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ───────────── PROJECTS TAB ───────────── -->
        <div id="tab-projects" class="tab-panel">
            <div class="controls">
                <div class="search-wrapper">
                    <input type="text"
                           class="search-box"
                           placeholder="🔍 Search projects..."
                           id="searchProjectsInput"
                           onkeyup="filterTable('projectsTable', 'searchProjectsInput')">
                </div>
                <button class="btn-add" onclick="openAddProjectModal()">+ Add Project</button>
            </div>

            <div class="table-wrapper">
                <table class="data-table" id="projectsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($projects as $project)
                        <tr>
                            <td>{{ $project->id }}</td>
                            <td>{{ $project->name }}</td>
                            <td>{{ $project->description ?? 'N/A' }}</td>
                            <td>{{ \Carbon\Carbon::parse($project->created_at)->format('M d, Y') }}</td>
                            <td>
                                <button class="btn-edit"
                                        onclick="openEditProjectModal({{ $project->id }}, '{{ addslashes($project->name) }}', '{{ addslashes($project->description) }}')">
                                    ✏️ Edit
                                </button>
                                <button class="btn-delete"
                                        onclick="confirmDeleteProject({{ $project->id }}, '{{ addslashes($project->name) }}')">
                                    🗑️ Delete
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="no-data">No projects found. Click "Add Project" to create one.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══════════ ROLE MODALS ═══════════ -->

    <!-- Add Role Modal -->
    <div id="addModal" class="modal-overlay">
        <div class="modal-box">
            <button class="modal-close" onclick="closeAddModal()">×</button>
            <h2 class="modal-title">Add New Role</h2>
            <form method="POST" action="{{ route('admin.roles.store') }}">
                @csrf
                <div class="form-group">
                    <label>Role Name <span class="required">*</span></label>
                    <input type="text" name="role_name" required placeholder="e.g., Admin, Manager, HR">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Brief description of the role"></textarea>
                </div>
                <div class="form-group">
                    <label>Hourly Rate (₱) <span class="required">*</span></label>
                    <input type="number" name="hourly_rate" step="0.01" required placeholder="e.g., 500.00">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save Role</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Role Modal -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-box">
            <button class="modal-close" onclick="closeEditModal()">×</button>
            <h2 class="modal-title">Edit Role</h2>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Role Name <span class="required">*</span></label>
                    <input type="text" name="role_name" id="edit_role_name" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Hourly Rate (₱) <span class="required">*</span></label>
                    <input type="number" name="hourly_rate" id="edit_hourly_rate" step="0.01" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-update">Update Role</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Role Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

    <!-- ═══════════ PROJECT MODALS ═══════════ -->

    <!-- Add Project Modal -->
    <div id="addProjectModal" class="modal-overlay">
        <div class="modal-box">
            <button class="modal-close" onclick="closeAddProjectModal()">×</button>
            <h2 class="modal-title">Add New Project</h2>
            <form method="POST" action="{{ route('admin.projects.store') }}">
                @csrf
                <div class="form-group">
                    <label>Project Name <span class="required">*</span></label>
                    <input type="text" name="name" required placeholder="e.g., Calamba - Laguna">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Brief description of the project"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeAddProjectModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save Project</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Project Modal -->
    <div id="editProjectModal" class="modal-overlay">
        <div class="modal-box">
            <button class="modal-close" onclick="closeEditProjectModal()">×</button>
            <h2 class="modal-title">Edit Project</h2>
            <form id="editProjectForm" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Project Name <span class="required">*</span></label>
                    <input type="text" name="name" id="edit_project_name" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_project_description" rows="3"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeEditProjectModal()">Cancel</button>
                    <button type="submit" class="btn-update">Update Project</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Project Form -->
    <form id="deleteProjectForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

    <script>
        // ── Tab Switching ──
        function switchTab(tabName, btn) {
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('tab-' + tabName).classList.add('active');
            btn.classList.add('active');
        }

        // ── Generic Table Filter ──
        function filterTable(tableId, inputId) {
            const filter = document.getElementById(inputId).value.toUpperCase();
            const rows = document.getElementById(tableId).getElementsByTagName('tr');
            for (let i = 1; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;
                for (let j = 0; j < cells.length; j++) {
                    if (cells[j] && cells[j].textContent.toUpperCase().includes(filter)) {
                        found = true; break;
                    }
                }
                rows[i].style.display = found ? '' : 'none';
            }
        }

        // ── Role Modals ──
        function openAddModal() { showModal('addModal'); }
        function closeAddModal() { hideModal('addModal'); }

        function openEditModal(id, roleName, description, hourlyRate) {
            document.getElementById('edit_role_name').value = roleName;
            document.getElementById('edit_description').value = description || '';
            document.getElementById('edit_hourly_rate').value = hourlyRate;
            document.getElementById('editForm').action = `/admin/rolemanagement/${id}`;
            showModal('editModal');
        }
        function closeEditModal() { hideModal('editModal'); }

        function confirmDelete(id, roleName) {
            if (confirm(`Are you sure you want to delete the role "${roleName}"?\n\nThis action cannot be undone.`)) {
                const form = document.getElementById('deleteForm');
                form.action = `/admin/rolemanagement/${id}`;
                form.submit();
            }
        }

        // ── Project Modals ──
        function openAddProjectModal() { showModal('addProjectModal'); }
        function closeAddProjectModal() { hideModal('addProjectModal'); }

        function openEditProjectModal(id, name, description) {
            document.getElementById('edit_project_name').value = name;
            document.getElementById('edit_project_description').value = description || '';
            document.getElementById('editProjectForm').action = `/admin/projects/${id}`;
            showModal('editProjectModal');
        }
        function closeEditProjectModal() { hideModal('editProjectModal'); }

        function confirmDeleteProject(id, name) {
            if (confirm(`Are you sure you want to delete the project "${name}"?\n\nThis action cannot be undone.`)) {
                const form = document.getElementById('deleteProjectForm');
                form.action = `/admin/projects/${id}`;
                form.submit();
            }
        }

        // ── Helpers ──
        function showModal(id) {
            document.getElementById(id).style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        function hideModal(id) {
            document.getElementById(id).style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close on backdrop click
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) hideModal(this.id);
            });
        });

        // Close on ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                ['addModal','editModal','addProjectModal','editProjectModal'].forEach(hideModal);
            }
        });

        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                document.getElementById('logoutForm').submit();
            }
        }
    </script>
</body>
</html>