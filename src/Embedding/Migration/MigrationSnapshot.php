<?php

declare(strict_types=1);

namespace Sifrious\Menard\Embedding\Migration;

use DomainException;
use InvalidArgumentException;
use Sifrious\Menard\Embedding\RetrievalSpace;

final readonly class MigrationSnapshot
{
    /**
     * @param  list<string>  $completedRecordIds
     * @param  array<string, string>  $failures
     */
    private function __construct(
        public string $migrationId,
        public RetrievalSpace $source,
        public RetrievalSpace $target,
        public MigrationManifest $manifest,
        public array $completedRecordIds,
        public array $failures,
        public MigrationState $state,
        public string $activeSpaceIdentity,
    ) {}

    public static function begin(
        string $migrationId,
        RetrievalSpace $source,
        RetrievalSpace $target,
        MigrationManifest $manifest,
    ): self {
        if (trim($migrationId) === '') {
            throw new InvalidArgumentException('Migration ID cannot be empty.');
        }

        if (hash_equals($source->identity(), $target->identity())) {
            throw new InvalidArgumentException('Migration source and target spaces must be distinct.');
        }

        return new self(
            migrationId: $migrationId,
            source: $source,
            target: $target,
            manifest: $manifest,
            completedRecordIds: [],
            failures: [],
            state: MigrationState::Reembedding,
            activeSpaceIdentity: $source->identity(),
        );
    }

    public function recordCompleted(string $recordId): self
    {
        $this->assertReembedding();
        $this->assertManifestRecord($recordId);

        $completed = array_values(array_unique([...$this->completedRecordIds, $recordId]));
        sort($completed);
        $failures = $this->failures;
        unset($failures[$recordId]);

        $state = $failures === [] && $completed === $this->manifest->recordIds
            ? MigrationState::Ready
            : MigrationState::Reembedding;

        return $this->withProgress($completed, $failures, $state);
    }

    public function recordFailed(string $recordId, string $reason): self
    {
        $this->assertReembedding();
        $this->assertManifestRecord($recordId);

        if (trim($reason) === '') {
            throw new InvalidArgumentException('Migration failure reason cannot be empty.');
        }

        $completed = array_values(array_diff($this->completedRecordIds, [$recordId]));
        $failures = [...$this->failures, $recordId => $reason];
        ksort($failures);

        return $this->withProgress($completed, $failures, MigrationState::Reembedding);
    }

    public function cutOver(): self
    {
        if ($this->state !== MigrationState::Ready || $this->failures !== []) {
            throw new DomainException('Migration cannot cut over until re-embedding succeeds.');
        }

        $this->manifest->assertComplete($this->completedRecordIds);

        return new self(
            migrationId: $this->migrationId,
            source: $this->source,
            target: $this->target,
            manifest: $this->manifest,
            completedRecordIds: $this->completedRecordIds,
            failures: [],
            state: MigrationState::Cutover,
            activeSpaceIdentity: $this->target->identity(),
        );
    }

    public function rollBack(): self
    {
        if ($this->state !== MigrationState::Cutover) {
            throw new DomainException('Only a cut-over migration can be rolled back.');
        }

        return new self(
            migrationId: $this->migrationId,
            source: $this->source,
            target: $this->target,
            manifest: $this->manifest,
            completedRecordIds: $this->completedRecordIds,
            failures: $this->failures,
            state: MigrationState::RolledBack,
            activeSpaceIdentity: $this->source->identity(),
        );
    }

    public function querySpace(): RetrievalSpace
    {
        if (hash_equals($this->activeSpaceIdentity, $this->source->identity())) {
            return $this->source;
        }

        if (hash_equals($this->activeSpaceIdentity, $this->target->identity())) {
            return $this->target;
        }

        throw new DomainException('Migration snapshot does not identify one known active query space.');
    }

    /**
     * Serialize a checksummed, complete checkpoint suitable for durable storage.
     */
    public function toJson(): string
    {
        $payload = $this->toArray();
        $encodedPayload = json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);

        return json_encode([
            'schema_version' => 1,
            'payload' => $payload,
            'checksum' => hash('sha256', $encodedPayload),
        ], JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
    }

    public static function restore(string $json): self
    {
        /** @var mixed $checkpoint */
        $checkpoint = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($checkpoint)
            || ($checkpoint['schema_version'] ?? null) !== 1
            || ! is_array($checkpoint['payload'] ?? null)
            || ! is_string($checkpoint['checksum'] ?? null)
        ) {
            throw new InvalidArgumentException('Migration checkpoint has an unsupported shape.');
        }

        $encodedPayload = json_encode(
            $checkpoint['payload'],
            JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION,
        );

        if (! hash_equals($checkpoint['checksum'], hash('sha256', $encodedPayload))) {
            throw new InvalidArgumentException('Migration checkpoint checksum is invalid.');
        }

        /** @var array<string, mixed> $payload */
        $payload = $checkpoint['payload'];

        /** @var array{generation: string, identity?: string, profile: array<string, mixed>} $source */
        $source = $payload['source'];
        /** @var array{generation: string, identity?: string, profile: array<string, mixed>} $target */
        $target = $payload['target'];
        /** @var array{record_ids: list<string>, digest?: string} $manifest */
        $manifest = $payload['manifest'];
        /** @var list<string> $completed */
        $completed = $payload['completed_record_ids'];
        /** @var array<string, string> $failures */
        $failures = $payload['failures'];

        $restored = new self(
            migrationId: (string) $payload['migration_id'],
            source: RetrievalSpace::fromArray($source),
            target: RetrievalSpace::fromArray($target),
            manifest: MigrationManifest::fromArray($manifest),
            completedRecordIds: $completed,
            failures: $failures,
            state: MigrationState::from((string) $payload['state']),
            activeSpaceIdentity: (string) $payload['active_space_identity'],
        );

        $restored->assertRestoredInvariants();

        return $restored;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'migration_id' => $this->migrationId,
            'source' => $this->source->toArray(),
            'target' => $this->target->toArray(),
            'manifest' => $this->manifest->toArray(),
            'completed_record_ids' => $this->completedRecordIds,
            'failures' => $this->failures,
            'state' => $this->state->value,
            'active_space_identity' => $this->activeSpaceIdentity,
        ];
    }

    private function assertReembedding(): void
    {
        if (! in_array($this->state, [MigrationState::Reembedding, MigrationState::Ready], true)) {
            throw new DomainException('A completed migration cannot accept re-embedding progress.');
        }
    }

    private function assertManifestRecord(string $recordId): void
    {
        if (! $this->manifest->contains($recordId)) {
            throw new DomainException("Record {$recordId} is absent from the migration manifest.");
        }
    }

    /**
     * @param  list<string>  $completed
     * @param  array<string, string>  $failures
     */
    private function withProgress(array $completed, array $failures, MigrationState $state): self
    {
        return new self(
            migrationId: $this->migrationId,
            source: $this->source,
            target: $this->target,
            manifest: $this->manifest,
            completedRecordIds: $completed,
            failures: $failures,
            state: $state,
            activeSpaceIdentity: $this->source->identity(),
        );
    }

    private function assertRestoredInvariants(): void
    {
        foreach ([...$this->completedRecordIds, ...array_keys($this->failures)] as $recordId) {
            $this->assertManifestRecord($recordId);
        }

        if (array_intersect($this->completedRecordIds, array_keys($this->failures)) !== []) {
            throw new InvalidArgumentException('A migration record cannot be both complete and failed.');
        }

        if ($this->state === MigrationState::Ready || $this->state === MigrationState::Cutover) {
            $this->manifest->assertComplete($this->completedRecordIds);
        }

        $expectedActive = $this->state === MigrationState::Cutover
            ? $this->target->identity()
            : $this->source->identity();

        if (! hash_equals($expectedActive, $this->activeSpaceIdentity)) {
            throw new InvalidArgumentException('Migration checkpoint active space conflicts with its state.');
        }
    }
}
