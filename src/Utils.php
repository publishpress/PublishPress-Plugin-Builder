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

class Utils
{
    public function getPluginNameFromComposerFile($projectPath): string
    {
        $composerFileJson = trim(file_get_contents($projectPath . '/composer.json'));
        $composerFileJson = json_decode($composerFileJson);

        $pluginName = explode('/', $composerFileJson->name);

        if (count($pluginName) > 1) {
            $pluginName = $pluginName[count($pluginName) - 1];
        } else {
            $pluginName = $pluginName[0];
        }

        return $pluginName;
    }

    public function getPluginVersionFromPluginFile($pluginFilePath): string
    {
        $pluginFileContent = trim(file_get_contents($pluginFilePath));

        preg_match('/Version:\s*([0-9\.a-z\-]*)/i', $pluginFileContent, $matches);

        return $matches[1];
    }
}
