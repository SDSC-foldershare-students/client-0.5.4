<?php

namespace Drupal\foldershare;

use Drupal\file\FileInterface;

use Drupal\foldershare\Constants;
use Drupal\foldershare\Messages;
use Drupal\foldershare\Settings;

/**
 * Defines file utility functions used throughout the module.
 *
 * <B>Warning:</B> This class is strictly internal to the FolderShare
 * module. The class's existance, name, and content may change from
 * release to release without any promise of backwards compatability.
 *
 * This class consolidates most of the local file and directory handling
 * functions used by this module. It defines two groups of static functions:
 * - Wrappers for standard PHP file and directory functions.
 * - Custom functions used by this module for files and directories.
 *
 * <B>Wrappers</B>
 * PHP provides a set of standard functions to manipulate file paths
 * (e.g. basename() and dirname()), get and change file attributes
 * (e.g. filesize(), file_exists(), chmod()), and create and delete
 * files and directories (e.g. touch(), mkdir(), rmdir(), and unlink()).
 * These functions initially worked only with local files, but as of
 * PHP 5.0.0, many of these gained support for URL wrappers and file
 * paths of the form SCHEME://PATH.
 *
 * Prior to PHP 5.0.0, Drupal provided wrappers for many of these PHP
 * functions to add support for URL wrappers, or work around quirks in
 * PHP's implementation. These functions existed in Drupal's "file.inc"
 * and had names of the form drupal_NAME(), where NAME was the original
 * PHP function name (e.g. drupal_unlink()).
 *
 * As of Drupal 8.0, these drupal_NAME() functions have been deprecated
 * and moved into an implementation of the FileSystemInterface class.
 * Ideally this isolates file system code into a single class implementation
 * and enables a future where remote file systems are supported via stream
 * wrappers hidden from the rest of Drupal code. Using these new functions,
 * code gets the 'file_system' service to get a FileSystemInterface
 * instance, and then calls a function on that instance (e.g.
 * \Drupal::service('file_system')->NAME()).
 *
 * Unfortunately, Drupal's FileSystemInterface and service are incomplete.
 * While they implement the old drupal_NAME() functions, they do not provide
 * access to other key PHP functions for files and directories, such as
 * filesize(), file_exists(), touch(), and scandir(). These are essential
 * and are needed throughout Drupal's code and this module. This forces code
 * to use an odd mix of FileSystemInterface calls and straight PHP calls.
 *
 * This utility class centralizes code dealing with files and directories.
 * Many of the class's functions call Drupal's 'file_system' service,
 * while other functions call PHP's functions directly. As Drupal's
 * FileSystemInterface matures, the functions that call PHP will migrate to
 * that interface.
 *
 * <B>Custom functions</B>
 * In addition to the above PHP and Drupal wrapper functions, this class
 * defines a variety of helper functions for dealing with files and
 * directories. This includes functions to build URIs for the module's
 * directories, check the legality of a proposed name, watch for file name
 * extensions, and so forth.
 *
 * @ingroup foldershare
 */
class FileUtilities {

  /*---------------------------------------------------------------------
   *
   * Constants.
   *
   *---------------------------------------------------------------------*/

  /**
   * The temp subdirectory in the module's local files directory tree.
   */
  const TEMP_DIRECTORY = 'temp';

  /*---------------------------------------------------------------------
   *
   * Path functions (wrappers).
   *
   *---------------------------------------------------------------------*/

  /**
   * Returns the trailing name component of a URI or local path.
   *
   * @param string $path
   *   The URI or local path to a file or directory.
   * @param string $suffix
   *   The suffix to remove, if non-NULL.
   *
   * @return string
   *   Returns the name following the last directory separator on the
   *   URI or local file path.
   *
   * @see ::dirname()
   * @see \Drupal\Core\File\FileSystemInterface::basename()
   * @see drupal_basename()
   * @see https://php.net/manual/en/function.basename.php
   */
  public static function basename(string $path, string $suffix = NULL) {
    return \Drupal::service('file_system')->basename($path, $suffix);
  }

  /**
   * Returns the parent directory path component of a URI or local path.
   *
   * @param string $path
   *   The URI or local path to a file or directory.
   *
   * @return string
   *   Returns the URI or local path with the name following the last
   *   directory separator removed.
   *
   * @see ::basename()
   * @see \Drupal\Core\File\FileSystemInterface::dirname()
   * @see drupal_dirname()
   * @see https://php.net/manual/en/function.dirname.php
   */
  public static function dirname(string $path) {
    // PHP's dirname() documentation notes that directory names are
    // separated by '/' on Linux, and either '/' or '\' on Windows.
    // This allows dirname() to parse a directory path whether it is
    // a URI or a local file system path.
    return \Drupal::service('file_system')->dirname($path);
  }

  /**
   * Returns the canonicalized absolute path of a URI or local path.
   *
   * URIs for local files or directories are converted to absolute paths.
   * Local paths are converted to absolute paths by collapsing '.' and '..'
   * in the path, removing trailing '/', and following symbolic links.
   *
   * @param string $path
   *   The URI or local path to a file or directory.
   *
   * @return string|bool
   *   Returns the absolute local path to the file or directory, or FALSE
   *   if the file or directory does not exist or the user does not have
   *   suitable permissions.
   *
   * @see \Drupal\Core\File\FileSystemInterface::realpath()
   * @see drupal_realpath()
   * @see https://php.net/manual/en/function.realpath.php
   */
  public static function realpath(string $path) {
    return \Drupal::service('file_system')->realpath($path);
  }

