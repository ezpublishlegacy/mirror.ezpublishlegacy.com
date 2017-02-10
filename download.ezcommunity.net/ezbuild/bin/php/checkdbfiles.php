#!/usr/bin/env php
<?php
//
// Created on: <26-Mar-2004 10:31:14 amos>
//
// Copyright (C) 1999-2005 eZ systems as. All rights reserved.
//
// This source file is part of the eZ publish (tm) Open Source Content
// Management System.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 as published by the Free
// Software Foundation and appearing in the file LICENSE included in
// the packaging of this file.
//
// Licencees holding a valid "eZ publish professional licence" version 2
// may use this file in accordance with the "eZ publish professional licence"
// version 2 Agreement provided with the Software.
//
// This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
// THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
// PURPOSE.
//
// The "eZ publish professional licence" version 2 is available at
// http://ez.no/ez_publish/licences/professional/ and in the file
// PROFESSIONAL_LICENCE included in the packaging of this file.
// For pricing of this licence please contact us via e-mail to licence@ez.no.
// Further contact information is available at http://ez.no/company/contact/.
//
// The "GNU General Public License" (GPL) is available at
// http://www.gnu.org/copyleft/gpl.html.
//
// Contact licence@ez.no if any conditions of this licencing isn't clear to
// you.
//

include_once( 'lib/ezutils/classes/ezcli.php' );
include_once( 'kernel/classes/ezscript.php' );

$cli =& eZCLI::instance();
$script =& eZScript::instance( array( 'description' => ( "eZ publish DB file verifier\n\n" .
                                                         "Checks the database update files and gives a report on them.\n" .
                                                         "It will show which files are missing and which should not be present.\n" .
                                                         "\n" .
                                                         "For each file with problems it will output a status and the filepath\n" .
                                                         "The status will be one of these:\n" .
                                                         " '?' file is not defined in upgrade path\n" .
                                                         " '!' file defined in upgrade path but missing on filesystem\n" .
                                                         " 'A' file is present in working copy but not in the original stable branch\n" .
                                                         " 'C' file data conflicts with the original stable branch\n" .
                                                         "\n" .
                                                         "Example output:\n" .
                                                         "  checkdbfiles.php\n" .
                                                         "  ? update/database/mysql/3.5/dbupdate-3.5.0-to-3.5.1.sql" ),
                                      'use-session' => false,
                                      'use-modules' => false,
                                      'use-extensions' => true ) );

$script->startup();

$options = $script->getOptions( "[no-verify-branches]",
                                "",
                                array( 'no-verify-branches' => "Do not verify the content of the files with previous branches (To avoid SVN usage)"
                                       ) );
$script->initialize();

$dbTypes = array();
$dbTypes[] = 'mysql';
$dbTypes[] = 'postgresql';

$branches = array();
$branches[] = '3.0';
$branches[] = '3.1';
$branches[] = '3.2';
$branches[] = '3.3';
$branches[] = '3.4';
$branches[] = '3.5';
$branches[] = '3.6';

// Controls the lowest version which will be exported and verified against current data
$lowestExportVersion = '3.3';

/********************************************************
*** NOTE: The following arrays do not follow the
***       coding standard, the reason for this is
***       to make it easy to merge any changes between
***       the various eZ publish branches.
*********************************************************/

$versions = array();
$versions30 = array( 'stable' => array( array( '2.9.7', '3.0-1' ),
                                        array( '3.0-1', '3.0-2' ) ),
                     'info_files' => true );
$versions31 = array( 'unstable' => array( array( '3.0-2', '3.1.0-1' ),
                                          array( '3.1.0-1', '3.1.0-2' ) ),
                     'stable' => array( array( '3.1.0-2', '3.1-1' ) ),
                     'info_files' => true );
$versions32 = array( 'unstable' => array( array( '3.1-1', '3.2.0-1' ),
                                          array( '3.2.0-1', '3.2.0-2' ),
                                          array( '3.2.0-2', '3.2-1' ) ),
                     'unstable_subdir' => 'unstable',
                     'stable' => array( array( '3.1-1', '3.2-1' )
                                        ,array( '3.2-1', '3.2-2' )
                                        ,array( '3.2-2', '3.2-3' )
                                        ,array( '3.2-3', '3.2-4' )
                                        ,array( '3.2-4', '3.2-5' )
                                        ,array( '3.2-5', '3.2-6' )
                                        ) );
