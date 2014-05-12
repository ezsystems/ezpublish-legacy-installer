<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace eZ\Publish\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class InstallerPlugin implements PluginInterface
{
    public function activate( Composer $composer, IOInterface $io )
    {
        $composer->getInstallationManager()->addInstaller( new LegacyKernelInstaller( $io, $composer ) );
        $composer->getInstallationManager()->addInstaller( new LegacyExtensionInstaller( $io, $composer ) );
    }
}
