@props(['project', 'active', 'subtitle' => null])

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $slot }}
        </h2>
        @if($subtitle)
            <p class="text-sm text-gray-500 mt-1">{{ $subtitle }}</p>
        @endif
    </div>
    @php
        $links = [
            'cost-detail' => ['route' => route('projects.show', $project), 'label' => 'Cost Detail'],
            'executive-summary' => ['route' => route('projects.executive-summary', $project), 'label' => 'Executive Summary'],
            'settings' => ['route' => route('projects.settings', $project), 'label' => 'Settings'],
            'dashboard' => ['route' => route('dashboard'), 'label' => 'Back to Projects'],
        ];
    @endphp

    {{-- Mobile: dropdown --}}
    <div class="sm:hidden">
        <select onchange="window.location.href = this.value"
                class="block w-full rounded-lg border-gray-300 text-sm font-medium text-gray-700 focus:border-gray-500 focus:ring-gray-500">
            @foreach($links as $key => $link)
                <option value="{{ $link['route'] }}" {{ $active === $key ? 'selected' : '' }}>
                    {{ $link['label'] }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- Desktop: buttons --}}
    <div class="hidden sm:flex sm:gap-3">
        @foreach($links as $key => $link)
            <a href="{{ $link['route'] }}"
               class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition
                   {{ $active === $key
                       ? 'bg-gray-800 border border-gray-800 text-white'
                       : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                {{ $link['label'] }}
            </a>
        @endforeach
    </div>
</div>
