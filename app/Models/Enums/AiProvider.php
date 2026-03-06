<?php

namespace App\Models\Enums;

enum AiProvider: string
{
    case OpenAi = 'openai';
    case Anthropic = 'anthropic';
    case Gemini = 'gemini';
    case Groq = 'groq';
    case DeepSeek = 'deepseek';
    case Mistral = 'mistral';
    case XAi = 'xai';
    case OpenRouter = 'openrouter';

    public function label(): string
    {
        return __('enums.ai_provider.'.$this->value);
    }

    /**
     * Default cooldown in seconds when rate limited by this provider.
     * Based on each provider's documented rate limit reset window.
     */
    public function rateLimitCooldownSeconds(): int
    {
        return match ($this) {
            self::Gemini => 60,
            self::OpenAi => 60,
            self::Anthropic => 60,
            self::Groq => 60,
            self::DeepSeek => 30,
            self::Mistral => 60,
            self::XAi => 60,
            self::OpenRouter => 60,
        };
    }

    /**
     * @return string[]
     */
    public function models(): array
    {
        return match ($this) {
            self::OpenAi => ['gpt-4o', 'gpt-4o-mini', 'gpt-4.1', 'gpt-4.1-mini', 'gpt-4.1-nano'],
            self::Anthropic => ['claude-sonnet-4-5-20250514', 'claude-haiku-4-5-20251001'],
            self::Gemini => ['gemini-2.5-flash-lite', 'gemini-2.5-flash', 'gemini-2.5-pro'],
            self::Groq => ['llama-3.3-70b-versatile', 'llama-3.1-8b-instant', 'gemma2-9b-it'],
            self::DeepSeek => ['deepseek-chat', 'deepseek-reasoner'],
            self::Mistral => ['mistral-large-latest', 'mistral-small-latest', 'open-mistral-nemo'],
            self::XAi => ['grok-3', 'grok-3-mini'],
            self::OpenRouter => ['openrouter/auto'],
        };
    }
}
