<?php
/*
 You may not change or alter any portion of this comment or credits of
 supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit
 authors.

 This program is distributed in the hope that it will be useful, but
 WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */
/**
 * Module: iSearch
 *
 * @package   module\Isearch\include
 * @author    Richard Griffith <richard@geekwright.com>
 * @author    trabis <lusopoemas@gmail.com>
 * @author    XOOPS Module Development Team
 * @copyright Copyright (c) 2001-2017 {@link https://xoops.org XOOPS Project}
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU Public License
 * @since     File available since version 1.91
 */

use XoopsModules\Isearch;

/* @internal {Make sure you PROTECT THIS FILE} */

if ((!defined('XOOPS_ROOT_PATH'))
    || !($GLOBALS['xoopsUser'] instanceof \XoopsUser)
    || !($GLOBALS['xoopsUser']->isAdmin())) {
    exit('Restricted access' . PHP_EOL);
}

/**
 * Pre-installation checks before installation of iSearch
 *
 * @param XoopsModule $module
 * @param string      $prev_version version * 100
 *
 * @see Utility
 *
 * @return bool success ok to install
 *
 */
function xoops_module_pre_update_isearch(\XoopsModule $module, $prev_version)
{
    /** @var \XoopsModules\Isearch\Helper $helper */
    /** @var \XoopsModules\Isearch\Utility $utility */
    $moduleDirName = basename(dirname(__DIR__));
    $helper       = Isearch\Helper::getInstance();
    $utility      = new Isearch\Utility();

    $xoopsSuccess = $utility::checkVerXoops($module);
    $phpSuccess   = $utility::checkVerPhp($module);
    return $xoopsSuccess && $phpSuccess;
}

/**
 * Upgrade works to update iSearch from previous versions
 *
 * @param XoopsModule $module
 * @param string      $prev_version version * 100
 *
 * @see Xmf\Module\Admin
 * @see Utility
 *
 * @return bool
 *
 */
function xoops_module_update_isearch(\XoopsModule $module, $prev_version)
{
    $moduleDirName = basename(dirname(__DIR__));
    $capsDirName   = strtoupper($moduleDirName);

    /** @var Isearch\Helper $helper */
    /** @var Isearch\Utility $utility */
    /** @var Isearch\Configurator $configurator */
    $helper  = Isearch\Helper::getInstance();
    $utility = new Isearch\Utility();
    $configurator = new Isearch\Configurator();


    $success = true;

    $helper->loadLanguage('modinfo');
    $helper->loadLanguage('admin');

    //----------------------------------------------------------------
    // Remove previous .css, .js and .images directories since they've
    // been relocated to ./assets
    //----------------------------------------------------------------
    $old_directories = [
        $isHelper->path('css/'),
        $isHelper->path('js/'),
        $isHelper->path('images/')
    ];
    foreach ($old_directories as $old_dir) {
        $dirInfo = new \SplFileInfo($old_dir);
        if ($dirInfo->isDir()) {
            // The directory exists so delete it
            if (false === $utility::rrmdir($old_dir)) {
                $module->setErrors(sprintf(_AM_ISEARCH_ERROR_BAD_DEL_PATH, $old_dir));

                return false;
            }
        }
        unset($dirInfo);
    }

    //-----------------------------------------------------------------------
    // Remove ./template/*.html (except index.html) files since they've
    // been replaced by *.tpl files
    //-----------------------------------------------------------------------
    $path       = $isHelper->path('templates/');
    $unfiltered = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    $iterator   = new RegexIterator($unfiltered, "/.*\.html/");
    foreach ($iterator as $name => $fObj) {
        if (($fObj->isFile()) && ('index.html' !== $fObj->getFilename())) {
            if (false === ($success = unlink($fObj->getPathname()))) {
                $module->setErrors(sprintf(_AM_ISEARCH_ERROR_BAD_REMOVE, $fObj->getPathname()));

                return false;
            }
        }
    }

    //-----------------------------------------------------------------------
    // Now remove a some misc files that were renamed or deprecated
    //-----------------------------------------------------------------------
    $oldFiles = [
        $isHelper->path('changelog.txt'),
        $isHelper->path('licence.txt'),
        $isHelper->path('lang.diff'),
        $isHelper->path('admin/functions.php'),
        $isHelper->path('assets/js/dhtmlXCommon.js'),
        $isHelper->path('assets/js/dhtmlXTabbar.js'),
        $isHelper->path('assets/js/dhtmlXTabbar_start.js')
    ];
    foreach ($oldFiles as $file) {
        if (is_file($file)) {
            if (false === ($delOk = unlink($file))) {
                $module->setErrors(sprintf(_AM_ISEARCH_ERROR_BAD_REMOVE, $file));
            }
            $success = $success && $delOk;
        }
    }

    return $success;
}
