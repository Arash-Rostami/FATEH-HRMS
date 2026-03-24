<div>
    @foreach($dms as $doc)

        @foreach($doc->departments() as $dep)
                {{ $dep->name }}

                <br>

        @endforeach
            <hr>
    @endforeach
</div>
