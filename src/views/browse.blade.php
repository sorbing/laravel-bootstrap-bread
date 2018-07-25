@extends($layout)

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12 mx-auto">
                <div class="row">
                    <div class="col-sm-8">
                        <h1 class="mb-0 mt-2">
                            {{ $title or ucwords(str_replace('.', ' ', $prefix)) }}
                            ({{count($collection)}})
                        </h1>
                    </div>
                    <div class="col-sm-4 text-right">
                        <a href="{{ route("$prefix.create") }}" class="btn btn-primary">{{ __('New') }}</a>

                        <div class="dropdown float-md-right">
                            <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMassToggler" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Mass actions
                            </button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMassToggler">
                                <a class="dropdown-item" href="#">Action</a>
                            </div>
                        </div>
                    </div>
                </div>

                @push('bread_assets')
                    {{--<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">--}}

                    {{-- 0.0Kb --}}
                    {{-- 6.8Kb --}}
                    {{-- 0.0Kb --}}
                    {{--<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
                    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>--}}

                    <style>
                        .dropdown-toggle:focus ~ .dropdown-menu { display: block; }
                    </style>
                @endpush

                @include('bread::table', ['collection' => $collection, 'columns' => $columns, 'prefix' => $prefix])
            </div>
        </div>
    </div>
@endsection