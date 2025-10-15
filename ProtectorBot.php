<?php

declare(strict_types=1);

require_once 'WikipediaBot.php';
require_once 'VandalismDetector.php';
require_once 'user_messages.php';
require_once 'constants.php';

/**
 * ProtectorBot - Anti-vandalism bot for Wikipedia
 * Monitors recent changes and automatically reverts vandalism
 */
class ProtectorBot {
    private WikipediaBot $bot;
    /** @var array<string, array<int>> */
    private array $userVandalismCount = [];
    /** @var array<string, array<array{revid: int, page: string, timestamp: string}>> */
    private array $userEdits = [];
    private const MAX_VANDALISM_THRESHOLD = 1; // Revert all edits if even 1 is vandalism
    
    public function __construct(WikipediaBot $bot) {
        $this->bot = $bot;
    }

    /**
     * Monitor recent changes and check for vandalism
     * 
     * @param int $limit Number of recent changes to check
     * @param bool $patrolledOnly Only check patrolled edits
     * @return array{checked: int, vandalism_detected: int, users_flagged: int}
     */
    public function monitorRecentChanges(int $limit = 50, bool $patrolledOnly = false): array {
        report_info("Starting to monitor recent changes (limit: $limit)...");
        
        $params = [
            'action' => 'query',
            'list' => 'recentchanges',
            'rcprop' => 'user|comment|timestamp|title|ids|sizes|flags|tags',
            'rclimit' => (string) $limit,
            'rctype' => 'edit',
            'rcnamespace' => '0', // Main namespace only
        ];
        
        if ($patrolledOnly) {
            $params['rcshow'] = 'patrolled';
        }
        
        $response = $this->queryAPI($params);
        
        if (!isset($response->query->recentchanges)) {
            report_warning("Failed to retrieve recent changes");
            return ['checked' => 0, 'vandalism_detected' => 0, 'users_flagged' => 0];
        }
        
        $changes = $response->query->recentchanges;
        $checked = 0;
        $vandalismDetected = 0;
        
        foreach ($changes as $change) {
            $checked++;
            
            // Check if this edit is flagged by patrol system
            $hasPatrolFlag = isset($change->tags) && 
                           (in_array('mw-rollback', $change->tags, true) || 
                            in_array('mw-undo', $change->tags, true));
            
            // Get the diff and check for vandalism
            $isVandalism = $this->checkEditForVandalism($change, $hasPatrolFlag);
            
            if ($isVandalism) {
                $vandalismDetected++;
                $user = (string) $change->user;
                
                // Track vandalism by user
                if (!isset($this->userVandalismCount[$user])) {
                    $this->userVandalismCount[$user] = [];
                }
                $this->userVandalismCount[$user][] = (int) $change->revid;
                
                // Track all edits by this user
                if (!isset($this->userEdits[$user])) {
                    $this->userEdits[$user] = [];
                }
                $this->userEdits[$user][] = [
                    'revid' => (int) $change->revid,
                    'page' => (string) $change->title,
                    'timestamp' => (string) $change->timestamp
                ];
                
                report_warning("Vandalism detected by user '$user' on page '{$change->title}' (revid: {$change->revid})");
                
                // If user has reached threshold, revert all their edits
                if (count($this->userVandalismCount[$user]) >= self::MAX_VANDALISM_THRESHOLD) {
                    $this->revertAllUserEdits($user);
                }
            }
        }
        
        return [
            'checked' => $checked,
            'vandalism_detected' => $vandalismDetected,
            'users_flagged' => count($this->userVandalismCount)
        ];
    }

    /**
     * Check a single edit for vandalism
     */
    private function checkEditForVandalism(object $change, bool $hasPatrolFlag): bool {
        // If already flagged by patrol system, increase confidence
        $patrolFlagBonus = $hasPatrolFlag ? 0.3 : 0.0;
        
        // Get the diff for this revision
        $diff = $this->getRevisionDiff((int) $change->revid);
        
        if ($diff === null) {
            return false;
        }
        
        // Extract added text from diff
        $addedText = $this->extractAddedText($diff);
        
        // Get full page text to check context
        $pageText = WikipediaBot::GetAPage((string) $change->title);
        
        // Run vandalism detection
        $result = VandalismDetector::detectVandalism($pageText, $addedText);
        
        // Adjust confidence with patrol flag bonus
        $finalConfidence = min($result['confidence'] + $patrolFlagBonus, 1.0);
        
        if ($result['is_vandalism'] || $finalConfidence > 0.5) {
            report_info("Detected vandalism (confidence: " . round($finalConfidence * 100) . "%): " . 
                       implode('; ', $result['reasons']));
            return true;
        }
        
        // Also check by size
        $oldSize = isset($change->oldlen) ? (int) $change->oldlen : 0;
        $newSize = isset($change->newlen) ? (int) $change->newlen : 0;
        if (VandalismDetector::isSuspiciousBySize($oldSize, $newSize)) {
            report_info("Detected suspicious size change");
            return true;
        }
        
        return false;
    }

