<?php

class PackageBuilderTasksCest
{
    private $filesToIgnore = [];

    public function _before(UnitTester $I)
    {
        $this->filesToIgnore = $this->getListOfFilesToIgnore();
    }

    private function getListOfFilesToIgnore(): array
    {
        return file(__DIR__ . '/../../files-to-ignore.txt', FILE_IGNORE_NEW_LINES);
    }

    private function callRoboCommand(
        string $command,
        string $sourcePath,
        string $roboPath = '../../../vendor/bin/robo'
    ): string {
        $pipes  = null;
        $output = '';

        $process = proc_open(
            $roboPath . ' ' . $command,
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            realpath($sourcePath)
        );

        if (is_resource($process)) {
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
        }

        proc_close($process);

        return $output;
    }

    public function testBuildTask_ShouldCreateAZipFileInTheDistDirNamedWithPluginNameAndVersion(UnitTester $I)
    {
        $I->wantToTest(
            'the build task with no custom destination, should create a ZIP file in the ./dist dir with the plugin name and version'
        );

        $sourcePath = __DIR__ . '/../_data/build-test';

        $this->callRoboCommand('build', realpath($sourcePath));

        $I->assertFileExists(
            realpath($sourcePath . '/dist/publishpress-dummy-2.4.0.zip'),
            'There should be a ZIP file in the path ' . $sourcePath
        );
    }