  /*---------------------------------------------------------------------
   *
   * File and directory attributes (wrappers).
   *
   *---------------------------------------------------------------------*/

  /**
   * Changes permissions on a file or directory.
   *
   * See PHP's chmod() documentation for an explanation of modes.
   *
   * Unlike the PHP chmod() function, this function (and Drupal's
   * FileSystemInterface implementations) default the mode to the server's
   * 'file_chmod_directory' and 'file_chmod_file' configuration settings.
   * By default, these give everyone read access so that the web server
   * can read and deliver the file or directory.
   *
   * @param string $path
   *   The URI or local path to parse.
   * @param mixed $mode
   *   (optional) The permissions mode of the file. This is ignored on
   *   Windows. The default is NULL, which defaults the mode to the server's
   *   configuration settings.
   *
   * @return bool
   *   Returns TRUE on success and FALSE on failure.
   *
   * @see ::is_readable()
   * @see ::is_writable()
   * @see \Drupal\Core\File\FileSystemInterface::chmod()
   * @see drupal_chmod()
   * @see https://php.net/manual/en/function.chmod.php
   */
  public static function chmod(string $path, $mode = NULL) {
    return \Drupal::service('file_system')->chmod($path, $mode);
  }

  /**
   * Returns TRUE if the local path is for a file or directory that exists.
   *
   * @param string $path
   *   The URI or local path to a file or directory.
   *
   * @return bool
   *   Returns TRUE if the path is for an item that exists, and FALSE
   *   if it does not.
   *
   * @see ::is_readable()
   * @see https://php.net/manual/en/function.is-readable.php
   */
  public static function file_exists(string $path) {
    // Drupal's FileSystem class does not yet support file attributes.
    // PHP's file_exists() does not support URIs as of PHP 7.
    $absPath = self::realpath($path);
    if ($absPath === FALSE) {
      // Unrecognized URI scheme, missing directory, or bad path.
      return FALSE;
    }

    return @file_exists($absPath);
  }

  /**
   * Returns the last accessed time stamp for the local file or directory.
   *
   * @param string $path
   *   The URI or local path to a file or directory.
   *
   * @return int
   *   Returns the last accessed time stamp, or FALSE on failure.
   *
   * @see ::stat()
   * @see ::touch()
   * @see ::filectime()
   * @see ::filemtime()
   * @see https://php.net/manual/en/function.fileatime.php
   */
  public static function fileatime(string $path) {
    // Drupal's FileSystem class does not yet support file attributes.
    // PHP's stat() does not support URIs as of PHP 7.
    $absPath = self::realpath($path);
    if ($absPath === FALSE) {
      // Unrecognized URI scheme, missing directory, or bad path.
      return FALSE;
    }

    return @fileatime($absPath);
  }

  /**
   * Returns the creation time stamp for the local file or directory.
   *
   * @param string $path
   *   The URI or local path to a file or directory.
   *
   * @return int
   *   Returns the creation time stamp, or FALSE on failure.
   *
   * @see ::stat()
   * @see ::touch()
   * @see ::fileatime()
   * @see ::filemtime()
   * @see https://php.net/manual/en/function.filectime.php
   */
  public static function filectime(string $path) {
    // Drupal's FileSystem class does not yet support file attributes.
    // PHP's filectime() does not support URIs as of PHP 7.
    $absPath = self::realpath($path);
    if ($absPath === FALSE) {
      // Unrecognized URI scheme, missing directory, or bad path.
      return FALSE;
    }

    return @filectime($absPath);
  }

  /**
   * Returns the last modified time stamp for the local file or directory.
   *
   * @param string $path
   *   The URI or local path to a file or directory.
   *
   * @return int
   *   Returns the last modified time stamp, or FALSE on failure.
   *
   * @see ::stat()
   * @see ::touch()
   * @see ::fileatime()
   * @see ::filectime()
   * @see https://php.net/manual/en/function.filemtime.php
   */
  public static function filemtime(string $path) {
    // Drupal's FileSystem class does not yet support file attributes.
    // PHP's filemtime() does not support URIs as of PHP 7.
    $absPath = self::realpath($path);
    if ($absPath === FALSE) {
      // Unrecognized URI scheme, missing directory, or bad path.
      return FALSE;
    }

    return @filemtime($absPath);
  }

  /**
   * Returns the size of the local file, in bytes.
   *
   * @param string $path
   *   The URI or local path to a file or directory.
   *
   * @return int
   *   Returns the size of the file, in bytes, or FALSE on failure.
   *
   * @see ::stat()
   * @see https://php.net/manual/en/function.filesize.php
   */
  public static function filesize(string $path) {
    // Drupal's FileSystem class does not yet support file attributes.
    // PHP's filesize() does not support URIs as of PHP 7.
    $absPath = self::realpath($path);
    if ($absPath === FALSE) {
      // Unrecognized URI scheme, missing directory, or bad path.
      return FALSE;
    }

    return @filesize($absPath);
  }

