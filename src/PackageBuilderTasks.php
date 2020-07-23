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
     * @var Parser
     */
    private $yamlParser = null;

    /**
     * @var string
     */
    private $composerPath = 'composer';

    /**
     * PackageBuilderTasks constructor.
     *
     *
     */
    public function __construct()
    {
        $this->sourcePath = getcwd();
        $this->yamlParser = new Parser();

        $this->destinationPath = $this->sourcePath . '/' . self::DIST_DIR_NAME;
        if ($this->envFileExists()) {
            $builderEnv = $this->yamlParser->parseFile($this->getEnvFilePath());
            if (isset($builderEnv['destination'])) {
                $this->destinationPath = realpath($builderEnv['destination']);
            }
        }

        $this->composerFileReader = new ComposerFileReader();
        $this->pluginFileReader   = new PluginFileReader();

        $this->pluginName    = $this->composerFileReader->getPluginName($this->sourcePath);
        $this->pluginVersion = $this->pluginFileReader->getPluginVersion(
            $this->sourcePath . '/' . $this->pluginName . '.php'
        );
    }

    private function envFileExists()
    {
        return file_exists($this->getEnvFilePath());
    }

    private function getEnvFilePath()
    {
        return $this->sourcePath . '/builder.env';
    }

    private function getZipFileName()
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
            $this->destinationPath,
            $this->getZipFileName()
        );

        $fullDestinationPath = $this->getFullDestinationPath();

        $this->packBuiltDir($zipPath, $this->pluginName, $fullDestinationPath);

        $this->_deleteDir($this->destinationPath . '/' . $this->pluginName);
    }

    /**
     * Build the plugin distribution files without packing into a ZIP file
     */
    public function buildUnpacked(): void
    {
        $this->sayTitle();

        $fullDestinationPath = $this->getFullDestinationPath();

        $this->prepareCleanDistDir($this->destinationPath, $this->pluginName);
        $this->buildToDir($this->sourcePath, $fullDestinationPath, $this->composerPath);
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

    private function getFullDestinationPath()
    {
        return $this->destinationPath . '/' . $this->pluginName;
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
        $this->taskComposerUpdate($composerPath)
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
