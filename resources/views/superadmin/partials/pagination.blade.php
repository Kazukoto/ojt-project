@if($users->hasPages())
    <div class="pagination">
        @if ($users->onFirstPage())
            <span class="disabled">««</span>
            <span class="disabled">«</span>
        @else
            <a href="{{ $users->url(1) }}">««</a>
            <a href="{{ $users->previousPageUrl() }}">«</a>
        @endif

        @php
            $currentPage = $users->currentPage();
            $lastPage    = $users->lastPage();
            $start       = max(1, $currentPage - 2);
            $end         = min($lastPage, $currentPage + 2);
        @endphp

        @for ($page = $start; $page <= $end; $page++)
            @if ($page == $currentPage)
                <span class="active">{{ $page }}</span>
            @else
                <a href="{{ $users->url($page) }}">{{ $page }}</a>
            @endif
        @endfor

        @if($end < $lastPage)
            <span class="disabled">...</span>
        @endif

        @if ($users->hasMorePages())
            <a href="{{ $users->nextPageUrl() }}">»</a>
            <a href="{{ $users->url($users->lastPage()) }}">»»</a>
        @else
            <span class="disabled">»</span>
            <span class="disabled">»»</span>
        @endif
    </div>
@endif