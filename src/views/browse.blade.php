@extends($layout)

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12 mx-auto">

                <h1>{{ $title or ucwords(str_replace('.', ' ', $prefix)) }}</h1>

                <div class="mb-4">
                    <a href="{{ route("$prefix.create") }}" class="btn btn-primary">{{ __('Add') }}</a>
                </div>

                @include('bread::table', ['collection' => $collection, 'columns' => $columns, 'prefix' => $prefix])

            </div>
        </div>
    </div>
@endsection