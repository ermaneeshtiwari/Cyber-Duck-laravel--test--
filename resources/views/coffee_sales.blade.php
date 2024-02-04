<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('New ☕️ Sales') }}
        </h2>
    </x-slot>
    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif
@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form action="/sales" method="post">
                        @csrf
                        <select id="product_id_select" onchange="handleChange()">
                            <option selected>Select Product</option>
                            @foreach($productList as $item)
                                <option value="{{$item->product_id}}" data-margin="{{$item->product_profit_margin}}">{{$item->product_name}}</option>
                            @endforeach
                          </select>
                        <input type="hidden" name="margin" id="margin" value="" />
                        <input type="hidden" name="product_id" id="product_id" value="" />
                        <input type="hidden" name="shipping_cost" id="shipping_cost" value="10" />
                        <input type="hidden" name="selling_price" id="selling_price" /> 

                        <label for="quantity">Quantity:</label>
                        <input type="number" name="quantity" id="quantity" required onchange="handleChange()">

                        <label for="unit_cost">Unit Cost: £</label>
                        <input type="number" step="any" name="unit_cost" id="unit_cost" required onchange="handleChange()">

                        <label for="selling_price_span">Selling Price</label>
                        <span id="selling_price_span"></span>
                        
                        <button class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150 ml-3" type="submit"> Record Sale</button>
                    </form>
                </div>
            </div>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight mt-2">Previous Sales</h1>
            <div class="table-responsive mt-2">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th scope="col">Product Name</th>
                        <th scope="col">Quantity</th>
                        <th scope="col">Unit Cost</th>
                        <th scope="col">Selling Price</th>
                        <th scope="col">Sold at</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($salesRecord as $item)
                        <tr>
                            <td>{{$item->product_name}}</td>
                            <td>{{$item->quantity}}</td>
                            <td>£{{number_format($item->unit_cost,2)}}</td>
                            <td>£{{number_format($item->selling_price,2)}}</td>
                            <td>{{ \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i') }}</td>
                        </tr>  
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        function handleChange() {

            // Get value for input
            let selectElement = document.getElementById('product_id_select');
           
            var product_id = selectElement.value;

            // set value on product input 
            document.getElementById('product_id').value = product_id;

            var selectedOption = selectElement.options[selectElement.selectedIndex];
        
            // Get the value of the data-margin attribute
            var product_margin = selectedOption.getAttribute('data-margin');

            document.getElementById('margin').value = product_margin;

            let quantity = parseFloat(document.getElementById('quantity').value);

            let unit_cost = parseFloat(document.getElementById('unit_cost').value);

            let profit_margin = parseFloat(document.getElementById('margin').value);

            let shipping_cost = parseFloat(document.getElementById('shipping_cost').value);

            //Check quantity and unit_cost must be number and greater than 0

            if (!isNaN(quantity) && !isNaN(unit_cost) && quantity > 0 && unit_cost > 0) {
            // calculate cost 
            let cost = quantity * unit_cost;
            // canculate selling price
            let selling_price = (cost / ( 1 - profit_margin) ) + shipping_cost;

            // set new value on input selling_price
            document.getElementById('selling_price').value = selling_price.toFixed(2);
            document.getElementById('selling_price_span').innerHTML = '£' + selling_price.toFixed(2);
            
            }
        }
    </script>
</x-app-layout>
