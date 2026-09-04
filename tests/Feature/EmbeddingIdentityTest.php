<?php

declare(strict_types=1);

use Sifrious\Menard\Embedding\EmbeddedRecordIdentity;
use Sifrious\Menard\Embedding\EmbeddingPolicy;
use Sifrious\Menard\Embedding\EmbeddingProfile;
use Sifrious\Menard\Embedding\RetrievalSpace;

function profile(
    string $digest = 'sha256:artifact-a',
    int $dimensions = 1536,
    array $normalization = ['l2' => true, 'precision' => 6],
): EmbeddingProfile {
    return new EmbeddingProfile(
        provider: 'example',
        model: 'embed-v1',
        revisionDigest: $digest,
        dimensions: $dimensions,
        normalization: $normalization,
        tokenizer: 'tokenizer-v2',
        preprocessing: 'markdown-v1',
    );
}

it('derives stable identities from the complete immutable profile', function (): void {
    $first = profile(normalization: ['precision' => 6, 'l2' => true]);
    $second = profile(normalization: ['l2' => true, 'precision' => 6]);

    expect($first->identity())->toBe($second->identity())
        ->and(fn () => $first->dimensions = 42)->toThrow(Error::class);
});

it('derives each retrieval-space identity from its generation and profile', function (): void {
    $first = new RetrievalSpace('generation-1', profile());
    $nextGeneration = new RetrievalSpace('generation-2', profile());
    $nextProfile = new RetrievalSpace('generation-1', profile('sha256:artifact-b'));

    expect($first->identity())
        ->not->toBe($nextGeneration->identity())
        ->not->toBe($nextProfile->identity());
});

it('stores full identity metadata on every embedded record', function (): void {
    $space = new RetrievalSpace('generation-1', profile());
    $metadata = (new EmbeddedRecordIdentity(
        recordId: 'passage-7',
        sourceReference: 'document:3',
        chunkReference: 'paragraph:8-10',
        space: $space,
    ))->toArray();

    expect($metadata)
        ->embedding_profile->toBe($space->profile->toArray())
        ->embedding_profile_identity->toBe($space->profile->identity())
        ->retrieval_space_identity->toBe($space->identity())
        ->source_reference->toBe('document:3')
        ->chunk_reference->toBe('paragraph:8-10');
});

it('rejects same-name embeddings with a different artifact digest', function (): void {
    $target = new RetrievalSpace('generation-1', profile('sha256:artifact-a'));
    $record = new EmbeddedRecordIdentity(
        'record-1',
        'source-1',
        'chunk-1',
        new RetrievalSpace('generation-1', profile('sha256:artifact-b')),
    );

    expect(fn () => (new EmbeddingPolicy)->assertWritable($target, $record))
        ->toThrow(DomainException::class, 'not compatible');
});

it('rejects same-model embeddings with different dimensions', function (): void {
    $target = new RetrievalSpace('generation-1', profile(dimensions: 1536));
    $record = new EmbeddedRecordIdentity(
        'record-1',
        'source-1',
        'chunk-1',
        new RetrievalSpace('generation-1', profile(dimensions: 3072)),
    );

    expect(fn () => (new EmbeddingPolicy)->assertWritable($target, $record))
        ->toThrow(DomainException::class, 'not compatible');
});

it('allows writes only when the full retrieval-space identity matches', function (): void {
    $space = new RetrievalSpace('generation-1', profile());
    $record = new EmbeddedRecordIdentity('record-1', 'source-1', 'chunk-1', $space);

    (new EmbeddingPolicy)->assertWritable($space, $record);

    expect(true)->toBeTrue();
});

it('selects exactly one profile-compatible query space', function (): void {
    $wanted = profile();
    $selected = new RetrievalSpace('generation-2', $wanted);
    $other = new RetrievalSpace('generation-1', profile('sha256:old'));
    $policy = new EmbeddingPolicy;

    expect($policy->selectQuerySpace([$other, $selected], $wanted))->toBe($selected)
        ->and(fn () => $policy->selectQuerySpace([$other], $wanted))
        ->toThrow(DomainException::class, 'found 0')
        ->and(fn () => $policy->selectQuerySpace([$selected, $selected], $wanted))
        ->toThrow(DomainException::class, 'found 2');
});
