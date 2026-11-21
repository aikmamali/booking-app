@props(['message' => null])

@php($text = $message ?? session('status'))
@if ($text)
    <p role="status" class="mb-4 rounded-md border border-teal-200 bg-teal-50 px-3 py-2 text-sm text-teal-900">{{ $text }}</p>
@endif
