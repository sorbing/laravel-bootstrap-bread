<?php $formAttrs = ''; ?>
@if (!empty($attrs) && is_array($attrs))
    @foreach($attrs as $attrKey => $attrVal)
        <?php $formAttrs .= " $attrKey=\"$attrVal\""; ?>
    @endforeach
@endif

<form action="{{ $action }}" method="{{ isset($method) && $method == 'GET' ? 'GET' : "POST" }}" class="breadMassActionForm d-inline" {!! $formAttrs !!}>
    <input type="hidden" name="_method" value="{{ $method or "POST" }}" />
    {!! @csrf_field() !!}

    @foreach(request()->all() as $prevKey => $prevVal)
        @if (!empty($prevVal))
            <input type="hidden" name="{{ $prevKey }}" value="{{ $prevVal }}"/>
        @endif
    @endforeach

    @if (!empty($params) && is_array($params))
        @foreach($params as $pKey => $pVal)
            <input type="hidden" name="{{ $pKey }}" value="{{ $pVal }}" />
        @endforeach
    @endif

    <div class="breadMassActionFormIdsContainer d-none">
        {{-- <input type="hidden" name="id[]" value="" /> --}}
    </div>
    <input class="btn btn-block btn-primary" type="submit" value="{{ $name }}" />
</form>