#!/usr/bin/env php
<?php
// application.php

require __DIR__.'/vendor/autoload.php';

use Bencoder\DrivingTest\Command\CheckCommand;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new CheckCommand());
$application->run();
