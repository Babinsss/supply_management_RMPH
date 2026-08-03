<x-layouts.admin title="User Management | Superadmin Control">

    <div class="d-flex justify-content-between align-items-center mb-3 bento-card py-3">
        <h5 class="fw-bolder mb-0 text-dark">
            <i class="bi bi-shield-lock-fill text-danger me-2"></i> System Security & User Management
        </h5>
    </div>

    @if(session('success'))
        <div class="alert alert-success shadow-sm rounded-4 border-0 mb-4 py-3">
            <i class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i> 
            <span class="fw-bold">{{ session('success') }}</span>
        </div>
    @endif

    <div class="bento-card mb-4">
        <div class="table-responsive">
            <table class="table table-clean mb-0">
                <thead>
                    <tr>
                        <th>Staff Member</th>
                        <th>Email Address</th>
                        <th>System Access Level</th>
                        <th class="text-end">Administrative Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <div class="fw-bold text-dark fs-6">{{ $user->name }}</div>
                            </td>
                            <td class="text-muted fw-medium">{{ $user->email }}</td>
                            <td>
                                @if($user->role === 'superadmin')
                                    <span class="badge bg-dark rounded-pill px-3 py-2"><i class="bi bi-shield-lock-fill me-1"></i> Superadmin</span>
                                @elseif($user->role === 'admin')
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2 fw-bold">ICT Admin</span>
                                @elseif($user->role === 'approver')
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-3 py-2 fw-bold">QMO Approver</span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-3 py-2 fw-bold">Standard User</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                {{-- Change Role Button --}}
                                <button class="btn btn-sm btn-light btn-modern text-primary border shadow-sm me-1" data-bs-toggle="modal" data-bs-target="#roleModal-{{ $user->id }}">
                                    <i class="bi bi-person-gear me-1"></i> Set Role
                                </button>
                                
                                {{-- Reset Password Button --}}
                                <form action="/superadmin/users/reset-password/{{ $user->id }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reset the password for {{ $user->name }} to the default?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-light btn-modern text-warning border shadow-sm me-1" title="Reset Password to Default">
                                        <i class="bi bi-key-fill"></i>
                                    </button>
                                </form>
                                
                                {{-- Delete User Button --}}
                                <a href="/superadmin/users/delete/{{ $user->id }}" class="btn btn-sm btn-light btn-modern text-danger border shadow-sm" onclick="return confirm('Are you sure you want to permanently delete this user account?');">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                            </td>
                        </tr>

                        {{-- Role Modification Modal --}}
                        <div class="modal fade text-start" id="roleModal-{{ $user->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content bento-card p-3 border-0">
                                    <form action="/superadmin/users/update-role/{{ $user->id }}" method="POST">
                                        @csrf
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="fw-bolder text-dark">Update System Role</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body py-4">
                                            <p class="text-muted small mb-3">Select the appropriate access level for <strong>{{ $user->name }}</strong>.</p>
                                            
                                            <label class="form-label text-muted fw-bold small text-uppercase tracking-wide">Assign Role</label>
                                            <select name="role" class="form-select input-modern" required>
                                                <option value="user" {{ $user->role == 'user' ? 'selected' : '' }}>Standard User (No Dashboard Access)</option>
                                                <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>ICT Admin (Full Inventory Control)</option>
                                                <option value="approver" {{ $user->role == 'approver' ? 'selected' : '' }}>QMO Approver (Read-Only Audit Access)</option>
                                                <option value="superadmin" {{ $user->role == 'superadmin' ? 'selected' : '' }}>Superadmin (System Management)</option>
                                            </select>
                                        </div>
                                        <div class="modal-footer border-0 pt-0">
                                            <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary btn-modern shadow-sm">Save Role</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted-soft fw-medium">
                                No other registered users found in the system yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</x-layouts.admin>