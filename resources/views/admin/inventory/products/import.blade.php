@extends('admin.layouts.app')

@section('content')
<h1>Import Products in Bulk</h1>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
<div class="alert alert-danger">
    <ul>
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ route('admin.inventory.products.import') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div>
        <label for="file">Upload CSV file:</label>
        <input type="file" name="file" id="file" accept=".csv" required>
    </div>

    <div>
        <button type="submit">Import Products</button>
    </div>
</form>

<hr>

<h3>Download Sample Import File</h3>
<a href="{{ route('admin.inventory.products.download-sample') }}" class="btn btn-primary">Download Sample CSV</a>
@endsection