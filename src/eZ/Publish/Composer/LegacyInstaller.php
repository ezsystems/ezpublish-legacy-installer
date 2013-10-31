<?php
/**
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;
use InvalidArgumentException;

/**
 * Abstract installer.
 * It defines the specific install path.
 */
abstract class LegacyInstaller extends LibraryInstaller
{
    /**
     * @var string Directory where eZ Publish legacy will be installed.
     */
    protected $ezpublishLegacyDir;

    public function __construct( IOInterface $io, Composer $composer, $type = '' )
    {
        parent::__construct( $io, $composer, $type );
        $options = $composer->getPackage()->getExtra();
        $this->ezpublishLegacyDir = isset( $options['ezpublish-legacy-dir'] ) ? rtrim( $options['ezpublish-legacy-dir'], '/' ) : '.';
    }

    /**
     * Returns the installation path of a package
     *
     * @param  PackageInterface $package
     * @return string           path
     * @throws \InvalidArgumentException
     */
    public function getInstallPath( PackageInterface $package )
    {
        if ( $package->getType() != $this->type )
        {
            throw new InvalidArgumentException( "Installer only supports {$this->type} package type, got instead: " . $package->getType() );
        }

        return $this->ezpublishLegacyDir;
    }
}
