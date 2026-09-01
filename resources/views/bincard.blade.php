<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bin Card - {{ $item->name }}</title>
    <style>
        /* Forces standard Long Bond cut in half vertically (4.25in x 13in) */
        @page { size: 4.25in 13in; margin: 0.25in; }
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; padding: 0; color: #000; }
        
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .header-table td { border: 1px solid #000; text-align: center; padding: 4px; }
        
        .title-cell { font-weight: bold; font-size: 14px; }
        .doc-no-cell { font-size: 9px; }
        .rev-cell { font-weight: bold; font-size: 14px; }
        
        .item-name { font-size: 12px; font-weight: bold; margin-bottom: 5px; text-transform: uppercase; }
        
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { 
            border: 1px solid #000; 
            padding: 5px 3px; 
            text-align: center; 
            font-size: 11px; 
            height: 20px; 
        }
        .data-table th { font-weight: bold; }
        .dept-col { text-align: left !important; width: 45%; }
        
        /* Auto-hide browser print UI */
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td rowspan="2" style="width: 15%;">
                <img src="{{ asset('images/capiz.jpg') }}" style="height: 35px;" alt="Provincial Logo">
            </td>
            <td class="title-cell" style="width: 55%; border-bottom: none;">Document Title:<br>BIN CARD</td>
            <td style="width: 15%; font-size: 10px; border-bottom: none;">Rev No</td>
            <td rowspan="2" style="width: 15%;">
                <img src="{{ asset('images/rmph.jpg') }}" style="height: 35px;" alt="PhilHealth">
            </td>
        </tr>
        <tr>
            <td class="doc-no-cell" style="border-top: none;">Document No: RMH-PPS-F02</td>
            <td class="rev-cell" style="border-top: none;">00</td>
        </tr>
    </table>

    <div class="item-name">ITEM: <u>{{ $item->name }} {{ $item->description ? ' - ' . $item->description : '' }}</u></div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;">DATE</th>
                <th class="dept-col">DEPARTMENT/<br>SUPPLIER</th>
                <th style="width: 13%;">DELIVERY<br>QTY</th>
                <th style="width: 12%;">QTY<br>ISSUED</th>
                <th style="width: 15%;">STOCK<br>BALANCE</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $tx)
            <tr>
                <td>{{ \Carbon\Carbon::parse($tx['date'])->format('m-d-y') }}</td>
                <td class="dept-col">{{ $tx['department'] }}</td>
                <td>{{ $tx['delivery_qty'] }}</td>
                <td>{{ $tx['qty_issued'] }}</td>
                <td>{{ $tx['balance'] }}</td>
            </tr>
            @endforeach
            
            {{-- Print empty rows to fill the card up to 35 total lines --}}
            @for($i = count($transactions); $i < 35; $i++)
            <tr>
                <td></td>
                <td class="dept-col"></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            @endfor
        </tbody>
    </table>

    <script>
        window.onload = function() { window.print(); }
    </script>
</body>
</html>