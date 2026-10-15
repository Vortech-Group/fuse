<?php

declare(strict_types=1);

use Vortech\Fuse\Enums\FuseEditor;
use Vortech\Fuse\Enums\FuseSeverity;
use Vortech\Fuse\Enums\FuseType;
use Vortech\Fuse\Exceptions\InvalidFuseConfiguration;

it('orders severities by weight', function () {
    expect(FuseSeverity::Low->weight())->toBeLessThan(FuseSeverity::Medium->weight())
        ->and(FuseSeverity::Critical->isAtLeast(FuseSeverity::High))->toBeTrue()
        ->and(FuseSeverity::Low->isAtLeast(FuseSeverity::Medium))->toBeFalse()
        ->and(FuseSeverity::High->isAtLeast(FuseSeverity::High))->toBeTrue();
});

it('resolves enums from user input', function () {
    expect(FuseSeverity::fromInput('HIGH'))->toBe(FuseSeverity::High)
        ->and(FuseSeverity::fromInput(FuseSeverity::Low))->toBe(FuseSeverity::Low)
        ->and(FuseType::fromInput('technical-debt'))->toBe(FuseType::TechnicalDebt);
});

it('rejects unknown input with the allowed values', function () {
    FuseSeverity::fromInput('blocker');
})->throws(InvalidFuseConfiguration::class, 'Invalid severity "blocker". Allowed values: low, medium, high, critical.');

it('builds editor links for every editor', function () {
    expect(FuseEditor::PhpStorm->url('/app/My File.php', 7))->toBe('phpstorm://open?file=/app/My%20File.php&line=7')
        ->and(FuseEditor::VsCode->url('/app/A.php', 3))->toBe('vscode://file/app/A.php:3')
        ->and(FuseEditor::Sublime->url('/app/A.php', 0))->toBe('subl://open?url=file:///app/A.php&line=1')
        ->and(FuseEditor::fromInput('PhpStorm'))->toBe(FuseEditor::PhpStorm);

    foreach (FuseEditor::cases() as $editor) {
        expect($editor->url('/app/A.php', 2))->not->toContain(';');
    }
});

it('rejects unknown editors', function () {
    FuseEditor::fromInput('notepad');
})->throws(InvalidFuseConfiguration::class);
