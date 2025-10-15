<?php

declare(strict_types=1);

require_once __DIR__ . '/../testBaseClass.php';
require_once __DIR__ . '/../../VandalismDetector.php';

/**
 * Tests for VandalismDetector
 */
final class VandalismDetectorTest extends testBaseClass {

    public function testDetectRudeWords(): void {
        $text = "This article is fucking stupid";
        $result = VandalismDetector::detectVandalism($text);
        
        $this->assertTrue($result['is_vandalism']);
        $this->assertGreaterThan(0.5, $result['confidence']);
        $this->assertNotEmpty($result['reasons']);
    }

    public function testDetectHumorIndicators(): void {
        $text = "lol this is hilarious haha";
        $result = VandalismDetector::detectVandalism($text);
        
        $this->assertTrue($result['is_vandalism']);
        $this->assertGreaterThan(0.5, $result['confidence']);
    }

    public function testDetectVandalismKeywords(): void {
        $text = "This person sucks and is stupid";
        $result = VandalismDetector::detectVandalism($text);
        
        $this->assertTrue($result['is_vandalism']);
        $this->assertGreaterThan(0.5, $result['confidence']);
    }

    public function testDetectAllCaps(): void {
        $text = "THIS IS ALL CAPS SCREAMING!!!";
        $result = VandalismDetector::detectVandalism($text);
        
        $this->assertTrue($result['is_vandalism']);
        $this->assertGreaterThan(0, $result['confidence']);
    }

    public function testDetectRepeatedCharacters(): void {
        $text = "aaaaaaaaaaaaaaaa";
        $result = VandalismDetector::detectVandalism($text);
        
        // Repeated characters should be detected with some confidence
        $this->assertGreaterThan(0.3, $result['confidence']);
    }

    public function testDetectGibberish(): void {
        $text = "asdfghjkl qwertyzxcvbn";
        $result = VandalismDetector::detectVandalism($text);
        
        // Gibberish should have some confidence
        $this->assertGreaterThan(0, $result['confidence']);
    }

    public function testLegitimateTextNotFlagged(): void {
        $text = "This is a legitimate encyclopedic article about history and science.";
        $result = VandalismDetector::detectVandalism($text);
        
        $this->assertFalse($result['is_vandalism']);
        $this->assertLessThan(0.5, $result['confidence']);
    }

    public function testMultipleVandalismIndicators(): void {
        $text = "lol this fucking article sucks!!!";
        $result = VandalismDetector::detectVandalism($text);
        
        $this->assertTrue($result['is_vandalism']);
        $this->assertGreaterThan(0.7, $result['confidence']);
        $this->assertGreaterThanOrEqual(2, count($result['reasons']));
    }

    public function testSuspiciousSizeReduction(): void {
        $oldSize = 5000;
        $newSize = 100;
        
        $suspicious = VandalismDetector::isSuspiciousBySize($oldSize, $newSize);
        $this->assertTrue($suspicious);
    }

    public function testNormalSizeChange(): void {
        $oldSize = 5000;
        $newSize = 5100;
        
        $suspicious = VandalismDetector::isSuspiciousBySize($oldSize, $newSize);
        $this->assertFalse($suspicious);
    }

    public function testLargeContentRemoval(): void {
        $oldSize = 10000;
        $newSize = 8500;
        
        $suspicious = VandalismDetector::isSuspiciousBySize($oldSize, $newSize);
        $this->assertTrue($suspicious);
    }

    public function testExcessivePunctuation(): void {
        $text = "What the hell!!! This is crazy!?!?!?";
        $result = VandalismDetector::detectVandalism($text);
        
        $this->assertGreaterThan(0, $result['confidence']);
    }

    public function testEmailAddress(): void {
        $text = "Contact me at spam@example.com for more info";
        $result = VandalismDetector::detectVandalism($text);
        
        $this->assertGreaterThan(0, $result['confidence']);
    }

    public function testChildishTerms(): void {
        $text = "poop and fart jokes everywhere";
        $result = VandalismDetector::detectVandalism($text);
        
        $this->assertTrue($result['is_vandalism']);
        $this->assertGreaterThan(0.5, $result['confidence']);
    }

    public function testEmptyText(): void {
        $text = "";
        $result = VandalismDetector::detectVandalism($text);
        
        $this->assertFalse($result['is_vandalism']);
        $this->assertEquals(0.0, $result['confidence']);
    }

    public function testURLSpam(): void {
        $text = "Check out http://spam1.com and http://spam2.com and http://spam3.com and http://spam4.com and http://spam5.com and http://spam6.com";
        $result = VandalismDetector::detectVandalism($text);
        
        $this->assertGreaterThan(0, $result['confidence']);
    }
}
