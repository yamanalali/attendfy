@extends('adminlte::page')
<!-- page title -->
@section('title', 'Create and Update Areas ' . Config::get('adminlte.title'))

@section('content_header')
<h1>Areas</h1>
@stop

@section('content')
{{--Show message if any--}}
@include('layouts.flash-message')

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Add or Update</h3>
    </div>

    {{ html()->form('POST', route($data->form_action))->id('areaId')->attribute('autocomplete', 'off')->attribute('files', true)->open() }}
    {{ html()->hidden('id', $data->id)->id('id') }}

    <div class="card-body">

        <div class="form-group row">
            <div class="col-sm-2 col-form-label">
                <strong class="field-title">Name</strong>
            </div>
            <div class="col-sm-8 col-content">
                {{ html()->text('name', $data->name)->class('form-control')->required() }}
                <small class="form-text text-muted">
                    <i class="fa fa-question-circle" aria-hidden="true"></i> Area name. You can specified like Mal Bali Galeria
                </small>
            </div>
            <div class="col-sm-2 col-content">
                <button type="button" id="searchPlace" class="btn btn-outline-primary"><i class="fas fa-search"></i> Search</button>
            </div>
        </div>
        <div class="form-group row">
            <div class="col-sm-2 col-form-label">
                <strong class="field-title">Address</strong>
            </div>
            <div class="col-sm-10 col-content">
                {{ html()->text('address', $data->address)->class('form-control')->id('address')->required() }}
                <small class="form-text text-muted">
                    <i class="fa fa-question-circle" aria-hidden="true"></i> Area address.
                </small>
            </div>
        </div>

        <div class="form-group row">
            <div class="col-sm-2 col-form-label">
                <strong class="field-title">Draw Area</strong>
            </div>
            <div class="col-sm-10 col-content">
                <div class="button-map">
                    <button type="button" id="draw" class="btn btn-outline-primary"><i class="fas fa-draw-polygon"></i> Draw</button>
                    <button type="button" id="clear" class="btn btn-outline-warning"><i class="fas fa-eraser"></i> Clear Area</button>
                    <button type="button" id="stopDraw" class="btn btn-outline-danger"><i class="far fa-stop-circle"></i> Stop Draw</button>
                </div>

                <div id="map-canvas"></div>
                <textarea id="info" class="hide"></textarea>
            </div>
        </div>
    </div>

    <div class="card-footer">
        <div id="form-button">
            <div class="col-sm-12 text-center top20">
                <button id="saveLocation" type="button" class="btn btn-primary">{{ $data->button_text }}</button>
            </div>
        </div>

        <small class="form-text text-center text-muted">
            <i class="fa fa-question-circle" aria-hidden="true"></i> The more polygon points, the longer the process of storing data to the database.
        </small>
    </div>
    {{ html()->form()->close() }}
</div>

<!-- /.card -->
</div>
<!-- /.row -->
<!-- /.content -->
@stop

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@v8.2.0/ol.css">
@stop

@section('js')
<script>
    var typePage = "{{ $data->page_type }}";
</script>
{{-- If you using Google uncomment this --}}
{{-- <script src="https://maps.googleapis.com/maps/api/js?libraries=geometry,drawing&ext=.js"></script> --}}
{{-- <script src="{{ asset('js/backend/areas/form_google.js'). '?v=' . rand(99999,999999) }}"></script> --}}

{{-- Using OpenLayer --}}
<script src="https://cdn.jsdelivr.net/npm/ol@v8.2.0/dist/ol.js"></script>
<script src="{{ asset('js/backend/areas/form.js'). '?v=' . rand(99999,999999) }}"></script>
@stop