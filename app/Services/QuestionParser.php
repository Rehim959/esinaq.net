<?php

declare(strict_types=1);

namespace App\Services;

final class QuestionParser
{
    /**
     * Parse copy-paste block into questions.
     *
     * Expected format:
     * 1. Sual mətni?
     * A) variant
     * B) variant
     * C) variant
     * D) variant
     * E) variant
     * +C
     *
     * Or: +C) variant  (correct marked with +)
     *
     * @return array<int, array{question_text:string,option_a:string,option_b:string,option_c:string,option_d:string,option_e:?string,correct_option:string}>
     */
    public function parse(string $raw): array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", trim($raw));
        if ($raw === '') {
            return [];
        }

        // Split by blank lines or numbered questions
        $blocks = preg_split('/\n\s*\n+/', $raw) ?: [];
        $questions = [];

        foreach ($blocks as $block) {
            $parsed = $this->parseBlock(trim($block));
            if ($parsed !== null) {
                $questions[] = $parsed;
            }
        }

        // If no blank-line blocks worked, try whole text as multi-question stream
        if ($questions === []) {
            $parts = preg_split('/(?=^\s*\d+[\.\)\-]\s+)/m', $raw) ?: [];
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part === '') {
                    continue;
                }
                $parsed = $this->parseBlock($part);
                if ($parsed !== null) {
                    $questions[] = $parsed;
                }
            }
        }

        return $questions;
    }

    private function parseBlock(string $block): ?array
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $block)), fn ($l) => $l !== ''));
        if (count($lines) < 3) {
            return null;
        }

        $correct = null;
        $options = [];
        $questionLines = [];

        foreach ($lines as $line) {
            // +C or Cavab: C or Düzgün: C
            if (preg_match('/^\+([A-Ea-e])\s*$/u', $line, $m)) {
                $correct = strtoupper($m[1]);
                continue;
            }
            if (preg_match('/^(?:cavab|düzgün|duzgun|correct)\s*[:\-]\s*([A-Ea-e])\s*$/iu', $line, $m)) {
                $correct = strtoupper($m[1]);
                continue;
            }

            // +A) text  or  A+) text
            if (preg_match('/^\+?\s*([A-Ea-e])\s*\+?\s*[\)\.\:\-]\s*(.+)$/u', $line, $m)) {
                $letter = strtoupper($m[1]);
                $text = trim($m[2]);
                if (str_starts_with(ltrim($line), '+') || str_contains(substr($line, 0, 4), '+')) {
                    $correct = $letter;
                    $text = ltrim($text, '+ ');
                }
                $options[$letter] = $text;
                continue;
            }

            // Numbered question start
            if (preg_match('/^\d+[\.\)\-]\s*(.+)$/u', $line, $m)) {
                $questionLines[] = $m[1];
                continue;
            }

            if ($options === []) {
                $questionLines[] = $line;
            }
        }

        if ($questionLines === [] || !isset($options['A'], $options['B'], $options['C'], $options['D'])) {
            return null;
        }

        if ($correct === null) {
            return null;
        }

        if (!isset($options[$correct])) {
            return null;
        }

        return [
            'question_text' => implode(' ', $questionLines),
            'option_a' => $options['A'],
            'option_b' => $options['B'],
            'option_c' => $options['C'],
            'option_d' => $options['D'],
            'option_e' => $options['E'] ?? null,
            'correct_option' => $correct,
        ];
    }
}
