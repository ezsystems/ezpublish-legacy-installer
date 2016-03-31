<?php
/**
 * File containing the LegacyKernelInstaller class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace eZ\Publish\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;

/**
 * Installer for eZ Publish legacy kernel.
 * Allows soft updates, ensuring that an existing installation is not wiped out.
 */
class LegacyKernelInstaller extends LegacyInstaller
{
    public function supports( $packageType )
    {
        return $packageType === 'ezpublish-legacy';
    }

    /**
     * We override this because if install dir is '.', existence is not enough - we add for good measure a check for
     * existence of the "settings" folder
     *
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface $package
     *
     * @return bool
     */
    public function isInstalled( InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        return parent::isInstalled( $repo, $package ) && is_dir( $this->ezpublishLegacyDir . '/settings' );
    }

    /**
     * If composer tries to install into a non-empty folder, we risk to effectively erase an existing installation.
     * This is not a composer limitation we can fix - it happens because composer might be using git to download the
     * sources, and git can not clone a repo into a non-empty folder.
     *
     * To prevent this, we adopt the following strategy:
     * - install in a separate, temporary directory
     * - then move over the installed files copying on top of the existing installation
     *
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface $package
     */
    public function install( InstalledRepositoryInterface $repo, PackageInterface $package )
    {
        $downloadPath = $this->getInstallPath( $package );
        $fileSystem = new Filesystem();
        if ( !is_dir( $downloadPath ) || $fileSystem->isDirEmpty( $downloadPath ) )
        {
            return parent::install( $repo, $package );
        }

        $actualLegacyDir = $this->ezpublishLegacyDir;
        $this->ezpublishLegacyDir = $this->generateTempDirName();
        if ( $this->io->isVerbose() )
        {
            $this->io->write( "Installing in temporary directory." );
        }

        parent::install( $repo, $package );

        /// @todo the following function does not warn of any failures in copying stuff over. We should probably fix it...
        if ( $this->io->isVerbose() )
        {
            $this->io->write( "Updating new code over existing installation." );
        }
        $fileSystem->copyThenRemove( $this->ezpublishLegacyDir, $actualLegacyDir );

        // if parent::install installed binaries, then the resulting shell/bat stubs will not work. We have to redo them
        if( method_exists($this,'removeBinaries') )
        {
            $this->removeBinaries( $package );
        }
        else
        {
            $this->binaryInstaller->removeBinaries( $package );
        }

        $this->ezpublishLegacyDir = $actualLegacyDir;
        if( method_exists($this,'installBinaries') )
        {
            $this->installBinaries( $package );
        }
        else
        {
            $this->binaryInstaller->installBinaries( $package, $this->getInstallPath( $package ) );
        }
    }

    /**
     * Same as install(): we need to insure there is no removal of actual eZ code.
     * updateCode is called by update()
     */
    public function updateCode( PackageInterface $initial, PackageInterface $target )
    {
        $actualLegacyDir = $this->ezpublishLegacyDir;
        $this->ezpublishLegacyDir = $this->generateTempDirName();
        if ( $this->io->isVerbose() )
        {
            $this->io->write( "Installing in temporary directory." );
        }

        $this->installCode( $target );

        $fileSystem = new Filesystem();
        if ( $this->io->isVerbose() )
        {
            $this->io->write( "Updating new code over existing installation." );
        }
        /// @todo the following function does not warn of any failures in copying stuff over. We should probably fix it...
        $fileSystem->copyThenRemove( $this->ezpublishLegacyDir, $actualLegacyDir );

        $this->ezpublishLegacyDir = $actualLegacyDir;
    }

    /**
     * Returns a unique temporary directory (full path).
     *
     * @return string
     */
    protected function generateTempDirName()
    {
        // @todo to be extremely safe, we should use PID+time
        $tmpDir = sys_get_temp_dir() . '/' . uniqid( 'composer_ezlegacykernel_' );
        if ( $this->io->isVerbose() )
        {
            $this->io->write( "Temporary directory for ezpublish-legacy updates: $tmpDir" );
        }

        return $tmpDir;
    }
}
