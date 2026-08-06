<x-layouts.admin title="Activity Logs | Superadmin">

    <div class="d-flex justify-content-between align-items-center mb-4 bento-card py-3">
        <div>
            <h5 class="fw-bolder mb-0 text-dark">
                <i class="bi bi-journal-text text-primary me-2"></i> System Activity Logs
            </h5>
            <p class="text-muted small mb-0 mt-1">Track administrative actions and system changes.</p>
        </div>
    </div>

    <div class="bento-card">
        <div class="table-responsive">
            <table class="table table-clean align-middle mb-0">
                <thead class="bg-white">
                    <tr>
                        <th style="width: 20%;">Date & Time</th>
                        <th style="width: 20%;">User</th>
                        <th style="width: 20%;">Action Category</th>
                        <th style="width: 40%;">Description</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>
                                <div class="fw-bold text-dark" style="font-size: 0.85rem;">{{ $log->created_at->format('M d, Y') }}</div>
                                <div class="text-muted small">{{ $log->created_at->format('h:i A') }}</div>
                            </td>
                            <td>
                                @if($log->user)
                                    <span class="fw-bold text-primary" style="font-size: 0.9rem;">
                                        <i class="bi bi-person-circle me-1"></i> {{ $log->user->name }}
                                    </span>
                                @else
                                    <span class="text-muted fst-italic">System / Deleted User</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-3 py-1">
                                    {{ $log->action_type }}
                                </span>
                            </td>
                            <td class="text-dark" style="font-size: 0.9rem;">
                                {{ $log->description }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-clipboard-x fs-1 d-block mb-3 text-muted opacity-50"></i>
                                No activity logs recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination Links --}}
        <div class="mt-4 d-flex justify-content-center">
            {{ $logs->links('pagination::bootstrap-4') }}
        </div>
    </div>

</x-layouts.admin>