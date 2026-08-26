# Menard

## Why “Menard”?

Menard is named after Pierre Menard, the fictional writer in Jorge Luis Borges’s “Pierre Menard, Author of the Quixote.” Menard does not merely copy an existing text. He approaches it again from another time and context, making the same words newly legible.

That is the idea behind this project. An archive may contain the material needed to answer a question, but retaining material is not the same as making it readable at the moment it matters. Menard prepares large bodies of recorded history for selective rereading.

The name reflects a central constraint: context changes meaning. Useful retrieval must preserve enough origin, sequence, and surrounding material for a passage to be understood—not merely matched.

## What is Menard?

Menard is a retrieval-preparation toolkit for turning large, heterogeneous collections into focused, citable context.

It divides source material into meaningful passages, builds replaceable search indexes, retrieves and reranks relevant evidence, and assembles the strongest available material within a defined context budget. The result is a compact context pack that another program—or a person—can inspect and use without losing the path back to the source.

Menard is intended for project histories, research collections, knowledge bases, conversations, documents, and other archives that are too large or varied to read in full for every question.

## The problems it solves

### An archive can be complete and still be unusable

Preserving every record does not make those records easy to revisit. As a collection grows, the relevant few paragraphs become harder to find, and repeatedly reading the entire archive becomes impossible.

Menard prepares the collection for selective reading: finding the smallest useful body of evidence for the question at hand.

### Search results are not context

A list of matching files or passages leaves important work to the caller. Results may overlap, repeat the same idea, omit necessary neighboring material, or exceed the amount that can be read or supplied to a model.

Menard can rerank, deduplicate, expand, trim, and assemble retrieved passages into a bounded context pack. The output is meant to be used as a coherent reading set rather than a bag of search hits.

### Arbitrary chunks damage meaning

Splitting text every fixed number of characters is easy, but it can separate a claim from its evidence, a decision from its rationale, or a message from the conversation that makes it intelligible.

Menard treats passage boundaries as part of retrieval quality. Chunking strategies can respect the structure of different materials while preserving stable references back to their origin.

### Relevant does not always mean useful

The closest semantic match may be stale, redundant, weakly sourced, or disconnected from the user’s actual task. Keyword search can find exact language while missing related concepts; vector search can find related concepts while missing exact identifiers.

Menard supports multiple retrieval strategies and explicit reranking so that usefulness is evaluated separately from raw similarity.

### Context has a hard budget

People have limited attention, and language models have limited context windows. Simply returning more material can make an answer slower, more expensive, and less grounded.

Menard assembles context against an explicit token budget. It makes inclusion, exclusion, and ordering deliberate so the most valuable evidence fits without silently truncating the result.

### Indexes become stale and implementation-specific

Embeddings, tokenizers, ranking logic, and search engines change. If an index becomes the authoritative record, improving the retrieval system risks changing or losing history.

Menard treats passages and indexes as derived projections. They can be versioned, discarded, and rebuilt from their sources. Applications can improve retrieval without rewriting the material being retrieved.

### Retrieved claims need traceable sources

Context without provenance is difficult to verify. A useful passage needs more than text: it needs a stable identity and enough source information for a reader to inspect the original material.

Menard keeps citations attached throughout passage creation, retrieval, reranking, and context assembly. Every selected passage can retain its route back to the canonical source.

## Core capabilities

Menard is being designed around a small set of composable capabilities:

- Define stable, citable passages from structured and unstructured material.
- Tokenize content consistently and measure it against explicit budgets.
- Build keyword, vector, or hybrid indexes as replaceable projections.
- Retrieve candidates using one or more search strategies.
- Rerank results according to the needs of a particular task.
- Deduplicate overlapping or substantially equivalent passages.
- Expand results with necessary neighboring context.
- Assemble deterministic, token-budgeted context packs.
- Report which passages were selected, excluded, or truncated and why.
- Preserve citations and provenance through every transformation.

## What Menard does not decide

Menard prepares material for reading; it does not decide what the material means.

It does not turn retrieved passages into historical claims, choose which interpretation should be canonical, or rewrite source records. Its responsibility ends with a bounded, inspectable, and reproducible body of context.

Keeping that boundary narrow makes Menard useful anywhere retrieval is needed, regardless of which interface, archive, search engine, or language model surrounds it.

## Design principles

- **Sources remain authoritative.** Passages and indexes are views over source material, not replacements for it.
- **Every passage is citable.** Retrieved text retains a stable route back to its origin.
- **Derived data is replaceable.** Indexes and embeddings can be rebuilt when strategies or models change.
- **Budgets are explicit.** Context selection operates against measured limits rather than accidental truncation.
- **Retrieval is observable.** Callers can inspect candidates, scores, exclusions, and final selection.
- **Strategies are composable.** Keyword, semantic, hybrid, and custom retrieval can share the same passage and result contracts.
- **Determinism is valuable.** The same inputs and configuration should produce the same context pack wherever practical.
- **Structure carries meaning.** Chunking and context expansion respect the form of the source material.
- **The toolkit remains portable.** Core contracts should not require a particular application, database, queue, or model provider.

## Project status

Menard is in early development. Its first milestone is deliberately narrow: define cited passages, build a replaceable index, retrieve relevant passages, and assemble a deterministic context pack within a token budget.

Installation and usage instructions will be added when that first supported release is available.
