<x-layouts.admin title="User Management | Superadmin Control">

    <div class="d-flex justify-content-between align-items-center mb-4 bento-card py-3">
        <h5 class="fw-bolder mb-0 text-dark">
            <i class="bi bi-people-fill text-primary me-2"></i> User Management
        </h5>
        <button class="btn btn-sm btn-primary btn-modern shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus-fill me-1"></i> Add New User
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success shadow-sm rounded-4 border-0 mb-4 py-3">
            <i class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i> 
            <span class="fw-bold">{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger shadow-sm rounded-4 border-0 mb-4 py-3">
            <i class="bi bi-exclamation-circle-fill me-2 fs-5 align-middle"></i> 
            <span class="fw-bold">{{ session('error') }}</span>
        </div>
    @endif

    <div class="bento-card">
        <div class="table-responsive">
            <table class="table table-clean align-middle mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td class="fw-bold text-dark">{{ $user->name }}</td>
                            <td class="text-muted">{{ $user->email }}</td>
                            <td>
                                @if($user->role === 'superadmin')
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3">Superadmin</span>
                                @elseif($user->role === 'admin')
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3">Supply Admin</span>
                                @else
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3">QMO Approver</span>
                                @endif
                            </td>
                            <td class="text-end">
                                {{-- Edit Button triggers Modal specific to this user --}}
                                <button class="btn btn-sm btn-light btn-modern text-primary border shadow-sm me-1" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $user->id }}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                
                                {{-- Delete Button --}}
                                @if(auth()->id() !== $user->id)
                                    <a href="/superadmin/users/delete/{{ $user->id }}" class="btn btn-sm btn-light btn-modern text-danger border shadow-sm" onclick="return confirm('Are you sure you want to delete {{ $user->name }}?');">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                @else
                                    <button class="btn btn-sm btn-light btn-modern text-muted border shadow-sm" disabled title="Cannot delete yourself">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>

                        {{-- Edit User Modal (Unique for each user) --}}
                        <div class="modal fade" id="editUserModal{{ $user->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content bento-card p-2 border-0">
                                    <form action="/superadmin/users/update/{{ $user->id }}" method="POST">
                                        @csrf
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="fw-bolder text-dark">Edit User: {{ $user->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label text-muted small fw-bold text-uppercase">Full Name</label>
                                                <input type="text" class="form-control input-modern" name="name" value="{{ $user->name }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label text-muted small fw-bold text-uppercase">Email Address</label>
                                                <input type="email" class="form-control input-modern" name="email" value="{{ $user->email }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label text-muted small fw-bold text-uppercase">Role</label>
                                                <select class="form-select input-modern" name="role" required>
                                                    <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>Supply Admin</option>
                                                    <option value="approver" {{ $user->role == 'approver' ? 'selected' : '' }}>QMO Approver</option>
                                                    <option value="superadmin" {{ $user->role == 'superadmin' ? 'selected' : '' }}>Superadmin</option>
                                                </select>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label text-muted small fw-bold text-uppercase">New Password (Optional)</label>
                                                <input type="password" class="form-control input-modern" name="password" placeholder="Leave blank to keep current password">
                                                <small class="text-muted" style="font-size: 0.70rem;">Only fill this if you want to change their password.</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0 pt-0">
                                            <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary btn-modern shadow-sm">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Add New User Modal --}}
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bento-card p-2 border-0">
                <form action="/superadmin/users/add" method="POST">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-bolder text-dark">Add New System User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">Full Name</label>
                            <input type="text" class="form-control input-modern" name="name" placeholder="e.g. Joy Castor" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">Email Address</label>
                            <input type="email" class="form-control input-modern" name="email" placeholder="email@rmph.gov.ph" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">System Role</label>
                            <select class="form-select input-modern" name="role" required>
                                <option value="" disabled selected>Select a role...</option>
                                <option value="admin">Supply Admin (Process Requests, Edit Inventory)</option>
                                <option value="approver">QMO Approver (View Only)</option>
                                <option value="superadmin">Superadmin (Full System Control)</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-muted small fw-bold text-uppercase">Initial Password</label>
                            <input type="password" class="form-control input-modern" name="password" required minlength="6">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-modern shadow-sm">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</x-layouts.admin>