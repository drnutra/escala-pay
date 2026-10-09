<?php

// Demo local: nomes e emblemas no tema EscalaX (imagens em public/images/conquistas-escalax).
// Slugs e faixas mantidos; originais: Filhote, Jacaré Caçador, Predador, Alpha do Pântano,
// Rei do Pântano, Lenda da Getfy, Imperador Predador, Jacaré Supremo (cdn.getfy.cloud/conquistas/*).
return [
    'achievements' => [
        [
            'threshold' => 10_000,
            'slug' => 'filhote',
            'name' => 'Primeira Escala',
            'image' => '/images/conquistas-escalax/10k.png',
        ],
        [
            'threshold' => 50_000,
            'slug' => 'jacare-cacador',
            'name' => 'Escalador',
            'image' => '/images/conquistas-escalax/50k.png',
        ],
        [
            'threshold' => 100_000,
            'slug' => 'predador',
            'name' => 'Escala Pro',
            'image' => '/images/conquistas-escalax/100k.png',
        ],
        [
            'threshold' => 500_000,
            'slug' => 'alpha-pantano',
            'name' => 'Alta Escala',
            'image' => '/images/conquistas-escalax/500k.png',
        ],
        [
            'threshold' => 1_000_000,
            'slug' => 'rei-pantano',
            'name' => 'Milionário X',
            'image' => '/images/conquistas-escalax/1m.png',
        ],
        [
            'threshold' => 5_000_000,
            'slug' => 'lenda-getfy',
            'name' => 'Lenda da EscalaX',
            'image' => '/images/conquistas-escalax/5m.png',
        ],
        [
            'threshold' => 10_000_000,
            'slug' => 'imperador-predador',
            'name' => 'Escala Máxima',
            'image' => '/images/conquistas-escalax/10m.png',
        ],
        [
            'threshold' => 25_000_000,
            'slug' => 'jacare-supremo',
            'name' => 'EscalaX Supremo',
            'image' => '/images/conquistas-escalax/25m.png',
        ],
    ],
];
