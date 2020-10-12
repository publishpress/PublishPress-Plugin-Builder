<?php
/**
 * GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package     PublishPressBuilder
 * @author      PublishPress
 * @copyright   Copyright (C) 2020 PublishPress. All rights reserved.
 */

class PluginVersionHandlerCest
{
    public function getPluginVersion_ForPluginFileWithStableVersionNumber_ReturnsVersionNumber(
        UnitTester $I
    ) {
        $I->wantToTest('getPluginVersion for plugin file with stable version number, should return the version number');

        $filePath = __DIR__ . '/../_data/plugin-files/plugin-stable-version.php';

        $utils         = new \PublishPressBuilder\PluginVersionHandler();
        $pluginVersion = $utils->getPluginVersion($filePath);

        $I->assertEquals('2.4.0', $pluginVersion);
    }

    public function getPluginVersion_ForPluginFileWithUnstableVersionNumber_ReturnsVersionNumber(
        UnitTester $I
    ) {
        $I->wantToTest(
            'getPluginVersion for plugin file with non-stable version number, should return the version number'
        );

        $filePath = __DIR__ . '/../_data/plugin-files/plugin-unstable-version.php';

        $utils         = new \PublishPressBuilder\PluginVersionHandler();
        $pluginVersion = $utils->getPluginVersion($filePath);

        $I->assertEquals('2.4.0-beta.1', $pluginVersion);
    }

    /**
     * @example ["0.0.1"]
     * @example ["0.0.14"]
     * @example ["0.1.1"]
     * @example ["0.10.1"]
     * @example ["0.10.13"]
     * @example ["1.0.0"]
     * @example ["1.3.0"]
     * @example ["1.31.10"]
     * @example ["43.31.0"]
     * @example ["43.31.0"]
     * @example ["43.31.0"]
     */
    public function isStableVersion_ForStableVersion_ReturnsTrue(UnitTester $I, \Codeception\Example $example)
    {
        $I->wantToTest(
            'isStableVersion for stable version should return true'
        );

        $versionHandler = new \PublishPressBuilder\PluginVersionHandler();

        $isStable = $versionHandler->isStableVersion($example[0]);

        $I->assertTrue($isStable);
    }

    /**
     * @example ["0.0.1-alpha.1"]
     * @example ["0.0.14-alpha.2"]
     * @example ["0.1.1-beta.1"]
     * @example ["0.10.1-beta.2"]
     * @example ["0.10.13-rc.1"]
     * @example ["1.0.0-rc.2"]
     * @example ["1.3.0-hotfix-114"]
     * @example ["1.31.10-feature-12"]
     * @example ["43.31.0-alpha"]
     * @example ["43.31.0-beta"]
     * @example ["43.31.0-beta.25"]
     */
    public function isStableVersion_ForUnstableVersion_ReturnsFalse(UnitTester $I, \Codeception\Example $example)
    {
        $I->wantToTest(
            'isStableVersion for unstable version should return false'
        );

        $versionHandler = new \PublishPressBuilder\PluginVersionHandler();

        $isStable = $versionHandler->isStableVersion($example[0]);

        $I->assertFalse($isStable);
    }

    /**
     * @example ["/ \\* Version: [0-9]\\.[0-9]\\.[0-9]/", " * Version: 2.4.0", " * Version: 3.0.0"]
     * @example ["/Copyright \\(C\\) 2020/", "Copyright (C) 2020", "Copyright (C) 2045"]
     */
    public function replaceTextInFile_ReplacesTextInAFile(
        UnitTester $I,
        \Codeception\Example $example
    ) {
        $dummyFilePath = __DIR__ . '/../_data/plugin-files/plugin-stable-version.php';
        $tmpFile       = sys_get_temp_dir() . '/' . microtime(true) . '-dummy-plugin-file.php';

        copy($dummyFilePath, $tmpFile);

        $handler = new \PublishPressBuilder\PluginVersionHandler();

        $reflection = new ReflectionClass(\PublishPressBuilder\PluginVersionHandler::class);
        $method     = $reflection->getMethod('replaceTextInFile');
        $method->setAccessible(true);

        $pattern   = $example[0];
        $oldString = $example[1];
        $newString = $example[2];

        $method->invoke($handler, $tmpFile, $pattern, $newString);

        $tmpFileContent = file_get_contents($tmpFile);

        $I->assertStringNotContainsString($oldString, $tmpFileContent);
        $I->assertStringContainsString($newString, $tmpFileContent);
    }