  /**
   * Returns TRUE if the local path is for a directory.
   *
   * @param string $path
   *   The URI or local path to a file or directory.
   *
   * @return bool
   *   Returns TRUE if the path is for a directory.
   *
   * @see ::is_file()
   * @see https://php.net/manual/en/function.is-dir.php
   */
  public static function is_dir(string $path) {
    // Drupal's FileSystem class does not yet support file attributes.
    // PHP's is_dir() does not support URIs as of PHP 7.
    $absPath = self::realpath($path);
    if ($absPath === FALSE) {
      // Unrecognized URI scheme, missing directory, or bad path.
      return FALSE;
    }

    return is_dir($absPath);
  }

  /**
   * Returns TRUE if the local path is for a file.
   *
   * @param string $path
   *   The URI or local path to a file or directory.
   *
   * @return bool
   *   Returns TRUE if the path is for a regular local file.
   *
   * @see ::is_dir()
   * @see https://php.net/manual/en/function.is-file.php
   */
  public static function is_file(string $path) {
    // Drupal's FileSystem class does not yet support file attributes.
    // PHP's is_file() does not support URIs as of PHP 7.
    $absPath = self::realpath($path);
    if ($absPath === FALSE) {
      // Unrecognized URI scheme, missing directory, or bad path.
      return FALSE;
    }

    return is_file($absPath);
  }

  /**
   * Returns TRUE if the local path is for an item that exists and is readable.
   *
   * @param string $path
   *   The URI or local path to a file or directory.
   *
   * @return bool
   *   Returns TRUE if the path is for an item that exists and is readable.
   *
   * @see ::is_writable()
   * @see ::file_exists()
   * @see https://php.net/manual/en/function.is-readable.php
   */
  public static function is_readable(string $path) {
    // Drupal's FileSystem class does not yet support file attributes.
    // PHP's is_readable() does not support URIs as of PHP 7.
    $absPath = self::realpath($path);
    if ($absPath === FALSE) {
      // Unrecognized URI scheme, missing directory, or bad path.
      return FALSE;
    }

    return is_readable($absPath);
  }

  /**
   * Returns TRUE if the local path is for an item that exists and is writable.
   *
   * @param string $path
   *   The URI or local path to a file or directory.
   *
   * @return bool
   *   Returns TRUE if the path is for an item that exists and is writable.
   *
   * @see ::is_readable()
   * @see https://php.net/manual/en/function.is-writable.php
   */
  public static function is_writable(string $path) {
    // Drupal's FileSystem class does not yet support file attributes.
    // PHP's is_writable() does not support URIs as of PHP 7.
    $absPath = self::realpath($path);
    if ($absPath === FALSE) {
      // Unrecognized URI scheme, missing directory, or bad path.
      return FALSE;
    }

    return is_writable($absPath);
  }

  /**
   * Renames a file or directory.
   *
   * @param string $oldPath
   *   The URI or local path to the file or directory.
   * @param string $newPath
   *   The intended URI or local path to the file or directory. If using a
   *   URI, the scheme used by the $oldPath and $newPath must match.
   * @param resource $context
   *   (optional) The stream context. Default is NULL.
   *
   * @return bool
   *   Returns TRUE on success and FALSE on failure.
   *
   * @see https://php.net/manual/en/function.rename.php
   */
  public static function rename(
    string $oldPath,
    string $newPath,
    $context = NULL) {
    // Drupal's FileSystem class does not yet support file rename.
    if ($context === NULL) {
      return @rename($oldPath, $newPath);
    }

    return @rename($oldPath, $newPath, $context);
  }

  /**
   * Returns information about a local file or directory.
   *
   * Most of the values returned by this function are low-level details
   * that are of minimal use by platform-independent code working with
   * local files. Key values that are useful include:
   * - 'size': the size, in bytes.
   * - 'atime': the last access time stamp.
   * - 'mtime': the last modified time stamp.
   * - 'ctime': the created time stamp.
   *
   * These values are also available via separate functions filesize(),
   * fileatime(), filemtime(), and filectime().
   *
   * @param string $path
   *   The URI or local path to a file or directory.
   *
   * @return array
   *   Returns an associative array with attributes of the file. Array entries
   *   are as per PHP's stat() and include:
   *   - 'dev': the device number.
   *   - 'ino': the inode number on Linux/macOS, and 0 on Windows.
   *   - 'mode': the inode protection mode on Linux/macOS.
   *   - 'nlink': the number of links.
   *   - 'uid': the owner's user ID on Linux/macOS, and 0 on Windows.
   *   - 'gid': the owner's group ID on Linux/macOS, and 0 on Windows.
   *   - 'rdev': the device type.
   *   - 'size': the size, in bytes.
   *   - 'atime': the last access time stamp.
   *   - 'mtime': the last modified time stamp.
   *   - 'ctime': the created time stamp.
   *   - 'blksize': the block size on some systems, and -1 on Windows.
   *   - 'blocks': the number of blocks allocated, and -1 on Windows.
   *
   * @see ::fileatime()
   * @see ::filectime()
   * @see ::filemtime()
   * @see ::filesize()
   * @see https://php.net/manual/en/function.stat.php
   */
  public static function stat(string $path) {
    // Drupal's FileSystem class does not yet support file attributes.
    // PHP's stat() does not support URIs as of PHP 7.
    $absPath = self::realpath($path);
    if ($absPath === FALSE) {
      // Unrecognized URI scheme, missing directory, or bad path.
      return FALSE;
    }

    return @stat($absPath);
  }

