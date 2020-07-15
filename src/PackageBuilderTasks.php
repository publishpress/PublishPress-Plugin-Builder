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

namespace PublishPressBuilder;

abstract class PackageBuilderTasks extends \Robo\Tasks
{
    const DIST_DIR_NAME = 'dist';

    const OUTPUT_SEPARATOR = '----------------------------------------------------------------------';

    const TITLE_SEPARATOR = '######################################################################';

    /**
     * @var string
     */
    private $sourcePath;

    /**
     * @var string
     */
    private $destinationPath;

    /**
     * @var ComposerFileReaderInterface
     */
    private $composerFileReader;

    /**
     * @var PluginFileReaderInterface
     */
    private $pluginFileReader;

    /**
     * @var string
     */
    private $pluginName;

    /**
     * @var string
     */
    private $pluginVersion;

    /**
     * @var array
     */
    private $filesToIgnore = [];

    /**
     * PackageBuilderTasks constructor.
     */
    public function __construct()
    {
        $this->sourcePath      = getcwd();
        $this->destinationPath = $this->sourcePath . '/' . self::DIST_DIR_NAME;

        $this->composerFileReader = new ComposerFileReader();
        $this->pluginFileReader   = new PluginFileReader();

        $this->pluginName    = $this->composerFileReader->getPluginName($this->sourcePath);
        $this->pluginVersion = $this->pluginFileReader->getPluginVersion(
            $this->sourcePath . '/' . $this->pluginName . '.php'
        );
    }

    private function sayTitle(): void
    {
        $this->say(self::TITLE_SEPARATOR);
        $this->say('PublishPress Plugin Builder');
        $this->say('');
        $this->say('Plugin Name: ' . $this->pluginName);
        $this->say('Plugin Version: ' . $this->pluginVersion);
        $this->say(self::OUTPUT_SEPARATOR);
    }

    public function build(): void
    {
        $this->sayTitle();

        $this->prepareCleanDistDir();
        $this->buildPackage();
    }

    private function buildPackage(): void
    {
        $zipPath = sprintf(
            '%s/%s-%s.zip',
            $this->destinationPath,
            $this->pluginName,
            $this->pluginVersion
        );

        $destinationPath = $this->destinationPath . '/' . $this->pluginName;
        $this->_mirrorDir($this->sourcePath, $destinationPath);

        $this->removeIgnoredFiles($destinationPath);

        $this->taskPack($zipPath)
             ->add([$this->pluginName => $destinationPath])
             ->run();
    }

    private function removeIgnoredFiles($dirPath): void
    {
        $filesToIgnore = $this->getListOfFilesToIgnore();

        if (empty($filesToIgnore)) {
            return;
        }

        foreach ($filesToIgnore as $file) {
            $path = $dirPath . '/' . $file;
            if (file_exists($path)) {
                $this->_remove($path);
            }
        }
    }

    private function prepareCleanDistDir(): void
    {
        if (file_exists($this->destinationPath)) {
            $this->_cleanDir($this->destinationPath);
        } else {
            $this->_mkdir($this->destinationPath);
        }
    }

    private function getListOfFilesToIgnore(): array
    {
        $defaultList = [
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
            '.github',
            '.gitignore',
            '.ide.php',
            '.idea',
            '.travis.yml',
            'CONTRIBUTING',
            'CONTRIBUTING.md',
            'CONTRIBUTING.txt',
            'Icon',
            'README.md',
            'RoboFile.php',
            'bin',
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
            'vendor/twig/twig/ext'
        ];

        return array_merge($defaultList, $this->filesToIgnore);
    }

    protected function appendToFileToIgnore(array $filesToIgnore): void
    {
        if (!empty($filesToIgnore)) {
            $this->filesToIgnore = array_merge($this->filesToIgnore, $filesToIgnore);
        }
    }
}
