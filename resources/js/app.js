import './bootstrap';


// import { Tooltip } from 'bootstrap';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import 'bootstrap-icons/font/bootstrap-icons.css';



document.querySelectorAll('[data-bs-toggle="tooltip"]')
    .forEach(el => new Tooltip(el));


