<x-layouts.admin title="Audit Trail | Superadmin Control">

    <div class="d-flex justify-content-between align-items-center mb-4 bento-card py-3">
        <h5 class="fw-bolder mb-0 text-dark">
            <i class="bi bi-journal-text text-danger me-2"></i> System Activity Logs
        </h5>
        
        <div class="text-muted small">
            <i class="bi bi-info-circle me-1"></i> Logs are read-only and cannot be altered or deleted.
        </div>
    </div>

    <div class="bento-card mb-4">
        <div class="table-responsive">
            <table class="table table-clean mb-0">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>User (Staff)</th>
                        <th>Action Performed</th>
                        <th>Activity Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-nowrap">
                                <span class="fw-bold text-dark">{{ $log->created_at->format('M d, Y') }}</span><br>
                                <span class="text-muted small">{{ $log->created_at->format('h:i A') }}</span>
                            </td>
                            <td>
                                @if($log->user)
                                    <div class="fw-bold text-dark fs-6">{{ $log->user->name }}</div>
                                    <div class="text-muted small">{{ $log->user->role }}</div>
                                @else
                                    <span class="text-muted fst-italic">System / Deleted User</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-3 py-1">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="text-muted fw-medium" style="max-width: 300px;">
                                {{ $log->details }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted-soft fw-medium">
                                No activity logged yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $logs->links('pagination::bootstrap-5') }}
        </div>
    </div>

</x-layouts.admin>