<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supply Requisition | RMPH</title>
    
    <link rel="icon" type="image/png" href="{{ asset('images/supply-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/supply-logo.png') }}">

    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<link href="{{ asset('bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f1f5f9; color: #0f172a; overflow-x: hidden; }
        .checkout-container { max-width: 1200px; margin: 3rem auto; padding-bottom: 80px; }
        .bento-card { background: #fff; border-radius: 1.5rem; border: none; box-shadow: 0 10px 40px -10px rgba(0,0,0,0.08); padding: 2rem; }
        .input-modern { border-radius: 1rem; border: 2px solid #e2e8f0; background-color: #f8fafc; padding: 0.8rem 1.2rem; font-weight: 500; width: 100%; transition: all 0.2s; }
        .input-modern:focus { background-color: #fff; border-color: #3b82f6; box-shadow: 0 4px 15px rgba(59,130,246,0.1); outline: none; }
        .btn-modern { border-radius: 1rem; font-weight: 700; padding: 0.8rem 1.5rem; transition: all 0.2s; }
        .btn-modern:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        
        /* Floating Cart Button */
        .fab-cart { position: fixed; bottom: 2rem; right: 2rem; z-index: 1040; border-radius: 50px; padding: 1rem 2rem; font-size: 1.1rem; box-shadow: 0 10px 25px rgba(13, 110, 253, 0.4); transition: transform 0.2s; }
        .fab-cart:hover { transform: scale(1.05); }
        
        /* Pill Styling */
        .filter-pill { cursor: pointer; transition: all 0.2s; font-weight: 600; }
        .filter-pill.active { background-color: #0d6efd !important; color: white !important; }
        
        /* Hide scrollbar for Quick Reorder lane but allow scrolling */
        .hide-scroll::-webkit-scrollbar { display: none; }
        .hide-scroll { -ms-overflow-style: none; scrollbar-width: none; }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body>

    {{-- Toast Notification Container --}}
    <div class="toast-container position-fixed top-0 end-0 p-4" style="z-index: 1055;">
        <div id="liveToast" class="toast align-items-center text-white border-0 shadow-lg rounded-4" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex px-2 py-1">
                <div class="toast-body fw-bold fs-6" id="toastMessage"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <div class="container checkout-container">
        
        <div class="text-center mb-4">
            <img src="{{ asset('images/supply-logo2.png') }}" alt="RMPH Supply Logo" style="height: 120px; width: auto;" class="mb-3">
            <h2 class="fw-bolder tracking-tight">Supply Requisition</h2>
            <p class="text-muted fw-medium">Department Portal</p>
        </div>

        {{-- DYNAMIC SCHEDULING BANNERS --}}
        @if($portalMode === 'NORMAL')
            <div class="alert bg-primary bg-opacity-10 border border-primary border-opacity-25 text-primary text-center rounded-4 p-3 mb-4 shadow-sm fw-bold">
                <i class="bi bi-clock-history me-2"></i> Normal Operations: Mon/Wed 8:00 AM - 5:00 PM
            </div>
        @elseif($portalMode === 'PRF_ONLY')
            <div class="alert bg-warning bg-opacity-10 border border-warning border-opacity-50 text-dark text-center rounded-4 p-4 mb-4 shadow-sm">
                <i class="bi bi-wallet2 fs-3 text-danger d-block mb-2"></i>
                <h5 class="fw-bolder text-danger mb-1">PURCHASE REQUISITION FORM (PRF) ONLY</h5>
                <p class="mb-0 fw-medium text-muted">Standard supply requesting is only available on Mondays and Wednesdays (8:00 AM - 5:00 PM). On Tuesdays, Thursdays, and Fridays, only PRF requests are available.</p>
            </div>
        @elseif($portalMode === 'CLOSED_WEEKEND' || $portalMode === 'CLOSED_OFF_HOURS')
            <div id="closedBanner" class="alert bg-danger bg-opacity-10 border border-danger border-opacity-50 text-danger text-center rounded-4 p-5 mb-4 shadow-sm">
                <i class="bi bi-shield-lock-fill fs-1 d-block mb-3"></i>
                <h4 class="fw-bolder">SYSTEM CLOSED</h4>
                
                @if($portalMode === 'CLOSED_WEEKEND')
                    <p class="mb-0 text-dark fw-medium">Requesting is closed Saturday through Sunday. Normal operations resume Monday at 8:00 AM.</p>
                @else
                    <p class="mb-0 text-dark fw-medium">Portal access is currently outside of operating hours (8:00 AM - 5:00 PM). Please wait until the next business day.</p>
                @endif
            </div>
        @endif

        @if(session('danger'))
            <div class="alert alert-danger border-0 rounded-4 p-4 mb-4 text-center shadow-sm fw-bold">
                <i class="bi bi-exclamation-octagon-fill fs-4 d-block mb-2"></i>
                {{ session('danger') }}
            </div>
        @endif

        @if(session('success'))
            <!-- Wipes the cart memory on successful submission -->
            <script>localStorage.removeItem('rmph_cart');</script>
            
            <div class="alert alert-success border-0 rounded-4 p-4 mb-4 text-center shadow-sm">
                <i class="bi bi-check2-circle fs-1 text-success d-block mb-2"></i> 
                <h5 class="fw-bold text-success mb-3">{{ session('success') }}</h5>
                @if(session('batch_id'))
                    <button type="button" class="btn btn-primary btn-modern shadow-sm px-4" onclick="printDirectly('/print-bulk/{{ session('batch_id') }}')">
                        <i class="bi bi-printer-fill me-2"></i> Print Requisition Slip (RIS)
                    </button>
                @endif
            </div>
        @endif

        @if(in_array($portalMode, ['NORMAL', 'PRF_ONLY']))
            @if($portalMode === 'NORMAL')
                <form action="/submit-request" method="POST" id="requisitionForm" onsubmit="return validateCart()">
                    @csrf
            @endif

            {{-- THE MAIN PORTAL UI --}}
            <div class="row g-4 mb-5 {{ $portalMode === 'PRF_ONLY' ? 'justify-content-center' : '' }}" id="mainPortalUI">
                
                {{-- Left/Center Column: Department Selection & Request Controls --}}
                <div class="{{ $portalMode === 'PRF_ONLY' ? 'col-lg-6' : 'col-lg-5' }}">
                    <div class="bento-card h-100">
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold text-uppercase">Department</label>
                            <select class="form-select input-modern mb-3" name="department_name" id="departmentSelect" required onchange="updateRequestor()">
                                <option value="" disabled selected>Select Department...</option>
                                @php $currentGroup = ''; @endphp
                                @foreach($departments as $dept)
                                    @if($currentGroup != $dept->group_name)
                                        @if($currentGroup != '') </optgroup> @endif
                                        <optgroup label="{{ $dept->group_name }}">
                                        @php $currentGroup = $dept->group_name; @endphp
                                    @endif
                                    <option value="{{ $dept->name }}" data-head="{{ $dept->head_name }}">{{ $dept->name }}</option>
                                @endforeach
                                @if($currentGroup != '') </optgroup> @endif
                            </select>

                            <label class="form-label text-muted small fw-bold text-uppercase">Requestor Name (Head)</label>
                            <input type="text" class="input-modern bg-light text-muted" name="requested_by" id="requestedByInput" placeholder="Auto-filled Head Name" required readonly>
                        </div>

                        @if($portalMode === 'PRF_ONLY')
                            <div class="p-4 bg-light rounded-4 border border-white border-4 shadow-sm text-center">
                                <p class="text-muted small fw-medium mb-3">
                                    Select your department above, then click below to create and print a Purchase Requisition Form (PRF).
                                </p>
                                <button type="button" class="btn btn-danger btn-modern w-100 shadow-sm" onclick="openPettyCashModal()">
                                    <i class="bi bi-wallet2 me-2"></i> Open Purchase Requisition Form (PRF)
                                </button>
                            </div>
                        @else
                            <div class="p-4 bg-light rounded-4 mb-4 border border-white border-4 shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <label class="form-label text-muted small fw-bold text-uppercase mb-0"><i class="bi bi-search me-2"></i>Manual Item Search</label>
                                    <button type="button" class="btn btn-sm btn-outline-danger fw-bold rounded-pill px-3 shadow-sm text-nowrap" style="font-size: 0.75rem;" onclick="openPettyCashModal()">
                                        <i class="bi bi-wallet2 me-1"></i> Purchase Requisition
                                    </button>
                                </div>
                                
                                @php $uniqueCategories = collect($supplies)->pluck('category')->filter()->unique()->sort(); @endphp
                                <select id="categorySelect" class="form-select input-modern mb-3" onchange="filterItemsByCategory()">
                                    <option value="" disabled selected>1. Select a Category...</option>
                                    @foreach($uniqueCategories as $category)
                                        <option value="{{ $category }}">{{ $category }}</option>
                                    @endforeach
                                </select>

                                <div id="itemSelectionRow" class="align-items-center gap-2" style="display: none;">
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <select id="supplySelect" class="form-select input-modern" style="width: 100%;">
                                            <option value="" disabled selected>2. Select an Item...</option>
                                        </select>
                                    </div>
                                    <input type="number" id="qtyInput" class="input-modern text-center px-1" style="width: 80px;" value="1" min="1">
                                    <button type="button" class="btn btn-dark btn-modern px-3" onclick="addItem()">Add</button>
                                </div>
                            </div>

                            <div>
                                <label class="form-label small fw-bold text-uppercase text-muted">
                                    Purpose / Remarks <span class="text-danger">*</span>
                                </label>
                                <textarea class="input-modern" name="purpose" rows="2" placeholder="Required for approval..." required></textarea>
                            </div>
                        @endif
                    </div>
                </div>

                @if($portalMode === 'NORMAL')
                    {{-- Right Column: Live Directory --}}
                    <div class="col-lg-7">
                        <div class="bento-card h-100 d-flex flex-column">
                            <h6 class="fw-bolder border-bottom pb-3 mb-3 text-uppercase small text-muted">Live Inventory Directory</h6>
                            
                            <div class="mb-3">
                                <div class="input-group mb-3">
                                    <span class="input-group-text bg-white border-end-0 rounded-start-4 ps-3 text-muted"><i class="bi bi-search"></i></span>
                                    <input type="text" id="searchInput" class="form-control input-modern border-start-0 rounded-end-4 ps-0" placeholder="Search for items, supplier, or RIS..." onkeyup="filterDirectory()">
                                </div>
                                
                                <div class="d-flex gap-2 overflow-auto pb-2" id="categoryPills">
                                    <span class="badge bg-primary text-white filter-pill active px-3 py-2 rounded-pill" onclick="filterByPill('All', this)">All</span>
                                    @foreach($uniqueCategories as $category)
                                        <span class="badge bg-light text-dark border filter-pill px-3 py-2 rounded-pill" onclick="filterByPill('{{ $category }}', this)">{{ $category }}</span>
                                    @endforeach
                                </div>
                            </div>

                            <div style="flex-grow: 1; overflow-y: auto; max-height: 450px;" class="pe-2" id="directoryContainer">
                                <ul class="list-unstyled mb-0" id="directoryList">
                                    @foreach($supplies as $item)
                                        <li class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom border-light directory-item" 
                                            data-name="{{ strtolower($item->name) }}" 
                                            data-category="{{ $item->category }}"
                                            data-meta="{{ strtolower($item->description . ' ' . $item->supplier . ' ' . $item->ris_number) }}">
                                            
                                            <div>
                                                <div class="small fw-bold text-dark mb-1">{{ $item->name }}</div>
                                                @if($item->description)
                                                    <div class="text-muted text-uppercase mb-1" style="font-size: 0.70rem; letter-spacing: 0.5px;">{{ $item->description }}</div>
                                                @endif
                                                @if($item->supplier)
                                                    <div class="text-muted-soft d-flex flex-column" style="font-size: 0.65rem;">
                                                        <span><i class="bi bi-truck me-1"></i>{{ $item->supplier }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            <div class="text-end d-flex flex-column align-items-end gap-2">
                                                @if($item->quantity <= 0)
                                                    <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-1 border border-danger border-opacity-25">Out of Stock</span>
                                                    <button type="button" class="btn btn-sm btn-light text-muted fw-bold rounded-pill shadow-sm" disabled><i class="bi bi-x-circle me-1"></i> Add</button>
                                                @else
                                                    <div class="d-flex align-items-center gap-2 mt-1">
                                                        <input type="number" id="dirQty-{{ $item->id }}" class="form-control form-control-sm text-center fw-bold border-secondary-subtle shadow-sm" style="width: 65px; border-radius: 8px;" value="1" min="1" max="{{ $item->quantity }}">
                                                        
                                                        <button type="button" class="btn btn-sm btn-primary fw-bold rounded-pill shadow-sm px-3" onclick="addFromDirectory('{{ $item->id }}', '{{ addslashes($item->name) }}', {{ $item->quantity }})">
                                                            <i class="bi bi-plus-lg"></i> Add
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                                <div id="noResultsMsg" class="text-center py-4 text-muted small fw-medium" style="display: none;">No items match your search.</div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            @if($portalMode === 'NORMAL')
                {{-- Floating Action Button --}}
                <button class="btn btn-primary fab-cart d-flex align-items-center gap-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas" id="fabCartButton">
                    <i class="bi bi-cart3 fs-4"></i> View Cart 
                    <span class="badge bg-white text-primary rounded-pill ms-1 fs-6" id="fabCartCount">0</span>
                </button>

                {{-- Offcanvas Cart --}}
                <div class="offcanvas offcanvas-end rounded-start-4 border-0 shadow" tabindex="-1" id="cartOffcanvas" aria-labelledby="cartOffcanvasLabel" style="width: 400px;">
                    <div class="offcanvas-header border-bottom p-4">
                        <h5 class="offcanvas-title fw-bolder d-flex align-items-center gap-2 text-primary" id="cartOffcanvasLabel">
                            <i class="bi bi-bag-check-fill"></i> Request Summary
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body p-4 bg-light d-flex flex-column">
                        <input type="hidden" name="cart_data" id="cartData">
                        
                        <div id="emptyCart" class="text-center py-5 text-muted fw-medium flex-grow-1">
                            <i class="bi bi-cart-x fs-1 d-block mb-3 text-secondary opacity-50"></i>
                            Your cart is currently empty.
                        </div>
                        
                        <div id="cartList" class="flex-grow-1 overflow-auto pe-2"></div>

                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary btn-modern w-100 fs-5 shadow-sm">
                                <i class="bi bi-send-fill me-2"></i> Submit Request
                            </button>
                        </div>
                    </div>
                </div>
                </form>
            @endif
        @endif
    </div>

    {{-- PETTY CASH MANUAL REQUEST MODAL (UPDATED WITH STATIC BACKDROP) --}}
    <div class="modal fade" id="pettyCashModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <form action="/portal/petty-cash/print" method="POST" target="hiddenPettyCashFrame" id="pettyCashForm" class="modal-content bento-card p-2 border-0">
                @csrf
                <input type="hidden" name="department_name" id="pcDeptName">
                <input type="hidden" name="requested_by" id="pcReqBy">

                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bolder text-danger"><i class="bi bi-wallet2 me-2"></i> Purchase Requisition Form (PRF)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold text-uppercase"><i class="bi bi-calendar3 me-1"></i> Request Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control input-modern" name="request_date" id="pcRequestDate" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="alert alert-warning border-0 rounded-3 py-2 small fw-bold mb-3 shadow-sm">
                        <i class="bi bi-info-circle-fill me-1"></i> Use this form to request items NOT in the standard inventory directory.
                    </div>

                    <div id="pcItemsContainer">
                        <div class="pc-item-row bg-light p-3 rounded-4 mb-3 border position-relative">
                            <div class="row g-2 pe-4">
                                <div class="col-8">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Item Description <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control input-modern" name="item_name[]" placeholder="e.g. SSD 500GB Kingston" required>
                                </div>
                                <div class="col-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control input-modern" name="quantity[]" value="1" min="1" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3 mb-3 shadow-sm" onclick="addPcItemRow()">
                        <i class="bi bi-plus-lg me-1"></i> Add Another Item
                    </button>

                    <div class="mb-2">
                        <label class="form-label text-muted small fw-bold text-uppercase">Purpose / Justification <span class="text-danger">*</span></label>
                        <textarea class="form-control input-modern" name="purpose" rows="2" placeholder="Why are these purchases necessary?" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                    <!-- UPDATED: Removed onclick="closePettyCashModal()" -->
                    <button type="submit" class="btn btn-danger btn-modern shadow-sm">
                        <i class="bi bi-printer-fill me-1"></i> Generate PRF
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Hidden Iframe for Petty Cash Printing --}}
    <iframe name="hiddenPettyCashFrame" id="hiddenPettyCashFrame" style="width:0; height:0; border:none; position:absolute;"></iframe>

    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script>
        const allSupplies = @json($supplies);
        // 1. Initialize cart from browser LocalStorage, or use empty array if new
        let cart = JSON.parse(localStorage.getItem('rmph_cart')) || [];
        let currentPillFilter = 'All';

        // Run on page load to render saved items automatically
        document.addEventListener('DOMContentLoaded', () => {
            updateUI();
        });

        // Toast Notification System
        function showToast(message, type = 'danger') {
            const toastEl = document.getElementById('liveToast');
            const toastBody = document.getElementById('toastMessage');
            
            toastEl.className = `toast align-items-center text-white bg-${type} border-0 shadow-lg rounded-4`;
            toastBody.innerHTML = message;
            
            const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
            toast.show();
        }

        // Live Directory Filtering (Search + Pills)
        function filterDirectory() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const items = document.querySelectorAll('.directory-item');
            let visibleCount = 0;

            items.forEach(item => {
                const name = item.getAttribute('data-name');
                const meta = item.getAttribute('data-meta');
                const category = item.getAttribute('data-category');
                
                const matchesSearch = name.includes(searchTerm) || meta.includes(searchTerm);
                const matchesPill = currentPillFilter === 'All' || category === currentPillFilter;

                if (matchesSearch && matchesPill) {
                    item.style.setProperty('display', 'flex', 'important');
                    visibleCount++;
                } else {
                    item.style.setProperty('display', 'none', 'important');
                }
            });

            document.getElementById('noResultsMsg').style.display = visibleCount === 0 ? 'block' : 'none';
        }

        function filterByPill(category, element) {
            currentPillFilter = category;
            
            document.querySelectorAll('.filter-pill').forEach(pill => {
                pill.classList.remove('bg-primary', 'text-white', 'active');
                pill.classList.add('bg-light', 'text-dark', 'border');
            });
            
            element.classList.remove('bg-light', 'text-dark', 'border');
            element.classList.add('bg-primary', 'text-white', 'active');
            
            filterDirectory();
        }

        // Dropdown Auto-fill & Item Selection
        function updateRequestor() {
            let select = document.getElementById('departmentSelect');
            let headName = select.options[select.selectedIndex].getAttribute('data-head');
            document.getElementById('requestedByInput').value = headName || '';
        }

        function filterItemsByCategory() {
            const selectedCategory = document.getElementById('categorySelect').value;
            const itemRow = document.getElementById('itemSelectionRow');
            const supplySelect = document.getElementById('supplySelect');

            supplySelect.innerHTML = '<option value="" disabled selected>2. Select an Item...</option>';
            const filteredSupplies = allSupplies.filter(item => item.category === selectedCategory);

            filteredSupplies.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.id;

                let nameDisplay = item.name + (item.description ? ` (${item.description})` : '');

                opt.dataset.name = nameDisplay;
                opt.dataset.max = item.quantity;

                if(item.quantity <= 0) {
                    opt.disabled = true;
                    opt.className = "text-danger fw-bold";
                    opt.textContent = nameDisplay;
                } else if(item.quantity <= item.reorder_level) {
                    opt.className = "text-warning fw-bold text-dark";
                    opt.textContent = nameDisplay;
                } else {
                    opt.className = "text-success fw-bold";
                    opt.textContent = nameDisplay;
                }

                supplySelect.appendChild(opt);
            });

            itemRow.style.setProperty('display', 'flex', 'important');
        }

        function addFromDirectory(id, name, maxStock) {
            let qtyInput = document.getElementById('dirQty-' + id);
            let qtyToAdd = parseInt(qtyInput.value) || 1;

            if (qtyToAdd < 1) {
                showToast("<i class='bi bi-info-circle-fill me-2'></i> Quantity must be at least 1.", "warning text-dark");
                return;
            }

            let existing = cart.find(i => i.id == id);
            let currentQty = existing ? parseInt(existing.qty) : 0;

            if (currentQty + qtyToAdd > maxStock) {
                showToast(`<i class="bi bi-exclamation-triangle-fill me-2"></i> Cannot request more than available stock (${maxStock}).`, 'danger');
                return;
            }

            if(existing) {
                existing.qty = currentQty + qtyToAdd;
            } else {
                cart.push({id: id, name: name, qty: qtyToAdd});
            }
            
            updateUI();
            
            let cartOffcanvas = new bootstrap.Offcanvas(document.getElementById('cartOffcanvas'));
            cartOffcanvas.show();
            
            showToast(`<i class="bi bi-check-circle-fill me-2"></i> Added <b>${qtyToAdd}</b> x ${name} to cart.`, 'success');
            
            // Reset the input box back to 1 for convenience
            qtyInput.value = 1;
        }

        // Keep the old add function specifically for the manual input selection logic
        function addItem() {
            let sel = document.getElementById('supplySelect');
            let qty = document.getElementById('qtyInput').value;
            
            if(!sel.value || qty < 1) return;
            
            let itemName = sel.options[sel.selectedIndex].dataset.name;
            let maxStock = parseInt(sel.options[sel.selectedIndex].dataset.max);
            let existing = cart.find(i => i.id === sel.value);
            
            let currentQty = existing ? existing.qty : 0;
            
            if (currentQty + parseInt(qty) > maxStock) {
                showToast(`<i class="bi bi-exclamation-triangle-fill me-2"></i> Cannot request more than available stock (${maxStock}).`, 'danger');
                return;
            }

            if(existing) {
                existing.qty = parseInt(existing.qty) + parseInt(qty);
            } else {
                cart.push({id: sel.value, name: itemName, qty: parseInt(qty)});
            }
            
            updateUI();
            showToast(`<i class="bi bi-check-circle-fill me-2"></i> Added <b>${qty}</b> x ${itemName} to cart.`, 'success');
            
            sel.value = "";
            document.getElementById('qtyInput').value = '1';
        }

        function updateUI() {
            // Save to browser memory
            localStorage.setItem('rmph_cart', JSON.stringify(cart));

            const cartDataInput = document.getElementById('cartData');
            if (!cartDataInput) {
                return;
            }

            cartDataInput.value = JSON.stringify(cart);
            document.getElementById('fabCartCount').innerText = cart.length;
            
            let list = document.getElementById('cartList');
            let empty = document.getElementById('emptyCart');
            
            if (cart.length > 0) {
                empty.style.display = 'none';
                list.innerHTML = '';
                cart.forEach((item, index) => {
                    list.innerHTML += `
                        <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-4 shadow-sm mb-3 border border-light">
                            <span class="fw-bold text-dark lh-sm" style="max-width: 65%; font-size: 0.9rem;">${item.name}</span>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-2 fs-6 shadow-none">x${item.qty}</span>
                                
                                <!-- UPDATED: Solid Red Bulletproof Remove Button -->
                                <button type="button" class="btn btn-sm btn-danger text-white border-0 shadow-sm fw-bolder d-flex align-items-center justify-content-center" 
                                        style="width: 28px; height: 28px; font-size: 18px; line-height: 0; padding-bottom: 2px; border-radius: 8px;" 
                                        onclick="removeItem(${index})" title="Remove item">
                                    &times;
                                </button>
                            </div>
                        </div>
                    `;
                });
            } else {
                empty.style.display = 'block';
                list.innerHTML = '';
            }
        }

        function removeItem(index) {
            cart.splice(index, 1);
            updateUI();
        }

        let isCartSubmitting = false;
        function validateCart() {
            if (isCartSubmitting) {
                return false;
            }
            if(cart.length === 0) { 
                showToast(`<i class="bi bi-info-circle-fill me-2"></i> Please add at least one item to submit a request.`, 'warning text-dark');
                return false; 
            }
            isCartSubmitting = true;
            const submitBtn = document.querySelector('#requisitionForm button[type="submit"]');
            if(submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';
            }
            return true;
        }

        function printDirectly(url) {
            let printFrame = document.getElementById('hiddenPrintFrame') || document.createElement('iframe');
            if(!printFrame.id) {
                printFrame.id = 'hiddenPrintFrame';
                printFrame.style.cssText = 'width:0; height:0; border:none; position:absolute;';
                document.body.appendChild(printFrame);
            }
            printFrame.src = url;
            printFrame.onload = () => { printFrame.contentWindow.focus(); printFrame.contentWindow.print(); };
        }

        function openPettyCashModal() {
            let dept = document.getElementById('departmentSelect').value;
            let reqBy = document.getElementById('requestedByInput').value;
            
            if(!dept) {
                showToast("Please select your Department first.", "warning text-dark");
                return;
            }
            
            document.getElementById('pcDeptName').value = dept;
            document.getElementById('pcReqBy').value = reqBy;
            
            let modal = new bootstrap.Modal(document.getElementById('pettyCashModal'));
            modal.show();
        }

        function addPcItemRow() {
            let container = document.getElementById('pcItemsContainer');
            let newRow = document.createElement('div');
            newRow.className = 'pc-item-row bg-light p-3 rounded-4 mb-3 border position-relative';
            newRow.innerHTML = `
                <button type="button" class="btn-close position-absolute top-0 end-0 m-2" onclick="this.closest('.pc-item-row').remove()"></button>
                <div class="row g-2 pe-4">
                    <div class="col-8">
                        <label class="form-label text-muted small fw-bold text-uppercase">Item Description <span class="text-danger">*</span></label>
                        <input type="text" class="form-control input-modern" name="item_name[]" placeholder="e.g. SSD 500GB Kingston" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control input-modern" name="quantity[]" value="1" min="1" required>
                    </div>
                </div>
            `;
            container.appendChild(newRow);
        }

        // Wait for the hidden iframe to finish loading the PDF before closing the modal
        document.getElementById('hiddenPettyCashFrame').addEventListener('load', function() {
            // Only trigger if the iframe isn't empty (avoids triggering on page load)
            if (this.src !== 'about:blank' && this.contentWindow.location.href !== 'about:blank') {
                
                let modalInstance = bootstrap.Modal.getInstance(document.getElementById('pettyCashModal'));
                if(modalInstance) {
                    modalInstance.hide();
                    
                    // Reset the form safely
                    document.getElementById('pettyCashForm').reset();
                    
                    // Clean up extra rows, keeping only the first one
                    let container = document.getElementById('pcItemsContainer');
                    let rows = container.querySelectorAll('.pc-item-row');
                    for (let i = 1; i < rows.length; i++) {
                        rows[i].remove();
                    }
                    
                    // Show a success message
                    showToast("<i class='bi bi-printer-fill me-2'></i> Purchase Requisition generated successfully.", "success");
                }
            }
        });
    </script>
</body>
</html>


