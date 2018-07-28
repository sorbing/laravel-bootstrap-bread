@push('bread_assets')
    <style>
        .table.bread-table th, .table.bread-table td { padding: 0.2rem 0.5rem; }
        .bread-table .bread-actions * { white-space: nowrap !important; }
        .bread-table .bread-actions > div { float: right; }
        .bread-table tbody .btn { white-space: nowrap; padding: 5px 10px; margin: 0 0 0 2px; }
        .breadMassActionsWrap .dropdown-item .btn { white-space: nowrap; }
    </style>

    <script>
        window.toggleAllBreadIdsCheckboxes = function(toggler) {
            document.querySelectorAll('.bread-table td.id input').forEach(function(checkbox) {
                checkbox.checked = toggler.checked;
            });

            window.updateBreadMassActionForm();
        };

        window.toggleBreadIdCheckbox = function(checkbox) {
            window.updateBreadMassActionForm();
        };

        window.updateBreadMassActionForm = function() {
            document.querySelectorAll('.breadMassActionFormIdsContainer').forEach(function(div) {
                div.innerHTML = '';
            });

            var checkedCheckboxes = [];
            document.querySelectorAll('.bread-table td.id input:checked').forEach(function(checkbox) {
                //checkedCheckboxes.push(checkbox);

                document.querySelectorAll('.breadMassActionFormIdsContainer').forEach(function(div) {
                    div.appendChild(checkbox.cloneNode());
                });
            });
        };
    </script>
@endpush

@if(!empty($preset_filters) && count($preset_filters))
    <div class="row bread-preset-filters-wrap">
        <div class="col-sm-12">
            @foreach($preset_filters as $name => $preset_filter)
                {{--<a href="{{ route("$prefix.index")."?".data_get($preset_filter, 'query') }}" class="badge badge-secondary">{{ $name }}</a>--}}
                <a href="{{ route("$prefix.index")."?".data_get($preset_filter, 'query') }}">{{ $name }}</a>
                @if (!$loop->last)
                    <span> | </span>
                @endif
            @endforeach
        </div>
    </div>
@endif