$versions33 = array( 'unstable' => array( array( '3.2-3', '3.3.0-1' ),
                                          array( '3.3.0-1', '3.3.0-2' ),
                                          array( '3.3.0-2', '3.3-1' ) ),
                     'unstable_subdir' => 'unstable',
                     'stable' => array( array( '3.2-4', '3.3-1' )
                                        ,array( '3.3-1', '3.3-2' )
                                        ,array( '3.3-2', '3.3-3' )
                                        ,array( '3.3-3', '3.3-4' )
                                        ,array( '3.3-4', '3.3-5' )
                                        ,array( '3.3-5', '3.3-6' )
                                        ,array( '3.3-6', '3.3-7' )
                                        ) );
$versions34 = array( 'unstable' => array( array( '3.3-3', '3.4.0alpha1' )
                                          ,array( '3.4.0alpha1', '3.4.0alpha2' )
                                          ,array( '3.4.0alpha2', '3.4.0alpha3' )
                                          ,array( '3.4.0alpha3', '3.4.0alpha4' )
                                          ,array( '3.4.0alpha4', '3.4.0beta1' )
                                          ,array( '3.4.0beta1', '3.4.0beta2' )
                                          ,array( '3.4.0beta2', '3.4.0' )
                                          ),
                     'unstable_subdir' => 'unstable',
                     'stable' => array( array( '3.3-5', '3.4.0' )
		                                ,array( '3.4.0', '3.4.1' )
		                                ,array( '3.4.1', '3.4.2' )
		                                ,array( '3.4.2', '3.4.3' )
		                                ,array( '3.4.3', '3.4.4' )
		                                ,array( '3.4.4', '3.4.5' )
		                                ,array( '3.4.5', '3.4.6' )
                                        ) );
$versions35 = array( 'unstable' => array( array( '3.4.2', '3.5.0alpha1' )
                                          ,array( '3.5.0alpha1', '3.5.0beta1' )
                                          ,array( '3.5.0beta1', '3.5.0rc1' )
                                          ,array( '3.5.0rc1', '3.5.0rc2' )
                                          ,array( '3.5.0rc2', '3.5.0' )
                                          ),
                     'unstable_subdir' => 'unstable',
                     'stable' => array( array( '3.4.4', '3.5.0' )
                                        ,array( '3.5.0', '3.5.1' )
                                        ,array( '3.5.1', '3.5.2' )
                                        ) );
$versions36 = array( 'unstable' => array( array( '3.5.2', '3.6.0beta1' )
                                          ,array( '3.6.0beta1', '3.6.0rc1' )
                                          ,array( '3.6.0rc1', '3.6.0' )
                                          ),
                     'unstable_subdir' => 'unstable',
                     'stable' => array( array( '3.5.2', '3.6.0' )
                                        ,array( '3.6.0', '3.6.1' ) ) );

$versions['3.0'] = $versions30;
$versions['3.1'] = $versions31;
$versions['3.2'] = $versions32;
$versions['3.3'] = $versions33;
$versions['3.4'] = $versions34;
$versions['3.5'] = $versions35;
$versions['3.6'] = $versions36;

$fileList = array();
$missingFileList = array();
$conflictFileList = array();
$exportMissingFileList = array();
$scannedDirs = array();

