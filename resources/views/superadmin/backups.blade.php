<x-layouts.admin title="Database Backups | Superadmin">
    {{-- Header Banner --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 bento-card py-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h5 class="fw-bolder mb-0 text-dark">
                    <i class="bi bi-database-fill-gear text-primary me-2"></i> Database Backups & Recovery
                </h5>
                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3 py-1 small">
                    <i class="bi bi-shield-lock-fill me-1"></i> Superadmin Only
                </span>
            </div>
            <p class="text-muted small mb-0 mt-1">Automated daily snapshots and on-demand disaster recovery archives for RMPH Supply Hub.</p>
        </div>

        {{-- 1-Click Manual Backup Button --}}
        <form action="{{ route('superadmin.backups.create') }}" method="POST" id="backupForm" onsubmit="handleBackupSubmit(event)">
            @csrf
            <button type="submit" class="btn btn-success btn-modern shadow-sm px-4 py-2 fw-bold text-nowrap" id="btnCreateBackup">
                <i class="bi bi-cloud-arrow-up-fill me-2"></i> Create Backup Now
            </button>
        </form>
    </div>

    {{-- System Status & Stat Bento Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="bento-card p-3 d-flex align-items-center gap-3 border shadow-sm">
                <div class="p-3 rounded-4 {{ $stats['is_protected'] ? 'bg-success bg-opacity-10 text-success' : 'bg-warning bg-opacity-10 text-warning' }} fs-3">
                    <i class="bi {{ $stats['is_protected'] ? 'bi-shield-check' : 'bi-shield-exclamation' }}"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold text-uppercase">Protection Status</div>
                    <div class="fw-bolder fs-6 {{ $stats['is_protected'] ? 'text-success' : 'text-warning' }}">
                        {{ $stats['is_protected'] ? 'Protected (Backed Up)' : 'Backup Recommended' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="bento-card p-3 d-flex align-items-center gap-3 border shadow-sm">
                <div class="p-3 rounded-4 bg-primary bg-opacity-10 text-primary fs-3">
                    <i class="bi bi-archive-fill"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold text-uppercase">Total Backups</div>
                    <div class="fw-bolder fs-4 text-dark">{{ $stats['count'] }} <span class="fs-6 fw-normal text-muted">files</span></div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="bento-card p-3 d-flex align-items-center gap-3 border shadow-sm">
                <div class="p-3 rounded-4 bg-info bg-opacity-10 text-info fs-3">
                    <i class="bi bi-hdd-network-fill"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold text-uppercase">Disk Storage</div>
                    <div class="fw-bolder fs-4 text-dark">{{ $stats['total_size'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="bento-card p-3 d-flex align-items-center gap-3 border shadow-sm">
                <div class="p-3 rounded-4 bg-secondary bg-opacity-10 text-secondary fs-3">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold text-uppercase">Latest Snapshot</div>
                    <div class="fw-bold text-dark fs-6">
                        @if($stats['latest'])
                            {{ $stats['latest']['created_at']->format('M d, Y - h:i A') }}
                        @else
                            <span class="text-muted">None</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Backup File Table --}}
    <div class="bento-card mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-list-columns-reverse me-2 text-primary"></i> Available Database Snapshots (Stored in <code>storage/app/backups</code>)
            </h6>
            <div class="text-muted small">
                <i class="bi bi-info-circle me-1"></i> Auto-retention: Older files past 30 days are purged automatically.
            </div>
        </div>

        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
            <table class="table table-clean align-middle mb-0">
                <thead class="sticky-top bg-white border-bottom" style="z-index: 1;">
                    <tr>
                        <th class="text-uppercase small text-muted">Snapshot File</th>
                        <th class="text-uppercase small text-muted">Date & Time</th>
                        <th class="text-uppercase small text-muted">File Size</th>
                        <th class="text-uppercase small text-muted">Age</th>
                        <th class="text-uppercase small text-muted text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backups as $b)
                    <tr class="border-bottom border-light">
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-code-fill fs-4 text-primary"></i>
                                <div>
                                    <div class="fw-bold text-dark font-monospace" style="font-size: 0.9rem;">{{ $b['filename'] }}</div>
                                    <div class="text-muted small">Full MySQL Dump (.sql)</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark">{{ $b['created_at']->format('F d, Y') }}</div>
                            <div class="text-muted-soft small"><i class="bi bi-clock me-1"></i>{{ $b['created_at']->format('h:i:s A') }}</div>
                        </td>
                        <td>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-3 py-1 fw-bold">
                                {{ $b['size_formatted'] }}
                            </span>
                        </td>
                        <td>
                            @if($b['age_days'] == 0)
                                <span class="badge bg-success bg-opacity-25 text-success rounded-pill px-3 py-1">Today</span>
                            @elseif($b['age_days'] == 1)
                                <span class="badge bg-info bg-opacity-25 text-info rounded-pill px-3 py-1">Yesterday</span>
                            @else
                                <span class="badge bg-light text-muted border rounded-pill px-3 py-1">{{ $b['age_days'] }} days ago</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-2">
                                {{-- Download Button --}}
                                <a href="{{ route('superadmin.backups.download', $b['filename']) }}" 
                                   class="btn btn-sm btn-primary btn-modern shadow-sm px-3" 
                                   title="Download to PC / USB">
                                    <i class="bi bi-download me-1"></i> Download
                                </a>

                                {{-- Delete Button --}}
                                <form action="{{ route('superadmin.backups.delete', $b['filename']) }}" 
                                      method="POST" 
                                      onsubmit="return confirmDeleteBackup(event, '{{ $b['filename'] }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-modern" title="Delete Snapshot">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted-soft fw-medium">
                            <i class="bi bi-database-exclamation fs-1 d-block mb-2 opacity-50 text-warning"></i>
                            No database backups found. Click <strong>"Create Backup Now"</strong> above to generate your first backup snapshot.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Automated Scheduler Card --}}
    <div class="bento-card p-4 border shadow-sm">
        <h6 class="fw-bold text-dark mb-2">
            <i class="bi bi-gear-wide-connected text-primary me-2"></i> How Automated Backups Work
        </h6>
        <div class="row g-3 small text-muted">
            <div class="col-md-4">
                <div class="p-3 bg-light rounded-4 h-100 border border-secondary-subtle">
                    <strong class="text-dark d-block mb-1"><i class="bi bi-calendar-check text-success me-1"></i> Nightly Schedule</strong>
                    The automated engine runs daily at <strong>5:30 PM (17:30)</strong> via Windows Task Scheduler (<code>RMPH Database Backup</code>), capturing all transactions, approvals, deliveries, and adjustments.
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-light rounded-4 h-100 border border-secondary-subtle">
                    <strong class="text-dark d-block mb-1"><i class="bi bi-usb-drive text-primary me-1"></i> Off-Site Storage Advice</strong>
                    It is recommended to click <strong>Download</strong> once a week and save a copy to an external encrypted USB flash drive or hospital backup drive.
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-light rounded-4 h-100 border border-secondary-subtle">
                    <strong class="text-dark d-block mb-1"><i class="bi bi-terminal-fill text-dark me-1"></i> Manual Command</strong>
                    Superadmins can also execute backups via command line at any time:
                    <code class="d-block mt-1 bg-white p-1 rounded border">php artisan db:backup</code>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="scripts">
        <script>
            function handleBackupSubmit(e) {
                const btn = document.getElementById('btnCreateBackup');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating Backup...';
            }

            function confirmDeleteBackup(e, filename) {
                return confirm(`Are you sure you want to permanently delete backup "${filename}"? This action cannot be undone.`);
            }
        </script>
    </x-slot>
</x-layouts.admin>

