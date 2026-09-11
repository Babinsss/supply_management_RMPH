<x-layouts.admin title="Master Data Management | Superadmin">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4 bento-card py-3">
        <h5 class="fw-bolder mb-0 text-dark">
            <i class="bi bi-database-fill-gear text-primary me-2"></i> Master Data Management
        </h5>
        <p class="text-muted small mb-0 d-none d-md-block">Manage dropdowns and system variables.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success shadow-sm rounded-4 border-0 mb-4 py-3">
            <i class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i> 
            <span class="fw-bold">{{ session('success') }}</span>
        </div>
    @endif

    <div class="row g-4 mb-5">
        
        {{-- DEPARTMENTS MANAGEMENT COLUMN --}}
        <div class="col-lg-8">
            <div class="bento-card h-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bolder text-dark mb-0"><i class="bi bi-diagram-3-fill text-primary me-2"></i>Hospital Departments</h6>
                    <button class="btn btn-sm btn-primary btn-modern shadow-sm fw-bold px-3" data-bs-toggle="modal" data-bs-target="#addDeptModal">
                        <i class="bi bi-plus-lg me-1"></i> Add Dept
                    </button>
                </div>
                
                <div class="table-responsive flex-grow-1 pe-2" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-clean align-middle mb-0">
                        <thead class="sticky-top bg-white" style="z-index: 1;">
                            <tr>
                                <th class="text-uppercase small text-muted">Group / Division</th>
                                <th class="text-uppercase small text-muted">Department Name</th>
                                <th class="text-uppercase small text-muted">Head / Personnel</th>
                                <th class="text-uppercase small text-muted text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($departments as $dept)
                                <tr class="border-bottom border-light">
                                    <td>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                            {{ $dept->group_name }}
                                        </span>
                                    </td>
                                    <td class="fw-bold text-dark">{{ $dept->name }}</td>
                                    <td class="text-muted-soft small fw-medium"><i class="bi bi-person me-1"></i>{{ $dept->head_name }}</td>
                                    <td class="text-end text-nowrap">
                                        {{-- Edit Button --}}
                                        <button class="btn btn-sm btn-light btn-modern text-primary border shadow-sm me-1" data-bs-toggle="modal" data-bs-target="#editDeptModal{{ $dept->id }}">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        
                                        {{-- Delete Button --}}
                                        <a href="/superadmin/master-data/department/delete/{{ $dept->id }}" class="btn btn-sm btn-light btn-modern text-danger border shadow-sm" onclick="return confirm('Are you sure you want to delete {{ $dept->name }}? This might affect existing supply records.');">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted-soft fw-medium">
                                        <i class="bi bi-diagram-3 fs-2 d-block mb-2 opacity-25"></i>
                                        No departments found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- CATEGORIES MANAGEMENT COLUMN --}}
        <div class="col-lg-4">
            <div class="bento-card h-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bolder text-dark mb-0"><i class="bi bi-tags-fill text-primary me-2"></i>Categories</h6>
                    <button class="btn btn-sm btn-primary btn-modern shadow-sm fw-bold px-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
                
                <div class="table-responsive flex-grow-1 pe-2" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-clean align-middle mb-0">
                        <thead class="sticky-top bg-white" style="z-index: 1;">
                            <tr>
                                <th class="text-uppercase small text-muted">Category Name</th>
                                <th class="text-uppercase small text-muted text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $cat)
                                <tr class="border-bottom border-light">
                                    <td class="fw-bold text-dark"><i class="bi bi-tag text-muted-soft me-2"></i>{{ $cat->name }}</td>
                                    <td class="text-end">
                                        <a href="/superadmin/master-data/category/delete/{{ $cat->id }}" class="btn btn-sm btn-light btn-modern text-danger border shadow-sm" onclick="return confirm('Delete category {{ $cat->name }}?');">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center py-5 text-muted-soft fw-medium">
                                        <i class="bi bi-tags fs-2 d-block mb-2 opacity-25"></i>
                                        No categories found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    {{-- ========================================== --}}
    {{-- SYSTEM MODALS (MOVED TO ROOT TO FIX FLICKER) --}}
    {{-- ========================================== --}}

    {{-- DYNAMIC EDIT DEPARTMENT MODALS --}}
    @foreach($departments as $dept)
        <div class="modal fade" id="editDeptModal{{ $dept->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bento-card p-2 border-0">
                    <form action="/superadmin/master-data/department/update/{{ $dept->id }}" method="POST">
                        @csrf
                        <div class="modal-header border-0 pb-0">
                            <h5 class="fw-bolder text-dark"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Department</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body bg-light rounded-4 p-4 mt-3 border border-secondary-subtle">
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold text-uppercase">Group / Division</label>
                                <select class="form-select input-modern" name="group_name" required>
                                    <option value="Administrative Department" {{ $dept->group_name == 'Administrative Department' ? 'selected' : '' }}>Administrative Department</option>
                                    <option value="Wards / Ancillary Units" {{ $dept->group_name == 'Wards / Ancillary Units' ? 'selected' : '' }}>Wards / Ancillary Units</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold text-uppercase">Department Name</label>
                                <input type="text" class="form-control input-modern" name="name" value="{{ $dept->name }}" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label text-muted small fw-bold text-uppercase">Department Head</label>
                                <input type="text" class="form-control input-modern" name="head_name" value="{{ $dept->head_name }}" required>
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

    {{-- ADD DEPARTMENT MODAL --}}
    <div class="modal fade" id="addDeptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bento-card p-2 border-0">
                <form action="/superadmin/master-data/department" method="POST">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-bolder text-dark"><i class="bi bi-plus-circle text-primary me-2"></i>Add New Department</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body bg-light rounded-4 p-4 mt-3 border border-secondary-subtle">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">Group / Division</label>
                            <select class="form-select input-modern" name="group_name" required>
                                <option value="" disabled selected>Select Group...</option>
                                <option value="Administrative Department">Administrative Department</option>
                                <option value="Wards / Ancillary Units">Wards / Ancillary Units</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">Department Name</label>
                            <input type="text" class="form-control input-modern" name="name" placeholder="e.g. Outpatient Department" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-muted small fw-bold text-uppercase">Department Head</label>
                            <input type="text" class="form-control input-modern" name="head_name" placeholder="e.g. DR. JUAN DELA CRUZ" required>
                        </div>
                        <input type="hidden" name="is_active" value="1">
                    </div>
                    <div class="modal-footer border-0 pt-3">
                        <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-modern shadow-sm">Save Department</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ADD CATEGORY MODAL --}}
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bento-card p-2 border-0">
                <form action="/superadmin/master-data/category" method="POST">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-bolder text-dark"><i class="bi bi-tag text-primary me-2"></i>Add New Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body bg-light rounded-4 p-4 mt-3 border border-secondary-subtle">
                        <div class="mb-2">
                            <label class="form-label text-muted small fw-bold text-uppercase">Category Name</label>
                            <input type="text" class="form-control input-modern" name="name" placeholder="e.g. Medical Supplies" required>
                        </div>
                        <input type="hidden" name="is_active" value="1">
                    </div>
                    <div class="modal-footer border-0 pt-3">
                        <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-modern shadow-sm">Save Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</x-layouts.admin>