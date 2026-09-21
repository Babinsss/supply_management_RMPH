<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Request - Office Supplies</title>
    <style>
        /* Print Specifics for Short Coupon Bond (Letter Size) */
        @media print {
            @page { size: 8.5in 11in; margin: 0.4in; }
            body { margin: 0; padding: 0; background: white !important; }
            .print-btn-container { display: none !important; }
            .document-container { box-shadow: none !important; margin: 0 !important; padding: 0 !important; width: 100% !important; }
        }
        
        /* Screen Preview Styling */
        body { 
            font-family: 'Times New Roman', Times, serif; 
            font-size: 13px; 
            background: #525659;
            display: flex;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }
        .document-container {
            background: white;
            width: 8.5in;
            min-height: 11in; /* Short Bond Paper Height */
            padding: 0.4in;
            box-shadow: 0 0 15px rgba(0,0,0,0.5);
            box-sizing: border-box;
            position: relative;
        }

        /* Table Grid Rules */
        table.pr-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid black; /* Thick outer border */
        }
        table.pr-table th, table.pr-table td {
            border: 1px solid black; /* Standard inner borders */
            padding: 4px 6px;
        }
        
        /* Typography & Alignment */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .font-italic { font-style: italic; }
        
        /* Exact Column Widths */
        .col-1 { width: 8%; }
        .col-2 { width: 8%; }
        .col-3 { width: 44%; }
        .col-4 { width: 10%; }
        .col-5 { width: 12%; }
        .col-6 { width: 18%; }

        /* Force row heights to mimic the form */
        .empty-row td { height: 23px; }
        
        .header-cell {
            padding: 15px 10px !important;
            position: relative;
        }
    </style>
