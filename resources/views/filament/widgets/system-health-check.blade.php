<x-filament::section>
    <x-slot name="heading">
        System Health & Constraint Validation
    </x-slot>

    <div class="overflow-x-auto w-full">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-2">Constraint Name</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Details</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($results as $result)
                <tr>
                    <td class="px-4 py-2 font-medium">{{ $result['name'] }}</td>
                    <td class="px-4 py-2">
                        @if($result['status'] === 'success')
                            <span style="color: green; font-weight: bold;">PASS</span>
                        @else
                            <span style="color: red; font-weight: bold;">FAIL</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-gray-500">{{ $result['message'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament::section>
