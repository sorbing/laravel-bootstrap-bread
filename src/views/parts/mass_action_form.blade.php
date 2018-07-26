<form action="{{ $action }}" method="post" class="breadMassActionForm d-inline">
    <input type="hidden" name="_method" value="delete" />
    {!! @csrf_field() !!}
    <div class="breadMassActionFormIdsContainer d-none">
        {{-- <input type="hidden" name="ids[]" value="" /> --}}
    </div>
    <input class="btn btn-block btn-primary" type="submit" value="{{ $name }}" />
</form>