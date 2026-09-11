<x-layouts.admin title="Dashboard | Supply Hub">
    
    <!-- TOP METRICS -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="bento-card bg-primary text-white d-flex justify-content-between align-items-center h-100">
                <div>
                    <p class="text-white-50 fw-bold mb-1 text-uppercase small">Pending Requests</p>
                    <h1 class="fw-bolder mb-0 display-4" id="navPendingCount">{{ $pending_count }}</h1>
                </div>
                <i class="bi bi-bell-fill fs-1 text-white-50"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bento-card d-flex flex-column justify-content-between h-100 position-relative overflow-hidden pt-4 pb-0 px-0">
                <div class="px-4 mb-2 d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted-soft fw-bold mb-1 text-uppercase small">Total Requisitions</p>
                        <h1 class="fw-bolder mb-0 display-4 text-dark">{{ is_object($requests) && method_exists($requests, 'total') ? $requests->total() : count($requests) }}</h1>
                    </div>
                    <i class="bi bi-graph-up-arrow fs-3 text-primary opacity-50"></i>
                </div>
                <div id="requisitionChart" class="mt-auto w-100" style="min-height: 80px;"></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bento-card bg-danger bg-opacity-10 border border-danger border-opacity-25 d-flex justify-content-between align-items-center h-100">
                <div>
                    <p class="text-danger fw-bold mb-1 text-uppercase small">Low Stock Alerts</p>
                    <h1 class="fw-bolder mb-0 display-4 text-danger">{{ $low_stock_count }}</h1>
                </div>
                <i class="bi bi-exclamation-octagon-fill fs-1 text-danger opacity-50"></i>
            </div>
        </div>
    </div>

    <!-- REQUEST PROCESSING TIMELINE SECTION -->
    <div class="d-flex justify-content-between align-items-center mb-4 mt-5">
        <h5 class="fw-bolder mb-0 text-dark"><i class="bi bi-clock-history text-info me-2"></i> Request Processing Timeline</h5>
        <div class="input-group" style="max-width: 350px;">
            <span class="input-group-text bg-body-tertiary border-end-0 rounded-start-4"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="requestSearchInput" class="form-control input-modern border-start-0 rounded-end-4 pl-0" placeholder="Search timeline..." onkeyup="filterRequests()">
        </div>
    </div>

    <div class="bento-card mb-4 p-5">
        <div class="position-relative ms-2 ms-md-4 border-start border-3 border-primary border-opacity-25 pb-4">
            
            @forelse($requests as $batch)
                <div class="timeline-node position-relative mb-5 ps-4 ps-md-5">
                    
                    {{-- THE TIMELINE DOT --}}
                    <span class="position-absolute top-0 start-0 translate-middle p-2 bg-body border border-3 {{ $batch['status'] == 'Pending' ? 'border-warning' : ($batch['status'] == 'Approved' ? 'border-success' : 'border-danger') }} rounded-circle shadow" style="width: 24px; height: 24px; margin-left: -1.5px;"></span>
                    
                    {{-- THE CONTENT CARD --}}
                    <div class="card timeline-card border-0 shadow-sm rounded-4 overflow-hidden {{ $batch['status'] == 'Pending' ? 'bg-warning bg-opacity-10' : 'bg-body-tertiary' }}">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bolder text-dark mb-0">{{ $batch['department_name'] }}</h5>
                                <span class="badge {{ $batch['status'] == 'Pending' ? 'bg-warning text-dark' : ($batch['status'] == 'Approved' ? 'bg-success' : 'bg-danger') }}">{{ $batch['status'] }}</span>
                            </div>
                            
                            <p class="text-muted small mb-3"><i class="bi bi-calendar-event me-2"></i>{{ \Carbon\Carbon::parse($batch['created_at'] ?? now())->timezone('Asia/Manila')->format('F d, Y - h:i A') }} | <i class="bi bi-person me-1"></i> {{ $batch['requested_by'] }}</p>
                            
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                @foreach(collect($batch['items'])->take(3) as $req)
                                    <span class="badge bg-body text-body border border-secondary-subtle px-3 py-2 shadow-sm">
                                        <span class="text-primary fw-bolder me-1">{{ $req->quantity }}x</span> {{ $req->supply->name }}
                                    </span>
                                @endforeach
                                @if(count($batch['items']) > 3)
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle px-3 py-2">+ {{ count($batch['items']) - 3 }} more</span>
                                @endif
                            </div>

                            @if($batch['status'] == 'Pending')
                                <div class="mt-4 text-end d-flex justify-content-end gap-2">
                                    <form action="/process-batch/{{ $batch['batch_id'] }}/deny" method="POST" onsubmit="return confirm('Disapprove this request?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger btn-modern"><i class="bi bi-x-circle me-1"></i> Cancel</button>
                                    </form>
                                    <button class="btn btn-sm btn-success btn-modern shadow-sm" data-bs-toggle="modal" data-bs-target="#approveModal-{{ $batch['batch_id'] }}"><i class="bi bi-check2-circle me-1"></i> Process Request</button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted-soft fw-medium ps-4">The timeline is empty.</div>
            @endforelse
            
        </div>
    </div>

    @if(is_object($requests) && method_exists($requests, 'links'))
        <div class="d-flex justify-content-end mb-5">{{ $requests->links() }}</div>
    @endif

    {{-- Review Modals --}}
    @foreach($requests as $batch)
        @if($batch['status'] == 'Pending')
            <div class="modal fade text-start" id="approveModal-{{ $batch['batch_id'] }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bento-card p-2 border-0">
                        <form action="/process-batch/{{ $batch['batch_id'] }}/approve" method="POST">
                            @csrf
                            <div class="modal-header border-0 pb-0">
                                <h5 class="fw-bolder text-dark">Issue Request</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                @php
                                    $hasOutOfStock = false;
                                    $totalFulfillable = 0;
                                    foreach($batch['items'] as $req) {
                                        if($req->supply->quantity <= 0) $hasOutOfStock = true;
                                        $totalFulfillable += min($req->quantity, $req->supply->quantity);
                                    }
                                @endphp

                                @if($hasOutOfStock)
                                    <div class="alert alert-danger border-0 rounded-3 py-2 small fw-bold mb-3 shadow-sm">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Heads up! Some items in this request are already out of stock.
                                    </div>
                                @else
                                    <p class="text-muted-soft small mb-4">Adjust quantities before issuing. You cannot exceed stock.</p>
                                @endif

                                @foreach($batch['items'] as $req)
                                <div class="d-flex justify-content-between align-items-center mb-3 bg-body-tertiary p-3 rounded-4 border border-secondary-subtle shadow-sm {{ $req->supply->quantity <= 0 ? 'opacity-75' : '' }}">
                                    <div class="pe-2">
                                        <div class="fw-bold text-dark fs-6">{{ $req->supply->name }}</div>
                                        <div class="text-muted-soft small mt-1">
                                            Req: <span class="fw-bold">{{ $req->quantity }}</span> | 
                                            Stock: <span class="fw-bold {{ $req->supply->quantity <= 0 ? 'text-danger' : 'text-dark' }}">{{ $req->supply->quantity }}</span>
                                        </div>
                                    </div>
                                    <div style="width: 90px; flex-shrink: 0;">
                                        <input type="number" class="input-modern text-center py-2 fw-bolder text-primary" name="qty_{{ $req->id }}" 
                                               value="{{ min($req->quantity, $req->supply->quantity) }}" min="0" max="{{ min($req->quantity, $req->supply->quantity) }}" {{ $req->supply->quantity <= 0 ? 'readonly' : 'required' }}>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div class="modal-header border-0 pt-0 justify-content-end gap-2">
                                <button type="button" class="btn btn-light btn-modern text-muted" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success btn-modern shadow-sm" {{ $totalFulfillable <= 0 ? 'disabled title="Nothing to issue"' : '' }}>
                                    <i class="bi bi-check-circle-fill me-2"></i> Confirm Issuance
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    <x-slot name="scripts">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

        <script>
            // 1. SKELETON LOADERS
            document.addEventListener('DOMContentLoaded', () => {
                const nodes = document.querySelectorAll('.timeline-card');
                nodes.forEach(node => node.classList.add('skeleton'));
                setTimeout(() => nodes.forEach(node => node.classList.remove('skeleton')), 450);
            });

            // 2. APEXCHARTS RENDERER
            document.addEventListener('DOMContentLoaded', function () {
                var options = {
                    series: [{ name: 'Requests Processed', data: {!! json_encode($chartData ?? [0,0,0,0,0,0,0]) !!} }],
                    chart: { type: 'area', height: 80, sparkline: { enabled: true }, animations: { enabled: true, easing: 'easeinout', speed: 800 } },
                    stroke: { curve: 'smooth', width: 2 },
                    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0, stops: [0, 100] } },
                    colors: ['#0d6efd'],
                    tooltip: { fixed: { enabled: false }, x: { show: false }, y: { title: { formatter: function () { return 'Requests: ' } } }, marker: { show: false } }
                };
                new ApexCharts(document.querySelector("#requisitionChart"), options).render();
            });

            // 3. SERVER ALERTS
            @if(session('danger')) Swal.fire({ icon: 'error', title: 'Queue Violation!', text: "{!! session('danger') !!}", confirmButtonColor: '#dc3545', backdrop: `rgba(220, 53, 69, 0.1)` }); @endif
            @if(session('success')) Swal.fire({ icon: 'success', title: 'Success!', text: "{!! session('success') !!}", confirmButtonColor: '#198754', timer: 2000, showConfirmButton: false }); @endif

            // 4. QUEUE LIVE UPDATE
            let currentCount = parseInt("{{ $pending_count }}") || 0;
            setInterval(() => {
                fetch('/api/pending-count').then(r => r.json()).then(data => {
                    if (data.count !== currentCount && !document.querySelector('.modal.show')) location.reload(); 
                });
            }, 5000);

            // 5. CUSTOM TIMELINE FILTER
            function filterRequests() {
                let input = document.getElementById('requestSearchInput').value.toLowerCase();
                let nodes = document.querySelectorAll('.timeline-node');
                nodes.forEach(node => {
                    if (node.innerText.toLowerCase().includes(input)) {
                        node.style.display = '';
                        let card = node.querySelector('.timeline-card');
                        card.classList.add('skeleton');
                        setTimeout(() => card.classList.remove('skeleton'), 300);
                    } else {
                        node.style.display = 'none';
                    }
                });
            }

            // 6. THE SNITCH: AUTOMATED LOW-STOCK ALERTS
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 6000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            function sniffLowStock() {
                fetch('/api/low-stock')
                    .then(r => r.json())
                    .then(data => {
                        if (data.count > 0) {
                            // Format the item list
                            let itemList = data.items.slice(0, 3).map(i => `<div class="text-start small mb-1"><strong class="text-danger">${i.quantity} left</strong> - ${i.name}</div>`).join('');
                            
                            if (data.count > 3) {
                                itemList += `<div class="text-start small text-muted-soft mt-1 fst-italic">+ ${data.count - 3} more critical items...</div>`;
                            }

                            // Match the toast background to the current theme
                            const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';

                            Toast.fire({
                                icon: 'warning',
                                title: 'Critical Stock Alert',
                                html: itemList,
                                background: isDark ? '#1e293b' : '#ffffff',
                                color: isDark ? '#f8fafc' : '#0f172a'
                            });
                        }
                    })
                    .catch(error => console.error('Snitch failed to run:', error));
            }

            // Fire it up 2 seconds after load, then every 5 minutes
            setTimeout(sniffLowStock, 2000);
            setInterval(sniffLowStock, 300000);
            
        </script>
    </x-slot>
</x-layouts.admin>