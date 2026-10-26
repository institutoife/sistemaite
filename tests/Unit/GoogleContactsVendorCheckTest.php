<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class GoogleContactsVendorCheckTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once dirname(__DIR__, 2).'/scripts/hosting/check-existing-vendor.php';
    }

    public function test_matching_existing_packages_support_composer_one_and_two_metadata()
    {
        $lock = ['packages' => [['name' => 'vendor/production', 'version' => 'v1.2.3']],
            'packages-dev' => [['name' => 'vendor/development', 'version' => '2.0.0']]];
        $installed = [['name' => 'vendor/production', 'version' => '1.2.3']];
        $this->assertSame([], \ifeGoogleVendorPackageErrors($lock, $installed));
        $this->assertSame([], \ifeGoogleVendorPackageErrors($lock, ['packages' => $installed, 'dev' => false]));
    }

    public function test_missing_or_different_production_dependencies_block_fallback()
    {
        $lock = ['packages' => [['name' => 'vendor/production', 'version' => '1.2.3']]];
        $this->assertCount(1, \ifeGoogleVendorPackageErrors($lock, ['packages' => []]));
        $this->assertCount(1, \ifeGoogleVendorPackageErrors($lock, ['packages' => [
            ['name' => 'vendor/production', 'version' => '1.2.2'],
        ]]));
    }

    public function test_missing_vendor_is_reported_before_attempting_to_boot_laravel()
    {
        $errors = \ifeGoogleExistingVendorErrors(__DIR__.'/not-an-installed-project');
        $this->assertContains('Falta vendor/autoload.php.', $errors);
        $this->assertContains('Falta composer.lock.', $errors);
    }

    public function test_current_installed_project_can_load_all_release_classes_without_bootstrapping_laravel()
    {
        $this->assertSame([], \ifeGoogleExistingVendorErrors(dirname(__DIR__, 2)));
    }

    public function test_fallback_checks_both_minimum_and_maximum_locked_php_constraints()
    {
        $lock = ['packages' => [['require' => ['php' => '>=8.1 <8.3']]]];
        $extensions = function () { return true; };
        $this->assertSame([], \ifeGoogleVendorPlatformErrors($lock, '8.2.12', $extensions));
        $this->assertCount(1, \ifeGoogleVendorPlatformErrors($lock, '8.3.0', $extensions));
        $this->assertCount(1, \ifeGoogleVendorPlatformErrors($lock, '8.0.30', $extensions));
    }

    public function test_missing_locked_php_extension_is_reported()
    {
        $lock = ['packages' => [['require' => ['ext-curl' => '*']]]];
        $errors = \ifeGoogleVendorPlatformErrors($lock, '8.2.12', function () { return false; });
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('curl', $errors[0]);
    }
}
