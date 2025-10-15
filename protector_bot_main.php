<?php

declare(strict_types=1);

/**
 * Protector Bot - Main Entry Point
 * Anti-vandalism bot that monitors recent changes and reverts vandalism
 */

set_time_limit(300); // 5 minutes for monitoring

require_once 'setup.php';
require_once 'ProtectorBot.php';

// Check for command line arguments
$monitorLimit = 50;
$patrolledOnly = false;
$continuous = false;

if (isset($argv)) {
    foreach ($argv as $arg) {
        if (strpos($arg, '--limit=') === 0) {
            $monitorLimit = (int) substr($arg, 8);
        } elseif ($arg === '--patrolled-only') {
            $patrolledOnly = true;
        } elseif ($arg === '--continuous') {
            $continuous = true;
        } elseif ($arg === '--help') {
            echo "Protector Bot - Anti-Vandalism Bot for Wikipedia\n\n";
            echo "Usage: php protector_bot_main.php [options]\n\n";
            echo "Options:\n";
            echo "  --limit=N           Number of recent changes to check (default: 50)\n";
            echo "  --patrolled-only    Only check patrolled edits\n";
            echo "  --continuous        Run continuously (monitoring mode)\n";
            echo "  --help              Show this help message\n\n";
            exit(0);
        }
    }
}

// Initialize bot
WikipediaBot::make_ch();
$wikiBot = new WikipediaBot();
$protectorBot = new ProtectorBot($wikiBot);

if (HTML_OUTPUT) {
    bot_html_header();
    report_info("<h1>Protector Bot - Anti-Vandalism Monitor</h1>");
}

report_info("Protector Bot starting...");
report_info("Monitor limit: $monitorLimit");
report_info("Patrolled only: " . ($patrolledOnly ? "Yes" : "No"));
report_info("Continuous mode: " . ($continuous ? "Yes" : "No"));

do {
    try {
        // Monitor recent changes
        $results = $protectorBot->monitorRecentChanges($monitorLimit, $patrolledOnly);
        
        report_info("\n=== Monitoring Results ===");
        report_info("Checked: {$results['checked']} edits");
        report_info("Vandalism detected: {$results['vandalism_detected']} edits");
        report_info("Users flagged: {$results['users_flagged']} users");
        
        // Display statistics
        $stats = $protectorBot->getStatistics();
        if ($stats['total_users'] > 0) {
            report_info("\n=== Detailed Statistics ===");
            report_info("Total users with vandalism: {$stats['total_users']}");
            report_info("Total vandalism instances: {$stats['total_vandalism']}");
            
            foreach ($stats['users_data'] as $user => $data) {
                report_info("  User '$user': {$data['vandalism_count']} vandalism / {$data['edit_count']} total edits");
            }
        }
        
        if ($continuous) {
            report_info("\nWaiting 60 seconds before next check...");
            sleep(60);
        }
        
    } catch (Exception $e) {
        report_error("Error during monitoring: " . $e->getMessage());
        if ($continuous) {
            report_info("Waiting 120 seconds before retry...");
            sleep(120);
        }
    }
} while ($continuous);

report_info("\nProtector Bot finished.");

if (HTML_OUTPUT) {
    bot_html_footer();
}
