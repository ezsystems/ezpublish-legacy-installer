<?php
/**
 * File containing the LegacyInstaller class.
 *
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Installer\InvalidArgumentException;
use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;

class LegacyInstaller extends LibraryInstaller
{
    private $ezpublishLegacyDir;

    public function __construct( IOInterface $io, Composer $composer, $type = 'ezpublish-legacy-extension' )
    {
        parent::__construct( $io, $composer, $type );
        $options = $composer->getPackage()->getExtra();
        $this->ezpublishLegacyDir = isset( $options['ezpublish-legacy-dir'] ) ? rtrim( $options['ezpublish-legacy-dir'], '/' ) : '.';
    }

    /**
     * Decides if the installer supports the given type
     *
     * @param  string $packageType
     *
     * @return bool
     */
    public function supports( $packageType )
    {
        return $packageType === 'ezpublish-legacy-extension' || $packageType === 'ezpublish-legacy';
    }

    /**
     * Returns the installation path of a package
     *
     * @param  PackageInterface $package
     * @return string           path
     */
    public function getInstallPath( PackageInterface $package )
    {
        switch( $package->getType() )
        {
            case 'ezpublish-legacy':
                return $this->ezpublishLegacyDir;
            case 'ezpublish-legacy-extension':
                list( $vendor, $packageName ) = explode( '/', $package->getPrettyName(), 2 );
                return $this->ezpublishLegacyDir . '/extension/' . $packageName;
            default:
                throw new \InvalidArgumentException( 'Installer only supports ezpublish-legacy and ezpublish-legacy-extension package types, got instead: ' . $package->getType() );
        }
    }
}
