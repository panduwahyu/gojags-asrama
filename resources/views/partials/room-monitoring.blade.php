<section class="room-monitoring" data-room-monitoring aria-label="Monitoring kamar">
    @foreach($monitoring as $state => $total)<div class="card"><span class="badge room-status-{{ strtolower($state) }}">{{ $state }}</span><strong>{{ $total }}</strong><span>kamar</span></div>@endforeach
</section>
