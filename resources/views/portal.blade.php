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
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f1f5f9; color: #0f172a; }
        .checkout-container { max-width: 1000px; margin: 3rem auto; }
        .bento-card { background: #fff; border-radius: 1.5rem; border: none; box-shadow: 0 10px 40px -10px rgba(0,0,0,0.08); padding: 2rem; }
        .input-modern { border-radius: 1rem; border: 2px solid #e2e8f0; background-color: #f8fafc; padding: 0.8rem 1.2rem; font-weight: 500; width: 100%; transition: all 0.2s; }
        .input-modern:focus { background-color: #fff; border-color: #3b82f6; box-shadow: 0 4px 15px rgba(59,130,246,0.1); outline: none; }
        .btn-modern { border-radius: 1rem; font-weight: 700; padding: 0.8rem 1.5rem; transition: all 0.2s; }
        .btn-modern:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        
        /* Custom Scrollbar for Directory */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        @media (min-width: 992px) {
            .sticky-layout { position: sticky; top: 2rem; }
        }
    </style>
</head>
<body>

    <div class="container checkout-container">
        
        <div class="text-center mb-5">
            <div class="mb-3">
                <img src="{{ asset('images/supply-logo2.png') }}" alt="RMPH Supply Logo" style="height: 150px; width: auto;">
            </div>
            <h2 class="fw-bolder tracking-tight">Supply Requisition</h2>
            <p class="text-muted fw-medium">RMPH Department Portal</p>
        </div>

        {{-- SUCCESS MESSAGE & PRINT BUTTON --}}
        @if(session('success'))
            <div class="alert alert-success border-0 rounded-4 p-4 mb-4 text-center shadow-sm">
                <i class="bi bi-check2-circle fs-1 text-success d-block mb-2"></i> 
                <h5 class="fw-bold text-success mb-3">{{ session('success') }}</h5>
                
                @if(session('batch_id'))
                    <button type="button" class="btn btn-primary btn-modern shadow-sm px-4" onclick="printDirectly('/print-bulk/{{ session('batch_id') }}')">
                        <i class="bi bi-printer-fill me-2"></i> Print Requisition Slip (RIS)
                    </button>
                    <p class="small text-muted mt-2 mb-0">Please print this slip, sign it, and present it to the Supply Section.</p>
                @endif
            </div>
        @endif

        <form action="/submit-request" method="POST" onsubmit="return validateCart()">
            @csrf
            
            <div class="row g-4 mb-5">
                
                {{-- Form & Selection Logic --}}
                <div class="col-lg-7">
                    <div class="bento-card h-100">
                        <div class="row g-4 mb-4">
                            
                            {{-- Department Dropdown (DYNAMIC DATABASE VERSION) --}}
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold text-uppercase tracking-wide">Department</label>
                                <select class="form-select input-modern" name="department_name" id="departmentSelect" required onchange="updateRequestor()">
                                    <option value="" disabled selected>Select Department...</option>
                                    
                                    @php $currentGroup = ''; @endphp
                                    
                                    @foreach($departments as $dept)
                                        @if($currentGroup != $dept->group_name)
                                            @if($currentGroup != '') </optgroup> @endif
                                            <optgroup label="{{ $dept->group_name }}">
                                            @php $currentGroup = $dept->group_name; @endphp
                                        @endif
                                        
                                        <option value="{{ $dept->name }}" data-head="{{ $dept->head_name }}">
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                    
                                    @if($currentGroup != '') </optgroup> @endif
                                </select>
                            </div>

                            {{-- Auto-filling Requestor Input --}}
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold text-uppercase tracking-wide">Requestor Name (Head)</label>
                                <input type="text" class="input-modern bg-light text-muted" name="requested_by" id="requestedByInput" placeholder="Auto-filled Head Name" required readonly>
                            </div>
                        </div>

                        {{-- Category & Item Selection Box --}}
                        <div class="p-4 bg-light rounded-4 mb-5 border border-white border-4 shadow-sm">
                            <label class="form-label text-muted small fw-bold text-uppercase tracking-wide mb-3"><i class="bi bi-cart-plus me-2"></i>Build Your Request</label>
                            
                            {{-- Step 1: Category Selection --}}
                            <div class="mb-3">
                                <select id="categorySelect" class="form-select input-modern" onchange="filterItemsByCategory()">
                                    <option value="" disabled selected>1. Select a Category...</option>
                                    @php
                                        $categories = collect($supplies)->pluck('category')->filter()->unique()->sort();
                                    @endphp
                                    @foreach($categories as $category)
                                        <option value="{{ $category }}">{{ $category }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Step 2: Item Selection --}}
                            <div id="itemSelectionRow" class="align-items-center gap-2 mb-4" style="display: none;">
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <select id="supplySelect" class="form-select input-modern" style="width: 100%;">
                                        <option value="" disabled selected>2. Select an Item Batch...</option>
                                    </select>
                                </div>
                                <input type="number" id="qtyInput" class="input-modern text-center" style="width: 100px;" value="1" min="1">
                                <button type="button" class="btn btn-dark btn-modern" onclick="addItem()">Add</button>
                            </div>

                            <div id="emptyCart" class="text-center py-4 text-muted small fw-medium">Cart is empty. Select items above.</div>
                            <div id="cartList"></div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label text-muted small fw-bold text-uppercase tracking-wide">Purpose / Remarks</label>
                            <textarea class="input-modern" name="purpose" rows="2" placeholder="Required for approval..." required></textarea>
                        </div>
                    </div>
                </div>

                {{-- Right Column (Sticky Cart & Directory) --}}
                <div class="col-lg-5">
                    <div class="sticky-layout d-flex flex-column gap-4">
                        
                        <div class="bento-card">
                            <h5 class="fw-bolder border-bottom pb-3 mb-4 d-flex justify-content-between align-items-center">
                                Request Summary
                                <span class="badge bg-dark rounded-pill fs-6 px-3" id="cartCount">0</span>
                            </h5>
                            <input type="hidden" name="cart_data" id="cartData">
                            <button type="submit" class="btn btn-primary btn-modern w-100 fs-5"><i class="bi bi-send-fill me-2"></i> Submit Request</button>
                        </div>

                        <div class="bento-card flex-grow-1">
                            <h6 class="fw-bolder border-bottom pb-3 mb-3 text-uppercase tracking-wide small text-muted">Live Inventory Directory</h6>
                            <div style="max-height: 350px; overflow-y: auto;" class="pe-2">
                                <ul class="list-unstyled mb-0">
                                    @foreach($supplies as $item)
                                        <li class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom border-light">
                                            <div>
                                                <div class="small fw-bold text-dark mb-1">{{ $item->name }}</div>
                                                
                                                @if($item->description)
                                                    <div class="text-muted text-uppercase mb-1" style="font-size: 0.70rem; letter-spacing: 0.5px;">
                                                        {{ $item->description }}
                                                    </div>
                                                @endif
                                                
                                                <div class="text-muted-soft d-flex flex-column" style="font-size: 0.65rem;">
                                                    @if($item->supplier) 
                                                        <span><i class="bi bi-truck me-1"></i>{{ $item->supplier }}</span> 
                                                    @endif
                                                    @if($item->ris_number) 
                                                        <span><i class="bi bi-hash me-1"></i>RIS: {{ $item->ris_number }}</span> 
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            <div class="text-end">
                                                @if($item->quantity > 0)
                                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill border border-success border-opacity-25 px-2 py-1 mb-1 d-block">Available</span>
                                                    <span class="small fw-bold text-muted">{{ $item->quantity }} {{ $item->unit }}</span>
                                                @else
                                                    <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill border border-danger border-opacity-25 px-2 py-1 d-block">Out of Stock</span>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>
                
            </div>
        </form>

    </div>

    {{-- Script Section --}}
    <script>
        const allSupplies = @json($supplies);

        // Auto-fill Requestor Function
        function updateRequestor() {
            let select = document.getElementById('departmentSelect');
            let headName = select.options[select.selectedIndex].getAttribute('data-head');
            document.getElementById('requestedByInput').value = headName || '';
        }

        function filterItemsByCategory() {
            const selectedCategory = document.getElementById('categorySelect').value;
            const itemRow = document.getElementById('itemSelectionRow');
            const supplySelect = document.getElementById('supplySelect');

            supplySelect.innerHTML = '<option value="" disabled selected>2. Select an Item Batch...</option>';

            const filteredSupplies = allSupplies.filter(item => item.category === selectedCategory);

            filteredSupplies.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.id;

                let nameDisplay = item.name;
                if(item.description) {
                    nameDisplay += ` (${item.description})`;
                }
                
                let batchInfo = [];
                if(item.supplier) batchInfo.push(`Supplier: ${item.supplier}`);
                if(item.ris_number) batchInfo.push(`RIS: ${item.ris_number}`);
                
                let metadataString = batchInfo.length > 0 ? ` [${batchInfo.join(' | ')}]` : '';
                nameDisplay += metadataString;

                opt.dataset.name = nameDisplay;
                opt.dataset.max = item.quantity;

                if(item.quantity > 0) {
                    opt.textContent = `${nameDisplay} - In Stock: ${item.quantity}`;
                } else {
                    opt.disabled = true;
                    opt.className = "text-danger fw-bold";
                    opt.textContent = `[OUT OF STOCK] ${nameDisplay}`;
                }

                supplySelect.appendChild(opt);
            });

            itemRow.style.setProperty('display', 'flex', 'important');
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

        let cart = [];
        
        function addItem() {
            let sel = document.getElementById('supplySelect');
            let qty = document.getElementById('qtyInput').value;
            
            if(!sel.value || qty < 1) return;
            
            let itemName = sel.options[sel.selectedIndex].dataset.name;
            let maxStock = parseInt(sel.options[sel.selectedIndex].dataset.max);
            
            let existing = cart.find(i => i.id === sel.value);
            
            let currentQty = existing ? existing.qty : 0;
            if (currentQty + parseInt(qty) > maxStock) {
                alert(`You cannot request more than the available stock (${maxStock}).`);
                return;
            }

            if(existing) {
                existing.qty = parseInt(existing.qty) + parseInt(qty);
            } else {
                cart.push({id: sel.value, name: itemName, qty: parseInt(qty)});
            }
            
            updateUI();
            
            sel.value = "";
            document.getElementById('qtyInput').value = '1';
        }

        function updateUI() {
            document.getElementById('cartData').value = JSON.stringify(cart);
            document.getElementById('cartCount').innerText = cart.length;
            
            let list = document.getElementById('cartList');
            let empty = document.getElementById('emptyCart');
            
            if (cart.length > 0) {
                empty.style.display = 'none';
                list.innerHTML = '';
                
                cart.forEach((item, index) => {
                    list.innerHTML += `
                        <div class="cart-item d-flex justify-content-between align-items-center bg-white p-3 rounded-4 shadow-sm mb-3 border">
                            <span class="fw-bold text-dark fs-6" style="max-width: 70%;">${item.name}</span>
                            <div class="d-flex align-items-center gap-3">
                                <span class="badge bg-light text-dark border px-3 py-2 fs-6 shadow-sm">Qty: ${item.qty}</span>
                                <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle" onclick="removeItem(${index})">
                                    <i class="bi bi-x-lg"></i>
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
                alert("Please add at least one item."); 
                return false; 
            }
            return true;
        }
    </script>
</body>
</html>