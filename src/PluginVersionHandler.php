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

class PluginVersionHandler
{
    public function getPluginVersion(string $pluginFilePath): string
    {
        $pluginFileContent = trim(file_get_contents($pluginFilePath));

        $matches = [];
        preg_match('/Version:\s*([0-9\.a-z\-]*)/i', $pluginFileContent, $matches);

        return $matches[1];
    }

    public function setPluginVersion(string $pluginPath, string $pluginName, string $pluginVersion): void
    {
        if ($this->isStableVersion($pluginVersion)) {
            $this->updateStableTagInTheReadmeFile($pluginPath, $pluginVersion);
        }

        $this->updateVersionInThePluginFile($pluginPath, $pluginName, $pluginVersion);
    }

    public function isStableVersion(string $version): bool
    {
        return preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/', $version);
    }

    private function updateStableTagInTheReadmeFile(string $pluginPath, string $version): void
    {
        $this->replaceTextInFile(
            $pluginPath . '/readme.txt',
            '/^(Stable tag: )([^\n]*)\n/m',
            'Stable tag: ' . $version . "\n"
        );
    }

    private function updateVersionInThePluginFile(string $pluginPath, string $pluginName, string $version): void
    {
        $this->replaceTextInFile(
            $pluginPath . '/' . $pluginName . '.php',
            '/^(\s*\*\s*Version:\s*)([^\n]+)\n/m',
            ' * Version: ' . $version . "\n"
        );
    }

    private function replaceTextInFile(string $path, string $pattern, string $replacement)
    {
        $path = str_replace('//', '/', $path);

        $fileContent = file_get_contents($path);

        // Update the content
        $fileContent = preg_replace($pattern, $replacement, $fileContent);

        // Store in the file
        file_put_contents($path, $fileContent);
    }
}
