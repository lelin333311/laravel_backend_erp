<!DOCTYPE html>
<html>
<head>
    <title>Add Product</title>
</head>
<body>

    <h1>Add New Product</h1>

    @if($errors->any())
        @foreach($errors->all() as $error)
            <p style="color:red">{{ $error }}</p>
        @endforeach
    @endif

    <form action="{{ route('products.store') }}" method="POST">

        @csrf

        <label>Product Name</label>
        <input type="text" name="name" value="{{ old('name') }}" required>

        <br><br>

        <label>Price</label>
        <input type="number" step="0.01" name="price" value="{{ old('price') }}" required>

        <br><br>

        <label>Stock</label>
        <input type="number" name="stock" value="{{ old('stock', 0) }}" required>

        <br><br>

        <button type="submit">Save Product</button>

    </form>

    <br>

    <a href="{{ route('products.index') }}">Back</a>

</body>
</html>