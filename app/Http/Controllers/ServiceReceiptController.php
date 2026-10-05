<?php

namespace App\Http\Controllers;

use App\Models\ServiceReceipt;
use Illuminate\View\View;

class ServiceReceiptController extends Controller
{
    public function show(ServiceReceipt $receipt): View
    {
        $user = auth()->user();
        abort_unless($user && (int) $user->company_id === (int) $receipt->company_id, 404);

        $receipt->load([
            'company',
            'serviceOrder.customer',
            'serviceOrder.vehicle',
            'serviceOrder.items',
        ]);

        return view('receipts.show', ['receipt' => $receipt]);
    }
}
