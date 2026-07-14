<x-layouts.approver title="Inventory Viewer | QMO">

    {{-- Top Action Bar with Search ONLY --}}
    <div class="row g-3 mb-4 bento-card align-items-center">
        <div class="col-12 col-md-4">
            <h5 class="fw-bolder mb-0 text-dark"><i class="bi bi-eye-fill text-primary me-2"></i> Inventory Status</h5>
        </div>
        
        <div class="col-12 col-md-8">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 rounded-start-4"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="searchInput" class="form-control input-modern border-start-0 rounded-end-4 pl-0" placeholder="Search item, description, supplier, or RIS..." onkeyup="filterInventory()">
            </div>
        </div>
    </div>

    {{-- Main Inventory Table (Read-Only) --}}
    <div class="bento-card mb-5">
        <div class="table-responsive">
            <table class="table table-clean mb-0" id="inventoryTable">
                <thead>
                    <tr>
                        <th>Item & Details</th>
                        <th>Current Stock</th>
                        <th>RIS Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($supplies as $item)
                    <tr class="inventory-row">
                        <td>
                            <div class="fw-bold text-dark fs-6 item-name">{{ $item->name }}</div>
                            @if($item->description)
                                <div class="text-muted text-uppercase item-desc mb-2" style="font-size: 0.70rem; letter-spacing: 0.5px;">
                                    {{ $item->description }}
                                </div>
                            @endif
                            <div class="d-flex flex-wrap gap-3 mt-1">
                                @if($item->unit_price)
                                    <span class="text-success fw-bold" style="font-size: 0.75rem;"><i class="bi bi-tag-fill me-1"></i>₱{{ number_format($item->unit_price, 2) }}</span>
                                @endif
                                @if($item->supplier)
                                    <span class="text-muted-soft item-supplier" style="font-size: 0.75rem;"><i class="bi bi-truck me-1"></i>{{ $item->supplier }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $item->quantity > 0 ? 'bg-success text-success' : 'bg-danger text-danger' }} bg-opacity-10 rounded-pill px-3 py-2 border {{ $item->quantity > 0 ? 'border-success' : 'border-danger' }} border-opacity-25">
                                {{ $item->quantity }} Units
                            </span>
                        </td>
                        <td>
                            @if($item->ris_number)
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 item-ris">
                                    <i class="bi bi-hash"></i> {{ $item->ris_number }}
                                </span>
                            @else
                                <span class="text-muted-soft small fst-italic">Without RIS</span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            {{-- Kept ONLY the Stockcard print button --}}
                            <button type="button" class="btn btn-sm btn-light btn-modern text-dark border shadow-sm" onclick="printDirectly('/stockcard/{{ $item->id }}')">
                                <i class="bi bi-printer"></i> Stockcard
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center py-5 text-muted-soft fw-medium">No inventory items found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-slot name="scripts">
        <script>
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

            function filterInventory() {
                let input = document.getElementById('searchInput').value.toLowerCase();
                let rows = document.querySelectorAll('.inventory-row');
                
                rows.forEach(row => {
                    let textData = row.innerText.toLowerCase();
                    row.style.display = textData.includes(input) ? '' : 'none';
                });
            }
        </script>
    </x-slot>

</x-layouts.approver>