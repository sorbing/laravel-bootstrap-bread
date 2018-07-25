@if ($collection->count())
    @push('bread_assets')
        <style>
            .table.bread-table th, .table.bread-table td { padding: 0.5rem; }
            .bread-table .bread-actions { white-space: nowrap; }
            .bread-table .bread-actions .btn { white-space: nowrap; padding: 5px 10px; margin: 0 0 0 2px; }
        </style>
    @endpush

    <table class="table bread-table">
        <thead>
            <tr>
                @foreach($columns as $key => $column)
                    <?php if (data_get($column, 'hide')) { continue; } ?>
                    <?php $order = (request('order') == "-$key") ? $key : "-$key"; ?>
                    <?php $header = !empty($column['name']) ? $column['name'] : ucwords(str_replace(['_', '.'], ' ', $key)); ?>
                    <?php
                        $width = data_get($column, 'width', ($key == 'id') ? 50 : '');
                        $colStyle = $width ? "style=\"width: {$width}px;\"" : '';
                    ?>
                    <th {!! $colStyle or '' !!}>
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
                @foreach($columns as $key => $column)
                    <?php if (data_get($column, 'hide')) { continue; } ?>
                    <td style="margin: 0; padding: 0;">
                        <form name="filter" action="{{ route("$prefix.index") }}" method="get">
                            <input type="hidden" name="order" value="{{ request('order') }}"/>
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
        <tbody>
        @foreach($collection as $item)
            <?php $id = $item->id; ?>
            <tr>
                @foreach($columns as $key => $column)
                    <?php
                        $value = data_get($item, $key, '');
                        $transformer = !empty($column['transformer']) ? $column['transformer'] : null;
                        if (data_get($column, 'hide')) { continue; }
                    ?>

                    @if(!empty($column['template']))
                        <td>
                            {!! app('bread')->renderBlade($column['template'], ['key' => $key, 'column' => $column, 'value' => $value, 'item' => $item]) !!}
                        </td>
                    @elseif ($transformer)
                        @if (preg_match('/card:(.+),(.+),(.+)/', $transformer, $match))
                            <td>
                                <a href="{{ data_get($item, $match[3], '') }}" target="_blank">
                                    <div class="d-flex">
                                        <div><img src="{{ data_get($item, $match[1], '//placehold.jp/64x64.png') }}" alt="{{$match[1]}}" width="64"></div>
                                        <div class="align-self-stretch ml-1">{{ data_get($item, $match[2], '') }}</div>
                                    </div>
                                </a>
                            </td>
                        @elseif (preg_match('/link:(.+)/', $transformer, $match))
                            <td>{!! link_to($match[1], $value) !!}</td>
                        @elseif (preg_match('/date:(.+)/', $transformer, $match))
                            {{--<td>{{ $value }}</td>--}}
                            <td>{{ date($match[1], strtotime($value)) }}</td>
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
                    <a href="{{ route("$prefix.edit", $id) }}" class="btn btn-sm btn-outline-primary">✎</a>

                    {{ Form::open(['route' => ["$prefix.destroy", $id], 'method' => 'delete', 'class' => 'd-inline']) }}
                    <button type="submit" class="btn btn-sm btn-outline-danger">✕</button>
                    {{ Form::close() }}

                    @if (!empty($actions) && is_array($actions))
                        @foreach($actions as $actionText => $actionFnUrl)
                            <a href="{{ $actionFnUrl($item) }}" class="btn btn-sm btn-outline-primary">{{ $actionText }}</a>
                        @endforeach
                    @else
                        {!! str_replace(':id', $id, !empty($actions) ? $actions : '') !!}
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif