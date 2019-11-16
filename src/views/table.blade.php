<?php
/** @var $key string */
/** @var $columns_settings array */
/** @var $options_registry array */
?>

@push('bread_assets')
    <style>
        .table.bread-table th, .table.bread-table td { padding: 0.2rem 0.5rem; }
        .bread-table .bread-actions * { white-space: nowrap !important; }
        .bread-table .bread-actions > div { float: right; }
        .bread-table thead tr:nth-child(1) th { vertical-align: middle; }
        .bread-table tbody td { vertical-align: middle; }
        .bread-table tbody .btn { white-space: nowrap; padding: 5px 10px; margin: 0 0 0 2px; }
        .breadMassActionsWrap .dropdown-item .btn { white-space: nowrap; }

        .table.bread-table th .sorting-cell-inner  { display: inline-block; position: relative; }
        .table.bread-table th .sorting-cell-inner .sortAsc  { display: inline-block; position: absolute; top: -2px; left: -10px; }
        .table.bread-table th .sorting-cell-inner .sortDesc { display: inline-block; position: absolute; bottom: 2px; right: -10px; }
        .table.bread-table th .sorting-cell-inner .sortAsc, .table.bread-table th .sorting-cell-inner .sortDesc { display: none; }
        /*.table.bread-table th .sorting-cell-inner:hover .sortAsc, .table.bread-table th .sorting-cell-inner:hover .sortDesc { display: inline-block; }*/
        .table.bread-table th:hover .sortAsc, .table.bread-table th:hover .sortDesc { display: inline-block; }

        /* Bread card widget popup big image */
        .table.bread-table .bread-thumbnail-popup { display: none; margin-top: -160px; }
        .table.bread-table .bread-thumbnail:hover ~ .bread-thumbnail-popup { display: block !important; }
        .table.bread-table .bread-thumbnail-popup:hover { display: block !important; }

        @foreach($columns as $key)
            <?php
                $column = isset($columns_settings[$key]) ? $columns_settings[$key] : null;
                $align = data_get($column, 'align', 'center');
                $width = data_get($column, 'width', ($key == 'id') ? 50 : '');
                $colStyle = $align ? "text-align: {$align}; " : '';
                $colStyle .= $width ? "width: {$width}px; " : '';
                $colStyle .= data_get($column, 'css', '');
            ?>
            .table.bread-table .bread-col-{{ str_replace('.', '-', $key) }} { {{ $colStyle }} }
        @endforeach
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

            let checkedCheckboxes = [];
            document.querySelectorAll('.bread-table td.id input:checked').forEach(function(checkbox) {
                //checkedCheckboxes.push(checkbox);

                document.querySelectorAll('.breadMassActionFormIdsContainer').forEach(function(div) {
                    div.appendChild(checkbox.cloneNode());
                });
            });
        };

        window.applyBreadTableFiltersForm = function() {
            let allowableHiddenParams = ['order', '_columns'];
            let params = {};

            document.querySelectorAll('.bread-table .filtering-form input').forEach(function(input) {
                if (input.getAttribute('type') === 'hidden' && allowableHiddenParams.indexOf(input.name) === -1) return;
                let val = (input.value || '').trim();
                if (val) params[input.name] = val;
            });

            let q = Object.keys(params).map(function(p) {
                return p + '=' + params[p];
            }).join('&');

            let url = document.querySelector('.bread-table .filtering-form').action;
            url += '?' + q;

            window.location = url;

            return false;
        };
    </script>
@endpush

