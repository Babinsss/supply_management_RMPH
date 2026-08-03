<x-layouts.admin title="Master Data | Superadmin Control">

    <div class="d-flex justify-content-between align-items-center mb-4 bento-card py-3">
        <h5 class="fw-bolder mb-0 text-dark">
            <i class="bi bi-database-fill-gear text-primary me-2"></i> Master Data Management
        </h5>
        <div class="text-muted small d-none d-md-block">
            <i class="bi bi-info-circle me-1"></i> Manage dropdowns and system variables dynamically.
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success shadow-sm rounded-4 border-0 mb-4 py-3">
            <i class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i> 
            <span class="fw-bold">{{ session('success') }}</span>
        </div>
    @endif

    <div class="row g-4 mb-5">
        
        {{-- ========================================== --}}
        {{-- DEPARTMENTS SECTION --}}
        {{-- ========================================== --}}
        <div class="col-lg-7">
            <div class="bento-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-building me-2 text-primary"></i>Hospital Departments</h6>
                    <button class="btn btn-sm btn-primary btn-modern shadow-sm" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                        <i class="bi bi-plus-lg me-1"></i> Add Dept
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-clean mb-0">
                        <thead>
                            <tr>
                                <th>Group</th>
                                <th>Department Details</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($departments as $dept)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2 py-1" style="font-size: 0.70rem;">
                                            {{ $dept->group_name }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark fs-6">{{ $dept->name }}</div>
                                        <div class="text-muted small"><i class="bi bi-person-badge me-1"></i>Head: {{ $dept->head_name }}</div>
                                    </td>
                                    <td class="text-end align-middle">
                                        <a href="/superadmin/master-data/department/delete/{{ $dept->id }}" class="btn btn-sm btn-light btn-modern text-danger border shadow-sm" onclick="return confirm('Are you sure you want to delete this department?');">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted-soft fw-medium">No departments configured yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- CATEGORIES SECTION --}}
        {{-- ========================================== --}}
        <div class="col-lg-5">
            <div class="bento-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-tags-fill me-2 text-primary"></i>Supply Categories</h6>
                    <button class="btn btn-sm btn-primary btn-modern shadow-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="bi bi-plus-lg me-1"></i> Add Category
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-clean mb-0">
                        <thead>
                            <tr>
                                <th>Category Name</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $category)
                                <tr>
                                    <td class="fw-bold text-dark align-middle">{{ $category->name }}</td>
                                    <td class="text-end align-middle">
                                        <a href="/superadmin/master-data/category/delete/{{ $category->id }}" class="btn btn-sm btn-light btn-modern text-danger border shadow-sm" onclick="return confirm('Are you sure you want to delete this category?');">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-muted-soft fw-medium">No categories configured yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    {{-- ========================================== --}}
    {{-- MODALS FOR ADDING DATA --}}
    {{-- ========================================== --}}

    {{-- Add Department Modal --}}
    <div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-hidden="true">
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
                            <input type="text" class="input-modern" name="group_name" placeholder="e.g. Wards, Administrative..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">Department Name</label>
                            <input type="text" class="input-modern" name="name" placeholder="e.g. ICT Section, OB Ward..." required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-muted small fw-bold text-uppercase">Department Head</label>
                            <input type="text" class="input-modern" name="head_name" placeholder="e.g. DR. JOHN DOE" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-modern shadow-sm">Save Department</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Add Category Modal --}}
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bento-card p-2 border-0">
                <form action="/superadmin/master-data/category" method="POST">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-bolder text-dark">Add Supply Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label text-muted small fw-bold text-uppercase">Category Name</label>
                            <input type="text" class="input-modern text-uppercase" name="name" placeholder="e.g. IT EQUIPMENT" required>
                        </div>
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