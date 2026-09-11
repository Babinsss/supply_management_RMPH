<x-layouts.admin title="Activity Logs | Superadmin">

    <div class="d-flex justify-content-between align-items-center mb-4 bento-card py-3">
        <div>
            <h5 class="fw-bolder mb-0 text-dark">
                <i class="bi bi-git text-primary me-2"></i> System Audit Trail
            </h5>
            <p class="text-muted small mb-0 mt-1">Track administrative commits, data adjustments, and system events.</p>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <span class="spinner-grow spinner-grow-sm text-success" role="status" aria-hidden="true"></span>
            <span class="text-muted small fw-bold text-uppercase">Live Monitoring Active</span>
        </div>
    </div>

    <div class="bento-card mb-5 p-4 p-md-5">
        
        {{-- GITHUB-STYLE TIMELINE --}}
        <div class="position-relative ms-2 ms-md-4 border-start border-2 border-secondary-subtle pb-4">
            
            @forelse($logs as $log)
                @php
                    // SMART COLOR CODING LOGIC
                    $desc = strtolower($log->description);
                    $type = strtolower($log->action_type);
                    
                    if (str_contains($desc, 'added') || str_contains($desc, 'created') || str_contains($desc, 'approved') || str_contains($desc, 'up')) {
                        $color = 'success';
                        $icon = 'bi-plus-circle-fill';
                    } elseif (str_contains($desc, 'deleted') || str_contains($desc, 'removed') || str_contains($desc, 'denied') || str_contains($desc, 'down')) {
                        $color = 'danger';
                        $icon = 'bi-trash-fill';
                    } elseif (str_contains($desc, 'updated') || str_contains($desc, 'adjusted') || str_contains($desc, 'modified')) {
                        $color = 'warning';
                        $icon = 'bi-pencil-fill';
                    } else {
                        $color = 'primary';
                        $icon = 'bi-info-circle-fill';
                    }
                @endphp
                
                <div class="position-relative mb-4 ps-4 ps-md-5">
                    {{-- The Timeline Node / Icon --}}
                    <span class="position-absolute top-0 start-0 translate-middle p-1 bg-body text-{{ $color }} rounded-circle" style="font-size: 1.25rem;">
                        <i class="bi {{ $icon }}"></i>
                    </span>
                    
                    {{-- The Commit Card --}}
                    <div class="card border border-secondary-subtle shadow-sm rounded-4 bg-body-tertiary">
                        <div class="card-body p-3 p-md-4">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    @if($log->user)
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bolder" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                            {{ strtoupper(substr($log->user->name, 0, 2)) }}
                                        </div>
                                        <span class="fw-bolder text-dark">{{ $log->user->name }}</span>
                                    @else
                                        <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <i class="bi bi-robot"></i>
                                        </div>
                                        <span class="fw-bolder text-muted fst-italic">System / Deleted User</span>
                                    @endif
                                    <span class="text-muted-soft small mx-1">•</span>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle rounded-pill px-2 py-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                                        {{ strtoupper($log->action_type) }}
                                    </span>
                                </div>
                                <div class="text-end text-muted-soft small">
                                    <div class="fw-bold">{{ $log->created_at->format('M d, Y') }}</div>
                                    <div style="font-size: 0.75rem;">{{ $log->created_at->format('h:i A') }}</div>
                                </div>
                            </div>
                            
                            <div class="mt-2 ms-md-5">
                                <p class="mb-0 text-body" style="font-family: monospace; font-size: 0.9rem;">
                                    <span class="fw-bolder text-{{ $color }} me-2">➔</span>
                                    {{ $log->description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted-soft fw-medium ps-4">
                    <i class="bi bi-shield-check fs-1 d-block mb-3 opacity-50"></i>
                    No system activity recorded yet.
                </div>
            @endforelse
            
        </div>

        {{-- Pagination Links --}}
        <div class="mt-5 d-flex justify-content-center">
            {{ $logs->links('pagination::bootstrap-4') }}
        </div>
    </div>

    {{-- Auto-Refresh Script for Live Monitoring --}}
    <x-slot name="scripts">
        <script>
            // Automatically refresh the page every 60 seconds to monitor live commits
            setTimeout(function() {
                window.location.reload(1);
            }, 60000);
        </script>
    </x-slot>

</x-layouts.admin>