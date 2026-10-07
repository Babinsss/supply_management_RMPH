<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly Inventory Report</title>
    <style>
        /* --- PRINT SETTINGS --- */
        @page { 
            size: letter portrait; 
            margin: 0.5in; 
        }
        
        body { 
            font-family: Arial, sans-serif; 
            font-size: 10px; 
            margin: 0; 
            padding: 0; 
            color: #000;
        }

        /* --- HEADER STYLES --- */
        .header { 
            text-align: center; 
            position: relative; 
            margin-bottom: 15px; 
        }
        .header img.left { 
            position: absolute; 
            left: 0; 
            top: 0; 
            width: 70px; 
            height: auto; 
        }
        .header img.right { 
            position: absolute; 
            right: 0; 
            top: 0; 
            width: 70px; 
            height: auto; 
        }
        .header p { 
            margin: 2px 0; 
            font-size: 11px;
        }
        .header h4 { 
            margin: 4px 0; 
            font-size: 14px; 
            font-weight: bold;
        }
        .report-title { 
            margin-top: 15px; 
            font-weight: bold; 
            text-decoration: underline; 
            font-size: 13px;
        }
        .report-date { 
            font-size: 11px; 
            margin-top: 4px; 
        }
        .thick-line { 
            border-top: 3px solid #000; 
            margin: 15px 0; 
        }

        /* --- TABLE STYLES --- */
        table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        
        /* NEW: Forces the browser to push a row to the next page rather than cutting it in half */
        tr {
            page-break-inside: avoid;
        }
        
        /* Ensures the table header repeats automatically on new pages */
        thead {
            display: table-header-group;
        }

        th, td { 
            border: 1px solid #000; 
            padding: 6px; 
            text-align: center; 
            vertical-align: middle;
        }
        th { 
            font-size: 9px; 
            font-weight: bold; 
            text-transform: uppercase; 
        }
        .text-left { 
            text-align: left; 
        }
        
        /* Status Colors */
        .status-normal { font-weight: bold; color: #000; }
        .status-low { color: red; font-weight: bold; }
    </style>
</head>
<body>

    {{-- Header Section --}}
    <div class="header">
        <img src="{{ asset('images/capiz.jpg') }}" class="left" alt="Capiz Logo">
        <img src="{{ asset('images/rmph.jpg') }}" class="right" alt="RMPH Logo">
        
        <p>Republic of the Philippines</p>
        <p>Province of Capiz</p>
        <h4>ROXAS MEMORIAL PROVINCIAL HOSPITAL</h4>
        <p>Arnaldo Boulevard, Roxas City</p>

        <div class="report-title">SUPPLY INVENTORY REPORT</div>
        
        <div class="report-date">
            {{-- Dynamic Date & Category based on Monthly Report Filter --}}
            @if(isset($reportMonth) && isset($reportYear))
                For the month of {{ $reportMonth }} {{ $reportYear }}<br>
                <span style="font-size: 9px; font-weight: bold;">
                    CATEGORY: {{ $reportCategory == 'ALL' ? 'ALL CATEGORIES' : $reportCategory }}
                </span>
            @else
                As of {{ \Carbon\Carbon::now()->format('F d, Y') }}
            @endif
        </div>
    </div>

    <div class="thick-line"></div>

    {{-- Inventory Table --}}
    <table>
        <thead>
            <tr>
                <th width="4%">#</th>
                <th width="31%" class="text-left">ITEM DESCRIPTION</th>
                <th width="14%">CATEGORY</th>
                <th width="7%">UNIT</th>
                <th width="12%">BEGINNING BALANCE</th>
                <th width="12%">ITEMS ISSUED</th>
                <th width="10%">AVAILABLE STOCK</th>
                <th width="10%">STATUS</th>
            </tr>
        </thead>
        <tbody>
            @forelse($supplies as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                
                {{-- Item Description Column (Stacked) --}}
                <td class="text-left">
                    <strong style="font-size: 11px;">{{ $item->name }}</strong>
                    
                    @if($item->description)
                        <br><span style="font-size: 8px; color: #555; text-transform: uppercase;">{{ $item->description }}</span>
                    @endif
                    
                    {{-- NEW: RIS Number --}}
                    @if($item->ris_number)
                        <br><span style="font-size: 9px; font-weight: bold; color: #222;">RIS: {{ $item->ris_number }}</span>
                    @endif
                    
                    {{-- NEW: Supplier --}}
                    @if($item->supplier)
                        <br><span style="font-size: 9px; font-style: italic; color: #444;">Supplier: {{ $item->supplier }}</span>
                    @endif
                </td>
                
                <td>{{ $item->category ?: 'UNCATEGORIZED' }}</td>
                <td>{{ $item->unit }}</td>

                {{-- NEW: Beginning Balance --}}
                <td style="background-color: #f9f9f9;"><strong>{{ $item->beginning_balance ?? $item->quantity }}</strong></td>
                
                {{-- Items Issued (Calculated by withSum logic in Controller) --}}
                <td><strong>{{ $item->department_requests_sum_quantity ?? 0 }}</strong></td>
                
                {{-- Available Stock --}}
                <td style="font-size: 11px; background-color: #f0fdf4;"><strong>{{ $item->quantity }}</strong></td>
                
                {{-- Dynamic Status Logic --}}
                <td>
                    @if($item->quantity == 0)
                        <span class="status-low">OUT OF<br>STOCK</span>
                    @elseif($item->quantity <= $item->reorder_level)
                        <span class="status-low">LOW<br>STOCK</span>
                    @else
                        <span class="status-normal">NORMAL</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="padding: 20px;">No inventory records found for this period.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Auto-trigger print dialog (Optional, remove if not needed) --}}
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>