    public function testBuildTask_ShouldCreateZipFileWithNoIgnoredFiles(UnitTester $I)
    {
        $I->wantToTest('the build task with no custom destination, should create a ZIP file without any ignore file');

        $sourcePath = __DIR__ . '/../_data/build-test';

        $this->callRoboCommand('build', $sourcePath);

        $unzippedPath = $sourcePath . '/dist/unzipped';

        $zip = new \PhpZip\ZipFile();
        try {
            if (!file_exists($unzippedPath)) {
                mkdir($unzippedPath);
            }

            $zip->openFile($sourcePath . '/dist/publishpress-dummy-2.4.0.zip');
            $zip->extractTo($unzippedPath);
        } catch (Exception $e) {
            $I->fail($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        } finally {
            $zip->close();
        }

        foreach ($this->filesToIgnore as $fileToIgnore) {
            $filePath = $unzippedPath . '/publishpress-dummy/' . $fileToIgnore;
            $I->assertFileDoesNotExist($filePath, 'The file ' . $filePath . ' should not exist in the package');
        }
    }

    public function testBuildTask_WithCustomDestination_ShouldCreateAZipFileInTheSpecificDirNamedWithPluginNameAndVersion(
        UnitTester $I
    ) {
        $I->wantToTest(
            'the build task with a custom destination, should create a ZIP file in the specific dir with the plugin name and version'
        );

        $sourcePath = __DIR__ . '/../_data/build-move-test';

        if (file_exists($sourcePath . '/../../_output/publishpress-dummy-2.4.0.zip')) {
            unlink($sourcePath . '/../../_output/publishpress-dummy-2.4.0.zip');
        }

        $this->callRoboCommand('build', realpath($sourcePath));

        $I->assertFileExists(
            realpath($sourcePath . '/../../_output/publishpress-dummy-2.4.0.zip'),
            'There should be a zip file in the path ' . $sourcePath
        );
    }

    public function testBuildTask_WithCustomFilesToIgnore_ShouldCreateAZipFileWithoutTheIgnoredFiles(
        UnitTester $I
    ) {
        $I->wantToTest(
            'the build task with a custom list of files to ignore, should create a ZIP file without the ignored files'
        );

        $sourcePath = __DIR__ . '/../_data/build-ignoring-test';

        $this->callRoboCommand('build', realpath($sourcePath));

        $unzippedPath = $sourcePath . '/dist/unzipped';

        $zip = new \PhpZip\ZipFile();
        try {
            if (!file_exists($unzippedPath)) {
                mkdir($unzippedPath);
            }

            $zip->openFile($sourcePath . '/dist/publishpress-dummy-2.4.0.zip');
            $zip->extractTo($unzippedPath);
        } catch (Exception $e) {
            $I->fail($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        } finally {
            $zip->close();
        }

        $filePath = $unzippedPath . '/publishpress-dummy/invalidfile1.txt';
        $I->assertFileDoesNotExist($filePath, 'The robo script should be ignorign the file invalidfile1.txt');

        $filePath = $unzippedPath . '/publishpress-dummy/invalidfile2.txt';
        $I->assertFileDoesNotExist($filePath, 'The robo script should be ignoring the file invalidfile2.txt');

        foreach ($this->filesToIgnore as $fileToIgnore) {
            $filePath = $unzippedPath . '/publishpress-dummy/' . $fileToIgnore;
            $I->assertFileDoesNotExist($filePath, 'The file ' . $filePath . ' should not exist in the package');
        }
    }

    public function testBuildTask_WithDevRequirements_ShouldCreateAZipFileWithoutTheDevRequirements(
        UnitTester $I
    ) {
        $I->wantToTest(
            'the build task with some dev required libraries, should create a ZIP file without the dev requirements'
        );

        $sourcePath = __DIR__ . '/../_data/build-dev-req-test';

        $this->callRoboCommand('build', realpath($sourcePath));

        $unzippedPath = $sourcePath . '/dist/unzipped';

        $zip = new \PhpZip\ZipFile();
        try {
            if (!file_exists($unzippedPath)) {
                mkdir($unzippedPath);
            }

            $zip->openFile($sourcePath . '/dist/publishpress-dummy-2.4.0.zip');
            $zip->extractTo($unzippedPath);
        } catch (Exception $e) {
            $I->fail(
                $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . '. $unzippedPath = ' . $unzippedPath
            );
        } finally {
            $zip->close();
        }

        $unzippedVendorPath = $unzippedPath . '/publishpress-dummy/vendor';

        // Twig is not a dev requirement, so it should be in the vendor folder
        $I->assertFileExists($unzippedVendorPath . '/twig/twig/src/TwigFilter.php');

        // Check if phpmd is not in the vendor dir
        $I->assertFileDoesNotExist($unzippedVendorPath . '/phpmd');

        // Check if pdepend is not in the vendor dir
        $I->assertFileDoesNotExist($unzippedVendorPath . '/pdepend');

        // Check if phpmd is not in the autoloader
        $autoloaderFilePath = $unzippedVendorPath . '/composer/autoload_static.php';
        $I->assertFileExists($autoloaderFilePath);
        $autoloaderText = file_get_contents($autoloaderFilePath);
        $I->assertStringNotContainsString('PHPMD', $autoloaderText);
    }

    public function testBuildNoPackTask_ShouldNotCreateAZipFileInTheDistDirButOnlyAFolderInTheDistFolder(UnitTester $I)
    {
        $I->wantToTest(
            'the build-unpacked task, should not create a ZIP file in the ./dist dir but only a folder in the dist dir'
        );

        $sourcePath = __DIR__ . '/../_data/build-test';

        $this->callRoboCommand('build:unpacked', realpath($sourcePath));

        $I->assertFileNotExists(
            realpath($sourcePath . '/dist/publishpress-dummy-2.4.0.zip'),
            'There should not be a ZIP file in the path ' . $sourcePath
        );

        $I->assertFileExists(
            $sourcePath . '/dist/publishpress-dummy',
            'There should be a folder named publishpress-dummy in the dist/ dir on the path ' . $sourcePath
        );
    }

    public function testBuildTask_ShouldDeleteTheTmpFolderAfterBuildingTheZipFile(UnitTester $I)
    {
        $I->wantToTest(
            'the build task, should delete the tmp folder after building the zip file'
        );

        $sourcePath = __DIR__ . '/../_data/build-test';

        $this->callRoboCommand('build', realpath($sourcePath));

        $I->assertFileExists(
            realpath($sourcePath . '/dist/publishpress-dummy-2.4.0.zip'),
            'There should be a ZIP file in the path ' . $sourcePath
        );

        $I->assertFileNotExists(
            realpath($sourcePath . '/dist/publishpress-dummy'),
            'The temp folder should be removed after building the zip'
        );
    }

    public function testVersionTask_WithNoArgument_ShouldDisplayTheCurrentVersionNumber(UnitTester $I)
    {
        $I->wantToTest(
            'the version task with no argument, should display the current version number only'
        );

        $tmpDirPath = __DIR__ . '/../_data/build-test/dist/publishpress-dummy';

        if (!file_exists($tmpDirPath)) {
            mkdir($tmpDirPath);
        }

        copy(__DIR__ . '/../_data/build-test/readme.txt', $tmpDirPath . '/readme.txt');
        copy(__DIR__ . '/../_data/build-test/publishpress-dummy.php', $tmpDirPath . '/publishpress-dummy.php');
        copy(__DIR__ . '/../_data/build-test/RoboFile.php', $tmpDirPath . '/RoboFile.php');
        copy(__DIR__ . '/../_data/build-test/composer.json', $tmpDirPath . '/composer.json');

        $output = $this->callRoboCommand('version', realpath($tmpDirPath), '../../../../../vendor/bin/robo');

        $I->assertStringContainsString('Plugin Version: 2.4.0', $output);
    }

    public function testVersionTask_WithArgument_ShouldDisplayTheNewVersionNumber(UnitTester $I)
    {
        $I->wantToTest(
            'the version task with a new version as argument, should update the plugin version number'
        );

        $tmpDirPath = __DIR__ . '/../_data/build-test/dist/publishpress-dummy';

        if (!file_exists($tmpDirPath)) {
            mkdir($tmpDirPath);
        }

        copy(__DIR__ . '/../_data/build-test/readme.txt', $tmpDirPath . '/readme.txt');
        copy(__DIR__ . '/../_data/build-test/publishpress-dummy.php', $tmpDirPath . '/publishpress-dummy.php');
        copy(__DIR__ . '/../_data/build-test/RoboFile.php', $tmpDirPath . '/RoboFile.php');
        copy(__DIR__ . '/../_data/build-test/composer.json', $tmpDirPath . '/composer.json');

        $output = $this->callRoboCommand('version 3.0.0-beta.1', realpath($tmpDirPath), '../../../../../vendor/bin/robo');

        $I->assertStringContainsString('Updating plugin version to 3.0.0-beta.1', $output);
    }

    public function testVersionTask_WithUnstableVersion_ShouldUpdateTheVersionNumberInThePluginFile(UnitTester $I)
    {
        $I->wantToTest(
            'the version task with a unstable version as argument, should update the plugin version number in the plugin file'
        );

        $tmpDirPath = __DIR__ . '/../_data/build-test/dist/publishpress-dummy';

        if (!file_exists($tmpDirPath)) {
            mkdir($tmpDirPath);
        }

        copy(__DIR__ . '/../_data/build-test/readme.txt', $tmpDirPath . '/readme.txt');
        copy(__DIR__ . '/../_data/build-test/publishpress-dummy.php', $tmpDirPath . '/publishpress-dummy.php');
        copy(__DIR__ . '/../_data/build-test/RoboFile.php', $tmpDirPath . '/RoboFile.php');
        copy(__DIR__ . '/../_data/build-test/composer.json', $tmpDirPath . '/composer.json');

        $this->callRoboCommand('version 3.0.0-beta.1', realpath($tmpDirPath), '../../../../../vendor/bin/robo');

        $pluginFileContents = file_get_contents($tmpDirPath . '/publishpress-dummy.php');

        $I->assertStringContainsString('* Version: 3.0.0-beta.1', $pluginFileContents);
    }

    public function testVersionTask_WithUnstableVersion_ShouldNotUpdateTheVersionNumberInTheReadmeFile(UnitTester $I)
    {
        $I->wantToTest(
            'the version task with a unstable version as argument, should update the plugin version number in the readme.txt file'
        );

        $tmpDirPath = __DIR__ . '/../_data/build-test/dist/publishpress-dummy';

        if (!file_exists($tmpDirPath)) {
            mkdir($tmpDirPath);
        }

        copy(__DIR__ . '/../_data/build-test/readme.txt', $tmpDirPath . '/readme.txt');
        copy(__DIR__ . '/../_data/build-test/publishpress-dummy.php', $tmpDirPath . '/publishpress-dummy.php');
        copy(__DIR__ . '/../_data/build-test/RoboFile.php', $tmpDirPath . '/RoboFile.php');
        copy(__DIR__ . '/../_data/build-test/composer.json', $tmpDirPath . '/composer.json');

        $this->callRoboCommand('version 3.0.0-beta.1', realpath($tmpDirPath), '../../../../../vendor/bin/robo');

        $pluginFileContents = file_get_contents($tmpDirPath . '/readme.txt');

        $I->assertStringContainsString('Stable tag: 2.4.0', $pluginFileContents);
    }

    public function testVersionTask_WithStableVersion_ShouldUpdateTheVersionNumberInTheReadmeFile(UnitTester $I)
    {
        $I->wantToTest(
            'the version task with a stable version as argument, should update the plugin version number in the readme.txt file'
        );

        $tmpDirPath = __DIR__ . '/../_data/build-test/dist/publishpress-dummy';

        if (!file_exists($tmpDirPath)) {
            mkdir($tmpDirPath);
        }

        copy(__DIR__ . '/../_data/build-test/readme.txt', $tmpDirPath . '/readme.txt');
        copy(__DIR__ . '/../_data/build-test/publishpress-dummy.php', $tmpDirPath . '/publishpress-dummy.php');
        copy(__DIR__ . '/../_data/build-test/RoboFile.php', $tmpDirPath . '/RoboFile.php');
        copy(__DIR__ . '/../_data/build-test/composer.json', $tmpDirPath . '/composer.json');

        $this->callRoboCommand('version 3.0.0', realpath($tmpDirPath), '../../../../../vendor/bin/robo');

        $pluginFileContents = file_get_contents($tmpDirPath . '/readme.txt');

        $I->assertStringContainsString('Stable tag: 3.0.0', $pluginFileContents);
    }

    public function testVersionTask_WithStableVersion_ShouldUpdateTheVersionNumberInThePluginFile(UnitTester $I)
    {
        $I->wantToTest(
            'the version task with a stable version as argument, should update the plugin version number in the plugin file'
        );

        $tmpDirPath = __DIR__ . '/../_data/build-test/dist/publishpress-dummy';

        if (!file_exists($tmpDirPath)) {
            mkdir($tmpDirPath);
        }

        copy(__DIR__ . '/../_data/build-test/readme.txt', $tmpDirPath . '/readme.txt');
        copy(__DIR__ . '/../_data/build-test/publishpress-dummy.php', $tmpDirPath . '/publishpress-dummy.php');
        copy(__DIR__ . '/../_data/build-test/RoboFile.php', $tmpDirPath . '/RoboFile.php');
        copy(__DIR__ . '/../_data/build-test/composer.json', $tmpDirPath . '/composer.json');

        $this->callRoboCommand('version 3.0.0', realpath($tmpDirPath), '../../../../../vendor/bin/robo');

        $pluginFileContents = file_get_contents($tmpDirPath . '/publishpress-dummy.php');

        $I->assertStringContainsString('* Version: 3.0.0', $pluginFileContents);
    }
}
