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

/**
 * Installer for legacy extensions.
 *
 * All this class does is to:
 * - tell Composer that extensions have to be installed in a different directory
 * - enable/disable the extension in settings/override/site.ini.append.php
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
        list( $vendor, $packageName ) = explode( '/', $package->getPrettyName(), 2 );
        return parent::getInstallPath( $package ) . '/extension/' . $packageName;
    }

    public function install( InstalledRepositoryInterface $repo, PackageInterface $package )
    {
        parent::install( $repo, $package );
        list( $vendor, $extensionName ) = explode( '/', $package->getPrettyName(), 2 );
        $this->activateExtension( $extensionName );
        $this->regenerateAutoloads();
    }

    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        parent::uninstall( $repo, $package );
        list( $vendor, $extensionName ) = explode( '/', $package->getPrettyName(), 2 );
        $this->deactivateEtxtension( $extensionName );
        $this->regenerateAutoloads();
    }

    /**
     * @todo we could find a way, via e.g. the "extras" key in composer.json, to let the user tell us if this extension
     * has to be activated in some siteaccess only
     */
    protected function activateExtension( $extensionName )
    {
        $settingsFile = $this->ezpublishLegacyDir . '/settings/override/site.ini.append.php';
        if ( !is_file( $settingsFile ) )
        {
            // throw an exception or only a warning?
            // what if this is run before user goes thru setup wizard? will it overwrite our work?
        }

        // Q: can we reuse existing code instead of doing this? (eg see what is done when doing same via backoffice)
        // note that ezp might not be set up well if at all when this code is run

        $activationLine = 'ActiveExtensions[]=' . $extensionName;
        $settings = file( $settingsFile, FILE_IGNORE_NEW_LINES );
        $inBlock = false;
        $lastLine = false;
        $alreadyActive = false;
        foreach( $settings as $i => $line )
        {
            if ( strpos( $line, '[ExtensionSettings]' ) === 0 )
            {
                $inBlock = true;
                $lastLine = $i;
                continue;
            }
            else if ( strpos( $line, '[' ) === 0 )
            {
                $inBlock = false;
                continue;
            }

            if ( $inBlock )
            {
                if ( strpos( $line, $activationLine ) === 0 )
                {
                    $alreadyActive = true;
                    break;
                }
                else if ( strpos( $line, 'ActiveExtensions[]=' ) === 0 )
                {
                    $lastLine = $i;
                }
            }
        }

        if ( $alreadyActive )
        {
            return;
        }

        if ( !$lastLine )
        {
            /// @todo handle php opening + comment tags
            array_unshift( $settings, '[ExtensionSettings]', $activationLine, '' );
        }
        else
        {
            // insert extension activation at end of other extensions
            $settings = array_merge(
                array_slice( $settings, 0, $lastLine + 1 ),
                array( $activationLine ),
                array_slice( $settings, $lastLine + 1 ) );
        }

        /// @todo make a backup first
        file_put_contents( $settingsFile, implode( "\n", $settings ) );

    }

    protected function deactivateExtension( $extensionName )
    {
        $settingsFile = $this->ezpublishLegacyDir . '/settings/override/site.ini.append.php';
        if ( !is_file( $settingsFile ) )
        {
            return;|
        }

        $activationLine = 'ActiveExtensions[]=' . $extensionName;
        $settings = file( $settingsFile, FILE_IGNORE_NEW_LINES );
        $inBlock = false;
        $alreadyActive = false;
        foreach( $settings as $i => $line )
        {
            if ( strpos( $line, '[ExtensionSettings]' ) === 0 )
            {
                $inBlock = true;
                $lastLine = $i;
                continue;
            }
            else if ( strpos( $line, '[' ) === 0 )
            {
                $inBlock = false;
                continue;
            }

            if ( $inBlock )
            {
                if ( strpos( $line, $activationLine ) === 0 )
                {
                    $alreadyActive = true;
                    unset( $settings[$i] );
                    // we do not break - in case extension is activated twice!
                }
            }
        }

        if ( !$alreadyActive )
        {
            return;
        }

        /// @todo make a backup first
        file_put_contents( $settingsFile, implode( "\n", $settings ) );

    }

    protected function regenerateAutoloads()
    {
        /// @todo should we use more options?
        exec( "cd $this->ezpublishLegacyDir && php bin/php/ezpgenerateautloads.php" );
    }
}