<table class="table bread-table">
    <thead>
        {{-- Titles --}}
        <tr>
            <th>
                <input type="checkbox" class="inputToggleCheckboxes" onchange="window.toggleAllBreadIdsCheckboxes(this)" />
            </th>
            @foreach($columns as $key)
                <?php $colClass = "bread-col-" . str_replace('.', '-', $key); ?>
                <?php $column = isset($columns_settings[$key]) ? $columns_settings[$key] : null; ?>
                <?php if (!key_exists($key, $columns_settings) || data_get($column, 'hide')) continue; ?>
                <?php $order = (request('order') == "-$key") ? $key : "-$key"; ?>
                <?php $header = !empty($column['name']) ? $column['name'] : ucwords(str_replace(['_', '.'], ' ', $key)); ?>
                <th title="{{ data_get($column, 'title') }}" class="text-center {{ $colClass }}">
                    @if (strpos($key, '.') || data_get($column, 'not_sortable'))
                        {{ $header }}
                    @else
                        <div class="sorting-cell-inner">
                            <a href="{{ route("$prefix.index") }}?order={{ $order }}&{{ urldecode(query_except('order')) }}">{{ $header }}</a>
                            <a href="{{ route("$prefix.index") }}?order={{ trim($order, '-') }}&{{ urldecode(query_except('order')) }}" class="sortAsc" title="По возрастанию (сначала меньшие)">▵</a>
                            <a href="{{ route("$prefix.index") }}?order=-{{ trim($order, '-') }}&{{ urldecode(query_except('order')) }}" class="sortDesc" title="По убыванию (сначала большие)">▿</a>
                        </div>
                    @endif
                </th>
            @endforeach
            <th style="text-align: center">Actions</th>
        </tr>

        {{-- Filters --}}
        <tr>
            <td></td>
            @foreach($columns as $key)
                <?php $colClass = "bread-col-" . str_replace('.', '-', $key); ?>
                <?php $column = key_exists($key, $columns_settings) ? $columns_settings[$key] : null; ?>
                <?php if (!key_exists($key, $columns_settings) || data_get($column, 'hide')) { continue; } ?>
                <td class="{{ $colClass }}" style="margin: 0; padding: 0;">
                    <form name="filter" action="{{ route("$prefix.index") }}" method="get" class="filtering-form" onsubmit="return window.applyBreadTableFiltersForm(); return false;" autocomplete="off">
                        <input type="hidden" name="order" value="{{ request('order') }}" />
                        {{--<input type="hidden" name="_confirmed_batch_action" value="{{ session('_confirmed_batch_action') }}"/>--}}
                        @foreach(request()->except(['order', 'page', $key]) as $prevKey => $prevVal)
                            @if (mb_strlen($prevVal))
                                <input type="hidden" name="{{ $prevKey }}" value="{{ $prevVal }}"/>
                            @endif
                        @endforeach
                        <?php /*$disabled = strpos($key, '.') ? 'disabled' : '';*/ ?>
                        <?php $disabled = data_get($column, 'not_filterable') ? 'disabled' : ''; ?>
                        <input type="text" class="form-control form-control-sm" {{ $disabled }} name="{{ $key }}" value="{{ request($key) }}" autocomplete="off" /> {{-- autocomplete="new-password" --}}
                    </form>
                </td>
            @endforeach
            <td style="margin: 0; padding: 0;">
                {{--<input type="submit" class="btn btn-sm btn-primary" style="margin: 0; padding: 6px 12px;" value="Фильтр"/>--}}
                {{--<button type="submit" class="btn btn-sm btn-primary" style="margin: 0; padding: 6px 12px;">Применить</button>--}}
            </td>
        </tr>
    </thead>

    @if ($paginator->total())
    <tbody>
    @foreach($paginator as $item)
        <?php
            /** @var $item \Illuminate\Database\Eloquent\Model */
            $id = $item->id;
        ?>
        <tr>
            <td class="id">
                <input type="checkbox" name="id[]" value="{{ $id }}" onchange="window.toggleBreadIdCheckbox(this)" @if(in_array($id, $breadOldCheckedIds)) checked @endif />
            </td>
            @foreach($columns as $key)
                <?php
                    $colClass = "bread-col-" . str_replace('.', '-', $key);
                    $column = isset($columns_settings[$key]) ? $columns_settings[$key] : null;

                    if (!key_exists($key, $columns_settings) || data_get($column, 'hide')) { continue; }

                    $value = $v = data_get($item, $key, '');

                    if (strpos($key, '__') !== false) {
                        $relationName = explode('__', $key)[0];
                        // @note Illuminate\Database\Eloquent\Collection|mixed
                        $value = $v = data_get($item, $relationName);
                    }

                    $vNamed = null;
                    if (key_exists($key, $options_registry)) {
                        $vOptions = $options_registry[$key];
                        $vId = $v;
                        if ($vOptions instanceof \Illuminate\Support\Collection) {
                            $vOption = $vOptions->get($vId); // @note Requred the keyBy('id')
                            //if ($vOption instanceof Illuminate\Database\Eloquent) {}
                            $vNamed = $vOption ? data_get($vOption, 'name', data_get($vOption, 'title')) : null;
                        } else if (is_array($vOptions)) {
                            // @todo Get value from indexed or item list array
                        }
                    }

                    // IdentificationColumns

                    //$mappedOptionValue = array_ // $options_registry

                    $transformer = !empty($column['transformer']) ? $column['transformer'] : null;
                    $prepareTemplate = !empty($column['prepare']) ? $column['prepare'] : null;
                    if ($prepareTemplate) {
                        $value = $v = app('bread')->renderBlade($prepareTemplate, ['key' => $key, 'id' => $id, 'value' => $value, 'v' => $v, 'column' => $column, 'item' => $item]);
                    }
                ?>

                {{-- @todo Применить transformer, а уже после него template --}}
                @if(!empty($column['template']))
                    <td class="{{ $colClass }}">
                        {!! app('bread')->renderBlade($column['template'], ['key' => $key, 'id' => $id, 'value' => $value, 'column' => $column, 'item' => $item]) !!}
                    </td>
                @elseif ($transformer)
                    @if (preg_match('/card:([^,]+),([^,]+),([^,]+)(?:,(.+)|)/', $transformer, $match))
                        <?php
                            $cardThumbnail = data_get($item, $match[1]);
                            // @todo Сервиса ImageManager здесь быть не должно. Закончить функционал `prepare`
                            //$cardThumbnail = $cardThumbnail ? app('ImageManager')->cachedOrLazyImageUrl($cardThumbnail, '120x120') : '//placehold.jp/48x48.png';

                            $cardText = data_get($item, $match[2], $value); // @todo Пока только для позиции card:text по дефолту отображается $value

                            // @note Instead methods use the `Model::getMyCustomAttribute()` method!
                            if (strpos($match[3], '(')) {
                                $cardUrl = call_user_func_array([$item, trim($match[3], '()')], []);
                            } else {
                                $cardUrl = data_get($item, $match[3], '');
                            }

                            $cardPopupImage = isset($match[4]) ? data_get($item, $match[4]) : null;
                        ?>
                        <td class="{{ $colClass }}">
                            <div class="d-flex bread-card-widget">
                                <div class="position-relative">
                                    <a href="{{ $cardUrl }}" target="_blank">
                                        <img src="{{ $cardThumbnail }}" alt="{{$match[1]}}" width="48" class="bread-thumbnail" />
                                        @if ($cardPopupImage)
                                        <img src="{{ $cardPopupImage }}" class="bread-thumbnail-popup" style="position: absolute; top: 0; left: 50px; max-width: 360px;" />
                                        @endif
                                    </a>
                                </div>
                                <div class="align-self-stretch ml-1">
                                    {{--<a href="{{ data_get($item, $match[3], '') }}" target="_blank">🔗</a>--}}
                                    {!! $cardText !!}
                                </div>
                            </div>
                        </td>
                    @elseif (preg_match('/thumbnails:(.+)/', $transformer, $match))
                        <td class="{{ $colClass }}">
                            <?php
                                $delimiter = $match[1];
                                $images = explode($delimiter, $v);
                            ?>

                            @foreach($images as $imageUrl)
                                <?php
                                    /* @var $imageUrl */
                                    $imageUrl = trim($imageUrl);
                                    $cachedImage = app('ImageManager')->cachedOrLazyImageUrl($imageUrl);
                                    $cachedThumb = app('ImageManager')->cachedOrLazyImageUrl($imageUrl, '120x120') ?: '//placehold.jp/48x48.png';
                                ?>
                                    <div class="position-relative d-inline-block">
                                        <a href="{{ $cachedImage }}" target="_blank"> {{-- class="d-inline-block position-relative" --}}
                                            <img src="{{ $cachedThumb }}" alt="" width="48" class="bread-thumbnail" />
                                            <img src="{{ $cachedImage }}" class="bread-thumbnail-popup" style="position: absolute; bottom: 50px; left: -310px; width: 360px; z-index: 999;" />
                                        </a>
                                    </div>
                            @endforeach
                        </td>
                    @elseif (preg_match('/link:(.+)/', $transformer, $match))
                        <td class="{{ $colClass }}">{!! link_to($match[1], $value) !!}</td>
                    @elseif (preg_match('/date:(.+)/', $transformer, $match))
                        {{--<td>{{ $value }}</td>--}}
                        <td class="{{ $colClass }}">{{ $value ? date($match[1], strtotime($value)) : ''}}</td>
                    @elseif($transformer === 'img')
                        <td class="{{ $colClass }}">{!! Html::image($value, '', ['height' => 30]) !!}</td>
                    @elseif(is_callable($transformer))
                        <td class="{{ $colClass }}">{!! $transformer($value, $item) !!}</td>
                    @endif
                @elseif (preg_match('/_json$/', $key))
                    <td class="{{ $colClass }}">
                        <?php
                            $decodedValue = json_decode($value, true);
                            if ($decodedValue) {
                                $decodedValue = function_exists('array_print') ? array_print($decodedValue) : print_r($decodedValue, true);
                            }
                        ?>
                        <pre style="text-align: left">{!! $decodedValue ?: $value !!}</pre>
                    </td>
                @else
                    <td class="{{ $colClass }}" @if($vNamed) title="ID: {{ $v }}" @endif>
                        {!! $vNamed ?: $v !!}
                    </td>
                @endif
            @endforeach
            <td class="bread-actions">
                <div>
                    @if (!empty($actions))
                    <div class="d-inline-block bread-actions-custom">
                        <div class="dropdown"> {{--float-md-right breadMassActionsWrap--}}
                            <button class="btn btn-primary dropdown-toggle" id="breadCustomActionsToggler{{ $id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                ☘{{-- ⚙ --}}
                            </button>
                            <div class="dropdown-menu" aria-labelledby="breadCustomActionsToggler{{ $id }}" style="right: 5px; left: auto;">
                                @if (is_array($actions))
                                    @foreach($actions as $action)
                                        <div class="dropdown-item">
                                        @if (is_array($action))
                                            <?php $url = is_callable(data_get($action, 'action')) ? $action['action']($item) : data_get($action, 'action'); ?>
                                            {{--{!! app('bread')->renderBlade($action['template'], ['key' => $key, 'value' => $value, 'column' => $column, 'item' => $item]) !!}--}}
                                            <a href="{{ $url }}" title="{{ data_get($action, 'title') }}" class="btn btn-sm btn-outline-primary">{{ data_get($action, 'name', 'Button') }}</a>
                                        @elseif(is_string($action))
                                            {!! app('bread')->renderBlade($action, ['id' => $id, 'item' => $item]) !!}
                                        @endif
                                        </div>
                                    @endforeach
                                @endif

                                {{--<div class="dropdown-item">
                                    @include('bread::parts.mass_action_form', ['name' => 'Delete', 'action' => route("$prefix.destroy", 0)])
                                </div>--}}
                            </div>
                        </div>
                    </div>
                    @endif

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
