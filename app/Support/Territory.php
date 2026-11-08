<?php

namespace App\Support;

/**
 * Territoire d'un membre ou d'une annonce : un département ou une région
 * d'outre-mer (976 Mayotte, 973 Guyane…) ou un département de l'Hexagone
 * (75, 2A…). Objet valeur immuable, construit par TerritoryResolver.
 */
final class Territory
{
    public const KIND_OVERSEAS = 'overseas';

    public const KIND_DEPARTMENT = 'department';

    /**
     * @param  list<string>  $communes  communes connues (config/locations.php)
     * @param  list<string>  $countryLabels  libellés de pays qui désignent ce territoire
     */
    public function __construct(
        public readonly string $code,
        public readonly string $label,
        public readonly string $slug,
        public readonly string $timezone,
        public readonly array $communes = [],
        public readonly string $ftLieux = '',
        public readonly string $kind = self::KIND_OVERSEAS,
        public readonly array $countryLabels = [],
        public readonly string $preposition = 'à',
    ) {}

    public function isOverseas(): bool
    {
        return $this->kind === self::KIND_OVERSEAS;
    }

    public function isDepartment(): bool
    {
        return $this->kind === self::KIND_DEPARTMENT;
    }

    public function is(?self $other): bool
    {
        return $other !== null && $other->code === $this->code;
    }

    /**
     * Débuts de code postal propres au territoire : « 976 » pour Mayotte,
     * « 75 » pour Paris, « 200 » et « 201 » pour la Corse-du-Sud.
     *
     * @return list<string>
     */
    public function postalPrefixes(): array
    {
        return match ($this->code) {
            '2A' => ['200', '201'],
            '2B' => ['202', '203', '204', '205', '206', '207', '208', '209'],
            default => [$this->code],
        };
    }

    /** « à Mayotte », « en Guyane », « dans le département 75 ». */
    public function withPreposition(): string
    {
        if ($this->isDepartment()) {
            return 'dans le département '.$this->code;
        }

        return $this->preposition.' '.$this->label;
    }

    /** « de Mayotte », « de La Réunion », « du département 75 ». */
    public function withDe(): string
    {
        if ($this->isDepartment()) {
            return 'du département '.$this->code;
        }

        return 'de '.$this->label;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'label' => $this->label,
            'slug' => $this->slug,
            'timezone' => $this->timezone,
            'communes' => $this->communes,
            'ft_lieux' => $this->ftLieux,
            'kind' => $this->kind,
        ];
    }
}
