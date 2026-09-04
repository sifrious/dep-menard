<?php

declare(strict_types=1);

use Sifrious\Menard\Embedding\Migration\MigrationManifest;
use Sifrious\Menard\Embedding\Migration\MigrationSnapshot;
use Sifrious\Menard\Embedding\Migration\MigrationState;
use Sifrious\Menard\Embedding\RetrievalSpace;

function migration(): MigrationSnapshot
{
    return MigrationSnapshot::begin(
        migrationId: 'migration-42',
        source: new RetrievalSpace('generation-1', profile('sha256:old')),
        target: new RetrievalSpace('generation-2', profile('sha256:new')),
        manifest: new MigrationManifest(['record-3', 'record-1', 'record-2']),
    );
}

it('keeps the source query space active while re-embedding is incomplete', function (): void {
    $migration = migration()->recordCompleted('record-1');

    expect($migration->state)->toBe(MigrationState::Reembedding)
        ->and($migration->querySpace())->toBe($migration->source)
        ->and($migration->manifest->recordIds)->toBe(['record-1', 'record-2', 'record-3'])
        ->and(fn () => $migration->cutOver())
        ->toThrow(DomainException::class, 'cannot cut over');
});

it('records failures and allows a failed record to be retried', function (): void {
    $failed = migration()->recordFailed('record-2', 'provider timeout');
    $retried = $failed->recordCompleted('record-2');

    expect($failed->failures)->toBe(['record-2' => 'provider timeout'])
        ->and($retried->failures)->toBe([])
        ->and($retried->completedRecordIds)->toBe(['record-2']);
});

it('rejects progress absent from the frozen manifest', function (): void {
    expect(fn () => migration()->recordCompleted('unplanned-record'))
        ->toThrow(DomainException::class, 'absent from the migration manifest');
});

it('snapshots and restores complete restartable migration progress', function (): void {
    $checkpoint = migration()
        ->recordCompleted('record-1')
        ->recordFailed('record-2', 'temporary failure');

    $restored = MigrationSnapshot::restore($checkpoint->toJson());

    expect($restored->toArray())->toBe($checkpoint->toArray())
        ->and($restored->manifest->digest())->toBe($checkpoint->manifest->digest())
        ->and($restored->querySpace()->identity())->toBe($checkpoint->source->identity());
});

it('rejects corrupted durable migration snapshots', function (): void {
    $checkpoint = migration()->toJson();
    $corrupted = str_replace('migration-42', 'migration-43', $checkpoint);

    expect(fn () => MigrationSnapshot::restore($corrupted))
        ->toThrow(InvalidArgumentException::class, 'checksum is invalid');
});

it('cuts over only after manifest completeness and retains rollback data', function (): void {
    $ready = migration()
        ->recordCompleted('record-1')
        ->recordCompleted('record-2')
        ->recordCompleted('record-3');

    expect($ready->state)->toBe(MigrationState::Ready);

    $cutover = $ready->cutOver();
    $restored = MigrationSnapshot::restore($cutover->toJson());

    expect($restored->state)->toBe(MigrationState::Cutover)
        ->and($restored->querySpace()->identity())->toBe($restored->target->identity())
        ->and($restored->source->identity())->toBe($ready->source->identity());

    $rolledBack = $restored->rollBack();

    expect($rolledBack->state)->toBe(MigrationState::RolledBack)
        ->and($rolledBack->querySpace()->identity())->toBe($rolledBack->source->identity())
        ->and($rolledBack->target->identity())->toBe($restored->target->identity());
});

it('does not permit rollback before cutover', function (): void {
    expect(fn () => migration()->rollBack())
        ->toThrow(DomainException::class, 'Only a cut-over migration');
});
