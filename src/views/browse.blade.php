@extends($layout)

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12 mx-auto">
                <div class="row">
                    <div class="col-sm-10">
                        <h1 class="mb-0 mt-2">
                            {{ $title or ucwords(str_replace('.', ' ', $prefix)) }}
                            ({{count($collection)}})
                        </h1>
                    </div>
                    <div class="col-sm-2 text-right">
                        <a href="{{ route("$prefix.create") }}" class="btn btn-primary">{{ __('New') }}</a>
                    </div>
                </div>

                @include('bread::table', ['collection' => $collection, 'columns' => $columns, 'prefix' => $prefix])
            </div>
        </div>
    </div>
@endsection