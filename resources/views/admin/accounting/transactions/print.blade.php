<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $transaction->transaction_type }} Voucher #{{ $transaction->id }}</title>
    <style>
        @media print {
            @page {
                size: 80mm auto;
                margin: 0mm;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                width: 80mm;
            }
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.3;
            margin: 0;
            padding: 2mm;
            font-size: 9pt;
            width: 76mm;
        }

        .voucher {
            width: 100%;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            padding: 2mm 0;
            border-bottom: 1px dashed #333;
        }

        .company-logo {
            max-height: 15mm;
            max-width: 70mm;
            margin-bottom: 2mm;
        }

        .company-name {
            font-size: 12pt;
            font-weight: bold;
            margin: 0;
        }

        .company-address {
            font-size: 8pt;
            color: #333;
            margin: 1mm 0;
        }

        .voucher-title {
            font-size: 10pt;
            font-weight: bold;
            margin: 2mm 0;
            text-transform: uppercase;
        }

        .voucher-details {
            padding: 2mm 0;
            border-bottom: 1px dashed #333;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1mm;
        }

        .detail-label {
            font-weight: bold;
            font-size: 8pt;
        }

        .detail-value {
            text-align: right;
            font-size: 8pt;
        }

        .transaction-table {
            width: 100%;
            border-collapse: collapse;
            margin: 2mm 0;
            font-size: 8pt;
        }

        .transaction-table th {
            border-bottom: 1px solid #ddd;
            padding: 1mm;
            text-align: left;
            font-weight: bold;
        }

        .transaction-table td {
            border-bottom: 1px dotted #ddd;
            padding: 1mm;
            text-align: left;
        }

        .transaction-table .amount-cell {
            text-align: right;
        }

        .total-row {
            font-weight: bold;
            border-top: 1px solid #333;
        }

        .amount-in-words {
            padding: 2mm 0;
            font-style: italic;
            font-size: 8pt;
            border-bottom: 1px dashed #333;
            word-wrap: break-word;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            padding: 3mm 0;
            margin-top: 3mm;
        }

        .signature-box {
            text-align: center;
            flex: 1;
        }

        .signature-line {
            width: 90%;
            margin: 5mm auto 1mm;
            border-top: 1px solid #333;
        }

        .signature-title {
            font-size: 7pt;
        }

        .footer {
            text-align: center;
            font-size: 7pt;
            margin-top: 2px;
            color: #666;
        }

        @media print {
            .no-print {
                display: none;
            }
        }

        .print-button {
            position: fixed;
            top: 5mm;
            right: 5mm;
            padding: 2mm 4mm;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 1mm;
            cursor: pointer;
            font-size: 10pt;
        }

        .print-button:hover {
            background-color: #45a049;
        }
    </style>
</head>

