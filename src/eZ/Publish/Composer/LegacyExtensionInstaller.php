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
use Composer\Repository\InstalledRepositoryInterface;
use eZINI;

/**
 * Installer for legacy extensions.
 *
 * This class does:
 * - tell Composer that extensions have to be installed in a different directory than the default "vendor" dir
 * - when an extension is installed or removed:
 *   . enable/disable the extension in settings/override/site.ini.append.php
 *   . clear caches
 *   . regenerate autoloads
 *
 * Package name of the extension will be the name of the extension folder.
 * E.g., for "ezsystems/ezfind", it will be installed under extension/ezfind/ directory.
 */
class LegacyExtensionInstaller extends LegacyInstaller
{
    public function __construct( IOInterface $io, Composer $composer, $type = 'ezpublish-legacy-extension' )
    {
        parent::__construct( $io, $composer, $type );
    }

    public function getInstallPath( PackageInterface $package )
    {
        $extensionName = $this->getExtensionName( $package );
        return parent::getInstallPath( $package ) . '/extension/' . $extensionName;
    }

    public function install( InstalledRepositoryInterface $repo, PackageInterface $package )
    {
        parent::install( $repo, $package );
        $extensionName = $this->getExtensionName( $package );
        $this->activateExtension( $extensionName );
        $this->regenerateAutoloads();
        $this->clearCaches();
    }

    public function update( InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target )
    {
        parent::update( $repo, $initial, $target );
        $this->regenerateAutoloads();
        $this->clearCaches();
    }

    /**
     * We do not call parent::uninstall directly because it tries to do too many things (eg remove the vendor/vendorname folder
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface $package
     * @throws \InvalidArgumentException
     */
    public function uninstall( InstalledRepositoryInterface $repo, PackageInterface $package )
    {
        if ( !$repo->hasPackage( $package ) )
        {
            throw new \InvalidArgumentException( 'Package is not installed: ' . $package );
        }
        $this->removeCode( $package );
        $this->removeBinaries( $package );
        $repo->removePackage( $package );

        $extensionName = $this->getExtensionName( $package );
        $this->deactivateExtension( $extensionName );
        $this->regenerateAutoloads();
        $this->clearCaches();
    }

    /**
     * This one gets called by parent::removeCode(), so we overload it
     * @param PackageInterface $package
     * @return string
     */
    protected function getPackageBasePath( PackageInterface $package )
    {
        return $this->getInstallPath( $package );
    }

    /**
     * @param PackageInterface $package
     * @return string
     *
     * @todo what if there are more than 2 slashes in package name? should we transform them? Also other characters non-valid for filenames
     */
    protected function getExtensionName( PackageInterface $package )
    {
        list( $vendor, $extensionName ) = explode( '/', $package->getPrettyName(), 2 );
        return $extensionName;
    }

    // *** code dealing with eZ Publish ***

