<!DOCTYPE html>
<html>
<head>
    <title>Edit Product</title>
</head>
<body>

    <h1>Edit Product</h1>

    @if($errors->any())
        @foreach($errors->all() as $error)
            <p style="color:red">{{ $error }}</p>
        @endforeach
    @endif

    <form action="{{ route('products.update', $product) }}" method="POST">

        @csrf
        @method('PUT')

        <label>Product Name</label>
        <input type="text" name="name"
               value="{{ old('name', $product->name) }}" required>

        <br><br>

        <label>Price</label>
        <input type="number" step="0.01" name="price"
               value="{{ old('price', $product->price) }}" required>

        <br><br>

        <label>Stock</label>
        <input type="number" name="stock"
               value="{{ old('stock', $product->stock) }}" required>

        <br><br>

        <button type="submit">Update Product</button>

    </form>

    <br>

    <a href="{{ route('products.index') }}">Back</a>

</body>
</html>