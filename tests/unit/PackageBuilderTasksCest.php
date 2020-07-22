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

    private function callRoboCommand($command, $sourcePath)
    {
        $procResource = proc_open(
            '../../../vendor/bin/robo ' . $command,
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            realpath($sourcePath)
        );

        proc_close($procResource);
    }

    public function testBuildTask_ShouldCreateAZipFileInTheDistDirNamedWithPluginNameAndVersion(UnitTester $I)
    {
        $I->wantToTest(
            'the build task with no custom destination, should create a ZIP file in the ./dist dir with the plugin name and version'
        );

        $sourcePath = __DIR__ . '/../_data/build-test';

        $this->callRoboCommand('build', realpath($sourcePath));

        $I->assertFileExists(
            realpath($sourcePath . '/dist/publishpress-dummy-2.0.4.zip'),
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

            $zip->openFile($sourcePath . '/dist/publishpress-dummy-2.0.4.zip');
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

        $this->callRoboCommand('build', realpath($sourcePath));

        $I->assertFileExists(
            realpath($sourcePath . '/../../_output/publishpress-dummy-2.0.4.zip'),
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

            $zip->openFile($sourcePath . '/dist/publishpress-dummy-2.0.4.zip');
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

            $zip->openFile($sourcePath . '/dist/publishpress-dummy-2.0.4.zip');
            $zip->extractTo($unzippedPath);
        } catch (Exception $e) {
            $I->fail($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
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
            realpath($sourcePath . '/dist/publishpress-dummy-2.0.4.zip'),
            'There should not be a ZIP file in the path ' . $sourcePath
        );

        $I->assertFileExists(
            $sourcePath . '/dist/publishpress-dummy',
            'There should be a folder named publishpress-dummy in the dist/ dir on the path ' . $sourcePath
        );
    }
}
