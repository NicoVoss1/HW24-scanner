<?php
return [
    'enabled'=>env('HW24_RESEARCH_QUEUE_ENABLED',false)===true||env('HW24_RESEARCH_QUEUE_ENABLED')==='true',
    'worker_enabled'=>env('HW24_RESEARCH_WORKER_ENABLED',false)===true||env('HW24_RESEARCH_WORKER_ENABLED')==='true',
];
