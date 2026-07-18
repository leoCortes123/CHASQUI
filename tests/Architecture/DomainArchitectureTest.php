<?php

declare(strict_types=1);

// Guardarraíl para Fase 2 (Sesión 4): hoy no existe ningún namespace *\Domain
// bajo Chasqui\, así que esta expectativa pasa vacuamente. En cuanto aparezca
// código bajo Domain/, este test empieza a exigir la Regla de Oro #1.
arch('el Domain de cada bounded context no depende de Illuminate ni del framework')
    ->expect([
        'Chasqui\Shared\Domain',
        'Chasqui\Pipeline\Domain',
        'Chasqui\Canal\Domain',
        'Chasqui\Cliente\Domain',
        'Chasqui\Billing\Domain',
        'Chasqui\LlmGateway\Domain',
        'Chasqui\Sync\Domain',
    ])
    ->not->toUse('Illuminate');