function handleVersionList( $basePath, $subdir,
                            &$fileList, &$missingFileList, &$conflictFileList, &$scannedDirs,
                            $useInfoFiles, $versionList )
{
    $updatePath = $basePath . $subdir;
    if ( is_dir( $updatePath ) )
    {
        if ( !in_array( $updatePath, $scannedDirs ) )
        {
            $dh = opendir( $updatePath );
            if ( $dh )
            {
                while ( ( $file = readdir( $dh ) ) !== false )
                {
                    if ( $file == '.' or
                         $file == '..' or
                         substr( $file, strlen( $file ) - 1, 1 ) == '~' or
                         is_dir( $updatePath . '/' . $file ) )
                        continue;
                    $fileList[] = $updatePath . '/' . $file;
                }
                closedir( $dh );
            }
            $fileList = array_unique( $fileList );
            $scannedDirs[] = $updatePath;
        }
    }

    foreach ( $versionList as $versionEntry )
    {
        $from = $versionEntry[0];
        $to = $versionEntry[1];
        $dir = $updatePath;
        $file = $dir . '/dbupdate-' . $from . '-to-' . $to . '.sql';
        $fileList = array_diff( $fileList, array( $file ) );
        if ( !file_exists( $file ) )
        {
            $missingFileList[] = $file;
        }
        if ( $useInfoFiles )
        {
            $infoFile = $dir . '/dbupdate-' . $from . '-to-' . $to . '.info';
            $fileList = array_diff( $fileList, array( $infoFile ) );
            if ( !file_exists( $infoFile ) )
            {
                $missingFileList[] = $infoFile;
            }
        }
    }
}

function handleExportVersionList( $basePath, $exportBasePath, $subdir,
                                  &$fileList, &$missingFileList, &$exportMissingFileList, &$conflictFileList, &$scannedDirs,
                                  $useInfoFiles, $versionList )
{
    $updatePath = $basePath . $subdir;
    $exportUpdatePath = $exportBasePath . $subdir;
    foreach ( $versionList as $versionEntry )
    {
        $from = $versionEntry[0];
        $to = $versionEntry[1];
        $file = $updatePath . '/dbupdate-' . $from . '-to-' . $to . '.sql';
        $exportFile = $exportUpdatePath . '/dbupdate-' . $from . '-to-' . $to . '.sql';
        if ( file_exists( $file ) and file_exists( $exportFile ) )
        {
            $srcMD5 = md5_file( $file );
            $dstMD5 = md5_file( $exportFile );
            // If the MD5s differ we flag it as a conflict
            if ( strcmp( $srcMD5, $dstMD5 ) != 0 )
            {
                $conflictFileList[] = $file;
            }
        }
        else
        {
            $exportMissingFileList[] = $file;
        }

        if ( $useInfoFiles )
        {
            $infoFile = $updatePath . '/dbupdate-' . $from . '-to-' . $to . '.info';
            $exportInfoFile = $exportUpdatePath . '/dbupdate-' . $from . '-to-' . $to . '.sql';
            if ( file_exists( $infoFile ) and file_exists( $exportInfoFile ) )
            {
                $srcMD5 = md5_file( $infoFile );
                $dstMD5 = md5_file( $exportInfoFile );
                // If the MD5s differ we flag it as a conflict
                if ( strcmp( $srcMD5, $dstMD5 ) != 0 )
                {
                    $conflictFileList[] = $file;
                }
            }
            else
            {
                $exportMissingFileList[] = $infoFile;
            }
        }
    }
}

function exportSVNVersion( $version, $exportPath )
{
    $versionPath = $exportPath . '/' . $version;
    if ( file_exists( $versionPath ) )
        return true;

    $svn = "svn export http://zev.ez.no/svn/nextgen/stable/$version/update/database '$versionPath'";
    exec( $svn, $output, $code );
    if ( $code != 0 )
    {
        print( "Failed to export using:\n$svn\n" );
        return false;
    }
    return file_exists( $versionPath );
}

// Check for required md5_file function
if ( !function_exists( 'md5_file' ) )
{
    $cli->error( "The function 'md5_file' does not exist in your PHP version" );
    $cli->error( "You must upgrade PHP to a version (4.2.0) that has this function" );
    $script->shutdown( 1 );
}

if ( !$options['no-verify-branches'] )
{
    // Clean up the export path and/or recreate the directory path
    $exportPath = '/tmp/';
    if ( isset( $_SERVER['TMPDIR'] ) )
        $exportPath = $_SERVER['TMPDIR'] . '/';
    if ( isset( $_SERVER['USER'] ) )
        $exportPath .= "ez-" . $_SERVER['USER'];
    $exportPath .= "/dbupdate-check/";
    if ( file_exists( $exportPath ) )
    {
        include_once( 'lib/ezfile/classes/ezdir.php' );
        eZDir::recursiveDelete( $exportPath );
    }
    include_once( 'lib/ezfile/classes/ezdir.php' );
    eZDir::mkdir( $exportPath, octdec( "777" ), true );
}

