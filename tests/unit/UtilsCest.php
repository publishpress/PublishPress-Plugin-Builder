<?php

class UtilsCest
{
    public function _before(UnitTester $I)
    {
    }

    public function getPluginNameFromComposerFile_ForPathWithComposerFileAndNameWithNamespace_ReturnsCorrectPluginName(
        UnitTester $I
    ) {
        $projectPath = __DIR__ . '/../_data/composer-files/name-with-namespace';

        $utils      = new \PublishPressBuilder\Utils();
        $pluginName = $utils->getPluginNameFromComposerFile($projectPath);

        $I->assertEquals('publishpress-dummy', $pluginName);
    }

    public function getPluginNameFromComposerFile_ForPathWithComposerFileAndNameWithNoNamespace_ReturnsCorrectPluginName(
        UnitTester $I
    ) {
        $projectPath = __DIR__ . '/../_data/composer-files/name-with-no-namespace';

        $utils      = new \PublishPressBuilder\Utils();
        $pluginName = $utils->getPluginNameFromComposerFile($projectPath);

        $I->assertEquals('publishpress-dummy', $pluginName);
    }

    public function getPluginVersionFromPluginFile_ForPluginFileWithStableVersionNumber_ReturnsVersionNumber(
        UnitTester $I
    ) {
        $filePath = __DIR__ . '/../_data/plugin-files/plugin-stable-version.php';

        $utils         = new \PublishPressBuilder\Utils();
        $pluginVersion = $utils->getPluginVersionFromPluginFile($filePath);

        $I->assertEquals('2.0.4', $pluginVersion);
    }

    public function getPluginVersionFromPluginFile_ForPluginFileWithUnstableVersionNumber_ReturnsVersionNumber(
        UnitTester $I
    ) {
        $filePath = __DIR__ . '/../_data/plugin-files/plugin-unstable-version.php';

        $utils         = new \PublishPressBuilder\Utils();
        $pluginVersion = $utils->getPluginVersionFromPluginFile($filePath);

        $I->assertEquals('2.0.4-beta.1', $pluginVersion);
    }
}