<table class="table bread-table">
    <thead>
        <tr>
            <th><input type="checkbox" class="inputToggleCheckboxes" onchange="window.toggleAllBreadIdsCheckboxes(this)" /></th>
            @foreach($columns as $key => $column)
                <?php if (data_get($column, 'hide')) { continue; } ?>
                <?php $order = (request('order') == "-$key") ? $key : "-$key"; ?>
                <?php $header = !empty($column['name']) ? $column['name'] : ucwords(str_replace(['_', '.'], ' ', $key)); ?>
                <?php
                    $width = data_get($column, 'width', ($key == 'id') ? 50 : '');
                    $colStyle = $width ? "style=\"width: {$width}px;\"" : '';
                ?>
                <th {!! $colStyle or '' !!} title="{{ data_get($column, 'title') }}">
                    @if (strpos($key, '.'))
                        {{ $header }}
                    @else
                        <a href="{{ route("$prefix.index") }}?order={{ $order }}&{{ query_except('order') }}">{{ $header }}</a>
                    @endif
                </th>
            @endforeach
            <th>Actions</th>
        </tr>
        <tr>
            <td></td>
            @foreach($columns as $key => $column)
                <?php if (data_get($column, 'hide')) { continue; } ?>
                <td style="margin: 0; padding: 0;">
                    <form name="filter" action="{{ route("$prefix.index") }}" method="get">
                        <input type="hidden" name="order" value="{{ request('order') }}"/>
                        @foreach(request()->except(['order', $key]) as $prevKey => $prevVal)
                            @if (!empty($prevVal))
                                <input type="hidden" name="{{ $prevKey }}" value="{{ $prevVal }}"/>
                            @endif
                        @endforeach
                        <?php $disabled = strpos($key, '.') ? 'disabled' : ''; ?>
                        <input type="text" class="form-control form-control-sm" {{ $disabled }} name="{{ $key }}" value="{{ request($key) }}" autocomplete="off"/>
                    </form>
                </td>
            @endforeach
            <td style="margin: 0; padding: 0;">
                {{--<input type="submit" class="btn btn-sm btn-primary" style="margin: 0; padding: 6px 12px;" value="Фильтр"/>--}}
            </td>
        </tr>
    </thead>
    @if ($paginator->total())
    <tbody>
    @foreach($paginator as $item)
        <?php $id = $item->id; ?>
        <tr>
            <td class="id"><input type="checkbox" name="id[]" value="{{ $id }}" onchange="window.toggleBreadIdCheckbox(this)"/></td>
            @foreach($columns as $key => $column)
                <?php
                    $value = data_get($item, $key, '');
                    $transformer = !empty($column['transformer']) ? $column['transformer'] : null;
                    if (data_get($column, 'hide')) { continue; }
                ?>

                {{-- @todo Применить transformer, а уже после него template --}}
                @if(!empty($column['template']))
                    <td>
                        {!! app('bread')->renderBlade($column['template'], ['key' => $key, 'id' => $id, 'value' => $value, 'column' => $column, 'item' => $item]) !!}
                    </td>
                @elseif ($transformer)
                    @if (preg_match('/card:(.+),(.+),(.+)/', $transformer, $match))
                        <td>
                            <div class="d-flex">
                                <div>
                                    <a href="{{ data_get($item, $match[3], '') }}" target="_blank">
                                        <img src="{{ data_get($item, $match[1], '//placehold.jp/48x48.png') }}" alt="{{$match[1]}}" width="48">
                                    </a>
                                </div>
                                <div class="align-self-stretch ml-1">
                                    {{--<a href="{{ data_get($item, $match[3], '') }}" target="_blank">🔗</a>--}}
                                    {{ data_get($item, $match[2], '') }}
                                </div>
                            </div>
                        </td>
                    @elseif (preg_match('/link:(.+)/', $transformer, $match))
                        <td>{!! link_to($match[1], $value) !!}</td>
                    @elseif (preg_match('/date:(.+)/', $transformer, $match))
                        {{--<td>{{ $value }}</td>--}}
                        <td>{{ $value ? date($match[1], strtotime($value)) : ''}}</td>
                    @elseif($transformer === 'img')
                        <td>{!! Html::image($value, '', ['height' => 30]) !!}</td>
                    @elseif(is_callable($transformer))
                        <td>{!! $transformer($value, $item) !!}</td>
                    @endif
                @else
                    <td>{!! $value !!}</td>
                @endif
            @endforeach
            <td class="bread-actions">
                <div>
                    <div class="d-inline-block bread-actions-custom">
                        @if (!empty($actions) && is_array($actions))
                            @foreach($actions as $action)
                                @if (is_array($action))
                                    <?php $url = is_callable(data_get($action, 'action')) ? $action['action']($item) : data_get($action, 'action'); ?>
                                    {{--{!! app('bread')->renderBlade($action['template'], ['key' => $key, 'value' => $value, 'column' => $column, 'item' => $item]) !!}--}}
                                    <a href="{{ $url }}" title="{{ data_get($action, 'title') }}" class="btn btn-sm btn-outline-primary">{{ data_get($action, 'name', 'Button') }}</a>
                                @elseif(is_string($action))
                                    {!! app('bread')->renderBlade($action, ['key' => $key, 'id' => $id, 'value' => $value, 'column' => $column, 'item' => $item]) !!}
                                @endif
                            @endforeach
                        @endif
                    </div>
                    <div class="d-inline-block bread-actions-default">
                        <a href="{{ route("$prefix.edit", $id) }}" class="btn btn-sm btn-outline-primary">✎</a>

                        {{ Form::open(['route' => ["$prefix.destroy", $id], 'method' => 'delete', 'class' => 'd-inline']) }}
                        <button type="submit" class="btn btn-sm btn-outline-danger">✕</button>
                        {{ Form::close() }}
                    </div>
                </div>
            </td>
        </tr>
    @endforeach
    </tbody>
    @endif
</table>

@if ($paginator->total())
    {{ $paginator->appends(request()->all())->links() }}
@else
    {{ $empty_content or '' }}
@endif