// Figure out the current branch, we do not want to export it
include_once( 'lib/version.php' );
$currentBranch = EZ_SDK_VERSION_MAJOR . '.' . EZ_SDK_VERSION_MINOR;

foreach ( $dbTypes as $dbType )
{
    foreach ( $branches as $branch )
    {
        $basePath = 'update/database/' . $dbType . '/' . $branch;
        $versionList = $versions[$branch];
        $useInfoFiles = false;
        if ( isset( $versionList['info_files'] ) )
        {
            $useInfoFiles = $versionList['info_files'];
        }
        if ( isset( $versionList['unstable'] ) )
        {
            $subdir = false;
            if ( isset( $versionList['unstable_subdir'] ) )
            {
                $subdir = '/' . $versionList['unstable_subdir'];
            }
            handleVersionList( $basePath, $subdir,
                               $fileList, $missingFileList, $conflictFileList, $scannedDirs,
                               $useInfoFiles, $versionList['unstable'] );
        }
        if ( isset( $versionList['stable'] ) )
        {
            $subdir = false;
            if ( isset( $versionList['stable_subdir'] ) )
            {
                $subdir = '/' . $versionList['stable_subdir'];
            }
            handleVersionList( $basePath, $subdir,
                               $fileList, $missingFileList, $conflictFileList, $scannedDirs,
                               $useInfoFiles, $versionList['stable'] );
        }

        if ( !$options['no-verify-branches'] and
             version_compare( $branch, $lowestExportVersion ) >= 0 and
             version_compare( $branch, $currentBranch ) < 0 )
        {
            if ( !exportSVNVersion( $branch, $exportPath ) )
            {
                $conflictFileList[] = $dir . '/' . $branch;
                continue;
            }
            if ( isset( $versionList['unstable'] ) )
            {
                $exportBasePath = $exportPath . '/' . $branch . '/' . $dbType . '/' . $branch;
                $subdir = false;
                if ( isset( $versionList['unstable_subdir'] ) )
                {
                    $subdir = '/' . $versionList['unstable_subdir'];
                }
                handleExportVersionList( $basePath, $exportBasePath, $subdir,
                                         $fileList, $missingFileList, $exportMissingFileList, $conflictFileList, $scannedDirs,
                                         $useInfoFiles, $versionList['unstable'] );
            }
            if ( isset( $versionList['stable'] ) )
            {
                $exportBasePath = $exportPath . '/' . $branch . '/' . $dbType . '/' . $branch;
                $subdir = false;
                if ( isset( $versionList['stable_subdir'] ) )
                {
                    $subdir = '/' . $versionList['stable_subdir'];
                }
                handleExportVersionList( $basePath, $exportBasePath, $subdir,
                                         $fileList, $missingFileList, $exportMissingFileList, $conflictFileList, $scannedDirs,
                                         $useInfoFiles, $versionList['stable'] );
            }
        }
    }
}

if ( count( $missingFileList ) > 0 or
     count( $exportMissingFileList ) > 0 or
     count( $fileList ) > 0 or
     count( $conflictFileList ) > 0 )
{
    if ( count( $fileList ) > 0 )
    {
        foreach ( $fileList as $file )
        {
            print( '? ' . $file . "\n" );
        }
    }

    if ( count( $missingFileList ) > 0 )
    {
        foreach ( $missingFileList as $file )
        {
            print( '! ' . $file . "\n" );
        }
    }

    if ( count( $exportMissingFileList ) > 0 )
    {
        foreach ( $exportMissingFileList as $file )
        {
            print( 'A ' . $file . "\n" );
        }
    }

    if ( count( $conflictFileList ) > 0 )
    {
        foreach ( $conflictFileList as $file )
        {
            print( 'C ' . $file . "\n" );
        }
    }
    $script->setExitCode( 1 );
}

if ( !$options['no-verify-branches'] )
{
    // Cleanup any exports
    if ( file_exists( $exportPath ) )
    {
        include_once( 'lib/ezfile/classes/ezdir.php' );
        eZDir::recursiveDelete( $exportPath );
    }
}

$script->shutdown();

?>
