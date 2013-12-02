<?php
/**
 * File containing the LegacyExtensionInstaller class.
 *
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;

/**
 * Installer for eZ Publish legacy extensions.
 * Ensures that packages are correctly placed in ezpublish_legacy/extension folder.
 */
class LegacyExtensionInstaller extends LegacyInstaller
{
    public function supports( $packageType )
    {
        return $packageType === 'ezpublish-legacy-extension';
    }

    public function getInstallPath( PackageInterface $package )
    {
        $extra = $package->getExtra();
        if( isset( $extra['ezpublish-legacy-extension-name'] ) )
        {
            $extensionName = $extra['ezpublish-legacy-extension-name'];
        }
        else
        {
            list( $vendor, $extensionName ) = explode( '/', $package->getPrettyName(), 2 );
        }

        $extensionInstallPath = parent::getInstallPath( $package ) . '/extension/' . $extensionName;
        if ( $this->io->isVerbose() )
        {
            $this->io->write( "eZ Publish legacy extension directory is '$extensionInstallPath'" );
        }

        return $extensionInstallPath;
    }
}
