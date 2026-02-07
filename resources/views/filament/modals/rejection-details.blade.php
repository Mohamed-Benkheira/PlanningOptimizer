<div class="space-y-4">
    @if ($dept_reason)
        <div>
            <h3 class="text-lg font-semibold text-danger-600">Department Head Rejection</h3>
            <p class="mt-2 text-sm text-gray-700">{{ $dept_reason }}</p>
        </div>
    @endif

    @if ($dean_reason)
        <div>
            <h3 class="text-lg font-semibold text-danger-600">Dean Rejection</h3>
            <p class="mt-2 text-sm text-gray-700">{{ $dean_reason }}</p>
        </div>
    @endif

    @if (!$dept_reason && !$dean_reason)
        <p class="text-sm text-gray-500">No rejection reason available.</p>
    @endif
</div>
