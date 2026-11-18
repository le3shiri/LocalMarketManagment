<?php

namespace App\Support;

/**
 * Each catalog section has its own shelf colours (design system: <section>-tint / <section>-ink).
 * Class names are written out in full so Tailwind can find them in this file.
 */
class SectionColors
{
    private const MAP = [
        'fruta-y-verdura' => ['tint' => 'bg-fruta-tint', 'ink' => 'text-fruta-ink', 'fill' => 'bg-fruta-ink'],
        'carniceria' => ['tint' => 'bg-carne-tint', 'ink' => 'text-carne-ink', 'fill' => 'bg-carne-ink'],
        'lacteos-y-huevos' => ['tint' => 'bg-lacteos-tint', 'ink' => 'text-lacteos-ink', 'fill' => 'bg-lacteos-ink'],
        'panaderia' => ['tint' => 'bg-panaderia-tint', 'ink' => 'text-panaderia-ink', 'fill' => 'bg-panaderia-ink'],
        'despensa' => ['tint' => 'bg-despensa-tint', 'ink' => 'text-despensa-ink', 'fill' => 'bg-despensa-ink'],
        'bebidas' => ['tint' => 'bg-bebidas-tint', 'ink' => 'text-bebidas-ink', 'fill' => 'bg-bebidas-ink'],
    ];

    private const FALLBACK = ['tint' => 'bg-otros-tint', 'ink' => 'text-otros-ink', 'fill' => 'bg-otros-ink'];

    /** @return array{tint: string, ink: string, fill: string} */
    public static function for(?string $slug): array
    {
        return self::MAP[$slug] ?? self::FALLBACK;
    }
}
