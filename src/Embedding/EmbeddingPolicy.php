<?php

declare(strict_types=1);

namespace Sifrious\Menard\Embedding;

use DomainException;

final class EmbeddingPolicy
{
    public function assertWritable(RetrievalSpace $space, EmbeddedRecordIdentity $record): void
    {
        if (! hash_equals($space->identity(), $record->space->identity())) {
            throw new DomainException('The embedded record is not compatible with the target retrieval space.');
        }

        if (! hash_equals($space->profile->identity(), $record->space->profile->identity())) {
            throw new DomainException('The embedded record profile is not compatible with the target retrieval space.');
        }
    }

    /**
     * @param  list<RetrievalSpace>  $spaces
     */
    public function selectQuerySpace(array $spaces, EmbeddingProfile $profile): RetrievalSpace
    {
        $compatible = array_values(array_filter(
            $spaces,
            static fn (RetrievalSpace $space): bool => hash_equals(
                $profile->identity(),
                $space->profile->identity(),
            ),
        ));

        if (count($compatible) !== 1) {
            throw new DomainException(sprintf(
                'A query must select exactly one compatible retrieval space; found %d.',
                count($compatible),
            ));
        }

        return $compatible[0];
    }
}
