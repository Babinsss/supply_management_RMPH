<x-layouts.admin title="Dashboard | Supply Hub">
    
    <div class="row g-4 mb-4">
        {{-- Quick Stat: Pending --}}
        <div class="col-md-6">
            <div class="bento-card bg-primary text-white d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-white-50 fw-bold mb-1 text-uppercase small">Pending Requests</p>
                    <h1 class="fw-bolder mb-0 display-4" id="navPendingCount">{{ $pending_count }}</h1>
                </div>
                <i class="bi bi-bell-fill fs-1 text-white-50"></i>
            </div>
        </div>
        {{-- Quick Stat: Processed --}}
        <div class="col-md-6">
            <div class="bento-card d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted-soft fw-bold mb-1 text-uppercase small">Total Requisitions Processed</p>
                    <h1 class="fw-bolder mb-0 display-4 text-dark">{{ count($requests) }}</h1>
                </div>
                <i class="bi bi-check2-all fs-1 text-primary opacity-25"></i>
            </div>
        </div>
    </div>

    <div class="bento-card mb-5">
        {{-- NEW: Replaced single h5 with Flexbox container for Header + Search Bar --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bolder mb-0"><i class="bi bi-inbox-fill text-warning me-2"></i> Department Requisitions Monitor</h5>
            
            {{-- Search Bar --}}
            <div class="input-group" style="max-width: 350px;">
                <span class="input-group-text bg-light border-end-0 rounded-start-4"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="requestSearchInput" class="form-control input-modern border-start-0 rounded-end-4 pl-0" placeholder="Search department, item, or status..." onkeyup="filterRequests()">
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-clean mb-0">
                <thead>
                    <tr>
                        <th>Date & Time (PH)</th>
                        <th>Requestor</th>
                        <th>Items</th>
                        {{-- DELETED the duplicate generic Status th here --}}
                        <th class="text-end">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $batch)
                    {{-- NEW: Added request-row class for the JavaScript to target --}}
                    <tr class="request-row">
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
                        
                        {{-- DELETED the duplicate generic Pending/Processed td block here --}}
                        
                        <td class="text-end">
                            @if($batch['status'] == 'Pending')
                                <span class="badge bg-warning bg-opacity-25 text-dark rounded-pill px-3 py-2">
                                    <i class="bi bi-hourglass-split me-1"></i> Pending
                                </span>
                            @elseif($batch['status'] == 'Approved')
                                <span class="badge bg-success bg-opacity-25 text-success rounded-pill px-3 py-2">
                                    <i class="bi bi-check-circle me-1"></i> Approved
                                </span>
                            @elseif($batch['status'] == 'Denied')
                                <span class="badge bg-danger bg-opacity-25 text-danger rounded-pill px-3 py-2">
                                    <i class="bi bi-x-circle me-1"></i> Disapproved
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center py-5 text-muted-soft fw-medium">No requests found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-slot name="scripts">
        <script>
            // Auto-refresh ICT Dashboard
            let currentCount = parseInt("{{ $pending_count }}") || 0;
            setInterval(() => {
                fetch('/api/pending-count')
                    .then(r => r.json())
                    .then(data => {
                        if (data.count !== currentCount) { location.reload(); }
                    });
            }, 5000);

            // NEW: Real-time Search Filter for Requests
            function filterRequests() {
                let input = document.getElementById('requestSearchInput').value.toLowerCase();
                let rows = document.querySelectorAll('.request-row');
                
                rows.forEach(row => {
                    let textContent = row.innerText.toLowerCase();
                    if (textContent.includes(input)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        </script>
    </x-slot>
</x-layouts.admin>