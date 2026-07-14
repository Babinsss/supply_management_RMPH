<x-layouts.approver title="Dashboard | QMO Approver">
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="bento-card bg-primary text-white d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-white-50 fw-bold mb-1 text-uppercase small">Awaiting Approval</p>
                    <h1 class="fw-bolder mb-0 display-4" id="navPendingCount">{{ $pending_count }}</h1>
                </div>
                <i class="bi bi-bell-fill fs-1 text-white-50"></i>
            </div>
        </div>
        <div class="col-md-6">
            <div class="bento-card d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted-soft fw-bold mb-1 text-uppercase small">Total Requisitions Approved</p>
                    <h1 class="fw-bolder mb-0 display-4 text-dark">{{ count($requests) }}</h1>
                </div>
                <i class="bi bi-check-circle-fill fs-1 text-success opacity-25"></i>
            </div>
        </div>
    </div>

    <div class="bento-card mb-5">
        <h5 class="fw-bolder mb-4"><i class="bi bi-inbox-fill text-warning me-2"></i> Department Requisitions</h5>
        <div class="table-responsive">
            <table class="table table-clean mb-0">
                <thead>
                    <tr>
                        <th>Date & Time (PH)</th>
                        <th>Requestor</th>
                        <th>Items</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
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
                                <div class="small fw-medium"><span class="text-primary">{{ $req->quantity }}x</span> {{ $req->supply->name }}</div>
                            @endforeach
                        </td>
                        <td>
                            @if($batch['status'] == 'Pending')
                                <span class="badge bg-warning bg-opacity-25 text-dark rounded-pill px-3 py-2">Pending</span>
                            @else
                                <span class="badge bg-success bg-opacity-25 text-success rounded-pill px-3 py-2">Processed</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($batch['status'] == 'Pending')
                                <button type="button" class="btn btn-sm btn-modern btn-success" data-bs-toggle="modal" data-bs-target="#approveModal-{{ $batch['batch_id'] }}">Review</button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-5 text-muted-soft fw-medium">No pending requests.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Review Modals --}}
    @foreach($requests as $batch)
        @if($batch['status'] == 'Pending')
            <div class="modal fade text-start" id="approveModal-{{ $batch['batch_id'] }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bento-card p-2 border-0">
                        <form action="/process-batch/{{ $batch['batch_id'] }}/approve" method="POST">
                            @csrf
                            <div class="modal-header border-0 pb-0">
                                <h5 class="fw-bolder text-dark">Review Request</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted-soft small mb-4">Adjust quantities as needed. You cannot exceed stock.</p>
                                @foreach($batch['items'] as $req)
                                <div class="d-flex justify-content-between align-items-center mb-3 bg-light p-3 rounded-4 border border-white border-2 shadow-sm">
                                    <div class="pe-2">
                                        <div class="fw-bold text-dark fs-6">{{ $req->supply->name }}</div>
                                        <div class="text-muted-soft small mt-1">Requested: {{ $req->quantity }} | Stock: {{ $req->supply->quantity }}</div>
                                    </div>
                                    <div style="width: 90px; flex-shrink: 0;">
                                        <input type="number" class="input-modern text-center py-2 fw-bolder text-primary" name="qty_{{ $req->id }}" 
                                               value="{{ $req->quantity <= $req->supply->quantity ? $req->quantity : $req->supply->quantity }}" 
                                               min="0" max="{{ $req->supply->quantity }}" required>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div class="modal-header border-0 pt-0 justify-content-end gap-2">
                                <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success btn-modern shadow-sm"><i class="bi bi-check-circle-fill me-2"></i> Approve</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    <x-slot name="scripts">
        <script>
            let currentCount = parseInt("{{ $pending_count }}") || 0;
            setInterval(() => {
                fetch('/api/pending-count')
                    .then(r => r.json())
                    .then(data => {
                        if (data.count !== currentCount && !document.querySelector('.modal.show')) { 
                            location.reload(); 
                        }
                    });
            }, 5000);
        </script>
    </x-slot>
</x-layouts.approver>