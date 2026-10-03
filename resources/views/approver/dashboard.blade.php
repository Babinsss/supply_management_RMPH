<x-layouts.approver title="Dashboard | QMO Department">
    
    {{-- Top Stat Cards --}}
    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="bento-card bg-primary text-white d-flex justify-content-between align-items-center h-100 shadow">
                <div>
                    <p class="text-white-50 fw-bold mb-1 text-uppercase small">Awaiting Issuance</p>
                    <h1 class="fw-bolder mb-0 display-4" id="navPendingCount">{{ $pending_count }}</h1>
                </div>
                <i class="bi bi-bell-fill display-3 text-white-50"></i>
            </div>
        </div>
        <div class="col-md-6">
            <div class="bento-card d-flex justify-content-between align-items-center h-100 shadow-sm border border-secondary-subtle">
                <div>
                    <p class="text-muted-soft fw-bold mb-1 text-uppercase small">Total Requisitions Monitored</p>
                    <h1 class="fw-bolder mb-0 display-4 text-dark">{{ is_object($requests) && method_exists($requests, 'total') ? $requests->total() : count($requests) }}</h1>
                </div>
                <i class="bi bi-check2-all display-3 text-primary opacity-25"></i>
            </div>
        </div>
    </div>

    <!-- NEW: TOP REQUESTED ITEMS ANALYTICS (QMO METRICS) -->
    <div class="bento-card mb-5 bg-body border border-secondary-subtle shadow-sm">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3 pb-3 border-bottom border-secondary-subtle">
            <div>
                <h5 class="fw-bolder mb-0 text-dark">
                    <i class="bi bi-bar-chart-fill text-primary me-2"></i> Most Requested Items
                </h5>
                <p class="text-muted small mb-0 mt-1">Highest requisition volume approved by Supply Section</p>
            </div>

            <div class="d-flex align-items-center gap-3 flex-wrap">
                <!-- DEPARTMENT FILTER DROPDOWN -->
                <select id="chartDeptFilter" class="form-select form-select-sm input-modern shadow-sm" style="min-width: 200px; max-width: 250px;" onchange="updateChart()">
                    <option value="All Departments" selected>All Departments</option>
                    @if(isset($activeDepartments))
                        @foreach($activeDepartments as $dept)
                            <option value="{{ $dept }}">{{ $dept }}</option>
                        @endforeach
                    @endif
                </select>

                <!-- TIMEFRAME SELECTOR PILLS -->
                <div class="btn-group p-1 bg-body-tertiary rounded-4 border border-secondary-subtle shadow-sm" role="group">
                    <button type="button" class="btn btn-sm btn-primary text-white rounded-3 fw-bold px-3 active" id="btnWeek" onclick="switchTopTimeframe('week')">
                        7 Days
                    </button>
                    <button type="button" class="btn btn-sm btn-light rounded-3 fw-bold px-3" id="btnMonth" onclick="switchTopTimeframe('month')">
                        Month
                    </button>
                    <button type="button" class="btn btn-sm btn-light rounded-3 fw-bold px-3" id="btnYear" onclick="switchTopTimeframe('year')">
                        Year
                    </button>
                </div>
            </div>
        </div>

        <!-- CHART RENDER CONTAINER -->
        <div id="topRequestedChart" style="min-height: 280px;"></div>
    </div>

    {{-- The Audit Grid Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bolder mb-0 text-dark"><i class="bi bi-ui-checks-grid text-primary me-2"></i> Quality Management Queue</h5>
        
        {{-- Search Bar --}}
        <div class="input-group shadow-sm" style="max-width: 350px;">
            <span class="input-group-text bg-white border-end-0 rounded-start-4"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="requestSearchInput" class="form-control input-modern border-start-0 rounded-end-4 pl-0 bg-white" placeholder="Search department, status..." onkeyup="filterRequests()">
        </div>
    </div>

    {{-- BENTO GRID (View Only) --}}
    <div class="row g-4 mb-5" id="requestGrid">
        @forelse($requests as $batch)
            @php 
                $batchDate = $batch['created_at'] ?? $batch['items'][0]->created_at; 
                
                // SMART WARNINGS LOGIC (Still great for QMO Audits!)
                $hasCriticalStock = false;
                $highVolume = false;
                foreach($batch['items'] as $req) {
                    if($req->supply && $req->supply->quantity <= $req->supply->reorder_level) $hasCriticalStock = true;
                    if($req->quantity >= 20) $highVolume = true;
                }
            @endphp
            
            <div class="col-md-6 col-xl-4 request-card">
                <div class="bento-card h-100 d-flex flex-column border shadow-sm position-relative {{ $batch['status'] == 'Pending' ? 'border-primary border-opacity-50' : 'border-secondary-subtle' }}">
                    
                    {{-- Status Badge --}}
                    <div class="position-absolute top-0 end-0 m-3">
                        @if($batch['status'] == 'Pending')
                            <span class="badge bg-warning bg-opacity-25 text-dark rounded-pill px-3 py-2 border border-warning shadow-sm"><i class="bi bi-hourglass-split me-1"></i> Pending</span>
                        @elseif($batch['status'] == 'Approved')
                            <span class="badge bg-success bg-opacity-25 text-success rounded-pill px-3 py-2"><i class="bi bi-check-circle-fill me-1"></i> Issued</span>
                        @elseif($batch['status'] == 'Denied')
                            <span class="badge bg-danger bg-opacity-25 text-danger rounded-pill px-3 py-2"><i class="bi bi-x-circle-fill me-1"></i> Cancelled</span>
                        @endif
                    </div>

                    <div class="mb-3">
                        <div class="text-muted small fw-bold text-uppercase"><i class="bi bi-calendar-event me-1"></i> {{ \Carbon\Carbon::parse($batchDate)->timezone('Asia/Manila')->format('M d, Y - h:i A') }}</div>
                        <h5 class="fw-bolder text-dark mt-2 mb-0 text-truncate" title="{{ $batch['department_name'] }}">{{ $batch['department_name'] }}</h5>
                        <div class="text-muted-soft small"><i class="bi bi-person me-1"></i> {{ $batch['requested_by'] }}</div>
                    </div>

                    {{-- Smart Warning Badges --}}
                    @if($batch['status'] == 'Pending')
                        <div class="d-flex gap-2 mb-3">
                            @if($hasCriticalStock)
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25"><i class="bi bi-exclamation-triangle-fill me-1"></i> Critical Stock Alert</span>
                            @endif
                            @if($highVolume)
                                <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-50"><i class="bi bi-graph-up-arrow me-1"></i> High Volume</span>
                            @endif
                        </div>
                    @endif

                    <div class="bg-light p-3 rounded-4 mb-4 flex-grow-1 border border-secondary-subtle">
                        <div class="text-muted-soft small fw-bold text-uppercase mb-2">Requested Items ({{ count($batch['items']) }})</div>
                        @foreach(collect($batch['items'])->take(2) as $req)
                            <div class="small fw-medium text-truncate mb-1">
                                <span class="text-primary fw-bolder">{{ $req->quantity }}x</span> {{ $req->supply?->name ?? 'Deleted/Archived Item' }}
                            </div>
                        @endforeach
                        @if(count($batch['items']) > 2)
                            <div class="small text-muted fst-italic mt-2">+ {{ count($batch['items']) - 2 }} more items...</div>
                        @endif
                    </div>

                    {{-- Just a View button now --}}
                    <button class="btn btn-light btn-modern w-100 border text-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#reviewModal-{{ $batch['batch_id'] }}">
                        <i class="bi bi-eye me-2"></i> Inspect Details
                    </button>
                </div>
            </div>

            {{-- SPLIT-SCREEN VIEWER MODAL (No forms, just data) --}}
            <div class="modal fade" id="reviewModal-{{ $batch['batch_id'] }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-xl">
                    <div class="modal-content bento-card p-2 border-0 bg-body">
                        <div class="modal-header border-0 pb-3">
                            <h4 class="fw-bolder text-dark mb-0">Requisition Inspection</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        
                        <div class="modal-body p-0">
                            <div class="row g-0 rounded-4 overflow-hidden border border-secondary-subtle">
                                
                                {{-- Left Pane: Request Details --}}
                                <div class="col-lg-5 bg-white p-4 border-end">
                                    <h6 class="text-muted small fw-bold text-uppercase mb-3">Department Details</h6>
                                    <h4 class="fw-bold text-dark mb-1">{{ $batch['department_name'] }}</h4>
                                    <p class="text-muted mb-4"><i class="bi bi-person me-2"></i>{{ $batch['requested_by'] }}</p>
                                    
                                    <h6 class="text-muted small fw-bold text-uppercase mb-2">Request Timeline</h6>
                                    <p class="text-dark fw-medium mb-4"><i class="bi bi-clock me-2 text-primary"></i>{{ \Carbon\Carbon::parse($batchDate)->timezone('Asia/Manila')->format('F d, Y at h:i A') }}</p>

                                    <h6 class="text-muted small fw-bold text-uppercase mb-2">Purpose / Remarks</h6>
                                    <div class="bg-light p-3 rounded-4 text-dark fst-italic border border-secondary-subtle">
                                        "{{ $batch['items'][0]->purpose ?? 'Standard supply requisition.' }}"
                                    </div>
                                </div>

                                {{-- Right Pane: Live Inventory Impact --}}
                                <div class="col-lg-7 bg-light p-4">
                                    <h6 class="text-muted small fw-bold text-uppercase mb-3">Live Stock Impact</h6>
                                    
                                    <div class="table-responsive" style="max-height: 400px;">
                                        <table class="table table-borderless align-middle">
                                            <thead>
                                                <tr class="border-bottom border-secondary-subtle text-muted small text-uppercase">
                                                    <th>Item</th>
                                                    <th class="text-center">Req</th>
                                                    <th class="text-center">Stock</th>
                                                    <th class="text-end">Impact</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($batch['items'] as $req)
                                                    @php
                                                        // Using null-safe operators in case the supply was deleted
                                                        $supplyQty = $req->supply?->quantity ?? 0;
                                                        $supplyReorder = $req->supply?->reorder_level ?? 0;
                                                        
                                                        $remaining = $supplyQty - $req->quantity;
                                                        $isNegative = $remaining < 0;
                                                        $isLow = $remaining <= $supplyReorder && !$isNegative;
                                                    @endphp
                                                    <tr class="border-bottom border-light">
                                                        <td class="fw-bold text-dark">{{ $req->supply?->name ?? 'Deleted/Archived Item' }}</td>
                                                        <td class="text-center fw-bolder text-primary">{{ $req->quantity }}</td>
                                                        <td class="text-center fw-bold">{{ $supplyQty }}</td>
                                                        <td class="text-end">
                                                            @if($isNegative)
                                                                <span class="badge bg-danger rounded-pill px-3 py-2"><i class="bi bi-x-octagon-fill me-1"></i> Deficit ({{ $remaining }})</span>
                                                            @elseif($isLow)
                                                                <span class="badge bg-warning text-dark rounded-pill px-3 py-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> Critical ({{ $remaining }})</span>
                                                            @else
                                                                <span class="badge bg-success bg-opacity-25 text-success rounded-pill px-3 py-2">Healthy ({{ $remaining }})</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons (Strictly Close) --}}
                        <div class="modal-footer border-0 pt-4 pb-2 justify-content-end">
                            <button type="button" class="btn btn-secondary btn-modern shadow-sm px-4" data-bs-dismiss="modal">
                                <i class="bi bi-x-lg me-1"></i> Close Viewer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted opacity-25 mb-3 d-block"></i>
                <h5 class="text-muted fw-medium">No requisitions in the queue.</h5>
            </div>
        @endforelse
    </div>

    @if(is_object($requests) && method_exists($requests, 'links'))
        <div class="d-flex justify-content-end mt-4">
            {{ $requests->links() }}
        </div>
    @endif

    <x-slot name="scripts">
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        
        <script>
            // Live Server Ping
            let currentCount = parseInt("{{ $pending_count }}") || 0;
            setInterval(() => {
                fetch('/api/pending-count')
                    .then(r => r.json())
                    .then(data => {
                        // Don't reload if they are actively looking at a modal
                        if (data.count !== currentCount && !document.querySelector('.modal.show')) { 
                            location.reload(); 
                        }
                    });
            }, 5000);

            // Bento Grid Search Filter
            function filterRequests() {
                let input = document.getElementById('requestSearchInput').value.toLowerCase();
                let cards = document.querySelectorAll('.request-card');
                
                cards.forEach(card => {
                    let textContent = card.innerText.toLowerCase();
                    if (textContent.includes(input)) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }

            // --- APEX CHARTS LOGIC ---
            const topDatasets = @json($topItemsData ?? ['week' => [], 'month' => [], 'year' => []]);
            let currentTimeframe = 'week';

            const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            const textColor = isDark ? '#94a3b8' : '#64748b';

            // Initial load data
            let initialData = topDatasets.week && topDatasets.week['All Departments'] ? topDatasets.week['All Departments'] : [];
            let defaultCategories = initialData.length ? initialData.map(item => item.name) : ['No requisitions recorded'];
            let defaultSeriesData = initialData.length ? initialData.map(item => item.qty) : [0];

            const chartOptions = {
                chart: {
                    type: 'bar',
                    height: 280,
                    toolbar: { show: false },
                    fontFamily: 'Plus Jakarta Sans, sans-serif'
                },
                plotOptions: {
                    bar: {
                        borderRadius: 8,
                        horizontal: true,
                        barHeight: '55%',
                        distributed: false
                    }
                },
                colors: ['#0d6efd'],
                dataLabels: {
                    enabled: true,
                    formatter: (val) => val > 0 ? val + ' units' : '',
                    style: { fontWeight: 700, colors: ['#ffffff'] }
                },
                series: [{
                    name: 'Total Requested',
                    data: defaultSeriesData
                }],
                xaxis: {
                    categories: defaultCategories,
                    labels: { style: { colors: textColor, fontWeight: 600 } }
                },
                yaxis: {
                    labels: { style: { colors: textColor, fontWeight: 700 }, maxWidth: 200 }
                },
                grid: {
                    borderColor: isDark ? '#334155' : '#e2e8f0',
                    strokeDashArray: 4
                },
                tooltip: {
                    theme: isDark ? 'dark' : 'light',
                    y: { formatter: (val) => val + ' units approved' }
                }
            };

            const topItemsChart = new ApexCharts(document.querySelector("#topRequestedChart"), chartOptions);
            topItemsChart.render();

            function switchTopTimeframe(period) {
                currentTimeframe = period;

                ['btnWeek', 'btnMonth', 'btnYear'].forEach(id => {
                    document.getElementById(id).classList.remove('btn-primary', 'text-white', 'active');
                    document.getElementById(id).classList.add('btn-light');
                });

                const activeBtnMap = { week: 'btnWeek', month: 'btnMonth', year: 'btnYear' };
                const activeBtn = document.getElementById(activeBtnMap[period]);
                activeBtn.classList.remove('btn-light');
                activeBtn.classList.add('btn-primary', 'text-white', 'active');

                updateChart();
            }

            function updateChart() {
                const selectedDept = document.getElementById('chartDeptFilter').value;
                const dataset = topDatasets[currentTimeframe][selectedDept] || [];

                topItemsChart.updateOptions({
                    xaxis: {
                        categories: dataset.length ? dataset.map(item => item.name) : ['No records found']
                    },
                    series: [{
                        name: 'Total Requested',
                        data: dataset.length ? dataset.map(item => item.qty) : [0]
                    }]
                });
            }
        </script>
    </x-slot>
</x-layouts.approver>