<?php
/**
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
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
