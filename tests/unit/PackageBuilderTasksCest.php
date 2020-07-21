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
}
