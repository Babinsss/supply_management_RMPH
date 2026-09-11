<x-layouts.admin title="User Management | Superadmin Control">

    <div class="d-flex justify-content-between align-items-center mb-4 bento-card py-3">
        <h5 class="fw-bolder mb-0 text-dark">
            <i class="bi bi-people-fill text-primary me-2"></i> User Management
        </h5>
        <button class="btn btn-sm btn-primary btn-modern shadow-sm fw-bold px-3" data-bs-toggle="modal" data-bs-target="#addUserModal">
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

    <div class="bento-card h-100 d-flex flex-column mb-5">
        <div class="table-responsive flex-grow-1 pe-2" style="max-height: 600px; overflow-y: auto;">
            <table class="table table-clean align-middle mb-0">
                <thead class="sticky-top bg-white" style="z-index: 1;">
                    <tr>
                        <th class="text-uppercase small text-muted">User Profile</th>
                        <th class="text-uppercase small text-muted">System Role</th>
                        <th class="text-uppercase small text-muted text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr class="border-bottom border-light">
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex justify-content-center align-items-center fw-bolder fs-5" style="width: 45px; height: 45px;">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $user->name }}</div>
                                        <div class="text-muted-soft small"><i class="bi bi-envelope me-1"></i>{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($user->role === 'superadmin')
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3 py-2"><i class="bi bi-shield-lock-fill me-1"></i> Superadmin</span>
                                @elseif($user->role === 'admin')
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2"><i class="bi bi-box-seam me-1"></i> Supply Admin</span>
                                @else
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2"><i class="bi bi-clipboard-check me-1"></i> QMO Approver</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                {{-- Edit Button triggers Modal specific to this user --}}
                                <button class="btn btn-sm btn-light btn-modern text-primary border shadow-sm me-1" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $user->id }}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                
                                {{-- Delete Button --}}
                                @if(auth()->id() !== $user->id)
                                    <a href="/superadmin/users/delete/{{ $user->id }}" class="btn btn-sm btn-light btn-modern text-danger border shadow-sm" onclick="return confirm('Are you sure you want to remove {{ $user->name }} from the system?');">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                @else
                                    <button class="btn btn-sm btn-light btn-modern text-muted border shadow-sm opacity-50" disabled title="Cannot delete yourself">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-5 text-muted-soft fw-medium">
                                <i class="bi bi-people fs-2 d-block mb-2 opacity-25"></i>
                                No users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ========================================== --}}
    {{-- SYSTEM MODALS (MOVED TO ROOT TO FIX FLICKER) --}}
    {{-- ========================================== --}}

    {{-- DYNAMIC EDIT USER MODALS --}}
    @foreach($users as $user)
        <div class="modal fade" id="editUserModal{{ $user->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bento-card p-2 border-0">
                    <form action="/superadmin/users/update/{{ $user->id }}" method="POST">
                        @csrf
                        <div class="modal-header border-0 pb-0">
                            <h5 class="fw-bolder text-dark"><i class="bi bi-person-gear text-primary me-2"></i>Edit User Profile</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body bg-light rounded-4 p-4 mt-3 border border-secondary-subtle">
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
                                <div class="form-text small"><i class="bi bi-info-circle me-1"></i>Only fill this if you want to reset their password.</div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-3">
                            <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-modern shadow-sm">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    {{-- ADD NEW USER MODAL --}}
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bento-card p-2 border-0">
                <form action="/superadmin/users/add" method="POST">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-bolder text-dark"><i class="bi bi-person-plus text-primary me-2"></i>Add New System User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body bg-light rounded-4 p-4 mt-3 border border-secondary-subtle">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">Full Name</label>
                            <input type="text" class="form-control input-modern" name="name" placeholder="e.g. Juan Dela Cruz" required>
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
                    <div class="modal-footer border-0 pt-3">
                        <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-modern shadow-sm">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</x-layouts.admin>