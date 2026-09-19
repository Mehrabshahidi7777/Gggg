<div class="corner-card listing-card">
    <a class="listing-clickable" href="{{ route('ad.show',$ad->slug) }}" style="text-decoration:none;">

        <div class="listing-media" style="{{ $ad->primaryImage ? '' : 'background:var(--color-blueprint-tint);' }}">
            <span class="listing-badge">{{ $ad->category->name }}</span>

            @if($ad->primaryImage)
                <img
                    src="{{ Storage::url($ad->primaryImage->path) }}"
                    alt="{{ $ad->title }}"
                    style="width:100%;height:100%;object-fit:cover;"
                >
            @else
                <span data-icon="box"></span>
            @endif
        </div>

        <div class="listing-body">
            <h3>{{ $ad->title }}</h3>
            <p style="color:var(--color-steel);font-size:0.85rem;margin:4px 0 0;">{{ $ad->province->name }}، {{ $ad->city->name }}</p>

            <div class="listing-meta">
                <span class="price">{{ $ad->price_formatted }}</span>
            </div>
        </div>

    </a>

    <a class="card-call-btn" href="{{ route('ad.show',$ad->slug) }}">مشاهده آگهی</a>
</div>
