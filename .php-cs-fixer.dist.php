<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

return (new Config())
    // Nodig voor risky rules zoals declare_strict_types (zie onder).
    ->setRiskyAllowed(true)
    ->setRules([
        // Basis: de Symfony-conventie. Framework-consistent.
        // Alternatief voor meer strengheid: '@PhpCsFixer' (zie notitie in de chat).
        '@Symfony' => true,

        // --- Jouw opinies, gedestilleerd uit je pint.json ---

        // Spaties rond concatenatie: 'a' . 'b'  (@Symfony wil 'none')
        'concat_space' => ['spacing' => 'one'],

        // Jouw '! $foo'-stijl, nu expliciet afgedwongen i.p.v. slechts toegestaan.
        'not_operator_with_successor_space' => true,

        // new NamedClass() mét haakjes, anonieme klassen zonder.
        'new_with_parentheses' => [
            'anonymous_class' => false,
            'named_class' => true,
        ],

        // Eén witregel tussen methods, geen tussen consts.
        'class_attributes_separation' => [
            'elements' => ['const' => 'none', 'method' => 'one'],
        ],

        'yoda_style' => false,

        // --- Bewust UIT ---

        // Zou automatisch declare(strict_types=1) toevoegen.
        // Uit gezet: bij het assessment typ je dit zelf, zonder fixer die het
        // achteraf rechtzet. Een vangnet dat de gewoonte overneemt werkt hier
        // tegen het leerdoel. Bij normaal projectwerk: gewoon aanzetten.
        'declare_strict_types' => false,
    ])
    ->setFinder($finder);
