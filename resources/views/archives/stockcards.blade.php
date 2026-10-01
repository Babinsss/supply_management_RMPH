<x-layouts.admin title="Stockcard Archives | Supply Hub">

    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary-subtle">
        <div>
            <h4 class="fw-bolder mb-0 text-body-emphasis"><i class="bi bi-archive-fill text-warning me-2"></i>Stockcard Archives</h4>
            <p class="text-muted small mb-0 mt-1">Review historical stockcards and ledgers from previous months.</p>
        </div>
    </div>

    <!-- ARCHIVE FILTER BAR -->
    <div class="bento-card bg-body-tertiary border border-secondary-subtle p-3 mb-4 rounded-4 shadow-sm">
        <form action="/stockcard-archives" method="GET" class="d-flex flex-wrap align-items-end gap-3">
            <div>
                <label class="form-label text-muted small fw-bold text-uppercase mb-1">Select Month</label>
                <select name="month" class="form-select input-modern shadow-sm" style="min-width: 200px;">
                    @for($m=1; $m<=12; ++$m)
                        <option value="{{ sprintf('%02d', $m) }}" {{ $month == sprintf('%02d', $m) ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                        </option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="form-label text-muted small fw-bold text-uppercase mb-1">Select Year</label>
                <select name="year" class="form-select input-modern shadow-sm" style="min-width: 150px;">
                    @foreach($availableYears as $yr)
                        <option value="{{ $yr }}" {{ $year == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-warning btn-modern shadow-sm text-dark fw-bold px-4">
                    <i class="bi bi-search me-1"></i> Load Archive
                </button>
            </div>
        </form>
    </div>

    <!-- ARCHIVED ITEMS TABLE -->
    <div class="bento-card bg-body border border-secondary-subtle p-0 overflow-hidden">
        
        <div class="p-3 border-bottom border-secondary-subtle bg-body-tertiary d-flex justify-content-between align-items-center flex-wrap gap-3">
            <h6 class="fw-bolder mb-0 text-body-emphasis">Items with movement in {{ $monthName }} {{ $year }}</h6>
            
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text bg-body border-end-0 rounded-start-4"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="archiveSearchInput" class="form-control input-modern border-start-0 rounded-end-4 pl-0 shadow-sm" placeholder="Search archive items..." onkeyup="filterArchives()">
            </div>
        </div>
        
        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
            <table class="table table-clean align-middle mb-0">
                <thead class="sticky-top bg-body">
                    <tr>
                        <th class="text-uppercase small text-muted ps-4">Item Description</th>
                        <th class="text-uppercase small text-muted">Category</th>
                        <th class="text-uppercase small text-muted text-end pe-4">Archived Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activeSupplies as $item)
                        <tr class="border-bottom border-secondary-subtle transition-all hover-lift archive-row">
                            <td class="ps-4">
                                <div class="fw-bold text-body-emphasis fs-6">{{ $item->name }}</div>
                                @if($item->trashed())
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-pill px-2 mt-1" style="font-size: 0.65rem;">Deleted Item</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-muted-soft small"><i class="bi bi-tag-fill me-1"></i>{{ $item->category ?: 'Uncategorized' }}</span>
                            </td>
                            <td class="text-end pe-4">
                                <!-- UPDATED: Changed to a button that triggers the print modal instantly -->
                                <button type="button" onclick="printDirectly('/stockcard/{{ $item->id }}?month={{ $month }}&year={{ $year }}')" class="btn btn-sm btn-outline-secondary btn-modern text-primary shadow-sm fw-bold">
                                    <i class="bi bi-printer-fill me-1"></i> Print Stockcard
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-5 text-muted-soft fw-medium">
                                <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                No inventory movement recorded during {{ $monthName }} {{ $year }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- SCRIPTS FOR LIVE SEARCH & DIRECT PRINT -->
    <x-slot name="scripts">
        <script>
            // 1. Live Search Filter
            function filterArchives() {
                let input = document.getElementById('archiveSearchInput').value.toLowerCase();
                let rows = document.querySelectorAll('.archive-row');

                rows.forEach(row => {
                    let textContent = row.innerText.toLowerCase();
                    if (textContent.includes(input)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            // 2. Silent Print Logic (Hidden Iframe)
            function printDirectly(url) {
                let printFrame = document.getElementById('hiddenPrintFrame') || document.createElement('iframe');
                if(!printFrame.id) {
                    printFrame.id = 'hiddenPrintFrame';
                    printFrame.style.cssText = 'width:0; height:0; border:none; position:absolute; z-index:-1;';
                    document.body.appendChild(printFrame);
                }
                
                printFrame.src = url;
                printFrame.onload = () => { 
                    printFrame.contentWindow.focus(); 
                    printFrame.contentWindow.print(); 
                };
            }
        </script>
    </x-slot>

</x-layouts.admin>