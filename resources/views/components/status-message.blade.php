@props(['status' => null])

@if ($status ?? session('status'))
    <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
        {{ $status ?? session('status') }}
    </div>
@endif

@if ($errors->any() && $errors->has('user'))
    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        {{ $errors->first('user') }}
    </div>
@endif
