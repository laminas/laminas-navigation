<?php

declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Preset\Preset;

return Architecture::define()
    ->withPresets(Preset::PSR4(), Preset::CODEQUALITY())
    ->layer('Exception', 'src/Exception')
    ->layer('Page', 'src/Page')
    ->layer('Container', [
        'src/AbstractContainer.php',
        'src/Navigation.php',
    ])
    ->layer('Service', 'src/Service')
    ->layer('View', 'src/View')
    ->layer('Config', [
        'src/ConfigProvider.php',
        'src/Module.php',
    ])
    ->ruleset([
        'Exception' => [],
        'Page'      => ['Exception', 'Container'],
        'Container' => ['Exception', 'Page'],
        'Service'   => ['+Container'],
        'View'      => [],
        'Config'    => ['+Service', '+View'],
    ]);