    /**
     * @todo we could find a way, via e.g. the "extras" key in composer.json, to let the user tell us if this extension
     *       has to be activated in some siteaccess only...
     * @todo support site.ini, site.ini.append as well as site.ini.append.php?
     * @todo examine interaction with composer packages installing only-settings. Is final result predictable, or does
     *       it depend on order of installation?
     */
    protected function activateExtension( $extensionName )
    {
        $settingsFile = $this->ezpublishLegacyDir . '/settings/override/site.ini.append.php';
        if ( !is_file( $settingsFile ) )
        {
            /// @todo what if this is run before user goes thru setup wizard? will it activate the extension?
            ///       Apparently not for now - see kernel/setup/ezstep_create_sites.php
            $this->io->write( "<warning>Extension $extensionName not activated because file '$settingsFile' is missing. You will have to do it manually later</warning>" );
            return;
        }

        // Q: can we safely use existing eZ Publish code instead of doing the changes by hand?
        // note that:
        // 1 - eZP might not be set up well, if at all, when this code is run
        // 2 - tested: the following code has the drawback of eliminating array-reset setting assignments, which means
        //     it is not 100% a stable operation ( what if an extra siteaccess is declared in an extension but reset in
        //     override/site.ini? )
        /*$currentDir = getcwd();
        chdir( $this->ezpublishLegacyDir );
        require_once 'autoload.php';
        $siteINI = eZINI::instance( 'site.ini.append', 'settings/override', null, null, false, true );
        $toSave = $siteINI->variable( 'ExtensionSettings', 'ActiveExtensions' );
        if (
            ( is_array( $toSave ) && in_array( $extensionName, $toSave ) ) ||
            ( is_array( $siteINI->variable( 'ExtensionSettings', 'ActiveAccessExtensions' ) ) && in_array( $extensionName, $siteINI->variable( 'ExtensionSettings', 'ActiveAccessExtensions' ) ) )
        )
        {
            $this->io->write( "<info>Extension $extensionName was already activated in file '$settingsFile'</info>" );
            chdir( $currentDir );
            return;
        }
        $toSave[] = $extensionName;
        $siteINI->setVariable( "ExtensionSettings", "ActiveExtensions", $toSave );
        $siteINI->save( 'site.ini.append', '.php', false, false );
        chdir( $currentDir );
        $this->io->write( "<info>File '$settingsFile' updated</info>" );*/

        // Doing all the work by hand

        $activationLines = array( 'ActiveExtensions[]=' . $extensionName, 'ActiveAccessExtensions[]=' . $extensionName );
        $settings = file( $settingsFile, FILE_IGNORE_NEW_LINES );
        $inBlock = false;
        $lastLine = false;
        $alreadyActive = false;
        $firstBlockLine = false;
        foreach( $settings as $i => $line )
        {
            if ( strpos( $line, '[ExtensionSettings]' ) === 0 )
            {
                $inBlock = true;
                $lastLine = $i;
                continue;
            }

            if ( strpos( $line, '[' ) === 0 )
            {
                $inBlock = false;
                if ( $firstBlockLine == false )
                {
                    $firstBlockLine = $i;
                }
                continue;
            }

            if ( $inBlock )
            {
                foreach( $activationLines as $activationLine )
                {
                    if ( strpos( $line, $activationLine ) === 0 )
                    {
                        $alreadyActive = true;
                        break 2;
                    }
                }

                if ( strpos( $line, 'ActiveExtensions[]=' ) === 0 )
                {
                    $lastLine = $i;
                }
            }
        }

        if ( $alreadyActive )
        {
            $this->io->write( "<info>Extension $extensionName was already activated in file '$settingsFile'</info>" );
            return;
        }

        if ( $lastLine )
        {
            // insert extension activation at end of other extensions
            $settings = array_merge(
                array_slice( $settings, 0, $lastLine + 1 ),
                array( $activationLines[0] ),
                array_slice( $settings, $lastLine + 1 ) );
        }
        else
        {
            // corner cases: a site.ini.append.php file exists but has no active extensions...
            if ( $firstBlockLine )
            {
                array_merge(
                    array_slice( $settings, 0, $firstBlockLine ),
                    array( '', '[ExtensionSettings]', $activationLines[0], '' ),
                    array_slice( $settings, $firstBlockLine ) );
            }
            else
            {
                $settings = array_merge( settings, array( '', '[ExtensionSettings]', $activationLines[0], '' ) );
            }
        }

        /// @todo make a backup first
        file_put_contents( $settingsFile, implode( "\n", $settings ) );

        $this->io->write( "<info>File '$settingsFile' updated</info>" );
    }

    /**
     * @todo support site.ini, site.ini.append as well as site.ini.override.php?
     */
    protected function deactivateExtension( $extensionName )
    {
        $settingsFile = $this->ezpublishLegacyDir . '/settings/override/site.ini.append.php';
        if ( !is_file( $settingsFile ) )
        {
            return;
        }

        $activationLines = array( 'ActiveExtensions[]=' . $extensionName, 'ActiveAccessExtensions[]=' . $extensionName );
        $settings = file( $settingsFile, FILE_IGNORE_NEW_LINES );
        $inBlock = false;
        $alreadyActive = false;
        foreach( $settings as $i => $line )
        {
            if ( strpos( $line, '[ExtensionSettings]' ) === 0 )
            {
                $inBlock = true;
                continue;
            }
            else if ( strpos( $line, '[' ) === 0 )
            {
                $inBlock = false;
                continue;
            }

            if ( $inBlock )
            {
                foreach( $activationLines as $activationLine )
                {
                    /// @todo to be on the safe side we should actually use preg matching
                    if ( strpos( $line, $activationLine ) === 0 )
                    {
                        $alreadyActive = true;
                        unset( $settings[$i] );
                        continue;
                    }
                }
                // we do not break here - in case extension is activated twice or more...
            }
        }

        if ( !$alreadyActive )
        {
            return;
        }

        /// @todo make a backup first
        file_put_contents( $settingsFile, implode( "\n", $settings ) );

        $this->io->write( "<info>File '$settingsFile' updated</info>" );
    }
}
