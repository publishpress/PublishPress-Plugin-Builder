<?php

class PackageBuilderTasksCest
{
    private $filesToIgnore = [
        '.AppleDB',
        '.AppleDesktop',
        '.AppleDouble',
        '.DS_Store',
        '.DocumentRevisions-V100',
        '.LSOverride',
        '.Spotlight-V100',
        '.TemporaryItems',
        '.Trashes',
        '.VolumeIcon.icns',
        '._*',
        '.apdisk',
        '.babelrc',
        '.com.apple.timemachine.donotpresent',
        '.editorconfig',
        '.fseventsd',
        '.git',
        '.github/',
        '.gitignore',
        '.ide.php',
        '.idea/',
        '.travis.yml',
        'CONTRIBUTING',
        'CONTRIBUTING.md',
        'CONTRIBUTING.txt',
        'Icon',
        'README.md',
        'RoboFile.php',
        'bin/',
        'build.xml',
        'codeception.yml',
        'composer.json',
        'composer.lock',
        'contributing.md',
        'contributing.txt',
        'dist.codeception.yml',
        'dist',
        'node_modules',
        'package-lock.json',
        'package.json',
        'phpcs.xml.dist',
        'phpunit.xml',
        'phpunit.xml.dist',
        'tests',
        'tools',
        'vendor/alledia/wordpress-plugin-builder',
        'vendor/bin',
        'vendor/phing',
        'webpack.config.js',
        'vendor/twig/twig/test',
        'vendor/twig/twig/README.rst',
        'vendor/twig/twig/phpunit.xml.dist',
        'vendor/pimple/pimple/ext',
        'vendor/pimple/pimple/CHANGELOG',
        'vendor/pimple/pimple/composer.json',
        'vendor/pimple/pimple/phpunit.xml.dist',
        'vendor/pimple/pimple/README.rst',
        'vendor/pimple/pimple/src/Pimple/Tests',
        'vendor/psr/container/composer.json',
        'vendor/psr/container/README.md',
        'vendor/symfony/polyfill-ctype/composer.json',
        'vendor/symfony/polyfill-ctype/README.md',
        'vendor/twig/twig/CHANGELOG',
        'vendor/twig/twig/composer.json',
        'vendor/twig/twig/doc',
        'vendor/twig/twig/ext',
    ];

    public function _before(UnitTester $I)
    {
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
        $sourcePath = __DIR__ . '/../_data/build-test';

        $this->callRoboCommand('build', $sourcePath);

        $I->assertFileExists($sourcePath . '/dist/publishpress-dummy-2.0.4.zip');
    }

    public function testBuildTask_ShouldCreateZipFileWithNoIgnoredFiles(UnitTester $I)
    {
        $sourcePath = __DIR__ . '/../_data/build-test';

        $this->callRoboCommand('build', $sourcePath);

        $unzippedPath = $sourcePath . '/dist/unzipped';

        try {
            $zip = new ZipArchive();
            $zip->open($sourcePath . '/dist/publishpress-dummy-2.0.4.zip');
            $zip->extractTo($unzippedPath);
        } catch (Exception $e) {
            $I->fail($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }

        foreach ($this->filesToIgnore as $fileToIgnore) {
            $filePath = $unzippedPath . '/publishpress-dummy/' . $fileToIgnore;
            $I->assertFileDoesNotExist($filePath);
        }
    }
}
