<div class="mt-5 flex flex-wrap items-center justify-center gap-2.5">
    @foreach ($links as $s)
        @php $p = $s['platform'] ?? 'website'; @endphp
        <a href="{{ $s['url'] }}" target="_blank" rel="noopener nofollow" title="{{ \App\Support\BioSocial::label($p) }}"
           class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/90 shadow-sm ring-1 ring-black/5 transition hover:-translate-y-0.5">
            <img src="{{ \App\Support\BioSocial::iconUrl($p) }}" alt="{{ $p }}" class="h-5 w-5">
        </a>
    @endforeach
</div>
