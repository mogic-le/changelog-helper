<?php

use LaravelZero\Framework\Application;

$app = Application::configure(basePath: dirname(__DIR__))->create();
if (is_file(getcwd().'/.env')) {
    $app->useEnvironmentPath(getcwd());
    // $app->loadEnvironmentFrom(getcwd().'/.env');
}

return $app;
