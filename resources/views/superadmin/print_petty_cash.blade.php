<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Petty Cash Voucher - PCV-{{ str_pad($transaction->id, 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page { size: 8.5in 5.5in landscape; margin: 0.25in; } /* Half page size */
        body { font-family: Arial, sans-serif; font-size: 12px; color: #000; background: #525659; margin: 0; padding: 20px; }
        .voucher-container { background: #fff; width: 8in; height: 5in; margin: 0 auto; border: 2px solid #000; box-sizing: border-box; position: relative; padding: 15px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; text-align: center; }
        .logo { height: 60px; width: 60px; object-fit: contain; }
        .title-area h2 { margin: 0; font-size: 16px; }
        .title-area h4 { margin: 5px 0 0; font-size: 14px; text-decoration: underline; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 12px; font-weight: bold; }
        .amount-box { border: 2px solid #000; padding: 8px 15px; font-size: 16px; font-weight: bold; }
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .details-table th, .details-table td { border: 1px solid #000; padding: 8px; text-align: left; }
        .details-table th { background: #f0f0f0; }
        .signatures { display: flex; justify-content: space-between; position: absolute; bottom: 15px; left: 15px; right: 15px; }
        .sig-box { width: 30%; text-align: center; }
        
        /* Updated Alignment and Spacing */
        .sig-line { 
            border-bottom: 1px solid #000; 
            height: 35px; 
            margin-bottom: 5px; 
            display: flex; 
            align-items: flex-end; /* Anchors the text to the bottom/line */
            justify-content: center; /* Centers the text horizontally */
        }
        .sig-name {
            font-size: 13px; /* Increased from 10px */
            font-weight: bold; 
            text-transform: uppercase;
            margin-bottom: 2px; /* Adds a tiny 2px gap so it doesn't touch the line directly */
        }

        @media print { body { background: none; padding: 0; } .voucher-container { box-shadow: none; border: 2px solid #000; } }
    </style>
</head>
<body onload="window.print()">
    <div class="voucher-container">
        <div class="header">
            <img src="{{ asset('images/capiz.jpg') }}" class="logo" alt="Capiz">
            <div class="title-area">
                <h2>ROXAS MEMORIAL PROVINCIAL HOSPITAL</h2>
                <h4>PETTY CASH VOUCHER</h4>
            </div>
            <img src="{{ asset('images/rmph.jpg') }}" class="logo" alt="RMPH">
        </div>

        <div class="info-row">
            <div>Payee / Source: ___________________________</div>
            <div>Date: <u>{{ $transaction->created_at->format('F d, Y') }}</u></div>
            <div>PCV No: <u>{{ str_pad($transaction->id, 5, '0', STR_PAD_LEFT) }}</u></div>
        </div>

        <table class="details-table">
            <thead>
                <tr>
                    <th style="width: 70%;">Particulars / Description</th>
                    <th style="width: 30%; text-align: center;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="height: 60px; vertical-align: top;">
                        {{ $transaction->description }}
                        @if($transaction->supply_id)
                            <br><small><i>*Added to Inventory: {{ $transaction->supply->name }}</i></small>
                        @endif
                        @if($transaction->receipt_reference)
                            <br><small><i>*Ref/OR: {{ $transaction->receipt_reference }}</i></small>
                        @endif
                    </td>
                    <td style="text-align: center; vertical-align: top; font-weight: bold; font-size: 14px;">
                        ₱ {{ number_format($transaction->amount, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="signatures">
            <div class="sig-box">
                <div class="sig-line">
                    <span class="sig-name">{{ $transaction->processor->name ?? '' }}</span>
                </div>
                <div>Prepared By</div>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <div>Checked By</div>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <div>Received / Approved By</div>
            </div>
        </div>
    </div>
</body>
</html>