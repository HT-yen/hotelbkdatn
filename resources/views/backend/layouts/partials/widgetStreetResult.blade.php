@if (isset($hintedStreets) && count($hintedStreets) != 0)
  <div class="col-md-12">
    @foreach($hintedStreets as $street)
      <li class="place-selected text-success text-center form-control" style="display:block;width: 100%">{{$street->street_name}}</li>
    @endforeach
  </div>
@endif