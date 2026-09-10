<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>For Approval RIS (For Approval)</title>
    <style>
        @page { size: 8.5in 11in; margin: 0.5in; }
        body { font-family: Arial, sans-serif; color: #000; font-size: 13px; line-height: 1.4; margin: 0; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 20px; position: relative; }
        .logo-left { position: absolute; left: 0; top: 0; height: 70px; }
        .logo-right { position: absolute; right: 0; top: 0; height: 70px; }
        .title h2 { margin: 0; font-size: 18px; }
        .title h3 { margin: 5px 0 0; font-size: 16px; text-decoration: underline; }
        .title p { margin: 5px 0 0; font-weight: bold; font-size: 14px; background: #f0f0f0; display: inline-block; padding: 3px 10px; border: 1px solid #000; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 5px; vertical-align: bottom; }
        .item-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .item-table th, .item-table td { border: 1px solid #000; padding: 8px; text-align: center; }
        .item-table th { background-color: #f8f8f8; }
        .signatures { width: 100%; border-collapse: collapse; margin-top: 50px; }
        .signatures td { width: 33%; text-align: center; padding: 0 15px; }
        .sig-line { border-bottom: 1px solid #000; height: 40px; margin-bottom: 5px; display: flex; align-items: flex-end; justify-content: center; font-weight: bold; font-size: 12px; text-transform: uppercase; }
        @media print { body { padding: 0; } }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <img src="{{ asset('images/capiz.jpg') }}" class="logo-left" alt="Capiz">
        <div class="title">
            <h2>ROXAS MEMORIAL PROVINCIAL HOSPITAL</h2>
            <h3>PURCHASE REQUESTION FORM (PRF)</h3>
            <p>FOR PURCHASE - APPROVAL</p>
        </div>
        <img src="{{ asset('images/rmph.jpg') }}" class="logo-right" alt="RMPH">
    </div>

    <table class="info-table">
        <tr>
            <td style="width: 12%;"><strong>Department:</strong></td>
            <td style="border-bottom: 1px solid #000; width: 45%;">{{ strtoupper($data['department_name']) }}</td>
            <td style="width: 8%; text-align: right; padding-right: 10px;"><strong>Date:</strong></td>
            <td style="border-bottom: 1px solid #000; width: 35%;">{{ \Carbon\Carbon::now()->format('F d, Y') }}</td>
        </tr>
    </table>

    <table class="item-table">
        <thead>
            <tr>
                <th style="width: 15%;">Quantity</th>
                <th style="width: 45%;">Item Description</th>
                <th style="width: 20%;">Unit Cost<br><span style="font-weight: normal; font-size: 10px;">(For Supply Use)</span></th>
                <th style="width: 20%;">Total Cost<br><span style="font-weight: normal; font-size: 10px;">(For Supply Use)</span></th>
            </tr>
        </thead>
        <tbody>
            {{-- Loop through every item requested --}}
            @foreach($data['item_name'] as $index => $itemName)
                @php 
                    $qty = $data['quantity'][$index];
                @endphp
                <tr>
                    <td style="font-weight: bold; font-size: 14px;">{{ $qty }}</td>
                    <td style="text-align: left; padding-left: 15px; font-weight: bold;">{{ strtoupper($itemName) }}</td>
                    <td></td> {{-- Blank for handwritten input --}}
                    <td></td> {{-- Blank for handwritten input --}}
                </tr>
            @endforeach

            {{-- Grand Total Row --}}
            <tr>
                <td colspan="3" style="text-align: right; font-weight: bold; padding-right: 15px;">GRAND TOTAL:</td>
                <td></td> {{-- Blank for handwritten input --}}
            </tr>

            <tr>
                <td colspan="4" style="text-align: left; padding: 15px; height: 60px; vertical-align: top;">
                    <strong>Purpose / Remarks:</strong><br>
                    {{ $data['purpose'] }}
                </td>
            </tr>
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td>
                <div class="sig-line">{{ strtoupper($data['requested_by']) }}</div>
                <div>Requested By (Dept. Head)</div>
            </td>
            <td>
                <div class="sig-line">JHOANNA Q. CRUZ-AM</div>
                <div>Acting Hospital Administrator</div>
            </td>
            <td>
                <div class="sig-line">LADY ORTALEZ LUCES</div>
                <div>Supply Officer</div>
            </td>
        </tr>
    </table>

</body>
</html>