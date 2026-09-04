<?php

declare(strict_types=1);

namespace Sifrious\Menard\Embedding\Migration;

use DomainException;
use InvalidArgumentException;

final readonly class MigrationManifest
{
    /** @var list<string> */
    public array $recordIds;

    /**
     * @param  list<string>  $recordIds
     */
    public function __construct(array $recordIds)
    {
        if ($recordIds === []) {
            throw new InvalidArgumentException('A migration manifest cannot be empty.');
        }

        foreach ($recordIds as $recordId) {
            if (trim($recordId) === '') {
                throw new InvalidArgumentException('Migration manifest record IDs cannot be empty.');
            }
        }

        $recordIds = array_values(array_unique($recordIds));
        sort($recordIds);
        $this->recordIds = $recordIds;
    }

    public function digest(): string
    {
        return hash('sha256', json_encode($this->recordIds, JSON_THROW_ON_ERROR));
    }

    /**
     * @param  list<string>  $completedRecordIds
     */
    public function assertComplete(array $completedRecordIds): void
    {
        $completedRecordIds = array_values(array_unique($completedRecordIds));
        sort($completedRecordIds);

        if ($completedRecordIds !== $this->recordIds) {
            throw new DomainException('Migration cannot cut over before its manifest is complete.');
        }
    }

    public function contains(string $recordId): bool
    {
        return in_array($recordId, $this->recordIds, true);
    }

    /**
     * @return array{record_ids: list<string>, digest: string}
     */
    public function toArray(): array
    {
        return ['record_ids' => $this->recordIds, 'digest' => $this->digest()];
    }

    /**
     * @param  array{record_ids: list<string>, digest?: string}  $manifest
     */
    public static function fromArray(array $manifest): self
    {
        $restored = new self($manifest['record_ids']);

        if (isset($manifest['digest']) && ! hash_equals($manifest['digest'], $restored->digest())) {
            throw new InvalidArgumentException('Migration manifest digest is invalid.');
        }

        return $restored;
    }
}
