@extends($layout)

@section('content')
    <div class="container-fluid">

        <div class="row">
            <div class="col-sm-6 mx-auto">

                <form action="{{ empty($id) ? route("$prefix.store") : route("$prefix.update", $id) }}" method="post">
                    @csrf()
                    {{ Form::hidden('_prev_index_url', \URL::previous()) }}

                    @if (!empty($id))
                        {{ method_field('PATCH') }}
                    @endif

                    @foreach($columns as $key => $column)
                        <?php
                            unset($columnOptions);
                            $pos = strpos($key, '_id');
                            $isSelect = (bool)$pos;

                            // @todo $columnOptions
                            if ($isSelect && empty($columnOptions)) {
                                $optionsTable = substr($key, 0, $pos) . 's';

                                // @note Try determine the Model for getting <options>
                                $optionsModelClass = 'App\\' . studly_case(str_singular($optionsTable));
                                $optionsModel = null;
                                if (class_exists($optionsModelClass)) {
                                    $optionsModel = app($optionsModelClass);
                                } else if (class_exists($optionsModelClass = 'App\\Models\\' . studly_case(str_singular($optionsTable)))) {
                                    $optionsModel = app($optionsModelClass);
                                }

                                if (is_object($optionsModel) && method_exists($optionsModel, 'getPlainOptions')) {
                                    $columnOptions = $optionsModel::getPlainOptions();
                                } else if (Schema::hasTable($optionsTable) && Schema::hasColumn($optionsTable, 'name')) {
                                    $columnOptions = \DB::table($optionsTable)->select(['id', 'name'])->pluck('name', 'id')->all();
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
                                            <option value="">Please select value</option>
                                            @foreach($columnOptions as $optionId => $optionName)
                                                <?php $selected = (data_get($item, $key) == $optionId) ? 'selected="selected"' : ''; ?>
                                                <option value="{{ $optionId }}" {{$selected}}>{{ $optionName }}</option>
                                            @endforeach
                                        </select>
                                    @elseif(preg_match('/description|_json/', $key))
                                        <textarea class="form-control" id="{{ $fieldKey }}" name="{{ $key }}" {{ $readonly }}>{!! data_get($item, $key, '') !!}</textarea>
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