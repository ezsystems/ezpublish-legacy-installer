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
 * All this class does is to tell composer that extensions have to be installed in a
 * different directory
 */
class LegacyExtensionInstaller extends LegacyInstaller
{
    public function supports( $packageType )
    {
        return $packageType === 'ezpublish-legacy-extension';
    }

    public function getInstallPath( PackageInterface $package )
    {
        list( $vendor, $packageName ) = explode( '/', $package->getPrettyName(), 2 );
        return parent::getInstallPath( $package ) . '/extension/' . $packageName;
    }

}
