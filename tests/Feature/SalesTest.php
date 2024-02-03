<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\SalesRecord;
use App\Models\Products;


class SalesTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_login_view_load(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }
    
    public function test_salesman_login_with_email_and_password(){
        //create User
        $user = User::factory()->create();
        // login
        $response = $this->post('/login',[
            'email' => $user->email,
            'password' => 'password'
        ]);
        $this->assertAuthenticated();

    }

    public function test_salesman_can_add_record_and_redirect_to_view_page() {

        //create User
        $user = User::factory()->create();
        // login
        $response = $this->post('/login',[
            'email' => $user->email,
            'password' => 'password'
        ]);
        $this->assertAuthenticated();
        // Create Save record
        $response = $this->from(route('coffee.sales'))->post(route('coffee.sales.reocrd'),
        [
            'product_id' => '2',
            'quantity' => '1',
            'unit_cost' => '10',
            'selling_price' => '23.34',    
            'shipping_cost'=> '10'
        ]);
         //check record count
        $this->assertEquals(1,SalesRecord::count());

        $response->assertStatus(302); 

        $response->assertRedirect(route('coffee.sales'));

    }

    public function test_salesman_can_view_listing(){

         //create User
         $user = User::factory()->create();
         // login
         $response = $this->post('/login',[
             'email' => $user->email,
             'password' => 'password'
         ]);
         $this->assertAuthenticated();
         // Create Save record
         $response = $this->from(route('coffee.sales'))->post(route('coffee.sales.reocrd'),
         [
             'product_id' => '2',
             'quantity' => '1',
             'unit_cost' => '10',
             'selling_price' => '23.34',    
             'shipping_cost'=> '10'
         ]);
          //check record count
         $this->assertEquals(1,SalesRecord::count());

    }
}
