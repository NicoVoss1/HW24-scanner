<?php
return [
    'enabled'=>env('HW24_RESEARCH_QUEUE_ENABLED',false)===true||env('HW24_RESEARCH_QUEUE_ENABLED')==='true',
    'worker_enabled'=>env('HW24_RESEARCH_WORKER_ENABLED',false)===true||env('HW24_RESEARCH_WORKER_ENABLED')==='true',
    'scan_intake_enabled'=>env('HW24_RESEARCH_SCAN_INTAKE_ENABLED',false)===true||env('HW24_RESEARCH_SCAN_INTAKE_ENABLED')==='true',
    'scan_auto_enqueue'=>env('HW24_RESEARCH_SCAN_AUTO_ENQUEUE',false)===true||env('HW24_RESEARCH_SCAN_AUTO_ENQUEUE')==='true',
];
