<?php

namespace App\Http\Livewire;

use App\Models\Product;
use App\Models\Sale;
use Livewire\Component;
use Gloudemans\Shoppingcart\Facades\Cart;

class DetailsComponent extends Component
{
    public $slug;

    public $qty;

    public function mount($slug) {

        $this->slug = $slug;

        $this->qty = 1;

    }

    public function increaseQuantity()
    {

        $this->qty++;

    }

    public function decreaseQuantity()
    {

        if($this->qty > 1)
        {

            $this->qty--;

        }

    }

    public function store($product_id,$product_name,$product_price) 
    {

        Cart::instance('cart')->add($product_id,$product_name,$this->qty,$product_price)->associate('App\Models\Product');
        session()->flash('success_message','Item added in Cart');
        return redirect()->route('product.cart');


    }

    public function render()
    {
        $product = Product::where('slug', $this->slug)->first();
        $popular_product = Product::inRandomOrder()->limit(4)->get();
        $related_product = Product::where('category_id',$product->category_id)->limit(5)->inRandomOrder()->get();
        $sale = Sale::find(1);
        return view('livewire.details-component',['product' => $product,'popular_product' => $popular_product,'related_product' => $related_product,"sale"=>$sale])->layout('layouts.base');
    }
}
