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
    
    {{-- Select2 CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
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
        
        /* FIXED: Select2 Custom Styling to match your theme */
        .select2-container--default .select2-selection--single { 
            height: 48px; 
            border-radius: 1rem; 
            border: 2px solid #e2e8f0; 
            background-color: #f8fafc; 
            font-weight: 500; 
        }
        .select2-container--default.select2-container--focus .select2-selection--single { 
            background-color: #fff; 
            border-color: #3b82f6; 
            box-shadow: 0 4px 15px rgba(59,130,246,0.1); 
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered { 
            line-height: 44px; /* Centers text vertically */
            padding-left: 1rem; 
            padding-right: 2.5rem; /* Adds padding so text doesn't hit the arrow */
            color: #0f172a;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow { 
            height: 46px; 
            right: 10px; 
        }
        .select2-container--default .select2-selection--single .select2-selection__clear {
            height: 46px;
            line-height: 46px;
            margin-right: 15px;
            color: #ef4444; /* Makes the clear X button slightly red */
        }
        
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
                
                {{-- Only show print button if batch_id is flashed to session --}}
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
                        <div class="row g-4 mb-5">
                            
                            {{-- Department Dropdown --}}
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold text-uppercase tracking-wide">Department</label>
                                <select class="input-modern" name="department_name" id="departmentSelect" required onchange="updateRequestor()">
                                    <option value="" disabled selected>Select Department...</option>
                                    
                                    {{-- Administrative Departments --}}
                                    <optgroup label="Administrative Department">
                                        <option value="Admitting" data-head="JULIO MEDINA">Admitting</option>
                                        <option value="Accounting" data-head="JAHZEILE SALISTRE">Accounting</option>
                                        <option value="Building & Maintenance" data-head="MARY JANE BALDISMO">Building & Maintenance</option>
                                        <option value="Credit & Collection" data-head="MEMIA BUGTONG">Credit & Collection</option>
                                        <option value="HR" data-head="FRANCES THERESE MIRANDA">Human Resources</option>
                                        <option value="ICT" data-head="AIZA OBLIGAR">ICT</option>
                                        <option value="Medical Records" data-head="SHERYL ABLAO">Medical Records</option>
                                        <option value="Cashier" data-head="ROSELA FERNANDO">Cashier</option>
                                        <option value="Billing & Claims" data-head="REGNER BRILLO">Billing & Claims</option>
                                        <option value="Malasakit" data-head="LIZAMAE BERANO">Malasakit Center</option>
                                        <option value="Dietary" data-head="ROBENIA DAYALO">Dietary</option>
                                        <option value="Consignment" data-head="ALIANA MARIE DULA/KRISTINE MAE BATAN">Consignment Section</option>
                                        <option value="Quality Management Office" data-head="JHOANNA CRUZ-AM">Quality Management Office</option>
                                        <option value="Chief of Hospital II" data-head="DR. FLORENCIO LUCHING">Chief of Hospital II</option>
                                        <option value="Chief of Clinics" data-head="DR. VINCENT JURY LAURON">Chief of Clinics</option>
                                    </optgroup>

                                    {{-- Wards & Ancillary Units --}}
                                    <optgroup label="Wards / Ancillary Units">
                                        <option value="CSR" data-head="MIA BUENVENIDA">Central Supply Room</option>
                                        <option value="LAB" data-head="MARIJOE ARTATES">Laboratory</option>
                                        <option value="RADIO" data-head="SOCRATES BERCADEZ">Radiology</option>
                                        <option value="PHARMA" data-head="SHARA PATRIA SANTOS">Pharmacy</option>
                                        <option value="CARDIO PULMONARY" data-head="SONIA FLORENCIO">Cardio Pulmonary</option>
                                        <option value="WCPU" data-head="ANNIELEE ARIEL">WCPU</option>
                                        <option value="IW" data-head="ANABELLE DENAGA">Institutional Workers</option>
                                        <option value="Laundry" data-head="LENNIE TOCONG">Laundry</option>
                                        <option value="REHAB" data-head="ANABELLE GARCIA">Rehab</option>
                                        <option value="NSO" data-head="GLENA PIMENTEL">NSO Office</option>
                                        <option value="ORTHO" data-head="SUSIE ARMIZA">Orthopedic Ward</option>
                                        <option value="OB" data-head="WENDY MARTINEZ">OB Ward</option>
                                        <option value="ER" data-head="CHRISTINE ESQUILLO">Emergency Room</option>
                                        <option value="FMW" data-head="MARY GRACE BUARON">Female Medical Ward</option>
                                        <option value="MMW" data-head="JOSETTH LYNANNE TAN">Male Medical Ward</option>
                                        <option value="ICU" data-head="DREXCY JHOY SAN ANTONIO">Intensive Care Unit</option>
                                        <option value="NICU" data-head="MAY RACILLE JOY LANTORIA">Neonatal Intensive Care Unit</option>
                                        <option value="SURGICAL" data-head="LOUIE ANN AJERA">Surgical Ward</option>
                                        <option value="PEDIA" data-head="EVELYN AMBROSIO">Pediatric Ward</option>
                                        <option value="OR" data-head="JADD LOUIE UVAS">Operating Room</option>
                                        <option value="PAYWARD" data-head="MARIA VICTORIA ESCUTIN">Pay Ward</option>
                                        <option value="HEMO" data-head="EVANGELINE DETANOY">Hemodialysis</option>
                                        <option value="GUGMA DIALYSIS" data-head="STEPHEN ESPENOCILLA">Gugma Dialysis</option>
                                    </optgroup>
                                </select>
                            </div>

                            {{-- Auto-filling Requestor Input --}}
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold text-uppercase tracking-wide">Requestor Name (Head)</label>
                                <input type="text" class="input-modern bg-light text-muted" name="requested_by" id="requestedByInput" placeholder="Auto-filled Head Name" required readonly>
                            </div>
                        </div>

                        <div class="p-4 bg-light rounded-4 mb-5 border border-white border-4 shadow-sm">
                            <label class="form-label text-muted small fw-bold text-uppercase tracking-wide mb-3"><i class="bi bi-cart-plus me-2"></i>Build Your Request</label>
                            
                            {{-- FIXED: Added alignment and wrapper to prevent overlaps --}}
                            <div class="d-flex align-items-center gap-2 mb-4">
                                {{-- Supply Selection Dropdown --}}
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <select id="supplySelect" style="width: 100%;">
                                        <option value="" disabled selected>Search inventory items...</option>
                                        @foreach($supplies as $item)
                                            @if($item->quantity > 0)
                                                <option value="{{ $item->id }}" data-name="{{ $item->name }} {{ $item->description ? '('.$item->description.')' : '' }}" data-max="{{ $item->quantity }}">
                                                    {{ $item->name }} {{ $item->description ? '- ' . $item->description : '' }} (In Stock: {{ $item->quantity }})
                                                </option>
                                            @else
                                                <option disabled class="text-danger fw-bold">
                                                    [OUT OF STOCK] {{ $item->name }} {{ $item->description ? '- ' . $item->description : '' }}
                                                </option>
                                            @endif
                                        @endforeach
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
                                                    <div class="text-muted text-uppercase" style="font-size: 0.70rem; letter-spacing: 0.5px;">
                                                        {{ $item->description }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div>
                                                @if($item->quantity > 0)
                                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill border border-success border-opacity-25 px-2 py-1">Available</span>
                                                @else
                                                    <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill border border-danger border-opacity-25 px-2 py-1">Out of Stock</span>
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

    {{-- jQuery & Select2 JS --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // Initialize Select2 Search
        $(document).ready(function() {
            $('#supplySelect').select2({
                placeholder: "Search inventory items..."
            });
        });

        // Auto-fill Requestor Function
        function updateRequestor() {
            let select = document.getElementById('departmentSelect');
            let headName = select.options[select.selectedIndex].getAttribute('data-head');
            document.getElementById('requestedByInput').value = headName || '';
        }

        // Print Iframe logic for the success screen
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

            if(existing) existing.qty = parseInt(existing.qty) + parseInt(qty);
            else cart.push({id: sel.value, name: itemName, qty: parseInt(qty)});
            
            updateUI();
            
            // Reset Select2 properly after adding
            $('#supplySelect').val(null).trigger('change');
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
                            <span class="fw-bold text-dark fs-5">${item.name}</span>
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
            if(cart.length === 0) { alert("Please add at least one item."); return false; }
            return true;
        }
    </script>
</body>
</html>