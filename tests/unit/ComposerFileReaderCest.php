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

class ComposerFileReaderCest
{
    public function getPluginName_ForPathWithComposerFileAndNameWithNamespace_ReturnsPluginName(
        UnitTester $I
    ) {
        $I->wantToTest('getPluginName for path with a composer file and name with namespace, should return the plugin name');

        $projectPath = __DIR__ . '/../_data/composer-files/name-with-namespace';

        $utils      = new \PublishPressBuilder\ComposerFileReader();
        $pluginName = $utils->getStandardPluginName($projectPath);

        $I->assertEquals('publishpress-dummy', $pluginName);
    }

    public function getPluginName_ForPathWithComposerFileAndNameWithNoNamespace_ReturnsPluginName(
        UnitTester $I
    ) {
        $I->wantToTest('getPluginName for path with a composer file and name without namespace, should return the plugin name');

        $projectPath = __DIR__ . '/../_data/composer-files/name-with-no-namespace';

        $utils      = new \PublishPressBuilder\ComposerFileReader();
        $pluginName = $utils->getStandardPluginName($projectPath);

        $I->assertEquals('publishpress-dummy', $pluginName);
    }
}