</head>
<body onload="window.print()">
    
    <!-- On-Screen Controls -->
    <div class="print-btn-container" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; font-weight: bold; background: #0d6efd; color: white; border: none; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">🖨️ Print PR</button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; margin-left: 10px; border-radius: 5px; border: 1px solid #ccc; background: white;">Close Tab</button>
    </div>

    <div class="document-container">
        <table class="pr-table">
            
            <!-- ROW 1: HEADER SECTION -->
            <tr>
                <td colspan="6" class="header-cell">
                    <div style="position: absolute; top: 5px; right: 15px; font-style: italic; font-size: 12px;">Appendix 47</div>
                    
                    <!-- Logos (Just place your logo images in the public folder to load them) -->
                    <div style="position: absolute; left: 40px; top: 15px;">
                        <img src="/images/capiz-logo.png" onerror="this.style.display='none'" style="width: 75px; height: 75px; border-radius: 50%;">
                    </div>
                    <div style="position: absolute; right: 40px; top: 15px;">
                        <img src="/images/rmph-logo.png" onerror="this.style.display='none'" style="width: 75px; height: 75px; border-radius: 50%;">
                    </div>

                    <div class="text-center">
                        <div style="font-size: 13px;">Republic of the Philippines</div>
                        <div style="font-size: 15px; font-weight: bold;">PROVINCIAL GOVERNMENT OF CAPIZ</div>
                        <div style="font-size: 13px;">Capitol Building, Roxas City</div>
                        <h2 style="margin: 15px 0 0 0; font-size: 24px; font-weight: bold; letter-spacing: 1px;">PURCHASE REQUEST</h2>
                    </div>
                    
                    <div style="position: absolute; bottom: 5px; right: 100px; font-size: 13px; font-weight: bold;">
                        Fund: <span style="display:inline-block; width: 60px; border-bottom: 1px solid black;"></span>
                    </div>
                </td>
            </tr>

            <!-- ROW 2 & 3: METADATA -->
            <tr>
                <td colspan="2"><span class="font-bold">Department:</span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="font-bold">RMPH</span></td>
                <td colspan="1"><span class="font-bold">P.R. No.:</span></td>
                <td colspan="3"><span class="font-bold">Date:</span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ date('M d, Y') }}</td>
            </tr>
            <tr>
                <td colspan="2"><span class="font-bold">Section:</span></td>
                <td colspan="1"><span class="font-bold">FPP :</span></td>
                <td colspan="3"></td>
            </tr>

            <!-- ROW 4: TABLE HEADERS -->
            <tr class="text-center font-bold">
                <td class="col-1">Item No.</td>
                <td class="col-2">Unit</td>
                <td class="col-3">Item Description</td>
                <td class="col-4">Quantity</td>
                <td class="col-5">Unit Cost</td>
                <td class="col-6">Total Cost</td>
            </tr>

            <!-- ITEM DATA ROWS -->
            @php 
                $total = 0; 
                $maxRows = 23; /* Controls page height - adjust if it spills over */
                $itemCount = count($criticalItems);
            @endphp

            @foreach($criticalItems as $index => $item)
                @php 
                    $suggestedQty = max($item->reorder_level, 1);
                    $lineTotal = $suggestedQty * ($item->unit_price ?? 0);
                    $total += $lineTotal;
                @endphp
                <tr class="empty-row">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $item->unit ?? 'pcs' }}</td>
                    <td style="padding-left: 8px;">
                        {{ $item->name }} 
                        @if($item->description)
                            - {{ $item->description }}
                        @endif
                    </td>
                    <td class="text-center">{{ $suggestedQty }}</td>
                    <td class="text-right">{{ $item->unit_price ? number_format($item->unit_price, 2) : '' }}</td>
                    <td class="text-right">{{ $lineTotal ? number_format($lineTotal, 2) : '' }}</td>
                </tr>
            @endforeach

            <!-- BLANK ROWS TO FILL THE PAGE -->
            @for ($i = $itemCount; $i < $maxRows; $i++)
                <tr class="empty-row">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endfor

            <!-- FOOTER NOTES (Constrained inside Col 3) -->
            <tr class="empty-row">
                <td></td>
                <td></td>
                <td><span class="font-italic">Schedule of Delivery: 15 to 30 days upon receipt of Notice to Proceed (NTP)</span></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr class="empty-row">
                <td></td>
                <td></td>
                <td><span class="font-italic">Place of Delivery: PGSO to RMPH</span></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr class="empty-row">
                <td></td>
                <td></td>
                <td class="text-center"><span class="font-italic">X-X-X-X-X-X-X-X-X-X-X-X</span></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>

            <!-- TOTAL ROW -->
            <tr>
                <td colspan="5" class="text-right font-bold" style="padding-right: 20px;">TOTAL</td>
                <td class="text-right font-bold">
                    <span style="float: left;">₱</span> {{ number_format($total, 2) }} <span style="float: right;">-</span>
                </td>
            </tr>

            <!-- PURPOSE ROW -->
            <tr>
                <td colspan="2"><span class="font-bold">Purpose:</span></td>
                <td colspan="4"><span class="font-bold font-italic" style="text-decoration: underline;">for the use of RMPH</span></td>
            </tr>

            <!-- SIGNATORIES HEADERS -->
            <tr>
                <td colspan="2"></td>
                <td colspan="1" class="text-center font-italic">Requested by:</td>
                <td colspan="3" class="text-center font-italic">Approved by:</td>
            </tr>
            
            <!-- SIGNATURE SPACING -->
            <tr>
                <td colspan="2">Signature :</td>
                <td colspan="1" style="padding: 15px;"></td>
                <td colspan="3" style="padding: 15px;"></td>
            </tr>

            <!-- NAMES -->
            <tr>
                <td colspan="2">Printed Name :</td>
                <td colspan="1" class="text-center font-bold">FLORENCIO O. LUCHING JR., M.D, FPCS</td>
                <td colspan="3" class="text-center font-bold">FREDENIL H. CASTRO</td>
            </tr>

            <!-- DESIGNATIONS -->
            <tr>
                <td colspan="2">Designation :</td>
                <td colspan="1" class="text-center font-italic">Chief of Hospital II</td>
                <td colspan="3" class="text-center font-italic">Provincial Governor</td>
            </tr>
        </table>

        <!-- COPIES NOTE -->
        <div style="font-size: 11px; font-style: italic; margin-top: 3px;">
            PR shall be prepared in four (4) copies
        </div>
    </div>

</body>
</html>