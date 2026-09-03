<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>RAPC - {{ $baseItem->supplier ?? 'No Supplier' }}</title>
    <style>
        /* Forces standard Long Bond Landscape (13in x 8.5in) */
        @page { 
            size: 13in 8.5in; 
            margin-top: 0.5in;
            margin-right: 0.5in;
            margin-bottom: 0.5in;
            margin-left: 0.85in; /* Increased to prevent left-side clipping */
        }
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; padding: 0; color: #000; }
        
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; text-transform: uppercase; line-height: 1.5; }
        .header-left, .header-right { width: 35%; }
        .header-center { width: 30%; text-align: center; font-size: 16px; font-weight: bold; padding-top: 10px; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 50px; }
        .data-table th, .data-table td { border: 1px solid #000; padding: 6px; text-align: center; font-size: 11px; }
        .data-table th { font-weight: bold; background-color: #fff; }
        .desc-col { text-align: left !important; width: 30%; }
        
        .signatories { display: flex; justify-content: space-between; padding-top: 20px; }
        .sig-block { width: 45%; text-align: left; }
        .sig-name { font-weight: bold; text-decoration: underline; text-transform: uppercase; margin-top: 40px; }
        .sig-title { font-size: 11px; }
        
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="header-left">
            <div>HOSPITAL: <b>ROXAS MEMORIAL PROVINCIAL HOSPITAL</b></div>
            <div>RIS NUMBER: <b>{{ $risNumbers ?: '_______________' }}</b></div>
            <div>RIS DATE: <b>{{ $risDates ?: '_______________' }}</b></div>
            <div>FOR THE YEAR: <b>{{ date('Y') }}</b></div>
        </div>
        
        <div class="header-center">
            REPORT ON ACTUAL PHYSICAL COUNT
        </div>
        
        <div class="header-right">
            <div>FUND: <b>MOOE - {{ strtoupper($baseItem->category ?? 'OFFICE SUPPLIES EXPENSES') }}</b></div>
            <div>PURCHASE/CONTRACT ORDER: <b>_______________</b></div>
            <div>SUPPLIER: <b>{{ $baseItem->supplier ?? '_______________' }}</b></div>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 5%;">ITEM NO.</th>
                <th colspan="4">PURCHASE</th>
                <th colspan="12">RECAPITULATION (ACTUAL PHYSICAL COUNT)</th>
            </tr>
            <tr>
                <th class="desc-col">DESCRIPTION</th>
                <th style="width: 7%;">UNIT COST</th>
                <th style="width: 5%;">QTY</th>
                <th style="width: 5%;">UNIT</th>
                <th>JAN</th><th>FEB</th><th>MAR</th><th>APR</th><th>MAY</th><th>JUN</th>
                <th>JUL</th><th>AUG</th><th>SEP</th><th>OCT</th><th>NOV</th><th>DEC</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $i)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="desc-col">{{ $i->name }} {{ $i->description ? ' - ' . $i->description : '' }}</td>
                <td>{{ $i->unit_price ? number_format($i->unit_price, 2) : '' }}</td>
                <td>{{ $i->quantity }}</td>
                <td>{{ $i->unit }}</td>
                {{-- Dynamically outputs historical stock balances --}}
                @for($m = 1; $m <= 12; $m++)
                    <td>{{ $i->monthly_balances[$m] ?? '' }}</td>
                @endfor
            </tr>
            @endforeach
            
            {{-- Print empty rows to fill the sheet --}}
            @for($r = count($items); $r < 15; $r++)
            <tr>
                <td></td><td class="desc-col"></td><td></td><td></td><td></td>
                <td></td><td></td><td></td><td></td><td></td><td></td>
                <td></td><td></td><td></td><td></td><td></td><td></td>
            </tr>
            @endfor
        </tbody>
    </table>

    <div class="signatories">
        <div class="sig-block">
            <div>Prepared and Certified Correct by:</div>
            <div class="sig-name">LADY ORTALEZ S. LUCES</div>
            <div class="sig-title">Administrative Aide III</div>
        </div>
        <div class="sig-block" style="text-align: right;">
            <div style="text-align: left; display: inline-block;">
                <div>Noted by:</div>
                <div class="sig-name">FLORENCIO O. LUCHING JR., M.D., FPCS</div>
                <div class="sig-title">Chief of Hospital II</div>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() { window.print(); }
    </script>
</body>
</html>