  /*---------------------------------------------------------------------
   *
   * File handling (wrappers).
   *
   *---------------------------------------------------------------------*/

  /**
   * Creates a file with a unique name in the selected directory.
   *
   * The function creates a new file in the indicated directory, gives it
   * a unique name, and sets access permission to 0600. The $prefix, if
   * given, is used as the start of the unique file name.
   *
   * When the $path argument is a URI, a URI for the new file is returned.
   * When the $path is a local file system path, a path for the new file
   * is returned.
   *
   * @param string $path
   *   The URI or local path to the directory to contain the file.
   * @param string $prefix
   *   (optional) The prefix for the temporary name. Some OSes (e.g. Windows)
   *   only use the first few characters of the prefix. The default is to
   *   include no prefix.
   *
   * @return string|bool
   *   Returns the URI or local path to the new file on success,
   *   and FALSE otherwise.
   *
   * @see \Drupal\Core\File\FileSystemInterface::tempnam()
   * @see drupal_tempnam()
   * @see https://php.net/manual/en/function.tempnam.php
   */
  public static function tempnam(string $path, string $prefix = '') {
    //
    // Implementation note:
    //
    // Drupal's 'file_system' service implements tempnam(), so the
    // apparent way to wrap the function is simply:
    // - return \Drupal::service('file_system')->tempnam($path, $prefix);
    //
    // Unfortunately, Drupal's implementation ignores $path if there is
    // a scheme in front. This is clearly a bug and we need a workaround.
    //
    // The incoming path may be of the form SCHEME://DIR. In this case,
    // a correct implementation uses the SCHEME to look up the equivalent
    // base directory path on the server. DIR is then appended and the
    // resulting path passed to PHP's tempnam() to create a temporary file
    // in that directory, and the resulting temp file path is returned.
    //
    // However, Drupal uses SCHEME to look up the base directory path,
    // and then it ignores DIR. It passes the base path to tempnam(), which
    // then always creates a temporary file in that scheme's top directory.
    // For public://DIR, DIR is ignored and temp files always get created
    // in public://, which is usually the /files directory of the site.
    //
    // So, below we reimplement tempnam() properly to use DIR.
    //
    // Split the path into a scheme and a directory path.
    $parts = explode('://', $path, 2);
    if (count($parts) === 1) {
      // There is no scheme. $path is a local path, not a URI.
      return tempnam($path, $prefix);
    }

    // Get the stream wrapper for the scheme.
    $scheme  = $parts[0];
    $dirPath = $parts[1];
    $mgr     = \Drupal::service('stream_wrapper_manager');
    $wrapper = $mgr->getViaScheme($scheme);

    if ($wrapper === FALSE) {
      // Unknown scheme.
      return FALSE;
    }

    // Use the wrapper's base path to build a directory path and create
    // a temporary file.
    $basePath   = $wrapper->realpath();
    $parentPath = $basePath . DIRECTORY_SEPARATOR . $dirPath;
    $tempPath   = tempnam($parentPath, $prefix);

    if ($tempPath === FALSE) {
      // Could not create temporary file.
      return FALSE;
    }

    // The wrapper's directory path is a site-relative path, but tempnam()
    // returns an absolute path. To build a URI to return, we need to
    // strip off the absolute path at the start.
    $baseLen = mb_strlen($basePath);
    return $scheme . '://' . mb_substr($tempPath, ($baseLen + 1));
  }

  /**
   * Sets the access and last modification time for a local item.
   *
   * Sets the modified and access time of the local file or directory.
   * If the file does not exist, it will be created.
   *
   * @param string $path
   *   The URI or local path to a file or directory.
   * @param int $mtime
   *   (optional) The last modified time stamp.
   * @param int $atime
   *   (optional) The last accessed time stamp.
   *
   * @return bool
   *   Returns TRUE on success and FALSE on failure.
   *
   * @see ::fileatime()
   * @see ::filemtime()
   * @see https://php.net/manual/en/function.touch.php
   */
  public static function touch(string $path, $mtime = NULL, $atime = NULL) {
    // Drupal's FileSystem class does not yet support file attributes.
    // PHP's touch() does not support URIs as of PHP 7.
    if ($mtime === NULL) {
      $mtime = time();
    }

    if ($atime === NULL) {
      $atime = $mtime;
    }

    $absPath = self::realpath($path);
    if ($absPath === FALSE) {
      // Unrecognized URI scheme, missing directory, or bad path.
      return FALSE;
    }

    return @touch($absPath, $mtime, $atime);
  }

  /**
   * Removes a local file.
   *
   * The URI or path must be for a file.
   *
   * @param string $path
   *   The URI or local path to the file.
   * @param resource $context
   *   (optional) The stream context. Default is NULL.
   *
   * @return bool
   *   Returns TRUE on success and FALSE on failure.
   *
   * @see ::rmdir()
   * @see \Drupal\Core\File\FileSystemInterface::unlink()
   * @see drupal_unlink()
   * @see https://php.net/manual/en/function.unlink.php
   */
  public static function unlink(string $path, $context = NULL) {
    return \Drupal::service('file_system')->unlink($path, $context);
  }

  /*---------------------------------------------------------------------
   *
   * Directory handling (wrappers).
   *
   *---------------------------------------------------------------------*/

