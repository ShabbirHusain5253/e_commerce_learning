<?php

namespace App\Http\Livewire;

use App\Models\Coupon;
use Carbon\Carbon;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use phpDocumentor\Reflection\DocBlock\Tags\See;
// use App\Http\Livewire\numberFormat;

class CartComponent extends Component
{
    public $haveCouponCode;
    public $couponCode;
    public $discount;
    public $subtotalAfterDiscount;
    public $taxAfterDiscount;
    public $totalAfterDiscount;


    public function increaseQuantity($rowId)
    {

        $product = Cart::instance('cart')->get($rowId);
        $qty = $product->qty + 1;
        Cart::instance('cart')->update($rowId,$qty);
        $this->emitTo('cart-count-component','refreshComponent');



    }    
    
    public function decreaseQuantity($rowId)
    {

        $product = Cart::instance('cart')->get($rowId);
        $qty = $product->qty - 1;
        Cart::instance('cart')->update($rowId,$qty);
        $this->emitTo('cart-count-component','refreshComponent');



    }

    public function destroy($rowId)
    {

        Cart::instance('cart')->remove($rowId);
        $this->emitTo('cart-count-component','refreshComponent');
        session()->flash('success_message',' Item has been deleted successfully');

    }

    public function destroyAll()
    {
        Cart::instance('cart')->destroy();
        $this->emitTo('cart-count-component','refreshComponent');

    }

    public function switchToSaveForLater($rowId)
    {

        $item = Cart::instance('cart')->get($rowId);

        Cart::instance('cart')->remove($rowId);

        Cart::instance('saveForLater')->add($item->id,$item->name,1,$item->price)->associate('App\Models\Product');

        $this->emitTo('cart-count-component','refreshComponent');

        session()->flash('success_message','Item Has Been Saved For Later');

    }

    public function moveToCart($rowId)
    {

        $item = Cart::instance('saveForLater')->get($rowId);

        Cart::instance('saveForLater')->remove($rowId);

        Cart::instance('cart')->add($item->id,$item->name,1,$item->price)->associate('App\Models\Product');

        $this->emitTo('cart-count-component','refreshComponent');

        session()->flash('s_success_message','Item Has Been Moved To Cart');


    }

    public function deleteFromSaveForLater($rowId)
    {

        Cart::instance('saveForLater')->remove($rowId);

        session()->flash('s_success_message','Item Has Been Removed Form Saved For Later!');

    }

    public function applyCouponCode()
    {
        

        $coupon = Coupon::where('code',$this->couponCode)->where('expiry_date','>=',Carbon::today())->where('cart_value', '<=', Cart::instance('cart')->subtotal())->first();

        // dd($coupon);
        if(!$coupon)
        {
            session()->flash('coupon_message','Coupon Code Is Invalidate!');
            return;

        }

        // dd($coupon);

        session()->put('coupon',[

            'code' => $coupon->code,
            'type' => $coupon->type,
            'value'=> $coupon->value,
            'cart_value' => $coupon->cart_value,

        ]);

        // dd(session()->get('coupon'));

    }

    public function calculateDiscount()
    {

        if(session()->has('coupon'))
        {
            if(session()->get('coupon')['type'] == 'fixed')
            {

                $this->discount = session()->get('coupon')['value'];

            }
            else
            {

                $this->discount = (Cart::instance('cart')->subtotal() * session()->get('coupon')['value'])/100;

            }

            
            // $str = Cart::instance('cart')->subtotal();
            // $num = intval($str,10);

            $this->subtotalAfterDiscount = Cart::instance('cart')->subtotal() - $this->discount;
            $this->taxAfterDiscount      = ($this->subtotalAfterDiscount * config('cart.tax'))/100;
            $this->totalAfterDiscount    = $this->subtotalAfterDiscount + $this->taxAfterDiscount;

            // dd($num);
        }
    }

    public function removeCoupon()
    {

        session()->forget('coupon');

    }

    public function checkout(){

        if(Auth::check())
        {

            return redirect()->route('checkout');

        }
        else
        {

            return redirect()->route('login');

        }
    }

    public function setAmountForCheckout()
    {

        if(!Cart::instance('cart')->count() > 0)
        {

            session()->forget('checkout');
            return;

        }

        if(session()->has('coupon'))
        {

            session()->put('checkout',[

                'discount' => $this->discount,
                'subtotal' => $this->subtotalAfterDiscount,
                'tax'      => $this->taxAfterDiscount,
                'total'    => $this->totalAfterDiscount
            ]);
        }
        else
        {

            session()->put('checkout',[

                'discount' => 0,
                'subtotal' => Cart::instance('cart')->subtotal(),
                'tax'      => Cart::instance('cart')->tax(),
                'total'    => Cart::instance('cart')->total()
            ]);
        }
    }

    public function render()
    {

        if(session()->has('coupon'))
        {

            if(!session()->get('coupon')['cart_value'])
            {

                session()->forget('coupon');

            }
            else
            {

                $this->calculateDiscount();

            }

        }

        $this->setAmountForCheckout();


        return view('livewire.cart-component')->layout('layouts.base');
    }
}
