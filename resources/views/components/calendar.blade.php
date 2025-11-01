@props([

'events'    => [],
'today'     => 'oggi',
'month'     => 'mese',
'week'      => 'settimana',
'day'       => 'giorno',
'list'      => 'elenco'
])

@php

//    $events[] = [
//                        'id' => 'xxxx',
//                        'title' => '<i class="fas fa-phone"></i> evento xxx',
//                        'description' => 'description',
//                        //'tooltip' => htmlspecialchars(@$e->subsid.' - '.Str::limit($e->planname, 100)." - ".$e->device_tracking_status.' '. $e->company->business_name, ENT_QUOTES),
//                        'start' => \Carbon\Carbon::parse('2025-05-13 12:00:00')->toDateTimeString(),
//                        'end' => \Carbon\Carbon::parse('2025-05-13 12:30:00')->toDateTimeString(),
//                        //'className' => $css,
//                        'display' => 'list-item',
//                    ];
        //$options = Arr::isAssoc($options) ? $options : array_combine($options, $options);
@endphp



<div id='calendar-container' wire:ignore>
    <div id='calendar' style="height:200px"></div>
</div>


@once
    @push('footer_scripts')

        <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.3.1/main.min.js'></script>
        <script type="application/javascript">

            document.addEventListener('livewire:load', function() {
                var Calendar = FullCalendar.Calendar;
                var calendarEl = document.getElementById('calendar');

                var calendar = new Calendar(calendarEl, {
                    height: 500,
                    // evita sovrapposizione orizzontale degli eventi
                    slotEventOverlap: false,

                    // opzionale: imposta un min-height per avere un po’ di spazio verticale
                    eventMinHeight: 24,
                    firstDay: 1, // 0 = Domenica, 1 = Lunedì
                    slotMinTime: "08:00:00",
                    slotMaxTime: "20:00:00",
                    locale: 'it',
                    initialView: 'listDay',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,listDay'
                    },
                    eventClassNames: function(arg) {
                        const ownerRaw = arg.event.extendedProps.owner || '';
                        const ownerName = ownerRaw.split('.')[0];
                        return ['bg-'+ownerName];
                    },
                    eventContent: function(arg) {

                        const ownerRaw = arg.event.extendedProps.owner || '';
                        const ownerName = ownerRaw.split('.')[0];
                        let ownerClass = 'text-gray-500';
                        return {
                            html: `<div class="small cursor-hand">
                                     <div> ${arg.event.title}</div>
                                     <div class="small text-gray-500">${ownerName}</div>
                                    </div>`
                        }
                    },

                    eventClick: function(arg) {
                        Livewire.emit('search', {
                            'q': arg.event.title,
                        });

                    },

                    buttonText: {
                        today:    '{{ $today }}',
                        month:    '{{ $month }}',
                        week:     '{{ $week }}',
                        day:      '{{ $day }}',
                        list:     '{{ $list }}'
                    },
                    allDayContent: '',
                    editable: false,
                    eventDurationEditable: false,
                    droppable: false,
                    eventReceive: info => @this.eventReceive(info.event),
                    eventDrop: info => @this.eventDrop(info.event, info.oldEvent),
                    loading: function(isLoading) {
                        if (!isLoading) {
                            // Reset custom events
                            this.getEvents().forEach(function(e){
                                if (e.source === null) {
                                    e.remove();
                                }
                            });
                        }
                    },
                    events: @json($events),
                });

                calendar.render();

                @this.on(`refreshCalendar`, () => {
                    calendar.getEvents().forEach(event => event.remove());
                    calendar.addEventSource(@json($events));
                    console.log(@json($events));

                });
            });



        </script>

        <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.3.1/main.min.css' rel='stylesheet' />
        <style>
            #calendar {
                /*max-width: 1100px;*/
                /*margin: 20px auto;*/
            }
            .fc {
                font-size: 0.75rem !important;
            }

            /* Se vuoi aggiustare specifiche parti: */
            .fc-toolbar,          /* toolbar (titolo e bottoni) */
            .fc .fc-daygrid-day-number, /* numeri dei giorni in month */
            .fc .fc-timegrid-event,     /* testo eventi */
            .fc .fc-list-item {
                font-size: 0.75rem !important;
            }

            .fc-event {
                min-height:2.5em;
            }
            .fc-v-event {
                border: none;
                background-color: inherit;
            }
            .fc-timegrid-event.task-comm {
                background-color: #e8fceb !important;
            }
            .fc-timegrid-event.task-tech {
                background-color: #dac1fd !important;
            }


            .fc-v-event .fc-event-main {
                color: inherit;
            }

            .fc-daygrid-dot-event, .fc-timegrid-event {
                overflow: hidden;
                font-size: 0.9em;
                border:1px solid #ccc;
                padding: 2px;
                /*height: 6em;*/
            }

            .fc .fc-timegrid-slot {
                height: 6em;
                padding: 2px;
            }

            .fc-toolbar-title {
                font-size: 1em !important;
            }

            .bg-marco {
                background-color: rgba(56, 173, 24, 0.22) !important;
            }
            .bg-danilo {
                background-color: rgba(40, 156, 225, 0.22) !important;
            }

        </style>
    @endpush
@endonce
