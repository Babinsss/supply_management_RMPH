<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly Inventory Report - {{ date('F Y') }}</title>
    <style>
        @page { size: letter portrait; margin: 0.5in; }
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; padding: 20px; background: #525659; }
        
        .print-toolbar { position: fixed; top: 0; left: 0; right: 0; height: 50px; background-color: #323639; display: flex; align-items: center; justify-content: center; gap: 10px; z-index: 1000; }
        .btn-action { padding: 10px 20px; font-size: 14px; font-weight: bold; border-radius: 4px; cursor: pointer; text-decoration: none; border: none; }
        .btn-print { background-color: #8ab4f8; color: #202124; }
        .btn-back { background-color: transparent; color: #e8eaed; border: 1px solid #5f6368; }

        .paper-preview { background: #fff; max-width: 8.5in; margin: 60px auto; padding: 0.4in; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        
        .header-text { text-align: center; margin-bottom: 20px; flex-grow: 1; }
        .header-text h3 { margin: 0; font-size: 15px; }
        .header-text p { margin: 2px 0; font-size: 11px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 10px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f8f8f8; text-align: center; font-weight: bold; text-transform: uppercase; }
        .text-center { text-align: center; }
        
        .signatures { display: flex; justify-content: space-between; margin-top: 50px; }
        .sig-block { width: 40%; }
        .sig-line { border-bottom: 1px solid #000; height: 30px; margin-bottom: 5px; display: flex; align-items: flex-end; justify-content: center; }
        .sig-name { font-weight: bold; text-transform: uppercase; font-size: 12px; }

        @media print { 
            body { background: #fff; padding: 0; margin: 0; } 
            .print-toolbar { display: none; } 
            .paper-preview { box-shadow: none; margin: 0; padding: 0; width: 100%; } 
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <button onclick="window.print()" class="btn-action btn-print">🖨️ Print Report</button>
        <a href="/inventory" class="btn-action btn-back">Return to Inventory</a>
    </div>

    <div class="paper-preview">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px;">
            <img src="{{ asset('images/capiz.jpg') }}" style="height: 55px;">
            <div class="header-text">
                <p>Republic of the Philippines</p>
                <p>Province of Capiz</p>
                <h3>ROXAS MEMORIAL PROVINCIAL HOSPITAL</h3>
                <p>Arnaldo Boulevard, Roxas City</p>
                <h3 style="margin-top: 15px; text-decoration: underline;">MONTHLY INVENTORY REPORT</h3>
                <p>As of {{ date('F d, Y') }}</p>
            </div>
            <img src="{{ asset('images/rmph.jpg') }}" style="height: 45px;">
        </div>

        <table>
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="40%">Item Description</th>
                    <th width="15%">Category</th>
                    <th width="10%">Unit</th>
                    <th width="15%">Available Stock</th>
                    <th width="15%">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($supplies as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->name }}</strong>
                        @if($item->description)
                            <br><span style="color: #444; font-size: 9px;">{{ $item->description }}</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->category ?? 'N/A' }}</td>
                    <td class="text-center">{{ $item->unit }}</td>
                    <td class="text-center" style="font-weight: bold; font-size: 12px;">{{ $item->quantity }}</td>
                    <td class="text-center" style="font-weight: bold; color: {{ $item->quantity <= $item->reorder_level ? 'red' : 'black' }};">
                        {{ $item->quantity <= $item->reorder_level ? 'LOW STOCK' : 'NORMAL' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="signatures">
            <div class="sig-block">
                <div>Prepared by:</div>
                <div class="sig-line">
                    {{-- Automatically prints the name of the ICT staff logged in --}}
                    <span class="sig-name">{{ Auth::user()->name }}</span>
                </div>
                <div class="text-center" style="font-size: 10px;">ICT / Supply Section</div>
            </div>
            
            <div class="sig-block">
                <div>Noted by:</div>
                <div class="sig-line">
                    <span class="sig-name">JHOANNA Q. CRUZ-AM</span>
                </div>
                <div class="text-center" style="font-size: 10px;">Quality Management Office</div>
            </div>
        </div>
    </div>

    
</body>
</html>