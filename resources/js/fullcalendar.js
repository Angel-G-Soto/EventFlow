import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import bootstrap5Plugin from '@fullcalendar/bootstrap5';
import listPlugin from '@fullcalendar/list';

document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('calendar')
    if (!el) return
    const cal = new Calendar(el, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin, bootstrap5Plugin,listPlugin],
        themeSystem: 'bootstrap5',           // optional, if you’re using Bootstrap 5
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'listWeek,listMonth, dayGridMonth'
        },
        // events: '/api/events',
        buttonText:{
            prev: '<',
            next: '>',
        },
        events: [
            { title: 'Resume Workshop', start: '2025-10-20T14:30:00', end: '2025-10-20T15:30:00',
                description: "Improve your Resume with helpful tips"},
            { title: 'SWE General Meeting', start: '2025-10-22', allDay: true },
            { title: 'Hackathon', start: '2025-10-25', end: '2025-10-28' },
        ],
    })
    cal.render()
})
