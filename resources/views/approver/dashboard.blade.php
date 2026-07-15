<x-layouts.approver title="Dashboard | QMO Approver">
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="bento-card bg-primary text-white d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-white-50 fw-bold mb-1 text-uppercase small">Awaiting Issuance</p>
                    <h1 class="fw-bolder mb-0 display-4" id="navPendingCount">{{ $pending_count }}</h1>
                </div>
                <i class="bi bi-bell-fill fs-1 text-white-50"></i>
            </div>
        </div>
        <div class="col-md-6">
            <div class="bento-card d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted-soft fw-bold mb-1 text-uppercase small">Total Requisitions Processed</p>
                    <h1 class="fw-bolder mb-0 display-4 text-dark">{{ is_object($requests) && method_exists($requests, 'total') ? $requests->total() : count($requests) }}</h1>
                </div>
                <i class="bi bi-check-circle-fill fs-1 text-success opacity-25"></i>
            </div>
        </div>
    </div>

    <div class="bento-card mb-5">
        <h5 class="fw-bolder mb-4"><i class="bi bi-inbox-fill text-warning me-2"></i> Department Requisitions Monitor</h5>
        <div class="table-responsive">
            <table class="table table-clean mb-0">
                <thead>
                    <tr>
                        <th>Date & Time (PH)</th>
                        <th>Requestor</th>
                        <th>Items</th>
                        <th class="text-end">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $batch)
                    <tr>
                        <td>
                            @php $batchDate = $batch['created_at'] ?? $batch['items']->first()->created_at; @endphp
                            <div class="fw-bold text-dark">{{ \Carbon\Carbon::parse($batchDate)->timezone('Asia/Manila')->format('M d, Y') }}</div>
                            <div class="text-muted-soft small">{{ \Carbon\Carbon::parse($batchDate)->timezone('Asia/Manila')->format('h:i A') }}</div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark">{{ $batch['department_name'] }}</div>
                            <div class="text-muted-soft small">{{ $batch['requested_by'] }}</div>
                        </td>
                        <td>
                            @foreach($batch['items'] as $req)
                                <div class="mb-2">
                                    <div class="small fw-medium">
                                        <span class="text-primary fw-bolder">{{ $req->quantity }}x</span> {{ $req->supply->name }}
                                    </div>
                                    @if($req->supply->category)
                                        <div class="text-muted-soft text-uppercase" style="font-size: 0.65rem; margin-left: 1.4rem; letter-spacing: 0.5px;">
                                            <i class="bi bi-tag"></i> {{ $req->supply->category }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </td>
                        <td class="text-end">
                            @if($batch['status'] == 'Pending')
                                <span class="badge bg-warning bg-opacity-25 text-dark rounded-pill px-3 py-2">
                                    <i class="bi bi-hourglass-split me-1"></i> Pending
                                </span>
                            @elseif($batch['status'] == 'Approved')
                                <span class="badge bg-success bg-opacity-25 text-success rounded-pill px-3 py-2">
                                    <i class="bi bi-check-circle-fill me-1"></i> Approved
                                </span>
                            @elseif($batch['status'] == 'Denied')
                                <span class="badge bg-danger bg-opacity-25 text-danger rounded-pill px-3 py-2">
                                    <i class="bi bi-x-circle-fill me-1"></i> Disapproved
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center py-5 text-muted-soft fw-medium">No pending requests.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if(is_object($requests) && method_exists($requests, 'links'))
            <div class="d-flex justify-content-end mt-4">
                {{ $requests->links() }}
            </div>
        @endif
    </div>

    <x-slot name="scripts">
        <script>
            let currentCount = parseInt("{{ $pending_count }}") || 0;
            setInterval(() => {
                fetch('/api/pending-count')
                    .then(r => r.json())
                    .then(data => {
                        if (data.count !== currentCount) { 
                            location.reload(); 
                        }
                    });
            }, 5000);
        </script>
    </x-slot>
</x-layouts.approver>