    /**
     * @example ["3.4.0"]
     * @example ["3.4.2"]
     * @example ["3.14.2"]
     * @example ["3.14.52"]
     * @example ["13.14.5"]
     * @example ["3.4.0-alpha.1"]
     * @example ["3.4.0-beta.1"]
     * @example ["3.54.0-beta.1"]
     * @example ["3.54.0-rc.1"]
     * @example ["3.54.0-rc.2"]
     */
    public function updateStableTagInTheReadmeFile_ShouldUpdateTheStableTagInTheReadFile(
        UnitTester $I,
        \Codeception\Example $example
    ) {
        $dummyFilePath = __DIR__ . '/../_data/build-test/readme.txt';
        $tmpDirPath    = sys_get_temp_dir() . '/' . microtime(true);
        $tmpFile       = $tmpDirPath . '/readme.txt';

        mkdir($tmpDirPath, 0644, true);
        copy($dummyFilePath, $tmpFile);

        $handler = new \PublishPressBuilder\PluginVersionHandler();

        $reflection = new ReflectionClass(\PublishPressBuilder\PluginVersionHandler::class);
        $method     = $reflection->getMethod('updateStableTagInTheReadmeFile');
        $method->setAccessible(true);

        $newVersion = $example[0];

        $method->invoke($handler, $tmpDirPath, $newVersion);

        $tmpFileContent = file_get_contents($tmpFile);

        $I->assertStringNotContainsString('Stable tag: 2.4.0', $tmpFileContent);
        $I->assertStringContainsString('Stable tag: ' . $newVersion, $tmpFileContent);
    }

    /**
     * @example ["3.4.0"]
     * @example ["3.4.2"]
     * @example ["3.14.2"]
     * @example ["3.14.52"]
     * @example ["13.14.5"]
     * @example ["3.4.0-alpha.1"]
     * @example ["3.4.0-beta.1"]
     * @example ["3.54.0-beta.1"]
     * @example ["3.54.0-rc.1"]
     * @example ["3.54.0-rc.2"]
     */
    public function updateVersionInThePluginFile_ShouldUpdateTheVersionInThePluginFile(
        UnitTester $I,
        \Codeception\Example $example
    ) {
        $dummyFilePath = __DIR__ . '/../_data/build-test/publishpress-dummy.php';
        $tmpDirPath    = sys_get_temp_dir() . '/' . microtime(true);
        $tmpFile       = $tmpDirPath . '/publishpress-dummy.php';

        mkdir($tmpDirPath);
        copy($dummyFilePath, $tmpFile);

        $handler = new \PublishPressBuilder\PluginVersionHandler();

        $reflection = new ReflectionClass(\PublishPressBuilder\PluginVersionHandler::class);
        $method     = $reflection->getMethod('updateVersionInThePluginFile');
        $method->setAccessible(true);

        $newVersion = $example[0];

        $method->invoke($handler, $tmpDirPath, 'publishpress-dummy', $newVersion);

        $tmpFileContent = file_get_contents($tmpFile);

        $I->assertStringNotContainsString('* Version: 2.4.0', $tmpFileContent);
        $I->assertStringContainsString('* Version: ' . $newVersion, $tmpFileContent);
    }

    /**
     * @example ["3.4.0"]
     * @example ["3.4.2"]
     * @example ["3.14.2"]
     * @example ["3.14.52"]
     * @example ["13.14.5"]
     * @example ["3.4.0-alpha.1"]
     * @example ["3.4.0-beta.1"]
     * @example ["3.54.0-beta.1"]
     * @example ["3.54.0-rc.1"]
     * @example ["3.54.0-rc.2"]
     */
    public function updateVersionInThePluginFile_ShouldUpdateTheVersionInTheDefinesFile_ForNonSpecifiedFileList(
        UnitTester $I,
        \Codeception\Example $example
    ) {
        $dummyFilePath = __DIR__ . '/../_data/build-version-const-test/defines.php';
        $tmpDirPath    = sys_get_temp_dir() . '/' . microtime(true);
        $tmpFile       = $tmpDirPath . '/defines.php';

        mkdir($tmpDirPath);
        copy($dummyFilePath, $tmpFile);

        $handler = new \PublishPressBuilder\PluginVersionHandler();

        $handler->updateVersionInACustomFile($tmpDirPath, 'defines.php', 'PUBLISHPRESS_DUMMY_VERSION', $example[0]);

        $newVersion = $example[0];

        $tmpFileContent = file_get_contents($tmpFile);

        $I->assertStringNotContainsString('define(\'PUBLISHPRESS_DUMMY_VERSION\', \'2.4.0\');', $tmpFileContent);
        $I->assertStringContainsString(sprintf('define(\'PUBLISHPRESS_DUMMY_VERSION\', \'%s\');', $newVersion), $tmpFileContent);
    }
}
