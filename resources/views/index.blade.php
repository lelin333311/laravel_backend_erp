<!DOCTYPE html>
<html>
<head>
    <title>Product CRUD</title>
</head>
<body>

    <h1>Product List</h1>

    @if(session('success'))
        <p style="color: green;">
            {{ session('success') }}
        </p>
    @endif

    <a href="{{ route('products.create') }}">
        Add New Product
    </a>

    <br><br>

    <table border="1" cellpadding="10">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Action</th>
        </tr>

        @forelse($products as $product)
            <tr>
                <td>{{ $product->id }}</td>
                <td>{{ $product->name }}</td>
                <td>{{ $product->price }}</td>
                <td>{{ $product->stock }}</td>

                <td>
                    <a href="{{ route('products.edit', $product) }}">
                        Edit
                    </a>

                    <form action="{{ route('products.destroy', $product) }}"
                          method="POST"
                          style="display:inline">

                        @csrf
                        @method('DELETE')

                        <button type="submit"
                            onclick="return confirm('Delete this product?')">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5">No products found.</td>
            </tr>
        @endforelse

    </table>

</body>
</html>