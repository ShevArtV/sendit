<?php

return [
    'SendIt' => [
        'file' => 'plugin.sendit',
        'description' => 'без этих плагинов компонент работать не будет',
        'events' => ['OnLoadWebDocument' => [], 'OnHandleRequest' => []],
    ],
];