  /**
   * Makes a new local directory.
   *
   * See PHP's chmod() documentation for an explanation of modes.
   *
   * Unlike the PHP mkdir() function, this function (and Drupal's
   * FileSystemInterface implementations) default the mode to the server's
   * 'file_chmod_directory' and 'file_chmod_file' configuration settings.
   * By default, these give everyone read access so that the web server
   * can read and deliver the file or directory.
   *
   * @param string $path
   *   The URI or local path to the directory.
   * @param mixed $mode
   *   (optional) The permissions mode of the directory. This is ignored on
   *   Windows. The default is NULL, which defaults the mode to the server's
   *   configuration settings.
   * @param bool $recursive
   *   (optional) Whether to created nested directories. Default is FALSE.
   * @param resource $context
   *   (optional) The stream context. Default is NULL.
   *
   * @return bool
   *   Returns TRUE on success and FALSE on failure.
   *
   * @see ::chmod()
   * @see \Drupal\Core\File\FileSystemInterface::mkdir()
   * @see drupal_mkdir()
   * @see https://php.net/manual/en/function.chmod.php
   * @see https://php.net/manual/en/function.mkdir.php
   */
  public static function mkdir(
    string $path,
    $mode = NULL,
    bool $recursive = FALSE,
    $context = NULL) {
    return \Drupal::service('file_system')->mkdir($path, $mode, $recursive, $context);
  }

  /**
   * Removes an empty local directory.
   *
   * The path must be for a directory, and it must be empty.
   *
   * @param string $path
   *   The URI or local path to the directory.
   * @param resource $context
   *   (optional) The stream context. Default is NULL.
   *
   * @return bool
   *   Returns TRUE on success and FALSE on failure.
   *
   * @see ::unlink()
   * @see \Drupal\Core\File\FileSystemInterface::rmdir()
   * @see drupal_rmdir()
   * @see https://php.net/manual/en/function.rmdir.php
   */
  public static function rmdir(string $path, $context = NULL) {
    return \Drupal::service('file_system')->rmdir($path, $context);
  }

  /**
   * Recursively removes a local directory and all of its contents.
   *
   * The standard rmdir() function deletes an empty directory, but not
   * one with files in it. The standard unlink() function deletes files,
   * but not directories. There is no standad function to delete a
   * directory that still contains files.
   *
   * This function recursively sweeps through a local directory and
   * removes all of its files and subdirectories, then removes the
   * resulting empty directory. If any file or directory cannot be
   * deleted, this function aborts.
   *
   * @param string $path
   *   The URI or local path to a directory to delete.
   * @param resource $context
   *   (optional) The stream context. Default is NULL.
   *
   * @return bool
   *   Returns TRUE on success and FALSE on failure.
   *
   * @see ::is_dir()
   * @see ::rmdir()
   * @see ::scandir()
   * @see ::unlink()
   */
  public static function rrmdir(string $path, $context = NULL) {
    if (empty($path) === TRUE) {
      return FALSE;
    }

    $path = self::realpath($path);
    if ($path === FALSE) {
      // Unrecognized URI scheme, missing directory, or bad path.
      return FALSE;
    }

    if ($context === NULL) {
      $dirs = @scandir($path);
      if ($dirs === FALSE) {
        return FALSE;
      }

      $allFileNames = array_diff($dirs, ['.', '..']);
      $success = TRUE;

      foreach ($allFileNames as $fileName) {
        $subPath = $path . DIRECTORY_SEPARATOR . $fileName;
        if (is_dir($subPath) === TRUE) {
          $status = self::rrmdir($subPath);
        }
        else {
          $status = @unlink($subPath);
        }

        $success = $success && $status;
      }

      return @rmdir($path) && $success;
    }

    $allFileNames = array_diff(@scandir($path), ['.', '..'], $context);
    $success = TRUE;
    foreach ($allFileNames as $fileName) {
      $subPath = $path . DIRECTORY_SEPARATOR . $fileName;
      if (is_dir($subPath) === TRUE) {
        $status = self::rrmdir($subPath, $context);
      }
      else {
        $status = @unlink($subPath, $context);
      }

      $success = $success && $status;
    }

    return @rmdir($path, $context) && $success;
  }

  /**
   * Returns a list of items inside a directory.
   *
   * @param string $path
   *   The URI or local path to a directory.
   * @param int $sortingOrder
   *   (optional) The sort order for the returned items. Recognized
   *   values are:
   *   - SCANDIR_SORT_ASCENDING (default).
   *   - SCANDIR_SORT_DESCENDING.
   *   - SCANDIR_SORT_NONE.
   * @param resource $context
   *   (optional) The stream context. Default is NULL.
   *
   * @return array
   *   Returns an array of names (without a leading directory path)
   *   for all of the items in the directory.
   *
   * @see https://php.net/manual/en/function.scandir.php
   */
  public static function scandir(
    string $path,
    int $sortingOrder = SCANDIR_SORT_ASCENDING,
    $context = NULL) {
    // Drupal's FileSystem class does not yet support directory operations.
    // PHP's scandir() does not support URIs as of PHP 7.
    $absPath = self::realpath($path);
    if ($absPath === FALSE) {
      // Unrecognized URI scheme, missing directory, or bad path.
      return FALSE;
    }

    if ($context === NULL) {
      return @scandir($absPath, $sortingOrder);
    }

    return @scandir($absPath, $sortingOrder, $context);
  }

  /*---------------------------------------------------------------------
   *
   * Temporary files.
   *
   *---------------------------------------------------------------------*/

