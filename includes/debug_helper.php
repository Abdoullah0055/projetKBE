<?php

define('DEBUG_LOG_PATH', sys_get_temp_dir() . '/darquest_debug.log');

function debug_log(string $msg): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    @file_put_contents(DEBUG_LOG_PATH, $line, FILE_APPEND | LOCK_EX);
}

function debug_log_clear(): void
{
    @file_put_contents(DEBUG_LOG_PATH, '');
}
