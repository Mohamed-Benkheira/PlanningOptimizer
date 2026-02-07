{{-- resources/views/layouts/app.blade.php or your main layout --}}

@if ($errors->has('title') || session('alert_message'))
    <div class="fixed top-4 right-4 z-50 max-w-md">
        <div
            class="bg-white border-l-4 @if (session('alert_type') === 'warning') border-yellow-500 @else border-red-500 @endif rounded-lg shadow-lg p-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    @if (session('alert_type') === 'warning')
                        <svg class="h-6 w-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    @else
                        <svg class="h-6 w-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @endif
                </div>

                <div class="ml-3 w-full">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ $errors->first('title') ?? session('alert_title') }}
                    </h3>
                    <div class="mt-2 text-sm text-gray-600 whitespace-pre-line">
                        {{ $errors->first('message') ?? session('alert_message') }}
                    </div>
                    <div class="mt-4">
                        <button onclick="this.parentElement.parentElement.parentElement.parentElement.remove()"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                            Dismiss
                        </button>
                    </div>
                </div>

                <button onclick="this.parentElement.parentElement.remove()"
                    class="ml-4 flex-shrink-0 text-gray-400 hover:text-gray-500">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Auto-dismiss after 10 seconds
        setTimeout(() => {
            document.querySelector('.fixed.top-4')?.remove();
        }, 10000);
    </script>
@endif
