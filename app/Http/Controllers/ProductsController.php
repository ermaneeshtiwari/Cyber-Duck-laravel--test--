<?php

namespace App\Http\Controllers;

use App\Models\Products;
use App\Models\SalesRecord;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    /**
     * Display a listing of the Product.
     */
    public function index()
    {
        $productList = json_decode(Products::get());
        $salesRecord = json_decode(SalesRecord::select('product_name','quantity','unit_cost','selling_price', 'sales_records.created_at')->join('products', 'products.product_id', '=', 'sales_records.product_id')->get());
        return view('coffee_sales', ['productList' => $productList, 'salesRecord' => $salesRecord]);
    }

}
