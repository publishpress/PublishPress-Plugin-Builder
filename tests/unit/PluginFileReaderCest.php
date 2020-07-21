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

class PluginFileReaderCest
{
    public function _before(UnitTester $I)
    {
    }

    public function getPluginVersion_ForPluginFileWithStableVersionNumber_ReturnsVersionNumber(
        UnitTester $I
    ) {
        $I->wantToTest('getPluginVersion for plugin file with stable version number, should return the version number');

        $filePath = __DIR__ . '/../_data/plugin-files/plugin-stable-version.php';

        $utils         = new \PublishPressBuilder\PluginFileReader();
        $pluginVersion = $utils->getPluginVersion($filePath);

        $I->assertEquals('2.0.4', $pluginVersion);
    }

    public function getPluginVersion_ForPluginFileWithUnstableVersionNumber_ReturnsVersionNumber(
        UnitTester $I
    ) {
        $I->wantToTest('getPluginVersion for plugin file with non-stable version number, should return the version number');

        $filePath = __DIR__ . '/../_data/plugin-files/plugin-unstable-version.php';

        $utils         = new \PublishPressBuilder\PluginFileReader();
        $pluginVersion = $utils->getPluginVersion($filePath);

        $I->assertEquals('2.0.4-beta.1', $pluginVersion);
    }
}
