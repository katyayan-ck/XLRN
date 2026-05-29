<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="la la-comments"></i> Communication & History</h5>
    </div>
    <div class="card-body p-0">
        @if($threads->isEmpty())
            <div class="p-4 text-center text-muted">No history yet.</div>
        @else
            <div class="history-timeline p-3">
                @foreach($threads as $thread)
                    @include('partials.entity-history-item', ['thread' => $thread])
                @endforeach
            </div>
        @endif
    </div>
</div>