// Bring in Bootstrap's precompiled CSS to avoid Sass deprecation warnings from SCSS imports
import 'bootstrap/dist/css/bootstrap.min.css';

// Keep icons here (or move to SCSS if you prefer a single CSS pipeline):
import 'bootstrap-icons/font/bootstrap-icons.css';

// Make Bootstrap JS available globally for inline Blade scripts
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;
