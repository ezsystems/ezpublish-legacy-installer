<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace eZ\Publish\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;
use InvalidArgumentException;

/**
 * Base eZ Publish legacy installer.
 * Provides the right directory to install files into.
 */
abstract class LegacyInstaller extends LibraryInstaller
{
    /**
     * eZ Publish legacy base dir.
     *
     * @var string
     */
    protected $ezpublishLegacyDir;

    public function __construct( IOInterface $io, Composer $composer, $type = '' )
    {
        parent::__construct( $io, $composer, $type );
        $options = $composer->getPackage()->getExtra();

        if ( isset( $options['ezpublish-legacy-dir'] ) )
        {
            $this->ezpublishLegacyDir = rtrim( $options['ezpublish-legacy-dir'], '/' );
        }
        else if ( isset( $options['symfony-app-dir'] ) )
        {
            // For Symfony install (eZ Platform or otherwise), default to `ezpublish_legacy` to avoid messing up root folder
            $this->ezpublishLegacyDir = 'ezpublish_legacy';
        }
        else
        {
            $this->ezpublishLegacyDir = '.';
        }
    }

    public function getInstallPath( PackageInterface $package )
    {
        if ( $this->io->isVerbose() )
        {
            $this->io->write( "eZ Publish legacy base directory is '$this->ezpublishLegacyDir/'" );
        }

        return $this->ezpublishLegacyDir;
    }
}
