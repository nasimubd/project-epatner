@forelse($transactions as $trx)
<div class="bg-white shadow-md rounded-lg p-4 border border-gray-100 hover:shadow-lg transition duration-300">
    <div class="flex justify-between items-center mb-2">
        <span class="text-base font-semibold text-blue-600">{{ $trx->ledger->name }}</span>
        <span class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($trx->transaction_date)->format('d M Y') }}</span>
    </div>
    <div class="flex justify-between items-center mb-3">
        <div>
            <p class="text-xl font-bold text-gray-800">৳{{ number_format($trx->grand_total, 2) }}</p>
            <div class="flex items-center mt-1">
                @if($trx->creators->isNotEmpty() && $trx->creators->first()->user)
                @php
                $creator = $trx->creators->first()->user;
                $initials = strtoupper(substr($creator->name, 0, 1));
                // If there's a space in the name, get the first letter of the last name
                if (strpos($creator->name, ' ') !== false) {
                $nameParts = explode(' ', $creator->name);
                $initials .= strtoupper(substr(end($nameParts), 0, 1));
                }
                @endphp
                <span class="w-7 h-7 flex items-center justify-center rounded-full bg-blue-100 text-blue-800 font-medium mr-2 text-sm">
                    {{ $initials }}
                </span>
                <div>
                    <div class="text-sm font-medium text-gray-600">{{ $trx->invoice_id ?? 'N/A' }}</div>
                    @if(isset($trx->contributors) && $trx->contributors->isNotEmpty())
                    <div class="flex flex-wrap text-xs text-gray-400 mt-0.5">
                        @foreach($trx->contributors as $contributor)
                        @php
                        $contInitials = strtoupper(substr($contributor->user->name, 0, 1));
                        if (strpos($contributor->user->name, ' ') !== false) {
                        $contNameParts = explode(' ', $contributor->user->name);
                        $contInitials .= strtoupper(substr(end($contNameParts), 0, 1));
                        }
                        @endphp
                        <span class="inline-flex items-center justify-center bg-gray-100 text-gray-600 rounded-full px-1.5 py-0.5 text-xs mr-1 mb-1">
                            {{ $contInitials }}
                        </span>
                        @endforeach
                    </div>
                    @endif
                </div>
                @else
                <span class="text-sm text-gray-500">System</span>
                @endif
            </div>
        </div>
    </div>
    <div class="grid grid-cols-2 gap-2 mt-2">
        <!-- Row 1 -->
        <div class="flex justify-center">
            <!-- Product Return Icon -->
            <!-- Product Return Icon (Mobile) -->
            <a href="javascript:void(0)"
                class="return-btn flex items-center justify-center p-2.5 rounded-lg {{ $trx->grand_total <= 0 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-amber-50 text-amber-500 hover:bg-amber-100 hover:text-amber-700' }} transition-colors w-full"
                data-id="{{ $trx->id }}"
                {{ $trx->grand_total <= 0 ? 'disabled' : '' }}
                title="{{ $trx->grand_total <= 0 ? 'Returns not available for fully returned transactions' : 'Return Products' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve" fill="currentColor">
                    <g>
                        <path d="M25.8,60.6h-4.5c-0.7,0-1.3,0.6-1.3,1.3v16.1c0,0.7,0.6,1.3,1.3,1.3h2.2c2,0,3.6-1.6,3.6-3.6V62C27.2,61.2,26.5,60.6,25.8,60.6z" />
                        <path d="M79.9,69.4c-0.7-1.6-2-3.3-3.9-3.5c-1-0.1-2,0.3-2.9,0.6c-3.6,1.3-7.2,2.5-10.8,3.8c-2.3,0.8-4.7,1.6-7.2,1.8c-1.7,0.1-3.4,0-5.1,0c-0.9,0-1.7-0.7-1.7-1.7s0.7-1.7,1.7-1.7l9.1,0c1.7,0,3-1.4,3-3s-1.4-3-3-3h-7c-0.3,0-2.2-0.1-3.4-0.6c-1.3-0.6-3-0.7-3-0.6c0,0,0,0-0.1,0H33.4c-1.5,0-2.7,1.2-2.7,2.7v11.3c0,1.3,1,2.4,2.3,2.6c0.1,0,0.2,0,0.3,0c2.3,0,4.6,0.5,6.9,0.9c2.3,0.5,4.5,0.8,6.9,0.8c3,0.1,6.1-0.4,9-1.1c2.9-0.8,5.7-1.9,8.5-2.8c4.8-1.6,9.7-3.3,14.5-4.9C79.7,70.7,80.2,70.2,79.9,69.4z" />
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M58,40.1v15c0,0.5,0.5,0.7,0.9,0.6c2.9-1.7,11.9-6.7,11.9-6.7c1.2-0.7,1.9-1.9,1.9-3.3V32.2c0-0.5-0.5-0.7-0.9-0.6l-13.2,7.4C58.3,39.3,58,39.7,58,40.1" />
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M56.8,36L70,28.6c0.4-0.2,0.4-0.8,0-1c-2.9-1.7-12-6.8-12-6.8c-1.2-0.7-2.6-0.7-3.8,0c0,0-9,5.1-12,6.8c-0.4,0.2-0.4,0.8,0,1L55.6,36C55.9,36.2,56.4,36.2,56.8,36" />
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M53.7,39.1l-13.2-7.4c-0.4-0.2-0.9,0.1-0.9,0.6v13.4c0,1.3,0.7,2.6,1.9,3.3c0,0,9,5.1,11.9,6.7c0.4,0.2,0.9-0.1,0.9-0.6V40.1C54.3,39.7,54.1,39.3,53.7,39.1" />
                    </g>
                </svg>
                <span class="ml-1 text-xs font-medium">Return</span>
            </a>
        </div>

        <div class="flex justify-center">
            <!-- View Icon -->
            <a href="{{ route('admin.inventory.inventory_transactions.show', $trx->id) }}" class="flex items-center justify-center p-2.5 rounded-lg bg-blue-50 text-blue-500 hover:bg-blue-100 hover:text-blue-700 transition-colors w-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" title="View Transaction">
                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                </svg>
                <span class="ml-1 text-xs font-medium">View</span>
            </a>
        </div>

        <!-- Row 2 -->
        <div class="flex justify-center">
            <!-- Payment Collection Icon (Mobile) -->
            <a href="javascript:void(0)"
                class="collection-btn flex items-center justify-center p-2.5 rounded-lg {{ ($trx->payment_method === 'credit' && $trx->grand_total > 0) ? 'bg-green-50 text-green-500 hover:bg-green-100 hover:text-green-700' : 'bg-gray-100 text-gray-400' }} transition-colors w-full"
                data-id="{{ $trx->id }}"
                data-amount="{{ $trx->grand_total }}"
                {{ ($trx->payment_method !== 'credit' || $trx->grand_total <= 0) ? 'disabled' : '' }}>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 {{ ($trx->payment_method !== 'credit' || $trx->grand_total <= 0) ? 'opacity-50' : '' }}" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z" />
                    <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd" />
                </svg>
                <span class="ml-1 text-xs font-medium">Collect</span>
            </a>

        </div>

        <div class="flex justify-center">
            <!-- Share Icon -->
            <button class="flex items-center justify-center p-2.5 rounded-lg bg-indigo-50 text-indigo-500 hover:bg-indigo-100 hover:text-indigo-700 transition-colors w-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" title="Share Transaction">
                    <path d="M15 8a3 3 0 10-2.977-2.63l-4.94 2.47a3 3 0 100 4.319l4.94 2.47a3 3 0 10.895-1.789l-4.94-2.47a3.027 3.027 0 000-.74l4.94-2.47C13.456 7.68 14.19 8 15 8z" />
                </svg>
                <span class="ml-1 text-xs font-medium">Share</span>
            </button>
        </div>

        @role('admin')
        <!-- Delete Icon -->
        <div class="flex justify-center mt-2">
            <button class="delete-btn flex items-center justify-center p-2.5 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 hover:text-red-700 transition-colors w-full"
                data-id="{{ $trx->id }}"
                title="Delete Transaction">
                <!-- ADD DELETE ICON HERE -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
        @endrole
    </div>
</div>
@empty
<div class="bg-white shadow-md rounded-lg p-4 text-center text-gray-500">
    No transactions found.
</div>
@endforelse