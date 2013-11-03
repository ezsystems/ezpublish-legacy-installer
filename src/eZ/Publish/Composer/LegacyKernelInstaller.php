<?php
/**
 * File containing the LegacyKernelInstaller class.
 *
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\Filesystem;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Installer for legacy kernel.
 *
 * It ensures a non destructive install/update on existing eZ Publish installations, using the following strategy:
 * - a full copy of eZ Publish LS sources is kept in vendor/ezsystems/ezpublish-legacy-composercopy
 * - after installation and updates, all files in there are copied over the actual installation dir
 * - at removal time, both copies are deleted
 * This is wasteful of hard disk space, but has the advantage of making updates tolerable (as we do not need to clone
 * the whole eZ Publish repo every time)
 */
class LegacyKernelInstaller extends LegacyInstaller
{
    public function __construct( IOInterface $io, Composer $composer, $type = 'ezpublish-legacy' )
    {
        parent::__construct( $io, $composer, $type );
    }

    public function getCopyInstallPath( PackageInterface $package )
    {
        list( $vendor, $name ) = explode( '/', $package->getPrettyName(), 2 );
        return $this->vendorDir . '/' . $vendor . '/' . $name . '-composercopy';
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
        $actualLegacyDir = $this->ezpublishLegacyDir;
        $this->ezpublishLegacyDir = $this->getCopyInstallPath( $package );

        $copyOk = parent::isInstalled( $repo, $package );

        $this->ezpublishLegacyDir = $actualLegacyDir;

        return $copyOk && is_dir( $this->ezpublishLegacyDir . '/settings' );
    }

    /**
     * If composer tries to install into a non-empty folder, we risk to effectively erase an existing installation.
     * This is not a composer limitation we can fix - it happens because composer might be using git to download the
     * sources, and git can not clone a repo into a non-empty folder.
     *
     * To prevent this, we adopt the following strategy:
     * - install in a separate directory
     * - then copy over the installed files copying on top of the existing installation
     *
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface $package
     */
    public function install( InstalledRepositoryInterface $repo, PackageInterface $package )
    {
        $actualLegacyDir = $this->ezpublishLegacyDir;
        $this->ezpublishLegacyDir = $this->getCopyInstallPath( $package );

        parent::install( $repo, $package );

        /// @todo the following function fails too frequently on windows with .git stuff. We should probably fix it...
        $this->copyRecursive( $this->ezpublishLegacyDir, $actualLegacyDir );

        // if parent::install installed binaries, then the resulting shell/bat stubs will not work. We have to redo them
        $this->removeBinaries( $package );
        $this->ezpublishLegacyDir = $actualLegacyDir;
        $this->installBinaries( $package );
    }

    /**
     * Same as install(): we need to ensure there is no removal of actual eZ code.
     */
    public function update( InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target )
    {
        $actualLegacyDir = $this->ezpublishLegacyDir;
        $this->ezpublishLegacyDir = $this->getCopyInstallPath( $initial );

        parent::update( $repo, $initial, $target );

        /// @todo the following function fails too frequently on windows with .git stuff. We should probably fix it...
        $this->copyRecursive( $this->ezpublishLegacyDir, $actualLegacyDir );

        // if parent::update installed binaries, then the resulting shell/bat stubs will not work. We have to redo them
        $this->removeBinaries( $target );
        $this->ezpublishLegacyDir = $actualLegacyDir;
        $this->installBinaries( $target );
    }

    public function uninstall( InstalledRepositoryInterface $repo, PackageInterface $package )
    {
        $actualLegacyDir = $this->ezpublishLegacyDir;
        $this->ezpublishLegacyDir = $this->getCopyInstallPath( $package );

        parent::uninstall( $repo, $package );

        $this->ezpublishLegacyDir = $actualLegacyDir;

        $fileSystem = new Filesystem();
        $fileSystem->removeDirectoryPhp( $this->ezpublishLegacyDir );
    }

    /**
     * Inspired from Composer\Util\Filesystem::copyThenRemove
     * @param string $source
     * @param string $target
     *
     * @todo this seems to fail frequently on windows when copying git files (courtesy of tortoisegit) - we could maybe
     *       switch to using xcopy (see how Composer\Util\Filesystem::rename() does it), or test using the
     *       FilesystemIterator::UNIX_PATHS flag
     */
    protected function copyRecursive( $source, $target )
    {
        $it = new RecursiveDirectoryIterator( $source, RecursiveDirectoryIterator::SKIP_DOTS );
        $ri = new RecursiveIteratorIterator( $it, RecursiveIteratorIterator::SELF_FIRST );
        $this->ensureDirectoryExists( $target );

        foreach ( $ri as $file )
        {
            $targetPath = $target . DIRECTORY_SEPARATOR . $ri->getSubPathName();
            if ( $file->isDir() )
            {
                $this->ensureDirectoryExists( $targetPath );
            }
            else
            {
                copy( $file->getPathname(), $targetPath );
            }
        }
    }

    protected function ensureDirectoryExists( $directory )
    {
        if ( !is_dir( $directory ) )
        {
            if ( file_exists( $directory ) )
            {
                throw new \RuntimeException( $directory.' exists and is not a directory.' );
            }
            if ( !@mkdir( $directory, 0777, true ) )
            {
                throw new \RuntimeException( $directory.' does not exist and could not be created.' );
            }
        }
    }

    /*protected function public function removeDirectory( $directory )
    {
        $it = new RecursiveDirectoryIterator( $directory, RecursiveDirectoryIterator::SKIP_DOTS );
        $ri = new RecursiveIteratorIterator( $it, RecursiveIteratorIterator::CHILD_FIRST );

        foreach ( $ri as $file )
        {
            if ( $file->isDir() )
            {
                rmdir( $file->getPathname() );
            }
            else
            {
                unlink( $file->getPathname() );
            }
        }
    }*/
}
