<?php
/**
 * SmartBulk — AIResponse value object
 *
 * @author    marcingajewski.pl
 * @copyright 2026 marcingajewski.pl
 * @license   https://opensource.org/licenses/AFL-3.0 AFL-3.0
 */

declare(strict_types=1);

namespace SmartBulk\Service\Ai;

final class AIResponse
{
    public function __construct(
        public readonly string $content,
        public readonly int $tokensIn,
        public readonly int $tokensOut,
        public readonly float $costUsd,
        public readonly int $latencyMs,
        public readonly string $model,
        public readonly string $provider,
        public readonly ?string $stopReason = null,
    ) {
    }

    /**
     * Why this response must not be queued for accept, or null when it's usable.
     *
     * Covers both providers' stop reasons: a refusal (Claude `refusal`,
     * OpenAI `content_filter`), output cut off at the token limit (Claude
     * `max_tokens`, OpenAI `length`) — with thinking on, the whole limit can
     * go to thinking and leave no text — and an empty reply for any other reason.
     */
    public function unusableReason(): ?string
    {
        $stop = $this->stopReason ?? 'unknown';
        $empty = trim($this->content) === '';

        if (in_array($stop, ['refusal', 'content_filter'], true)) {
            return "The model declined to answer (stop_reason: {$stop}). Nothing was queued.";
        }
        if (in_array($stop, ['max_tokens', 'length'], true)) {
            return $empty
                ? "The model hit the Max tokens limit before writing any text (stop_reason: {$stop}). "
                    . 'Thinking tokens count toward the limit — raise Max tokens in the prompt, or lower effort / switch thinking off.'
                : "The output was cut off at the Max tokens limit (stop_reason: {$stop}); the partial text was not queued. "
                    . 'Raise Max tokens in the prompt.';
        }
        if ($empty) {
            return "The model returned no text (stop_reason: {$stop}). Nothing was queued.";
        }
        return null;
    }
}
