<?php
/**
 * File containing the LegacySettingsInstaller class.
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

/**
 * This class allows user to deploy eZ LS setting as composer packages
 *
 * @todo ideally we should always remove anything in settings/siteaccess and settings/override, plus settings/composr.json
 *       and settings/.git when we install or upgrade (but of course keep all settings/*.ini)
 */
class LegacySettingsInstaller extends LegacyKernelInstaller
{
    public function __construct( IOInterface $io, Composer $composer, $type = 'ezpublish-legacy-settings' )
    {
        parent::__construct( $io, $composer, $type );
    }

    /**
     * We override this because existence of 'settings' dir is not enough - we add for good measure a check for
     * existence of siteaccess or override
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface $package
     * @return bool
     */
    public function isInstalled( InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        return parent::isInstalled( $repo, $package ) && (
            is_dir( $this->ezpublishLegacyDir . '/settings/override' ) || is_dir( $this->ezpublishLegacyDir . '/settings/siteaccess' ) );
    }

    public function getInstallPath( PackageInterface $package )
    {
        return parent::getInstallPath( $package ) . '/settings';
    }

    protected function generateTempDirName()
    {
        /// @todo to be extremely safe, we should use PID+time
        return sys_get_temp_dir() . '/' . uniqid( 'composer_ezlegacysetting_' );
    }
}
