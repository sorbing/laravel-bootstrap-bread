@extends($layout)

@section('content')
    <div class="container-fluid">

        <div class="row">
            <div class="col-sm-6 mx-auto">

                <form action="{{ empty($id) ? route("$prefix.store") : route("$prefix.update", $id) }}" method="post">
                    @csrf()

                    @if (!empty($id))
                        {{ method_field('PATCH') }}
                    @endif

                    @foreach($columns as $key => $column)
                        <?php
                            unset($columnOptions);
                            $pos = strpos($key, '_id');
                            $isSelect = (bool)$pos;

                            if ($isSelect && empty($columnOptions)) {
                                $columnOptionsTable = substr($key, 0, $pos) . 's';
                                if (Schema::hasTable($columnOptionsTable) && Schema::hasColumn($columnOptionsTable, 'name')) {
                                    $columnOptions = \DB::table($columnOptionsTable)->select(['id', 'name'])->pluck('name', 'id')->all();
                                }
                            }
                        ?>

                        <div class="form-group row">
                            <?php $fieldKey = "$key-field"; ?>
                            <?php $readonly = in_array($key, ['id', 'created_at', 'updated_at']) ? 'readonly' : ''; ?>
                            <label for="{{ $fieldKey }}" class="col-sm-3 col-form-label">{{ str_replace('_', ' ', ucfirst($key)) }}</label>
                                <div class="col-sm-9">
                                    @if (!empty($columnOptions) && is_array($columnOptions) && count($columnOptions))
                                        <select class="form-control" id="{{ $fieldKey }}" name="{{ $key }}">
                                            @foreach($columnOptions as $optionId => $optionName)
                                                <?php $selected = (data_get($item, $key) == $optionId) ? 'selected="selected"' : ''; ?>
                                                <option value="{{ $optionId }}" {{$selected}}>{{ $optionName }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        {{--aria-describedby="emailHelp" placeholder="Enter email"--}}
                                        <input type="text" class="form-control" id="{{ $fieldKey }}" name="{{ $key }}" value="{{ data_get($item, $key, '') }}" {{ $readonly }}>
                                    @endif
                                </div>
                        </div>
                    @endforeach

                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </form>

            </div>
        </div>
    </div>
@endsection