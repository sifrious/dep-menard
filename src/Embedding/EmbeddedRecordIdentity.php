<?php

declare(strict_types=1);

namespace Sifrious\Menard\Embedding;

use InvalidArgumentException;

final readonly class EmbeddedRecordIdentity
{
    public function __construct(
        public string $recordId,
        public string $sourceReference,
        public string $chunkReference,
        public RetrievalSpace $space,
    ) {
        foreach ([
            'record ID' => $recordId,
            'source reference' => $sourceReference,
            'chunk reference' => $chunkReference,
        ] as $field => $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException("Embedded record {$field} cannot be empty.");
            }
        }
    }

    /**
     * Full metadata is intentionally repeated on every record so compatibility
     * never depends on mutable application configuration.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'record_id' => $this->recordId,
            'source_reference' => $this->sourceReference,
            'chunk_reference' => $this->chunkReference,
            'retrieval_space' => $this->space->toArray(),
            'retrieval_space_identity' => $this->space->identity(),
            'embedding_profile' => $this->space->profile->toArray(),
            'embedding_profile_identity' => $this->space->profile->identity(),
        ];
    }
}
