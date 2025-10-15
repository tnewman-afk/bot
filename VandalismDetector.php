<?php

declare(strict_types=1);

/**
 * VandalismDetector class for Protector Bot
 * Detects potential vandalism in Wikipedia edits using multiple heuristics
 */
class VandalismDetector {
    // Rude and offensive words list (basic set)
    private const RUDE_WORDS = [
        'fuck', 'shit', 'damn', 'ass', 'bitch', 'bastard', 'crap', 'piss',
        'dick', 'cock', 'pussy', 'cunt', 'whore', 'slut', 'nigger', 'fag',
        'retard', 'idiot', 'stupid', 'dumb', 'moron', 'imbecile'
    ];

    // Humor and joke indicators
    private const HUMOR_INDICATORS = [
        'lol', 'lmao', 'rofl', 'lmfao', 'haha', 'hehe', 'joke', 'funny',
        'hilarious', 'trolling', 'troll', 'meme', 'your mom', 'ur mom',
        'deez nuts', 'ligma', 'penis', 'poop', 'fart', 'butt'
    ];

    // Common vandalism patterns
    private const VANDALISM_KEYWORDS = [
        'sucks', 'rules', 'is gay', 'is stupid', 'is dumb', 'pwned',
        'owned', 'kilroy was here', 'hello', 'test', 'asdf', 'qwerty',
        'was here', 'hi mom', 'poop', 'penis', 'vagina'
    ];

    // Suspicious patterns (regex)
    private const SUSPICIOUS_PATTERNS = [
        '/^[A-Z\s!]+$/',           // All caps screaming
        '/(.)\1{5,}/',              // Character repeated 6+ times
        '/\d{10,}/',                // Long numbers (phone numbers, etc)
        '/[A-Z]{10,}/',             // Long sequences of capitals
        '/\b(penis|poop|fart|butt|pee)\b/i' // Childish terms
    ];

    /**
     * Check if text contains vandalism
     * 
     * @param string $text The text to check
     * @param string $addedText Text that was added in the edit (for diff analysis)
     * @return array{is_vandalism: bool, reasons: array<string>, confidence: float}
     */
    public static function detectVandalism(string $text, string $addedText = ''): array {
        $reasons = [];
        $score = 0.0;
        
        // Use added text if available, otherwise check full text
        $checkText = $addedText !== '' ? $addedText : $text;
        $checkTextLower = mb_strtolower($checkText);
        
        // Check for rude words
        $rudeWordsFound = self::checkRudeWords($checkTextLower);
        if (count($rudeWordsFound) > 0) {
            $reasons[] = "Contains rude/offensive words: " . implode(', ', array_slice($rudeWordsFound, 0, 3));
            $score += 0.4 * min(count($rudeWordsFound), 3); // Cap at 3 words
        }
        
        // Check for humor indicators
        $humorFound = self::checkHumorIndicators($checkTextLower);
        if (count($humorFound) > 0) {
            $reasons[] = "Contains humor/joke indicators: " . implode(', ', array_slice($humorFound, 0, 3));
            $score += 0.3 * min(count($humorFound), 2);
        }
        
        // Check for vandalism keywords
        $vandalismFound = self::checkVandalismKeywords($checkTextLower);
        if (count($vandalismFound) > 0) {
            $reasons[] = "Contains vandalism keywords: " . implode(', ', array_slice($vandalismFound, 0, 3));
            $score += 0.3 * min(count($vandalismFound), 2);
        }
        
        // Check suspicious patterns
        $patternsMatched = self::checkSuspiciousPatterns($checkText);
        if (count($patternsMatched) > 0) {
            $reasons[] = "Matches suspicious patterns: " . implode(', ', $patternsMatched);
            $score += 0.4 * count($patternsMatched);
        }
        
        // Additional heuristics
        $additionalChecks = self::performAdditionalChecks($checkText, $text);
        if ($additionalChecks['score'] > 0) {
            $reasons = array_merge($reasons, $additionalChecks['reasons']);
            $score += $additionalChecks['score'];
        }
        
        // Cap score at 1.0
        $confidence = min($score, 1.0);
        
        // Consider it vandalism if confidence >= 0.5
        $isVandalism = $confidence >= 0.5;
        
        return [
            'is_vandalism' => $isVandalism,
            'reasons' => $reasons,
            'confidence' => $confidence
        ];
    }

    /**
     * Check for rude words in text
     * @return array<string>
     */
    private static function checkRudeWords(string $text): array {
        $found = [];
        foreach (self::RUDE_WORDS as $word) {
            if (strpos($text, $word) !== false) {
                $found[] = $word;
            }
        }
        return $found;
    }

    /**
     * Check for humor indicators in text
     * @return array<string>
     */
    private static function checkHumorIndicators(string $text): array {
        $found = [];
        foreach (self::HUMOR_INDICATORS as $indicator) {
            if (strpos($text, $indicator) !== false) {
                $found[] = $indicator;
            }
        }
        return $found;
    }

    /**
     * Check for vandalism keywords in text
     * @return array<string>
     */
    private static function checkVandalismKeywords(string $text): array {
        $found = [];
        foreach (self::VANDALISM_KEYWORDS as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $found[] = $keyword;
            }
        }
        return $found;
    }

    /**
     * Check for suspicious patterns using regex
     * @return array<string>
     */
    private static function checkSuspiciousPatterns(string $text): array {
        $matched = [];
        
        foreach (self::SUSPICIOUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $text)) {
                $matched[] = "Pattern: " . $pattern;
            }
        }
        
        return $matched;
    }

    /**
     * Perform additional complex checks
     * @return array{score: float, reasons: array<string>}
     */
    private static function performAdditionalChecks(string $checkText, string $fullText): array {
        $reasons = [];
        $score = 0.0;
        
        // Check for extremely short edits with no substance
        if (strlen(trim($checkText)) < 10 && strlen(trim($checkText)) > 0) {
            if (preg_match('/^[a-z\s]+$/i', trim($checkText))) {
                $reasons[] = "Very short, potentially non-encyclopedic edit";
                $score += 0.2;
            }
        }
        
        // Check for excessive punctuation
        $punctuationCount = preg_match_all('/[!?]{2,}/', $checkText);
        if ($punctuationCount > 0) {
            $reasons[] = "Excessive punctuation usage";
            $score += 0.15;
        }
        
        // Check for URL spam (multiple URLs added)
        $urlCount = preg_match_all('/https?:\/\//', $checkText);
        if ($urlCount > 5) {
            $reasons[] = "Potential URL spam (multiple URLs)";
            $score += 0.3;
        }
        
        // Check for email addresses (potential spam)
        if (preg_match('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $checkText)) {
            $reasons[] = "Contains email address";
            $score += 0.2;
        }
        
        // Check for gibberish (lots of consonants in a row)
        if (preg_match('/[bcdfghjklmnpqrstvwxyz]{7,}/i', $checkText)) {
            $reasons[] = "Possible gibberish text";
            $score += 0.25;
        }
        
        return [
            'score' => $score,
            'reasons' => $reasons
        ];
    }

    /**
     * Check if edit should be flagged based on size
     */
    public static function isSuspiciousBySize(int $oldSize, int $newSize): bool {
        $diff = abs($newSize - $oldSize);
        $percentChange = $oldSize > 0 ? ($diff / $oldSize) * 100 : 100;
        
        // Flag if content is reduced by more than 50% or more than 1000 bytes removed
        if ($newSize < $oldSize && ($percentChange > 50 || $diff > 1000)) {
            return true;
        }
        
        return false;
    }
}
