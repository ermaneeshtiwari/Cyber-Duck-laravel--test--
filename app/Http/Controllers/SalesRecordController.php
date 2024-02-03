<?php

namespace App\Http\Controllers;

use App\Models\SalesRecord;
use Illuminate\Http\Request;

class SalesRecordController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request data, if needed
        $validatedData = $request->validate([
            'product_id' => 'required',
            'shipping_cost' => 'required',
            'selling_price' => 'required',
            'quantity' => 'required',
            'unit_cost' =>'required',
            // Add validation rules for other fields
        ]);
        // Create a new instance of YourModel and set its attributes
        $SalesRecordModel = new SalesRecord;
        $SalesRecordModel->product_id = $validatedData['product_id'];
        $SalesRecordModel->quantity = $validatedData['quantity'];
        $SalesRecordModel->unit_cost = number_format($validatedData['unit_cost'], 2, '.', ',');
        $SalesRecordModel->selling_price = number_format($validatedData['selling_price'], 2, '.', ',');
        $SalesRecordModel->shipping_cost = $validatedData['shipping_cost'];
        // Set other fields as needed

        // Save the model to the database
        $SalesRecordModel->save();

        // Optionally, you can redirect the user to a different page
        return redirect('/sales')->with('success', 'Data has been saved successfully.');
    }

}