    /**
     * Get the diff for a revision
     */
    private function getRevisionDiff(int $revid): ?object {
        $params = [
            'action' => 'query',
            'prop' => 'revisions',
            'revids' => (string) $revid,
            'rvprop' => 'content|ids',
            'rvdiffto' => 'prev'
        ];
        
        $response = $this->queryAPI($params);
        
        if (!isset($response->query->pages)) {
            return null;
        }
        
        $pages = (array) $response->query->pages;
        $page = reset($pages);
        
        if (isset($page->revisions[0])) {
            return $page->revisions[0];
        }
        
        return null;
    }

    /**
     * Extract added text from diff
     */
    private function extractAddedText(object $revision): string {
        // Simple extraction - look for additions in diff
        // In a real implementation, this would parse the diff format properly
        if (isset($revision->{'*'})) {
            return (string) $revision->{'*'};
        }
        return '';
    }

    /**
     * Revert all edits by a user
     */
    private function revertAllUserEdits(string $user): void {
        if (!isset($this->userEdits[$user])) {
            return;
        }
        
        report_warning("Reverting ALL edits by user '$user' due to vandalism threshold reached");
        
        $edits = $this->userEdits[$user];
        $revertedCount = 0;
        
        foreach ($edits as $edit) {
            $success = $this->revertEdit($edit['page'], $edit['revid'], $user);
            if ($success) {
                $revertedCount++;
            }
        }
        
        report_info("Reverted $revertedCount edits by user '$user'");
        
        // Report to user's talk page
        $this->reportToUserTalk($user, $revertedCount);
    }

    /**
     * Revert a specific edit
     */
    private function revertEdit(string $page, int $revid, string $user): bool {
        report_info("Reverting edit $revid on page '$page'");
        
        // Get the previous revision
        $params = [
            'action' => 'query',
            'prop' => 'revisions',
            'titles' => $page,
            'rvprop' => 'content|ids',
            'rvlimit' => '2',
            'rvstartid' => (string) $revid
        ];
        
        $response = $this->queryAPI($params);
        
        if (!isset($response->query->pages)) {
            report_warning("Could not get page content for revert");
            return false;
        }
        
        $pages = (array) $response->query->pages;
        $pageData = reset($pages);
        
        if (!isset($pageData->revisions[1]->{'*'})) {
            report_warning("Could not get previous revision content");
            return false;
        }
        
        $previousContent = (string) $pageData->revisions[1]->{'*'};
        
        // Write the reverted content
        $editSummary = "Reverting vandalism by [[User:$user|$user]] (Protector Bot)";
        $lastRevId = isset($pageData->lastrevid) ? (int) $pageData->lastrevid : 0;
        
        return $this->bot->write_page($page, $previousContent, $editSummary, $lastRevId, '');
    }

    /**
     * Report vandalism to user's talk page
     */
    private function reportToUserTalk(string $user, int $editCount): void {
        $talkPage = "User talk:$user";
        $message = "\n\n== Vandalism Warning ==\n\n" .
                   "Your recent edits have been identified as vandalism by [[User:Protector Bot|Protector Bot]]. " .
                   "$editCount edit(s) have been reverted. Please review [[Wikipedia:Vandalism|Wikipedia's policy on vandalism]]. " .
                   "Continued vandalism may result in a block from editing. ~~~~\n";
        
        report_info("Posting warning to $talkPage");
        
        // Get current talk page content
        $currentContent = WikipediaBot::GetAPage($talkPage);
        $newContent = $currentContent . $message;
        
        $lastRevId = (int) WikipediaBot::get_last_revision($talkPage);
        $this->bot->write_page($talkPage, $newContent, "Warning about vandalism (Protector Bot)", $lastRevId, '');
    }

    /**
     * Query Wikipedia API
     */
    private function queryAPI(array $params): ?object {
        $params['format'] = 'json';
        
        $ch = bot_curl_init(1.0, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_URL => API_ROOT,
        ]);
        
        $data = @curl_exec($ch);
        curl_close($ch);
        
        if ($data === false) {
            return null;
        }
        
        return @json_decode((string) $data);
    }

    /**
     * Get statistics about detected vandalism
     * @return array{total_users: int, total_vandalism: int, users_data: array<string, array{vandalism_count: int, edit_count: int}>}
     */
    public function getStatistics(): array {
        $usersData = [];
        
        foreach ($this->userVandalismCount as $user => $vandalismRevids) {
            $usersData[$user] = [
                'vandalism_count' => count($vandalismRevids),
                'edit_count' => isset($this->userEdits[$user]) ? count($this->userEdits[$user]) : 0
            ];
        }
        
        return [
            'total_users' => count($this->userVandalismCount),
            'total_vandalism' => array_sum(array_map('count', $this->userVandalismCount)),
            'users_data' => $usersData
        ];
    }
}
