<?php

use Einenlum\CashierPaddleWebhooks\Commands\ListenPaddleWebhooksCommand;

describe('ListenPaddleWebhooksCommand Unit Tests', function () {
    describe('Process handling', function () {
        it('correctly parses tunnelmole output for tunnel URL', function () {
            $command = new ListenPaddleWebhooksCommand();
            
            // Test output that contains tunnel URL
            $tunnelmoleOutput = "tmole: Launching HTTP tunnel...\n" .
                "tmole: Tunnel available at https://abc123.tunnelmole.net\n" .
                "tmole: Remaining time: 23:59:58\n";
            
            // Test the regex pattern directly
            $regex = '/https:\/\/[^\s]+\.tunnelmole\.net/';
            expect(preg_match($regex, $tunnelmoleOutput, $matches))->toBe(1);
            expect($matches[0])->toBe('https://abc123.tunnelmole.net');
        });

        it('extracts tunnel URL with various formats', function () {
            $regex = '/https:\/\/[^\s]+\.tunnelmole\.net/';
            
            // Test different tunnel URL formats
            $testCases = [
                'https://abc123.tunnelmole.net' => 'https://abc123.tunnelmole.net',
                'https://test-123-abc.tunnelmole.net' => 'https://test-123-abc.tunnelmole.net',
                'Output: https://xyz789.tunnelmole.net more text' => 'https://xyz789.tunnelmole.net',
                'Multiple https://first.tunnelmole.net and https://second.tunnelmole.net' => 'https://first.tunnelmole.net',
            ];
            
            foreach ($testCases as $input => $expected) {
                expect(preg_match($regex, $input, $matches))->toBe(1);
                expect($matches[0])->toBe($expected);
            }
        });

        it('handles cases where no tunnel URL is found', function () {
            $regex = '/https:\/\/[^\s]+\.tunnelmole\.net/';
            
            $invalidOutputs = [
                'tmole: Starting tunnel...',
                'Error: Could not establish connection',
                'https://example.com',
                'tunnelmole.net without protocol',
                '',
            ];
            
            foreach ($invalidOutputs as $output) {
                expect(preg_match($regex, $output, $matches))->toBe(0);
            }
        });
    });

    describe('Output cleaning', function () {
        beforeEach(function () {
            $this->command = new ListenPaddleWebhooksCommand();
            
            // Make cleanOutput method accessible
            $reflection = new ReflectionClass($this->command);
            $this->cleanOutputMethod = $reflection->getMethod('cleanOutput');
            $this->cleanOutputMethod->setAccessible(true);
        });

        it('removes remaining time lines', function () {
            $input = "tmole: Starting tunnel...\n" .
                "Remaining time: 23:59:58\n" .
                "tmole: Tunnel ready\n";
            
            $expected = "tmole: Starting tunnel...\ntmole: Tunnel ready";
            
            $result = $this->cleanOutputMethod->invoke($this->command, $input);
            expect($result)->toBe($expected);
        });

        it('cleans multiple spaces and whitespace', function () {
            $input = "  tmole:   Starting    tunnel...  \n" .
                "\t\tTunnel     ready   \n" .
                "   \n";
            
            $expected = "tmole: Starting tunnel...\nTunnel ready";
            
            $result = $this->cleanOutputMethod->invoke($this->command, $input);
            expect($result)->toBe($expected);
        });

        it('removes empty lines', function () {
            $input = "Line 1\n\n\nLine 2\n   \n\t\nLine 3";
            $expected = "Line 1\nLine 2\nLine 3";
            
            $result = $this->cleanOutputMethod->invoke($this->command, $input);
            expect($result)->toBe($expected);
        });

        it('handles empty input', function () {
            expect($this->cleanOutputMethod->invoke($this->command, ''))->toBe('');
            expect($this->cleanOutputMethod->invoke($this->command, '   '))->toBe('');
            expect($this->cleanOutputMethod->invoke($this->command, "\n\n\n"))->toBe('');
        });

        it('processes complex tunnelmole output correctly', function () {
            $input = "tmole: Launching HTTP tunnel...\n" .
                "   \n" .
                "tmole:    Tunnel     available   at    https://abc123.tunnelmole.net   \n" .
                "Remaining time: 23:59:58\n" .
                "\t\n" .
                "tmole:  Ready   for   connections  \n";
            
            $expected = "tmole: Launching HTTP tunnel...\n" .
                "tmole: Tunnel available at https://abc123.tunnelmole.net\n" .
                "tmole: Ready for connections";
            
            $result = $this->cleanOutputMethod->invoke($this->command, $input);
            expect($result)->toBe($expected);
        });

        it('handles multiple remaining time occurrences', function () {
            $input = "Remaining time: 23:59:58\n" .
                "Some output\n" .
                "Remaining time: 23:59:57\n" .
                "More output\n";
            
            $expected = "Some output\nMore output";
            
            $result = $this->cleanOutputMethod->invoke($this->command, $input);
            expect($result)->toBe($expected);
        });
    });
});