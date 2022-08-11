<?php namespace App;

use App\Service\Sync;

require_once 'vendor/autoload.php';
require_once 'service/Result.php';
require_once 'service/Todoist.php';
require_once 'service/Data.php';
require_once 'service/Sync.php';

$sync = new Sync();

$sync->init();
$sync->exec();
$sync->term();
