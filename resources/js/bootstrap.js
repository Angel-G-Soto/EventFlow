import _ from 'lodash';
import axios from 'axios';

window._ = _;

// Setup axios defaults
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';