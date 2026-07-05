@extends('docs.layout')

@section('content')
    {{-- Negative margins undo the layout padding so Scalar fills the full content width --}}
    <div class="-mx-6 md:-mx-12 -my-12">
        <script id="api-reference" data-url="/api-spec/openapi.yaml"></script>
        <script src="https://cdn.jsdelivr.net/npm/@scalar/api-reference"></script>
    </div>
@endsection
