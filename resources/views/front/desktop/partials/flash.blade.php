@if (session('status'))
    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
        {{ session('status') }}
    </div>
@endif

@php
    $globalErrorMessages = collect($errors->getBag('default')->getMessages())
        ->except(['product_option_value_id'])
        ->flatten();
@endphp

@if ($globalErrorMessages->isNotEmpty())
    <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        <p class="font-semibold">Please review the highlighted fields.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach ($globalErrorMessages as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
