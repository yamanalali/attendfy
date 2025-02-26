(function () {
    'use strict';

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        selectable: true,
        editable: userR != 1 && userR != 2 ? false : true,
        dayMaxEvents: true, // allow "more" link when too many events
        events: baseUrl + '/events/getAllDataEvent', // Fetch all events
        loading: function (bool) {
            if (bool) {
                $('.reload').css('display', 'block');
            } else {
                $('.reload').css('display', 'none');
            }
        },
        // Create Event
        select: function (arg) {
            // User role to create, edit or delete this event
            // If not administrator or admin cannot do event
            if (userR != 1 && userR != 2) {
                return;
            }

            // Alert box to add event
            Swal.fire({
                title: 'Add New Event',
                showCancelButton: true,
                confirmButtonText: 'Create',
                html:
                    '<input id="title" class="swal2-input" placeholder="Event name" style="width: 84%;"  >' +
                    '<textarea id="desc" class="swal2-input" placeholder="Event desc" style="width: 84%; height: 100px;"></textarea>',
                focusConfirm: false,
                preConfirm: () => {
                    return [
                        document.getElementById('title').value,
                        document.getElementById('desc').value
                    ]
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    var title = result.value[0].trim();
                    var desc = result.value[1].trim();
                    var start_date = arg.startStr;
                    var end_date = arg.endStr;

                    if (title != '' && desc != '') {

                        // AJAX - Add event
                        $.ajax({
                            url: baseUrl + '/events/create',
                            type: 'post',
                            data: { title: title, desc: desc, start_date: start_date, end_date: end_date },
                            dataType: 'json',
                            success: function (response) {
                                if (response.code == 1) {
                                    // Add event
                                    calendar.addEvent({
                                        eventid: response.eventid,
                                        title: title,
                                        description: desc,
                                        start: arg.start,
                                        end: arg.end,
                                        allDay: arg.allDay
                                    })

                                    // Alert message
                                    Swal.fire(response.message, '', 'success');

                                } else {
                                    // Alert message
                                    Swal.fire(response.message, '', 'error');
                                }
                            }
                        });
                    }
                }
            })
            calendar.unselect();
        },
        // Move event
        eventDrop: function (event) {
            // User role to create, edit or delete this event
            // If not administrator or admin cannot do event
            if (userR != 1 && userR != 2) {
                return;
            }

            // Event details
            var id = event.event.extendedProps.eventid;
            var newStart_date = event.event.startStr;
            var newEnd_date = event.event.endStr;

            $.ajax({
                url: baseUrl + '/events/update',
                type: 'post',
                data: { id: id, start_date: newStart_date, end_date: newEnd_date },
                dataType: 'json',
                async: false,
                success: function (response) {
                    console.log(response);
                }
            });

        },
        // Edit or Delete event
        eventClick: function (arg) {
            // Event details
            var id = arg.event._def.extendedProps.eventid;
            var desc = arg.event._def.extendedProps.description;
            var title = arg.event._def.title;

            // User role to create, edit or delete this event
            // If not administrator or admin cannot do event
            if (userR != 1 && userR != 2) {
                Swal.fire({
                    title: 'Show Event',
                    html:
                        '<b>Title:</b></br>' + title + '<br><br>' +
                        '<b>Description:</b></br>' + desc,
                })

                return;
            }

            // Alert box to edit and delete event
            Swal.fire({
                title: 'Edit Event',
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: 'Update',
                denyButtonText: 'Delete',
                html:
                    '<input id="title" class="swal2-input" placeholder="Event name" style="width: 84%;" value="' + title + '" >' +
                    '<textarea id="desc" class="swal2-input" placeholder="Event desc" style="width: 84%; height: 100px;">' + desc + '</textarea>',
                focusConfirm: false,
                preConfirm: () => {
                    return [
                        document.getElementById('title').value,
                        document.getElementById('desc').value
                    ]
                }
            }).then((result) => {
                if (result.isConfirmed) { // Update
                    var newTitle = result.value[0].trim();
                    var newdesc = result.value[1].trim();

                    if (newTitle != '' && newdesc != '') {
                        // AJAX - Edit event
                        $.ajax({
                            url: baseUrl + '/events/update',
                            type: 'post',
                            data: { id: id, title: newTitle, desc: newdesc },
                            dataType: 'json',
                            async: false,
                            success: function (response) {
                                if (response.code == 1) {
                                    // Refetch all events
                                    arg.event.remove();
                                    calendar.refetchEvents();
                                    // Alert message
                                    Swal.fire(response.message, '', 'success');
                                } else {
                                    // Alert message
                                    Swal.fire(response.message, '', 'error');
                                }

                            }
                        });
                    }

                } else if (result.isDenied) { // Delete
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You won't be able to revert this!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // AJAX - Delete Event
                            $.ajax({
                                url: baseUrl + '/events/delete/' + id,
                                type: 'post',
                                dataType: 'json',
                                async: false,
                                success: function (response) {

                                    if (response.code == 1) {
                                        // Remove event from Calendar
                                        arg.event.remove();

                                        // Alert message
                                        Swal.fire(response.message, '', 'success');
                                    } else {
                                        // Alert message
                                        Swal.fire(response.message, '', 'error');
                                    }

                                }
                            });
                        }
                    })
                }
            })
            calendar.unselect();
        }
    });
    calendar.render();
})(jQuery);