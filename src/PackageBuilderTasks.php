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

use Robo\Tasks;
use Symfony\Component\Yaml\Parser;

abstract class PackageBuilderTasks extends Tasks
{
    protected const DIST_DIR_NAME = 'dist';

    protected const OUTPUT_SEPARATOR = '----------------------------------------------------------------------';

    protected const TITLE_SEPARATOR = '######################################################################';

    /**
     * @var string
     */
    private $sourcePath;

    /**
     * @var string
     */
    private $finalDestinationPath;

    /**
     * @var string
     */
    private $distPath;

    /**
     * @var ComposerFileReaderInterface
     */
    private $composerFileReader;

    /**
     * @var PluginVersionHandlerInterface
     */
    private $pluginVersionHandler;

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
     * @var Parser
     */
    private $yamlParser = null;

    /**
     * @var string
     */
    private $composerPath = 'composer';

    /**
     * @var string
     */
    private $versionConstantName = 'DUMMY_CONSTANT_NAME';

    /**
     * @var string[]
     */
    private $versionConstantFiles = ['defines.php', 'includes.php'];

    /**
     * PackageBuilderTasks constructor.
     *
     *
     */
    public function __construct()
    {
        $this->sourcePath = getcwd();
        $this->yamlParser = new Parser();

        $this->distPath             = $this->sourcePath . '/' . self::DIST_DIR_NAME;
        $this->finalDestinationPath = $this->distPath;
        if ($this->settingsFileExists()) {
            $customSettings = $this->yamlParser->parseFile($this->getSettingsFilePath());
            if (isset($customSettings['destination'])) {
                $this->finalDestinationPath = realpath($customSettings['destination']);
            }
        }

        $this->composerFileReader   = new ComposerFileReader();
        $this->pluginVersionHandler = new PluginVersionHandler();

        $this->pluginName    = $this->composerFileReader->getPluginName($this->sourcePath);
        $this->pluginVersion = $this->pluginVersionHandler->getPluginVersion(
            $this->sourcePath . '/' . $this->pluginName . '.php'
        );
    }

    protected function setVersionConstantName(string $constantName): void
    {
        $this->versionConstantName = $constantName;
    }

    protected function setVersionConstantFiles(array $fileNames): void
    {
        $this->versionConstantFiles = $fileNames;
    }

    private function settingsFileExists(): string
    {
        return file_exists($this->getSettingsFilePath());
    }

    private function getSettingsFilePath(): string
    {
        return $this->sourcePath . '/builder.yml';
    }

    private function getZipFileName(): string
    {
        return sprintf(
            '%s-%s.zip',
            $this->pluginName,
            $this->pluginVersion
        );
    }

    /**
     * Build the plugin distribution files packing into a ZIP file
     */
    public function build(): void
    {
        $this->buildUnpacked();

        $zipPath = sprintf(
            '%s/%s',
            $this->finalDestinationPath,
            $this->getZipFileName()
        );

        // Remove the zip file if it already exists
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }

        $this->packBuiltDir($zipPath, $this->pluginName, $this->distPath . '/' . $this->pluginName);

        $this->_deleteDir($this->distPath . '/' . $this->pluginName);
    }

    /**
     * Build the plugin distribution files without packing into a ZIP file
     */
    public function buildUnpacked(): void
    {
        $this->sayTitle();

        $this->prepareCleanDistDir($this->distPath, $this->pluginName);
        $this->buildToDir($this->sourcePath, $this->distPath . '/' . $this->pluginName, $this->composerPath);
    }

    /**
     * Show or set the version number in the plugin files
     *
     * @param string|null $newVersion
     */
    public function version(string $newVersion = null): void
    {
        $this->sayTitle();

        if (empty($newVersion)) {
            return;
        }

        $this->say(
            sprintf(
                'Updating plugin version to %s',
                $newVersion
            )
        );
        $this->say('');

        if ($this->pluginVersionHandler->isStableVersion($newVersion)) {
            $this->pluginVersionHandler->updateStableTagInTheReadmeFile($this->sourcePath, $newVersion);
            $this->say('Updated stable tag in the file readme.txt');
        }

        $this->pluginVersionHandler->updateVersionInComposerDistUrl($this->sourcePath, $newVersion);
        $this->say('Updated the dist url in the composer.json file');

        $this->pluginVersionHandler->updateVersionInThePluginFile($this->sourcePath, $this->pluginName, $newVersion);
        $this->say('Updated version number in the file ' . $this->pluginName);

        foreach ($this->versionConstantFiles as $fileName) {
            if (file_exists($this->sourcePath . '/' . $fileName)) {
                $this->pluginVersionHandler->updateVersionInACustomFile(
                    $this->sourcePath,
                    $fileName,
                    $this->versionConstantName,
                    $newVersion
                );
                $this->say('Updated version number in the file ' . $fileName);
            }
        }
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

    private function prepareCleanDistDir($destinationPath, $pluginName): void
    {
        if (file_exists($destinationPath)) {
            $this->_deleteDir($destinationPath . '/' . $pluginName);
            $this->_remove($destinationPath . '/' . $this->getZipFileName());
            return;
        }

        $this->_mkdir($destinationPath);
    }

    private function buildToDir($sourcePath, $fullDestinationPath, $composerPath): void
    {
        $this->_mirrorDir($sourcePath, $fullDestinationPath);

        // Runs composer update --no-dev for removing any dev requirements from the vendor folder
        $this->taskComposerInstall($composerPath)
             ->optimizeAutoloader()
             ->noInteraction()
             ->noSuggest()
             ->dir($fullDestinationPath)
             ->noDev()
             ->run();

        $this->removeIgnoredFiles($fullDestinationPath);
    }

    private function removeIgnoredFiles($dirPath): void
    {
        $listOfFilesToIgnore = $this->getListOfFilesToIgnore();

        if (empty($listOfFilesToIgnore)) {
            return;
        }

        foreach ($listOfFilesToIgnore as $file) {
            $path = $dirPath . '/' . $file;
            if (file_exists($path)) {
                $this->_remove($path);
            }
        }
    }

    private function getListOfFilesToIgnore(): array
    {
        $defaultList = file(__DIR__ . '/../files-to-ignore.txt', FILE_IGNORE_NEW_LINES);

        return array_merge($defaultList, $this->filesToIgnore);
    }

    private function packBuiltDir($zipPath, $pluginName, $fullDestinationPath): void
    {
        $this->taskPack($zipPath)
             ->add([$pluginName => $fullDestinationPath])
             ->run();
    }

    protected function appendToFileToIgnore(array $filesToIgnore): void
    {
        if (!empty($filesToIgnore)) {
            $this->filesToIgnore = array_merge($this->filesToIgnore, $filesToIgnore);
        }
    }
}
