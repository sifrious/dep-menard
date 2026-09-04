<?php

declare(strict_types=1);

namespace Sifrious\Menard\Embedding\Migration;

enum MigrationState: string
{
    case Reembedding = 'reembedding';
    case Ready = 'ready';
    case Cutover = 'cutover';
    case RolledBack = 'rolled_back';
}
