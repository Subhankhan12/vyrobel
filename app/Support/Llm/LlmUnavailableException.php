<?php

declare(strict_types=1);

namespace App\Support\Llm;

use App\Services\AskVyrobelService;
use RuntimeException;

/**
 * Thrown when the LLM cannot be reached or refuses (network error, non-2xx, malformed body, missing key).
 * {@see AskVyrobelService} catches it and trips the circuit breaker → an honest fallback to the
 * retrieved help content + the relay. Never surfaces a stack trace or provider detail to the owner.
 */
final class LlmUnavailableException extends RuntimeException {}