  /**
   * Creates an empty temporary file in the module's temporary directory.
   *
   * A new empty file is created in the module's temporary files directory.
   * The file has a randomly generated name that does not collide with
   * anything else in the directory. Permissions are set appropriately for
   * web server access.
   *
   * @return string
   *   Returns a URI for a local temp file on success, and NULL on error.
   *
   * @see ::getTempDirectoryUri()
   */
  public static function createLocalTempFile() {
    $uri = self::tempnam(self::getTempDirectoryUri(), 'tmp');
    if ($uri === FALSE) {
      return NULL;
    }

    if (self::chmod($uri) === FALSE) {
      return NULL;
    }

    return $uri;
  }

  /**
   * Creates an empty temporary directory in the module's data directory tree.
   *
   * A new empty directory is created in the module's temporary files directory.
   * The directory has a randomly generated name that does not collide with
   * anything else in the directory. Permissions are set appropriate for
   * web server access.
   *
   * @return string
   *   Returns a URI for a local temp directory on success, and NULL on error.
   */
  public static function createLocalTempDirectory() {
    // Create a temp file in the module's temp directory.
    // Then replace the file with a directory using the same name.
    $uri = self::createLocalTempFile();
    if ($uri === NULL ||
        self::unlink($uri) === FALSE ||
        self::mkdir($uri) === FALSE ||
        self::chmod($uri) === FALSE) {
      return NULL;
    }

    return $uri;
  }

  /**
   * Returns the URI for a temporary directory.
   *
   * @return string
   *   The URI for the directory path.
   *
   * @see \Drupal\foldershare\Settings::getFileScheme()
   */
  public static function getTempDirectoryUri() {
    return 'temporary://';
  }

  /*---------------------------------------------------------------------
   *
   * URIs.
   *
   *---------------------------------------------------------------------*/

  /**
   * Returns the URI for a file managed by the module.
   *
   * The returned URI has the form "SCHEME://SUBDIR/DIRS.../FILE.EXTENSION"
   * where:
   *
   * - SCHEME is either "public" or "private", based upon the module's file
   *   storage configuration.
   *
   * - SUBDIR is the name of the module's subdirectory for its files within
   *   the public or private file system.
   *
   * - DIRS.../FILE is a chain of numeric subdirectory names and a numeric
   *   file name (see below).
   *
   * - EXTENSION is the filename extension from the user-visible file name.
   *
   * Numeric subdirectory names are based upon the File object's entity ID,
   * and not the user-chosen file name or folder names. Such names have
   * multiple possible problems:
   *
   * - User file and directory names are not guaranteed to be unique site-wide,
   *   so they cannot safely coexist with names from other users unless some
   *   safe organization mechanism is introduced.
   *
   * - User file and directory names may use the full UTF-8 character set
   *   (except for a few restricted characters, like '/', ':', and '\'), but
   *   the underlying file system may not support the same characters.
   *
   * - USer file and diretory names may be up to the maximum length supported
   *   by the module (such as 255 characters), but the underlying file system
   *   may be more restrictive. For instance, file paths (the chain of
   *   directory names from '/' down to and including the file name) may be
   *   limited as well (such as to 1024 characters), which imposes another
   *   limit on directory depth.
   *
   * - User files may be nested within folders, and those folders within
   *   folders, to an arbitrary depth, but the underlying file system may
   *   have depth limits.
   *
   * - Any number of user files may be stored within the same folder, but
   *   the underlying file system may have limits or performance issues with
   *   very large directories.
   *
   * For these reasons, user-chosen file and directory names cannot be used
   * as-is. They must be replaced with alternative names that are safe for
   * any file system and unique.
   *
   * This module creates directory and file names based upon the unique
   * integer entity ID of the File object referencing the file. These IDs
   * are stored in Drupal as 64-bit integers, which can be represented as
   * 20 zero-padded digits (0..9).  These digits are then divided into groups
   * of consecutive digits based upon a module-wide configuration constant
   * DIGITS_PER_SERVER_DIRECTORY_NAME.  A typical value for this constant
   * is 4. This causes a 20-digit number to be split into 5 groups of 4 digits
   * each. The last of these groups is used as a numeric name for the file,
   * while the preceding groups are used as numeric names for subdirectories.
   *
   * As more File objects are created for use by this module, more numeric
   * files are added to the directories. When an entity ID rolls over to
   * update the 5th digit, the file is put into the next numeric subdirectory,
   * and so on. This keeps directory sizes under control (with a maximum of
   * 10^4 = 10,000 files each).
   *
   * The numeric file name has the original file's filename extension appended
   * so that file field formatters and other Drupal tools that look at paths
   * and filenames will see a proper extension.
   *
   * Once the file path is built for the given File entity, any directories
   * needed are created, permissions are set, and .htaccess files are added.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file current stored locally, or soon to be stored locally.
   *
   * @return string
   *   The URI for the directory path to the file.
   *
   * @see ::getTempDirectoryUri()
   * @see ::prepareDirectories()
   * @see \Drupal\foldershare\Settings::getFileScheme()
   */
  public static function getFileUri(FileInterface $file) {
    //
    // Create file path
    // ----------------
    // Use the file's entity ID to create a numeric file path.
    // Start the path with the file directory.
    $path = Constants::FILE_DIRECTORY;

    // Entity IDs in Drupal are 64-bits. The maximum number of base-10 digits
    // is therefore 20. The DIGITS_PER_SERVER_DIRECTORY_NAME constant
    // determines how many digits are used in the directory and file names.
    $digits = sprintf('%020d', (int) $file->id());

    // Add numeric subdirectories based on the entity ID.
    // The last name added is for the file itself.
    for ($pos = 0; $pos < 20;) {
      $path .= '/';
      for ($i = 0; $i < Constants::DIGITS_PER_SERVER_DIRECTORY_NAME; ++$i) {
        $path .= $digits[$pos++];
      }
    }

    $path = trim($path, '\/');

    // Add the file's extension, if any.
    $filename = $file->getFilename();
    $i = strrpos($filename, '.');
    if ($i !== FALSE) {
      $extension = substr($filename, ($i + 1));
      $path .= '.' . $extension;
    }

    // Create the URI, using the "private" or "public" scheme configured
    // for the module.
    $uri = Settings::getFileScheme() . '://' . $path;

    //
    // Create directories
    // ------------------
    // Create any numeric directories that don't already exist, set their
    // permissions, and add .htaccess files as needed.
    self::prepareFileDirectories($path);

    return $uri;
  }

