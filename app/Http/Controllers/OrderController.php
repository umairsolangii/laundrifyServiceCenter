<?php

namespace App\Http\Controllers;
use App\Models\Service;
use App\Models\Seller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;




class OrderController extends Controller
{

    public function allOrders()
{
    // Assuming there's an authenticated user
    $user = auth()->user();

    // Fetch all orders for the authenticated user or return an empty collection
    $orders = $user->orders ?? collect();

    return view('order.all-orders', compact('orders'));
}

    public function showCheckout()
    {
        $cart = Session::get('cart', []);

        if (empty($cart)) {
            return redirect()->route('cart.view')->with('error', 'Your cart is empty.');
        }

        return view('checkout.show', compact('cart'));


    }
   
    public function placeOrder(Request $request)
    {
        // Ensure $service is defined
        $service = $this->getService($request->service_id); // Example function to get service

        if (!$service) {
            Log::error('Invalid service_id for order placement', ['service_id' => $request->service_id]);
            return redirect()->route('home')->with('error', 'Invalid service.');
        }

        // Check if seller exists
        $cart = session('cart');
        $totalAmount = collect($cart)->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });
        $seller = Seller::find($cart[$service->id]['seller_id']);
        if (!$seller) {
            Log::error('Invalid seller_id for order placement', ['seller_id' => $cart[$service->id]['seller_id']]);
            return redirect()->route('home')->with('error', 'Invalid seller.');
        }

        try {
            Log::info('Attempting to place an order', [
                'user' => auth()->user(),
                'cart' => $cart,
            ]);

            // Validate inputs
            $request->validate([
                'service_id' => 'required|exists:services,id',
                'address' => 'required|string|max:255',
                'phone' => 'required|string|max:15',
            ]);

            // Create the order with seller_id
            $order = Order::create([
                'user_id' => Auth::id(),
                'seller_id' => $cart[$service->id]['seller_id'],
                'address' => $request->address,
                'phone' => $request->phone,
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'updated_at' => now(),
                'created_at' => now(),
            ]);

            if (!$order) {
                Log::error('Failed to create order', ['data' => $request->all()]);
                throw new \Exception('Order creation failed.');
            }

            // Add order items
            foreach ($cart as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'service_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            // Clear the cart
            session()->forget('cart');

            return redirect()->route('home')->with('success', 'Order placed successfully.');

        } catch (\Exception $e) {
            Log::error('Error occurred during order placement', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('home')->with('error', 'Order placement failed.');
        }
    }

    // Define the getService method
    private function getService($service_id)
    {
        return Service::find($service_id);
    }

    public function trackOrder(Order $order)
{
    if ($order->user_id !== auth()->id()) {
        abort(403, 'Unauthorized access');
    }

    return view('order.track', compact('order'));
}

public function track(Order $order)
{
    // Ensure the authenticated user is the owner of the order
    $user = auth()->user();

    if ($order->user_id !== $user->id) {
        abort(403, 'Unauthorized action.');
    }

    // Load related order items and other details
    $order->load('orderItems');
    $order->load(['user', 'seller', 'items.service']);


    return view('order.track', compact('order'));
}


public function acceptRejectOrder(Request $request, Order $order)
{
    $request->validate([
        'status' => 'required|string|in:accepted,rejected',
    ]);

    $order->update([
        'status' => $request->input('status'),
    ]);

    return redirect()->route('seller.panel')->with('success', 'Order status updated successfully.');
}

public function handleOrder(Order $order)
{
    // Check if the logged-in seller owns this order
    $sellerId = auth()->guard('seller')->id();
    if ($order->seller_id !== $sellerId) {
        return redirect()->route('seller.panel')->with('error', 'You do not have permission to manage this order.');
    }

    return view('seller.order-handle', compact('order'));
}
public function updateOrderStatus(Request $request, Order $order)
{
    $request->validate([
        'status' => 'required|string|in:accepted,pickup_departed,picked_up,started_washing,ironing,ready_for_delivery,delivered,completed',
    ]);

    // Check if the logged-in seller owns this order
    $sellerId = auth()->guard('seller')->id();
    if ($order->seller_id !== $sellerId) {
        return redirect()->route('seller.panel')->with('error', 'You do not have permission to update this order.');
    }

    $order->update(['status' => $request->status]);

    return redirect()->route('order.handle', $order)->with('success', 'Order status updated successfully!');
}
public function showOrderHandling(Order $order)
{
    return view('seller.order-handle', compact('order'));
}


}