<x-layouts.admin title="RIS Archive | Superadmin">
    <div class="bento-card mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bolder mb-0"><i class="bi bi-archive-fill text-primary me-2"></i> Master RIS Archive</h5>
        </div>
        
        <div class="table-responsive">
            <table class="table table-clean mb-0">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Department / Requestor</th>
                        <th>Items Count</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $batch)
                    <tr class="align-middle">
                        <td>
                            <div class="fw-bold text-dark">{{ \Carbon\Carbon::parse($batch['created_at'])->format('M d, Y') }}</div>
                            <div class="text-muted-soft small">{{ \Carbon\Carbon::parse($batch['created_at'])->format('h:i A') }}</div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark">{{ $batch['department_name'] }}</div>
                            <div class="text-muted-soft small">{{ $batch['requested_by'] }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ count($batch['items']) }} Items</span>
                        </td>
                        <td>
                            @if($batch['status'] == 'Pending')
                                <span class="badge bg-warning bg-opacity-25 text-dark rounded-pill px-3 py-2">Pending</span>
                            @elseif($batch['status'] == 'Approved')
                                <span class="badge bg-success bg-opacity-25 text-success rounded-pill px-3 py-2">Issued</span>
                            @else
                                <span class="badge bg-danger bg-opacity-25 text-danger rounded-pill px-3 py-2">{{ $batch['status'] }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <button type="button" onclick="printDirectly('/print-bulk/{{ $batch['batch_id'] }}')" class="btn btn-sm btn-modern btn-primary shadow-sm">
                                <i class="bi bi-printer-fill me-1"></i> Print Copy
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-5 text-muted-soft fw-medium">No RIS records found.</td></tr>
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
                printFrame.onload = () => { 
                    printFrame.contentWindow.focus(); 
                    printFrame.contentWindow.print(); 
                };
            }
        </script>
    </x-slot>
</x-layouts.admin>