@if (session('status'))
    <div class="card card-style bg-green-light mb-3">
        <div class="content py-2">
            <p class="mb-0 font-600 color-black">{{ session('status') }}</p>
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="card card-style bg-red-light mb-3">
        <div class="content py-2">
            <p class="mb-2 font-700 color-black">Please review highlighted fields.</p>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li class="font-12 color-black">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
