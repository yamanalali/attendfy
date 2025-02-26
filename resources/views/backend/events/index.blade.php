{{-- resources/views/admin/dashboard.blade.php --}}

@extends('adminlte::page')

@section('title', 'Events | ' . Config::get('adminlte.title'))

@section('content_header')
<h1>Events</h1>
@stop

@section('content')
{{--Show message if any--}}
@include('layouts.flash-message')

<div class="card">
   <div class="card-header">
      <h3 class="card-title">Calendar</h3>
   </div>

   <div class="card-body">
      <div class="alert alert-info" role="alert">
         @if (Auth::user()->role != 1 && Auth::user()->role != 2)
         <i class="fa fa-question-circle" aria-hidden="true"></i> Click the date to show the event.
         @else
         <i class="fa fa-question-circle" aria-hidden="true"></i> Click the date to create an event, and click again to edit or delete. You can also drag and drop events by dragging the event to the date you want.
         @endif
      </div>
      <div id="calendar"></div>
   </div>
</div>

<!-- calendar modal -->
<div id="modal-view-event" class="modal modal-top fade calendar-modal">
   <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
         <div class="modal-body">
            <h4 class="modal-title"><span class="event-icon"></span><span class="event-title"></span></h4>
            <div class="event-body"></div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
         </div>
      </div>
   </div>
</div>
<div id="modal-view-event-add" class="modal modal-top fade calendar-modal">
   <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
         <form id="add-event">
            <div class="modal-body">
               <h4>Add Event Detail</h4>
               <div class="form-group">
                  <label>Event name</label>
                  <input type="text" class="form-control" name="ename">
               </div>
               <div class="form-group">
                  <label>Event Date</label>
                  <input type='text' class="datePickerJs form-control" name="edate">
               </div>
               <div class="form-group">
                  <label>Event Description</label>
                  <textarea class="form-control" name="edesc"></textarea>
               </div>
               <div class="form-group">
                  <label>Event Color</label>
                  <select class="form-control" name="ecolor">
                     <option value="fc-bg-default">fc-bg-default</option>
                     <option value="fc-bg-blue">fc-bg-blue</option>
                     <option value="fc-bg-lightgreen">fc-bg-lightgreen</option>
                     <option value="fc-bg-pinkred">fc-bg-pinkred</option>
                     <option value="fc-bg-deepskyblue">fc-bg-deepskyblue</option>
                  </select>
               </div>
               <div class="form-group">
                  <label>Event Icon</label>
                  <select class="form-control" name="eicon">
                     <option value="circle">circle</option>
                     <option value="cog">cog</option>
                     <option value="group">group</option>
                     <option value="suitcase">suitcase</option>
                     <option value="calendar">calendar</option>
                  </select>
               </div>
            </div>
            <div class="modal-footer">
               <button type="submit" class="btn btn-primary">Save</button>
               <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
            </div>
         </form>
      </div>
   </div>
</div>
@stop

@section('css')
<link href="{{ asset('vendor/gijgo/css/gijgo.css'). '?v=' . rand(99999,999999) }}" rel="stylesheet" type="text/css" />
@stop

@section('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.js"></script>
<script src="{{ asset('vendor/fullcalendar/dist/index.global.min.js'). '?v=' . rand(99999,999999) }}"></script>
<script src="{{ asset('vendor/gijgo/js/gijgo.min.js'). '?v=' . rand(99999,999999) }}" type="text/javascript"></script>
<script>
   var userR = "{{ Auth::user()->role; }}"
</script>
<script src="{{ asset('js/backend/events/index.js'). '?v=' . rand(99999,999999) }}"></script>
@stop