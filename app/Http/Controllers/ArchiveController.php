<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supply;
use App\Models\DepartmentRequest;
use Carbon\Carbon;

class ArchiveController extends Controller
{
    public function index(Request $request)
    {
        // Default to the previous month, as current month is live in Inventory
        $defaultMonth = Carbon::now()->subMonth();
        
        $year = $request->input('year', $defaultMonth->format('Y'));
        $month = $request->input('month', $defaultMonth->format('m'));
        $monthName = Carbon::createFromDate($year, $month, 1)->format('F');

        // Find supplies that had APPROVED ISSUANCES during this specific month/year
        $activeSupplyIds = DepartmentRequest::where('status', 'Approved')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->pluck('supply_id')
            ->unique();

        $activeSupplies = Supply::withTrashed() // Include soft-deleted items in archives
            ->whereIn('id', $activeSupplyIds)
            ->orderBy('name')
            ->get();

        // Get available years from the database for the dropdown
        $availableYears = DepartmentRequest::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->pluck('year')
            ->toArray();
        
        if (empty($availableYears)) $availableYears = [date('Y')];

        return view('archives.stockcards', compact('activeSupplies', 'year', 'month', 'monthName', 'availableYears'));
    }
}