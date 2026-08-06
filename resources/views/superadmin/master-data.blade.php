<x-layouts.admin title="Master Data Management | Superadmin">

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

    <div class="row g-4">
        
        {{-- DEPARTMENTS MANAGEMENT COLUMN --}}
        <div class="col-lg-8">
            <div class="bento-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-diagram-3-fill text-primary me-2"></i>Hospital Departments</h6>
                    <button class="btn btn-sm btn-primary btn-modern shadow-sm" data-bs-toggle="modal" data-bs-target="#addDeptModal">
                        <i class="bi bi-plus-lg me-1"></i> Add Dept
                    </button>
                </div>
                
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-clean align-middle mb-0">
                        <thead class="sticky-top bg-white" style="z-index: 1;">
                            <tr>
                                <th>Group / Division</th>
                                <th>Department Name</th>
                                <th>Head / Personnel</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($departments as $dept)
                                <tr>
                                    <td class="text-muted small fw-bold text-uppercase">{{ $dept->group_name }}</td>
                                    <td class="fw-bold text-dark">{{ $dept->name }}</td>
                                    <td>{{ $dept->head_name }}</td>
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

                                {{-- EDIT DEPARTMENT MODAL --}}
                                <div class="modal fade" id="editDeptModal{{ $dept->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content bento-card p-2 border-0">
                                            <form action="/superadmin/master-data/department/update/{{ $dept->id }}" method="POST">
                                                @csrf
                                                <div class="modal-header border-0 pb-0">
                                                    <h5 class="fw-bolder text-dark">Edit Department</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
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
                                                <div class="modal-footer border-0 pt-0">
                                                    <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary btn-modern shadow-sm">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No departments found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- CATEGORIES MANAGEMENT COLUMN (Bonus) --}}
        <div class="col-lg-4">
            <div class="bento-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-tags-fill text-primary me-2"></i>Categories</h6>
                    <button class="btn btn-sm btn-primary btn-modern shadow-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
                
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-clean align-middle mb-0">
                        <thead class="sticky-top bg-white">
                            <tr>
                                <th>Category Name</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $cat)
                                <tr>
                                    <td class="fw-bold text-dark">{{ $cat->name }}</td>
                                    <td class="text-end">
                                        <a href="/superadmin/master-data/category/delete/{{ $cat->id }}" class="btn btn-sm btn-light btn-modern text-danger border shadow-sm" onclick="return confirm('Delete category {{ $cat->name }}?');">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-muted">No categories found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    {{-- ADD DEPARTMENT MODAL --}}
    <div class="modal fade" id="addDeptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bento-card p-2 border-0">
                <form action="/superadmin/master-data/department" method="POST">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-bolder text-dark">Add New Department</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
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
                    <div class="modal-footer border-0 pt-0">
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
                        <h5 class="fw-bolder text-dark">Add New Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label text-muted small fw-bold text-uppercase">Category Name</label>
                            <input type="text" class="form-control input-modern" name="name" placeholder="e.g. Medical Supplies" required>
                        </div>
                        <input type="hidden" name="is_active" value="1">
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-modern shadow-sm">Save Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</x-layouts.admin>