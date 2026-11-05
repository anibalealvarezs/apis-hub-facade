<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Filament\App\Pages\CatalogEntity;
use InvalidArgumentException;

final class EntityReference
{
    public const array SUPPORTED_LAYERS = ['channeled', 'base'];

    public const array ALL_ENTITY_TYPES = [
        'accounts',
        'campaigns',
        'ad-sets',
        'ad-groups',
        'ads',
        'pages',
        'posts',
        'queries',
        'customers',
        'orders',
        'products',
        'events',
    ];

    public const array PHASE_1_ADS_ENTITY_TYPES = [
        'accounts',
        'campaigns',
        'ad-sets',
        'ad-groups',
        'ads',
    ];

    public const array SUPPORTED_ENTITY_TYPES = [
        'accounts',
        'campaigns',
        'ad-sets',
        'ad-groups',
        'ads',
        'pages',
        'posts',
        'queries',
        'events',
        'customers',
    ];

    public function __construct(
        private readonly string $layer,
        private readonly string $channel,
        private readonly string $entityType,
        private readonly int $id,
    ) {
        if (! in_array($this->layer, self::SUPPORTED_LAYERS, true)) {
            throw new InvalidArgumentException("Unsupported catalog reference layer '{$this->layer}'.");
        }

        if ($this->channel === '' || ! preg_match('/^[a-z0-9_-]+$/', $this->channel)) {
            throw new InvalidArgumentException("Invalid catalog reference channel '{$this->channel}'.");
        }

        if (! in_array($this->entityType, self::ALL_ENTITY_TYPES, true)) {
            throw new InvalidArgumentException("Unsupported catalog entity type '{$this->entityType}'.");
        }

        if ($this->id <= 0) {
            throw new InvalidArgumentException('Catalog reference ID must be a positive integer.');
        }
    }

    public static function format(string $layer, string $channel, string $entityType, int|string $id): string
    {
        return (new self(
            strtolower(trim($layer)),
            strtolower(trim($channel)),
            strtolower(trim($entityType)),
            (int) $id
        ))->toString();
    }

    public static function parse(string $reference): self
    {
        $trimmed = trim(urldecode($reference));
        $parts = explode(':', $trimmed);
        if (count($parts) !== 4) {
            throw new InvalidArgumentException("Malformed catalog entity reference '{$reference}'.");
        }

        [$layer, $channel, $entityType, $rawId] = $parts;
        if (! ctype_digit($rawId) || (int) $rawId <= 0) {
            throw new InvalidArgumentException("Invalid catalog entity reference ID in '{$reference}'.");
        }

        return new self(
            strtolower(trim($layer)),
            strtolower(trim($channel)),
            strtolower(trim($entityType)),
            (int) $rawId
        );
    }

    public static function tryParse(?string $reference): ?self
    {
        if ($reference === null || trim($reference) === '') {
            return null;
        }

        try {
            return self::parse($reference);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public static function isPhase1Supported(string $entityType): bool
    {
        return in_array(strtolower(trim($entityType)), self::PHASE_1_ADS_ENTITY_TYPES, true);
    }

    public static function isSupported(string $entityType): bool
    {
        return in_array(strtolower(trim($entityType)), self::SUPPORTED_ENTITY_TYPES, true);
    }

    /**
     * Build a tenant-scoped Catalog URL preserving active scope/query parameters.
     *
     * @param  array<string, mixed>  $query
     */
    public static function buildUrl(string $entityType, ?string $reference = null, array $query = [], mixed $tenant = null): string
    {
        $params = ['entity' => $entityType];
        if ($reference !== null && trim($reference) !== '') {
            $params['record'] = trim($reference);
        }

        $cleanQuery = array_filter($query, static fn ($v) => $v !== null && $v !== '');
        $url = CatalogEntity::getUrl($params, tenant: $tenant);

        if ($cleanQuery !== []) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . http_build_query($cleanQuery);
        }

        return $url;
    }

    public function getLayer(): string
    {
        return $this->layer;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function toString(): string
    {
        return sprintf('%s:%s:%s:%d', $this->layer, $this->channel, $this->entityType, $this->id);
    }
}
