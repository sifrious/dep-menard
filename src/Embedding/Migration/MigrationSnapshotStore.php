<?php

declare(strict_types=1);

namespace Sifrious\Menard\Embedding\Migration;

/**
 * Host applications provide the durable backend; Menard owns the checkpoint
 * format and recovery semantics.
 */
interface MigrationSnapshotStore
{
    public function save(MigrationSnapshot $snapshot): void;

    public function restore(string $migrationId): ?MigrationSnapshot;
}
