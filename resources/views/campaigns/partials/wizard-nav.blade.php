@php
    $steps = [
        1 => ['Goal', 'campaigns.wizard.goal.edit'],
        2 => ['Assets', 'campaigns.wizard.assets.edit'],
        3 => ['Advertisement', 'campaigns.wizard.creative.edit'],
        4 => ['Audience', 'campaigns.wizard.audience.edit'],
        5 => ['Budget & review', 'campaigns.wizard.budget.edit'],
    ];
@endphp
<div class="d-flex gap-2 overflow-auto pb-2 mb-4" aria-label="Advertisement wizard progress">
    @foreach ($steps as $number => [$label, $routeName])
        @if ($campaign && $campaign->current_step >= $number)
            <a href="{{ route($routeName, $campaign) }}" class="wizard-step {{ ($activeStep ?? 1) === $number ? 'active' : '' }}">
                <span>{{ $number }}</span>{{ $label }}
            </a>
        @else
            <span class="wizard-step disabled"><span>{{ $number }}</span>{{ $label }}</span>
        @endif
    @endforeach
</div>