  /**
   * Creates module file directories and adds .htaccess files.
   *
   * The given URI is converted to a file system path and all missing
   * directories on the path created and their permissions set. All
   * directories starting at the module's file directory have .htaccess
   * files added that block direct web server access.
   *
   * @param string $path
   *   The local file path for a file under management by this module.
   *
   * @return bool
   *   Returns TRUE on success and FALSE on an error. The only error case
   *   occurs when a directory cannot be created, and a message is logged.
   */
  private static function prepareFileDirectories(string $path) {
    //
    // Check if file exists
    // --------------------
    // Get the current public/private file system choice for file storage
    // and prepend the directory path.
    // Convert the URI to a local file path. The conversion checks that all
    // directories on the path exist and that the file at the end of the
    // path exists.
    $manager = \Drupal::service('stream_wrapper_manager');
    $stream = $manager->getViaScheme(Settings::getFileScheme());
    if ($stream === FALSE) {
      // Fail with unknown scheme.
      return FALSE;
    }

    $fullPath = $stream->getDirectoryPath() . '/' . $path;
    if (file_exists($fullPath) === TRUE) {
      // The directories and file all exist.
      return TRUE;
    }

    //
    // Check if parent directories exist
    // ---------------------------------
    // At this point, the file is known to not exist. Check if the parent
    // directory exists.
    $parentPath = dirname($fullPath);
    if (file_exists($parentPath) === TRUE) {
      // The parent directories exist, though the file does not yet.
      return TRUE;
    }

    //
    // Create parent directories
    // -------------------------
    // Parent directories don't exist. Create them, setting permissions.
    if (file_prepare_directory(
      $parentPath,
      (FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) === FALSE) {
      // The parent directory creation failed. There are several possible
      // reasons for this:
      //   - The directory already exists.
      //   - The file system is full.
      //   - The file system is offline.
      //   - Something horrific is going on.
      //
      // Of these, only the first case is interesting. While above we
      // checked if the directory already exists, it is possible that
      // another process has snuck in and created the directory before
      // we've tried to create it. That will cause a failure here.
      //
      // Ideally, that other process was an instance of Drupal using
      // FolderShare. In that case the file prepare would have occurred
      // correctly, with permissions set, etc. But we can't be sure.
      // So, let's try again to be sure, but only if the directory now
      // appears to exist.
      $failure = TRUE;
      if (file_exists($parentPath) === TRUE) {
        if (file_prepare_directory(
          $parentPath,
          (FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) === TRUE) {
          // It worked this time!
          $failure = FALSE;
        }
      }

      if ($failure === TRUE) {
        // The parent directories could not be created.
        \Drupal::logger(Constants::MODULE)->error(
          Messages::OS_CANNOT_CREATE_DIRECTORY,
          [
            '@path' => $parentPath,
          ]);
        return FALSE;
      }
    }
    elseif (file_exists($parentPath) === FALSE) {
      // The parent directory creation succeeded, but the directory isn't
      // there? This should not be possible.
      \Drupal::logger(Constants::MODULE)->error(
        Messages::OS_CANNOT_READ_FILE,
        [
          '@path' => $parentPath,
        ]);
      return FALSE;
    }

    //
    // Create .htaccess files, if needed
    // ---------------------------------
    // Every directory on the path needs a .htaccess file. Drupal supports
    // two standard files:
    // - .htaccess for a public directory.
    // - .htaccess for a private directory.
    //
    // For FolderShare, all directories are treated as private so that
    // file access must go through Drupal and FolderShare for access control.
    //
    // Confusing things, .htaccess files (for public or private access) are
    // only needed for directories that can be served by the web server.
    // If FolderShare has been configured to use the site's private
    // file system (if any), then that private file system is not supposed
    // to be visible to the web server. In that case, .htaccess files are
    // not needed. BUT, we'd prefer to be paranoid. So we add .htaccess
    // files to all directories regardless of whether the file system is
    // supposed to be visible to the web server or not.
    //
    // Create a list of parent directories.
    $highestPath = $stream->getDirectoryPath();
    $d = $parentPath;

    $htDirs = [];
    while (TRUE) {
      $htDirs[] = $d;
      $d = dirname($d);
      if ($d === $highestPath || $d === '/' || $d === '.') {
        break;
      }
    }

    // Loop through these directories and make sure they all have
    // a standard .htaccess file for a private directory. The TRUE
    // argument indicates that a private style .htaccess file should
    // be created. The FALSE argument says to not overwrite it if there
    // is already a file there.
    foreach ($htDirs as $htDir) {
      file_save_htaccess($htDir, TRUE, FALSE);
    }

    // The parent directories now all exist, though the file does not yet.
    return TRUE;
  }

  /**
   * Parses a URI for a module file and returns the File entity ID.
   *
   * @param string $path
   *   The local file path for a file managed by this module.
   *
   * @return int|bool
   *   Returns the integer File entity ID parsed from the path, or FALSE if
   *   the path is not for a file managed by this module.
   */
  public static function getFileEntityId(string $path) {
    //
    // Find start of numeric directory names
    // -------------------------------------
    // Look for use of the module's FILE_DIRECTORY name. If not found,
    // the path is malformed.
    $fileDirectory = Constants::FILE_DIRECTORY;
    $pos = strpos($path, $fileDirectory);
    if ($pos === FALSE) {
      // Fail. Unrecognized path.
      return FALSE;
    }

    //
    // Parse entity ID
    // ---------------
    // Strip the file directory from the front of the path, then use the
    // remainder to build the 20 digit entity ID. Return the integer form
    // of that ID.
    $remainder = substr($path, ($pos + strlen($fileDirectory) + 1));

    // Remove all of the slashes, joining together the numeric names of
    // the subdirectories.
    $digits = preg_replace('|/|', '', $remainder);

    // Remove the file extension, if any.
    $dotIndex = strrpos($digits, '.');
    if ($dotIndex !== FALSE) {
      $digits = substr($digits, 0, $dotIndex);
    }

    if (strlen($digits) !== 20) {
      // Fail. Too few digits. Not directory names and file name.
      return FALSE;
    }

    // Convert the numeric string to a file entity ID.
    if (is_numeric($digits) === FALSE) {
      // Fail. Malformed directory and file names.
      return FALSE;
    }

    return (int) intval($digits);
  }

  /*---------------------------------------------------------------------
   *
   * File name functions.
   *
   *---------------------------------------------------------------------*/

  /**
   * Returns the file name extension in lower case.
   *
   * This function uses multi-byte functions to support UTF-8 paths.
   *
   * @param string $path
   *   The URI or local path to parse.
   *
   * @return string
   *   Returns the part of the path after the last '.', converted to
   *   lower case. If there is no '.', an empty string is returned.
   */
  public static function getExtension(string $path) {
    // Note: Must use multi-byte functions to insure UTF-8 support.
    $lastDotIndex = mb_strrpos($path, '.');

    if ($lastDotIndex === FALSE) {
      return '';
    }

    return mb_convert_case(
      mb_substr($path, ($lastDotIndex + 1)),
      MB_CASE_LOWER);
  }

  /**
   * Returns a guess for the MIME type of a file.
   *
   * @param string $path
   *   The URI or local path to parse.
   *
   * @return string
   *   Returns a guess for the MIME type of the named file.
   */
  public static function guessMimeType(string $path) {
    return \Drupal::service('file.mime_type.guesser')->guess($path);
  }

  /**
   * Returns TRUE if the file name is using an allowed file extension.
   *
   * The text following the last '.' in the given file name is extracted
   * as the name's extension, then checked against the given array of
   * allowed extensions. If the name is found, TRUE is returned.
   *
   * If the file name has no '.', it has no extension, and TRUE is
   * returned.
   *
   * If the extensions array is empty, all extensions are accepted and
   * TRUE is returned.
   *
   * @param string $path
   *   The local path to parse.
   * @param array $extensions
   *   An array of allowed file name extensions. If the extensions array
   *   is empty, all extensions are allowed.
   *
   * @return bool
   *   Returns TRUE if the name has no extension, the extensions array is
   *   empty, or if it uses an allowed extension, and FALSE otherwise.
   *
   * @see ::isNameLegal()
   */
  public static function isNameExtensionAllowed(
    string $path,
    array $extensions) {

    // If there are no extensions to check, then any name is allowed.
    if (count($extensions) === 0) {
      return TRUE;
    }

    $ext = self::getExtension($path);
    if (empty($ext) === TRUE) {
      // No extension. Default to allowed.
      return TRUE;
    }

    // Look for in allowed extensions array.
    return in_array($ext, $extensions);
  }

  /**
   * Returns TRUE if the proposed file or folder name is a legal name.
   *
   * A name is legal if:
   * - It is not empty.
   * - It has 255 characters or less.
   * - It does not contain reserved characters ':', '/', and '\'.
   *
   * @param string $path
   *   The local path to parse.
   *
   * @return bool
   *   Returns TRUE if the name is legal, and FALSE otherwise.
   *
   * @see ::isNameExtensionAllowed()
   * @see \Drupal\foldershare\Constants::MAX_NAME_LENGTH
   */
  public static function isNameLegal(string $path) {
    // Note: Must use multi-byte functions to insure UTF-8 support.
    return (empty($path) === FALSE) &&
      (mb_strlen($path) <= Constants::MAX_NAME_LENGTH) &&
      (mb_ereg('[:\/\\\]', $path) === FALSE);
  }

}
