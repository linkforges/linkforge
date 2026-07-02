@php
    $clickLogs = $clickLogs ?? [];
@endphp

<div class="lf-card mt-5 p-5">
    <h3 class="text-sm font-semibold text-slate-900">Click log</h3>
    
    @if (empty($clickLogs))
        <div class="mt-4 rounded-lg border border-slate-100 bg-slate-50 px-4 py-6 text-center">
            <p class="text-sm text-slate-500">No clicks recorded yet</p>
        </div>
    @else
        {{-- Desktop View (Table) --}}
        <div class="mt-4 hidden overflow-x-auto lg:block">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Time</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">IP Hash</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Country</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Device</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Browser</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Referrer</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($clickLogs as $click)
                        <tr class="hover:bg-slate-50 transition {{ $click['is_duplicate'] ? 'bg-slate-50' : '' }}">
                            <td class="px-4 py-3 text-slate-700 whitespace-nowrap">
                                <span title="{{ $click['created_at']->format('Y-m-d H:i:s') }}">
                                    {{ $click['created_at']->diffForHumans() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600">
                                {{ substr($click['ip_hash'], 0, 8) }}...
                            </td>
                            <td class="px-4 py-3 text-slate-700">
                                {{ $click['country'] ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-slate-700">
                                <span class="inline-flex items-center gap-1">
                                    @switch($click['device'])
                                        @case('mobile')
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="20" x2="12" y2="21"/></svg>
                                            @break
                                        @case('tablet')
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="19"/></svg>
                                            @break
                                        @case('desktop')
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="2" y1="17" x2="22" y2="17"/></svg>
                                            @break
                                        @default
                                            <span>-</span>
                                    @endswitch
                                    <span class="hidden sm:inline">{{ ucfirst($click['device'] ?? 'unknown') }}</span>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-700 truncate" title="{{ $click['browser'] ?? 'Unknown' }}">
                                {{ $click['browser'] ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-slate-700 truncate max-w-xs" title="{{ $click['referer_host'] ?? 'Direct' }}">
                                {{ $click['referer_host'] ?? 'Direct' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($click['is_duplicate'])
                                    <span class="inline-flex items-center gap-1 rounded-full bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-700" title="Duplicate click">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                        <span class="hidden sm:inline">Duplicate</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700" title="Unique click">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        <span class="hidden sm:inline">Unique</span>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile View (Stacked Cards) --}}
        <div class="mt-4 space-y-3 lg:hidden">
            @foreach ($clickLogs as $click)
                <div class="rounded-lg border border-slate-200 p-4 {{ $click['is_duplicate'] ? 'bg-orange-50 border-orange-200' : 'bg-white' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">
                                    {{ $click['created_at']->format('M d, H:i') }}
                                </span>
                                @if ($click['is_duplicate'])
                                    <span class="inline-flex items-center gap-1 rounded-full bg-orange-100 px-2 py-0.5 text-xs font-semibold text-orange-700">
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                        Dup
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        New
                                    </span>
                                @endif
                            </div>
                            
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between items-start gap-2">
                                    <span class="text-slate-500 font-medium">IP:</span>
                                    <span class="font-mono text-xs text-slate-700 text-right" title="{{ $click['ip_hash'] }}">{{ substr($click['ip_hash'], 0, 12) }}...</span>
                                </div>
                                
                                <div class="flex justify-between items-start gap-2">
                                    <span class="text-slate-500 font-medium">Country:</span>
                                    <span class="text-slate-700 text-right">{{ $click['country'] ?? '-' }}</span>
                                </div>
                                
                                <div class="flex justify-between items-start gap-2">
                                    <span class="text-slate-500 font-medium">Device:</span>
                                    <span class="text-slate-700 text-right flex items-center gap-1">
                                        @switch($click['device'])
                                            @case('mobile')
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="20" x2="12" y2="21"/></svg>
                                                @break
                                            @case('tablet')
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="19"/></svg>
                                                @break
                                            @case('desktop')
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="2" y1="17" x2="22" y2="17"/></svg>
                                                @break
                                        @endswitch
                                        {{ ucfirst($click['device'] ?? 'unknown') }}
                                    </span>
                                </div>
                                
                                <div class="flex justify-between items-start gap-2">
                                    <span class="text-slate-500 font-medium">Browser:</span>
                                    <span class="text-slate-700 text-right" title="{{ $click['browser'] ?? 'Unknown' }}">{{ $click['browser'] ?? '-' }}</span>
                                </div>
                                
                                <div class="flex justify-between items-start gap-2">
                                    <span class="text-slate-500 font-medium">Referrer:</span>
                                    <span class="text-slate-700 text-right truncate text-xs" title="{{ $click['referer_host'] ?? 'Direct' }}">{{ $click['referer_host'] ?? 'Direct' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <p class="mt-4 text-xs text-slate-500">Showing the 50 most recent clicks (excluding bots)</p>
    @endif
</div>
