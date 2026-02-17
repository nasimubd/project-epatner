@foreach($transactions as $transaction)
<tr>
    <td class="px-6 py-4 whitespace-nowrap">
        <input type="checkbox" name="invoice_ids[]" value="{{ $transaction->id }}" class="invoice-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        {{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d F') }}
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        @php
        $customerName = 'Walk-in Customer';
        if(isset($transaction->ledger_id)) {
        $ledger = \App\Models\Ledger::find($transaction->ledger_id);
        if($ledger) {
        $customerName = $ledger->name;
        }
        }
        @endphp
        {{ $customerName }}
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        ৳{{ number_format($transaction->grand_total ?? $transaction->amount, 2) }}
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <a href="{{ route('admin.invoices.print', $transaction->id) }}" target="_blank" class="text-blue-600 hover:text-blue-900">
            Print
        </a>
    </td>
</tr>
@endforeach