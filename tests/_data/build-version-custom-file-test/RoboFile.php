<?php

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \PublishPressBuilder\PackageBuilderTasks
{
    public function __construct()
    {
        parent::__construct();

        $this->setVersionConstantName('PUBLISHPRESS_DUMMY_VERSION');
        $this->setVersionConstantFiles(['defines.php', 'includes.php', 'subfolder/constants.php']);
    }
}
