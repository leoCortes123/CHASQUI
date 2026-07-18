<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Chasqui\Canal\Presentation\CanalServiceProvider;
use Chasqui\Pipeline\Presentation\PipelineServiceProvider;

return [
    AppServiceProvider::class,
    PipelineServiceProvider::class,
    CanalServiceProvider::class,
];