<body>
    <button onclick="window.print()" class="print-button no-print">Print Voucher</button>
    <div class="voucher">
        <div class="header">
            @php
            $business = null;
            if (isset($transaction) && $transaction->business_id) {
            $business = \App\Models\Business::find($transaction->business_id);
            }
            @endphp

            @if($business)
            <h1 class="company-name">{{ $business->name }}</h1>
            <p class="company-address">{{ $business->address }}</p>
            <p class="company-address">+88{{ $business->contact_number }}</p>
            @else
            <h1 class="company-name">{{ config('app.name', 'Company Name') }}</h1>
            <p class="company-address">{{ config('app.address', 'Company Address') }}</p>
            @endif
            <div class="voucher-title">{{ $transaction->transaction_type }} Voucher</div>
        </div>

        <div class="voucher-details">
            <div class="detail-row">
                <span class="detail-label">Voucher No:</span>
                <span class="detail-value">#{{ $transaction->id }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date:</span>
                <span class="detail-value">{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d/m/Y') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Amount:</span>
                <span class="detail-value">{{ number_format($transaction->amount, 2) }}</span>
            </div>
        </div>

        <table class="transaction-table">
            <thead>
                <tr>
                    <th width="40%">Account</th>
                    <th width="30%" class="amount-cell">Debit</th>
                    <th width="30%" class="amount-cell">Credit</th>
                </tr>
            </thead>
            <tbody>
                @php
                $totalDebit = 0;
                $totalCredit = 0;
                @endphp
                @foreach($transaction->transactionLines as $line)
                @php
                $totalDebit += $line->debit_amount;
                $totalCredit += $line->credit_amount;
                @endphp
                <tr>
                    <td>{{ optional($line->ledger)->name }}</td>
                    <td class="amount-cell">{{ $line->debit_amount ? number_format($line->debit_amount, 2) : '-' }}</td>
                    <td class="amount-cell">{{ $line->credit_amount ? number_format($line->credit_amount, 2) : '-' }}</td>
                </tr>
                @if($line->narration || $transaction->narration)
                <tr>
                    <td colspan="3" style="font-style: italic; font-size: 7pt; padding-top: 0;">
                        {{ $line->narration ?: $transaction->narration }}
                    </td>
                </tr>
                @endif
                @endforeach
                <tr class="total-row">
                    <td>Total</td>
                    <td class="amount-cell">{{ number_format($totalDebit, 2) }}</td>
                    <td class="amount-cell">{{ number_format($totalCredit, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="amount-in-words">
            Amount:
            @php
            function convertNumberToWords($number) {
            $hyphen = '-';
            $conjunction = ' and ';
            $separator = ', ';
            $negative = 'negative ';
            $decimal = ' point ';
            $dictionary = array(
            0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five',
            6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten', 11 => 'eleven',
            12 => 'twelve', 13 => 'thirteen', 14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen',
            17 => 'seventeen', 18 => 'eighteen', 19 => 'nineteen', 20 => 'twenty', 30 => 'thirty',
            40 => 'forty', 50 => 'fifty', 60 => 'sixty', 70 => 'seventy', 80 => 'eighty',
            90 => 'ninety', 100 => 'hundred', 1000 => 'thousand', 1000000 => 'million',
            1000000000 => 'billion', 1000000000000 => 'trillion'
            );

            if (!is_numeric($number)) return false;

            if ($number < 0) return $negative . convertNumberToWords(abs($number));

                $string=$fraction=null;

                if (strpos($number, '.' ) !==false) {
                list($number, $fraction)=explode('.', $number);
                }

                switch (true) {
                case $number < 21:
                $string=$dictionary[$number];
                break;
                case $number < 100:
                $tens=((int)($number / 10)) * 10;
                $units=$number % 10;
                $string=$dictionary[$tens];
                if ($units) $string .=$hyphen . $dictionary[$units];
                break;
                case $number < 1000:
                $hundreds=$number / 100;
                $remainder=$number % 100;
                $string=$dictionary[(int)$hundreds] . ' ' . $dictionary[100];
                if ($remainder) $string .=$conjunction . convertNumberToWords($remainder);
                break;
                default:
                $baseUnit=pow(1000, floor(log($number, 1000)));
                $numBaseUnits=(int)($number / $baseUnit);
                $remainder=$number % $baseUnit;
                $string=convertNumberToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                if ($remainder) {
                $string .=$remainder < 100 ? $conjunction : $separator;
                $string .=convertNumberToWords($remainder);
                }
                break;
                }

                if (null !==$fraction && is_numeric($fraction)) {
                $string .=$decimal;
                $words=array();
                foreach (str_split((string)$fraction) as $number) {
                $words[]=$dictionary[$number];
                }
                $string .=implode(' ', $words);
                    }
                    
                    return $string;
                }
                
                echo ucfirst(convertNumberToWords($transaction->amount)) . ' Only';
                @endphp
                </div>

                <div class="signatures">
                    <div class="signature-box">
                        <div class="signature-line"></div>
                        <p class="signature-title">Prepared By</p>
                    </div>
                    <div class="signature-box">
                        <div class="signature-line"></div>
                        <p class="signature-title">Checked By</p>
                    </div>
                    <div class="signature-box">
                        <div class="signature-line"></div>
                        <p class="signature-title">Approved By</p>
                    </div>
                </div>

                <div class="footer">
                    Generated by <span style="font-weight: bold;">ePATNER</span>
                </div>
        </div>
</body>

</html>