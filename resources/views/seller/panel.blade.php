<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Panel - Laundrify</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        /* Styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #005f73;
            padding: 20px;
            color: #fff;
        }

        .header-container h1 {
            margin: 0;
            font-size: 24px;
        }

        .seller-nav {
            display: flex;
            gap: 10px;
        }

        .seller-nav .nav-btn {
            padding: 10px 15px;
            color: #fff;
            background-color: #0a9396;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .seller-nav .nav-btn:hover {
            background-color: #94d2bd;
        }

        .seller-nav .logout-btn {
            background-color: #ee9b00;
        }

        .seller-nav .logout-btn:hover {
            background-color: #ca6702;
        }

        main {
            padding: 20px;
            text-align: center;
        }

        main p {
            font-size: 18px;
            color: #555;
        }

        /* Service Grid Styles */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            padding: 20px;
            justify-items: center;
        }

        .product-card {
            background-color: #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 20px;
            width: 100%;
            max-width: 300px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .product-card h2 {
            font-size: 22px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }

        .product-card .price {
            font-size: 18px;
            color: #00796b;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .product-card img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .button-container {
            margin-top: 15px;
        }

        .edit-service, .delete-service {
            background-color: #0a9396;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-right: 10px;
            transition: background-color 0.3s ease;
        }

        .edit-service:hover, .delete-service:hover {
            background-color: #00796b;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <h1>Welcome to the Seller Panel, {{ auth()->guard('seller')->user()->name }}!</h1>
            <nav class="seller-nav">
                <a href="{{ route('add.service') }}" class="nav-btn">Add Service</a>
                <a href="" class="nav-btn">Remove Service</a>
                <a href="" class="nav-btn">Update Service</a>
                <a href="" class="nav-btn">Orders</a>

                @if(session('admin_id'))
                    <form action="{{ route('admin.returnToAdmin') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-danger">Return to Admin Panel</button>
                    </form>
                 @endif

                <form id="logout-form" action="{{ route('logout.seller') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="nav-btn logout-btn">Logout</button>
                </form>
            </nav>
        </div>
    </header>

    <main>
        <p>This is your dashboard. You can manage your services here.</p>

        <!-- Display services -->
        <div class="seller-panel">
            @if($services->isEmpty())
                <p>You haven't added any services yet.</p>
            @else
                <div class="product-grid">
                    @foreach($services as $service)
                        <div class="product-card">
                            <h2>{{ $service->service_name }}</h2>
                            <p class="price">Start Price {{ $service->service_price }} PKR</p>
                            <img src="{{ Storage::url($service->image) }}" alt="{{ $service->service_name }}">
                            <div class="button-container">
                                <a href="{{ route('seller.editService', $service->id) }}" class="edit-service">Edit</a>
                                <form action="{{ route('seller.deleteService', $service->id) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="delete-service">Delete</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>


        <h2>Your Orders</h2>
@if($orders->isEmpty())
    <p>No orders found.</p>
@else
    <table class="table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Buyer Name</th>
                <th>Services</th>
                <th>Total Price</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
                <tr>
                    <td>{{ $order->id }}</td>
                    <td>{{ $order->user->name }}</td> <!-- Assuming the user relationship is loaded -->
                    <td>
                        <ul>
                            @foreach($order->items as $item) <!-- Assuming items relationship is loaded -->
                                <li>{{ $item->service->service_name }} ({{ $item->quantity }} x {{ $item->price }} PKR)</li>
                            @endforeach
                        </ul>
                    </td>
                    <td>{{ $order->total_amount ?? 'N/A' }} PKR</td> <!-- Ensure total_amount is calculated -->
                    <td>{{ $order->status }}</td>
                    <td>
                        @if($order->status === 'pending')
                            <form action="{{ route('order.acceptReject', $order) }}" method="POST" style="display: inline;">
                                @csrf
                                <button type="submit" name="status" value="accepted" class="btn btn-success">Accept</button>
                                <button type="submit" name="status" value="rejected" class="btn btn-danger">Reject</button>
                            </form>
                        @else
                            <a href="{{ route('order.handle', $order) }}" class="btn btn-info">View</a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

    </main>
</body>
</html>
