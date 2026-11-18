<?php

return [
    /*
    | Where backups are written (outside public/, never web-accessible directly).
    */
    'path' => env('BACKUP_PATH', storage_path('app/backups')),

    /*
    | How many backups to keep; older ones are deleted after each new backup.
    */
    'keep' => (int) env('BACKUP_KEEP', 14),

    /*
    | MySQL client tools. On XAMPP (Windows) they are in C:\xampp\mysql\bin.
    */
    'mysqldump' => env('BACKUP_MYSQLDUMP', 'mysqldump'),
    'mysql' => env('BACKUP_MYSQL', 'mysql'),
];
