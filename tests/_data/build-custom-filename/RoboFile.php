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
        $this->setPluginFileName('customPluginFile.php');
        $this->setVersionConstantName('PUBLISHPRESS_DUMMY_VERSION');

        parent::__construct();
    }
}
