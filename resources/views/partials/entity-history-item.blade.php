<div class="history-item border-start border-3 border-primary ps-3 mb-4">
    <div class="d-flex justify-content-between">
        <div>
            <strong>{{ $thread->actor?->name ?? $thread->actor?->username ?? 'System' }}</strong>
            <span class="badge bg-info ms-2">{{ $thread->action?->value ?? $thread->title }}</span>
        </div>
        <small class="text-muted">{{ $thread->created_at->format('d M, Y • h:i A') }}</small>
    </div>
    
    <h6 class="mt-1 mb-2">{{ $thread->title }}</h6>
    @if($thread->body)
        <p class="mb-2">{{ $thread->body }}</p>
    @endif

    @if($thread->media->isNotEmpty())
        <div class="mt-2">
            @foreach($thread->media as $media)
                <a href="{{ $media->getFullUrl() }}" target="_blank" class="btn btn-sm btn-outline-primary me-1">
                    <i class="la la-download"></i> Attachment
                </a>
            @endforeach
        </div>
    @endif

    @if($thread->children->isNotEmpty())
        <div class="mt-3 ms-4 border-start border-2 ps-3">
            @foreach($thread->children as $child)
                @include('partials.entity-history-item', ['thread' => $child])
            @endforeach
        </div>
    @endif
</div>