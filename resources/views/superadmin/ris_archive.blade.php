<x-layouts.admin title="RIS Archive | Superadmin">
    <div class="d-flex justify-content-between align-items-center mb-4 bento-card py-3">
        <h5 class="fw-bolder mb-0 text-dark">
            <i class="bi bi-archive-fill text-primary me-2"></i> Master RIS Archive
        </h5>
        
        {{-- Search bar for quick filtering --}}
        <div class="input-group shadow-sm" style="max-width: 350px;">
            <span class="input-group-text bg-light border-end-0 rounded-start-4"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="archiveSearch" class="form-control input-modern border-start-0 rounded-end-4 pl-0" placeholder="Search department, status..." onkeyup="filterArchive()">
        </div>
    </div>
    
    <div class="bento-card mb-5">
        <div class="table-responsive" style="max-height: 650px; overflow-y: auto;">
            <table class="table table-clean mb-0">
                <thead class="sticky-top bg-white" style="z-index: 1;">
                    <tr>
                        <th class="text-uppercase small text-muted">Date & Time</th>
                        <th class="text-uppercase small text-muted">Department / Requestor</th>
                        <th class="text-uppercase small text-muted text-center">Items Count</th>
                        <th class="text-uppercase small text-muted">Status</th>
                        <th class="text-uppercase small text-muted text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $batch)
                    <tr class="align-middle archive-row border-bottom border-light">
                        <td>
                            <div class="fw-bold text-dark">{{ \Carbon\Carbon::parse($batch['created_at'])->format('M d, Y') }}</div>
                            <div class="text-muted-soft small"><i class="bi bi-clock me-1"></i>{{ \Carbon\Carbon::parse($batch['created_at'])->format('h:i A') }}</div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark">{{ $batch['department_name'] }}</div>
                            <div class="text-muted-soft small fw-medium"><i class="bi bi-person me-1"></i>{{ $batch['requested_by'] }}</div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-3 py-1">{{ count($batch['items']) }} Items</span>
                        </td>
                        <td>
                            @if($batch['status'] == 'Pending')
                                <span class="badge bg-warning bg-opacity-25 text-dark rounded-pill px-3 py-2 border border-warning shadow-sm"><i class="bi bi-hourglass-split me-1"></i> Pending</span>
                            @elseif($batch['status'] == 'Approved')
                                <span class="badge bg-success bg-opacity-25 text-success rounded-pill px-3 py-2"><i class="bi bi-check-circle-fill me-1"></i> Issued</span>
                            @else
                                <span class="badge bg-danger bg-opacity-25 text-danger rounded-pill px-3 py-2"><i class="bi bi-x-circle-fill me-1"></i> {{ $batch['status'] }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <button type="button" onclick="printDirectly('/print-bulk/{{ $batch['batch_id'] }}')" class="btn btn-sm btn-light btn-modern text-primary border shadow-sm px-3">
                                <i class="bi bi-printer-fill me-1"></i> Print Copy
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted-soft fw-medium">
                            <i class="bi bi-archive fs-2 d-block mb-2 opacity-25"></i>
                            No RIS records found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-slot name="scripts">
        <script>
            // Archive Table Filtering
            function filterArchive() {
                let input = document.getElementById('archiveSearch').value.toLowerCase();
                let rows = document.querySelectorAll('.archive-row');
                
                rows.forEach(row => {
                    if (row.innerText.toLowerCase().includes(input)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            // Silent Print Function
            function printDirectly(url) {
                let printFrame = document.getElementById('hiddenPrintFrame') || document.createElement('iframe');
                if(!printFrame.id) {
                    printFrame.id = 'hiddenPrintFrame';
                    printFrame.style.cssText = 'width:0; height:0; border:none; position:absolute;';
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