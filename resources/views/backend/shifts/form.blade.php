@extends('adminlte::page')
<!-- page title -->
@section('title', 'Create and Update Shifts ' . Config::get('adminlte.title'))

@section('content_header')
<h1>Shifts</h1>
@stop

@section('content')
{{--Show message if any--}}
@include('layouts.flash-message')

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Add or Update</h3>
    </div>

    {{ html()->form()->route($data->form_action)->method('POST')->autocomplete('off')->open() }}
    {{ html()->hidden('id', $data->id)->id('id') }}

    <div class="card-body">

        <div class="form-group row">
            <div class="col-sm-2 col-form-label">
                <strong class="field-title">Name</strong>
            </div>
            <div class="col-sm-10 col-content">
                {{ html()->text('name')->value($data->name)->class('form-control')->required() }}
                <p class="form-text text-muted"><i class="fa fa-question-circle" aria-hidden="true"></i> Shift name</p>
            </div>
        </div>
        <div class="form-group row">
            <div class="col-sm-2 col-form-label">
                <strong class="field-title">Start Time</strong>
            </div>
            <div class="col-sm-4 col-content">
                {{ html()->text('start_time')->value($data->start_time)->class('form-control timepicker')->required() }}
                <p class="form-text text-muted"><i class="fa fa-question-circle" aria-hidden="true"></i> <b>(Format: Hour:Minute)</b> Fill with the start time hour office</p>
            </div>
        </div>
        <div class="form-group row">
            <div class="col-sm-2 col-form-label">
                <strong class="field-title">End Time</strong>
            </div>
            <div class="col-sm-4 col-content">
                {{ html()->text('end_time')->value($data->end_time)->class('form-control timepicker')->required() }}
                <p class="form-text text-muted"><i class="fa fa-question-circle" aria-hidden="true"></i> <b>(Format: Hour:Minute)</b> Fill with the end time hour office</p>
            </div>
        </div>
        <div class="form-group row">
            <div class="col-sm-2 col-form-label">
                <strong class="field-title">Late Mark After (in minutes)</strong>
            </div>
            <div class="col-sm-4 col-content">
                {{ html()->text('late_mark_after')->value($data->late_mark_after)->class('form-control timepicker_minutes')->required() }}
                <p class="form-text text-muted"><i class="fa fa-question-circle" aria-hidden="true"></i> <b>(Format: Hour:Minute)</b> <b>(in minutes)</b> How many minutes is said to be late</p>
            </div>
        </div>
        <div class="form-group row">
            <div class="col-sm-2 col-form-label">
                <strong class="field-title">Color</strong>
            </div>
            <div class="col-sm-4 col-content">
                {{ html()->select('color')->class('form-control')->options(['chartreuse' => 'Chartreuse', 'cyan' => 'Cyan', 'LightPink' => 'LightPink', 'yellow' => 'Yellow', 'snow' => 'Snow'])->value($data->color) }}
                <p class="form-text text-muted"><i class="fa fa-question-circle" aria-hidden="true"></i> Choose color. Color will show in Attendance page. So you can see their shift from the color</p>
            </div>
        </div>
    </div>

    <div class="card-footer">
        <div id="form-button">
            <div class="col-sm-12 text-center top20">
                {{ html()->button($data->button_text)->type('submit')->class('btn btn-primary')->id('btn-admin-member-submit') }}
            </div>
        </div>
    </div>
    {{ html()->form()->close() }}
</div>

<!-- /.card -->
</div>
<!-- /.row -->
<!-- /.content -->
@stop

@section('css')
<link rel="stylesheet" href="{{ asset('vendor/jquery-timepicker/jquery.timepicker.css') }}">
@stop

@section('js')
<script>
    var typePage = "{{ $data->page_type }}";
</script>
<script src="{{ asset('vendor/jquery-timepicker/jquery.timepicker.js') }}"></script>
<script src="{{ asset('js/backend/shifts/form.js'). '?v=' . rand(99999,999999) }}"></script>
@stop