<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supply Requisition | RMPH</title>
    
    <link rel="icon" type="image/png" href="{{ asset('images/supply-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/supply-logo.png') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
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

    <form action="/submit-request" method="POST" id="requisitionForm" onsubmit="return validateCart()">
        @csrf
        <div class="container checkout-container">
            
            <div class="text-center mb-5">
                <img src="{{ asset('images/supply-logo2.png') }}" alt="RMPH Supply Logo" style="height: 120px; width: auto;" class="mb-3">
                <h2 class="fw-bolder tracking-tight">Supply Requisition</h2>
                <p class="text-muted fw-medium">Department Portal</p>
            </div>

            @if(session('success'))
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
            
            <div class="row g-4 mb-5">
                
                {{-- Left Column: Form & Item Selection --}}
                <div class="col-lg-5">
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

                        <div class="p-4 bg-light rounded-4 mb-4 border border-white border-4 shadow-sm">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label text-muted small fw-bold text-uppercase mb-0"><i class="bi bi-cart-plus me-2"></i>Add Items</label>
                                
                                {{-- Petty Cash Trigger Button --}}
                                <button type="button" class="btn btn-sm btn-outline-danger fw-bold rounded-pill px-3 shadow-sm" onclick="openPettyCashModal()">
                                    <i class="bi bi-wallet2 me-1"></i> Request Petty Cash
                                </button>
                            </div>
                            
                            <select id="categorySelect" class="form-select input-modern mb-3" onchange="filterItemsByCategory()">
                                <option value="" disabled selected>1. Select a Category...</option>
                                @php $uniqueCategories = collect($supplies)->pluck('category')->filter()->unique()->sort(); @endphp
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
                            <label class="form-label text-muted small fw-bold text-uppercase">Purpose / Remarks</label>
                            <textarea class="input-modern" name="purpose" rows="2" placeholder="Required for approval..." required></textarea>
                        </div>
                    </div>
                </div>

                {{-- Right Column: Live Directory with Search & Filters --}}
                <div class="col-lg-7">
                    <div class="bento-card h-100 d-flex flex-column">
                        <h6 class="fw-bolder border-bottom pb-3 mb-3 text-uppercase small text-muted">Live Inventory Directory</h6>
                        
                        {{-- Search & Pills --}}
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

                        {{-- Directory List --}}
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
                                            <div class="text-muted-soft d-flex flex-column" style="font-size: 0.65rem;">
                                                @if($item->supplier) <span><i class="bi bi-truck me-1"></i>{{ $item->supplier }}</span> @endif
                                                @if($item->ris_number) <span><i class="bi bi-hash me-1"></i>RIS: {{ $item->ris_number }}</span> @endif
                                            </div>
                                        </div>
                                        
                                        <div class="text-end">
                                            @if($item->quantity <= 0)
                                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill border border-danger border-opacity-25 px-2 py-1 d-block">Out of Stock</span>
                                            @elseif($item->quantity <= $item->reorder_level)
                                                <span class="badge bg-warning bg-opacity-10 text-dark rounded-pill border border-warning border-opacity-25 px-2 py-1 d-block fw-bold">Low Stock</span>
                                            @else
                                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill border border-success border-opacity-25 px-2 py-1 d-block">In Stock</span>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                            <div id="noResultsMsg" class="text-center py-4 text-muted small fw-medium" style="display: none;">No items match your search.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Floating Action Button --}}
        <button class="btn btn-primary fab-cart d-flex align-items-center gap-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas">
            <i class="bi bi-cart3 fs-4"></i> View Cart 
            <span class="badge bg-white text-primary rounded-pill ms-1 fs-6" id="fabCartCount">0</span>
        </button>

        {{-- Offcanvas Cart --}}
        <div class="offcanvas offcanvas-end rounded-start-4 border-0 shadow" tabindex="-1" id="cartOffcanvas" aria-labelledby="cartOffcanvasLabel" style="width: 400px;">
            <div class="offcanvas-header border-bottom p-4">
                <h5 class="offcanvas-title fw-bolder d-flex align-items-center gap-2" id="cartOffcanvasLabel">
                    <i class="bi bi-bag-check-fill text-primary"></i> Request Summary
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
                    <button type="submit" class="btn btn-primary btn-modern w-100 fs-5 shadow-sm"><i class="bi bi-send-fill me-2"></i> Submit Request</button>
                </div>
            </div>
        </div>
    </form>

    {{-- PETTY CASH MANUAL REQUEST MODAL --}}
    <div class="modal fade" id="pettyCashModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            {{-- FIX: Merged <form> with <div class="modal-content"> so the scrollbar activates --}}
            <form action="/portal/petty-cash/print" method="POST" target="hiddenPettyCashFrame" id="pettyCashForm" class="modal-content bento-card p-2 border-0">
                @csrf
                <input type="hidden" name="department_name" id="pcDeptName">
                <input type="hidden" name="requested_by" id="pcReqBy">

                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bolder text-danger"><i class="bi bi-wallet2 me-2"></i> Petty Cash Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                {{-- This body will now scroll perfectly --}}
                <div class="modal-body">
                    <div class="alert alert-warning border-0 rounded-3 py-2 small fw-bold mb-3 shadow-sm">
                        <i class="bi bi-info-circle-fill me-1"></i> Use this form to request items NOT in the standard inventory directory.
                    </div>

                    {{-- Container for dynamic item rows --}}
                    <div id="pcItemsContainer">
                        <div class="pc-item-row bg-light p-3 rounded-4 mb-3 border position-relative">
                            <div class="mb-2 pe-4">
                                <label class="form-label text-muted small fw-bold text-uppercase">Item Description <span class="text-danger">*</span></label>
                                <input type="text" class="form-control input-modern" name="item_name[]" placeholder="e.g. SSD 500GB Kingston" required>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Est. Price (₱) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control input-modern" name="estimated_price[]" placeholder="0.00" required>
                                </div>
                                <div class="col-6">
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
                    <button type="submit" class="btn btn-danger btn-modern shadow-sm" onclick="closePettyCashModal()">
                        <i class="bi bi-printer-fill me-1"></i> Generate RIS
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Hidden Iframe for Petty Cash Printing --}}
    <iframe name="hiddenPettyCashFrame" id="hiddenPettyCashFrame" style="width:0; height:0; border:none; position:absolute;"></iframe>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const allSupplies = @json($supplies);
        let cart = [];
        let currentPillFilter = 'All';

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

        // Cart Logic
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
            document.getElementById('cartData').value = JSON.stringify(cart);
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
                                <button type="button" class="btn btn-sm btn-light text-danger border-0 rounded-circle" onclick="removeItem(${index})">
                                    <i class="bi bi-trash3-fill"></i>
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

        function validateCart() {
            if(cart.length === 0) { 
                showToast(`<i class="bi bi-info-circle-fill me-2"></i> Please add at least one item to submit a request.`, 'warning text-dark');
                return false; 
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

        // PETTY CASH MODAL LOGIC
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
                <div class="mb-2 pe-4">
                    <label class="form-label text-muted small fw-bold text-uppercase">Item Description <span class="text-danger">*</span></label>
                    <input type="text" class="form-control input-modern" name="item_name[]" placeholder="e.g. SSD 500GB Kingston" required>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold text-uppercase">Est. Price (₱) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control input-modern" name="estimated_price[]" placeholder="0.00" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold text-uppercase">Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control input-modern" name="quantity[]" value="1" min="1" required>
                    </div>
                </div>
            `;
            container.appendChild(newRow);
        }

        function closePettyCashModal() {
            // Give it 1 second to submit the form to the hidden iframe, then close and reset the modal
            setTimeout(() => {
                let modalInstance = bootstrap.Modal.getInstance(document.getElementById('pettyCashModal'));
                if(modalInstance) {
                    modalInstance.hide();
                    document.getElementById('pettyCashForm').reset();
                    
                    // Reset modal back to only 1 item row
                    let container = document.getElementById('pcItemsContainer');
                    let rows = container.querySelectorAll('.pc-item-row');
                    for (let i = 1; i < rows.length; i++) {
                        rows[i].remove();
                    }
                }
            }, 1000);
        }
    </script>
</body>
</html>