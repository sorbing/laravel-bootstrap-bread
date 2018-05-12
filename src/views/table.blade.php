@if ($collection->count())
    <table class="table">
        <thead>
        <tr>
            @foreach($columns as $column)
                <th>{{ ucwords(str_replace(['_', '.'], ' ', $column)) }}</th>
            @endforeach
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        @foreach($collection as $item)
            <?php $id = $item->id; ?>
            <tr>
                @foreach($columns as $column)
                    <?php
                        $value = data_get($item, $column, '');
                        $transformer = (!empty($transformers) && isset($transformers[$column]) ? $transformers[$column] : null);
                    ?>

                    @if ($transformer)
                        @if ($transformer === 'link')
                            <td>{!! link_to($value, $value) !!}</td>
                        @elseif($transformer === 'img')
                            <td>{!! Html::image($value, '', ['height' => 30]) !!}</td>
                        @elseif(is_callable($transformer))
                            <td>{!! $transformer($value) !!}</td>
                        @endif
                    @else
                        <td>{{ $value }}</td>
                    @endif
                @endforeach
                <td>
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