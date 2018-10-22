#!/usr/bin/php
<?php
/**
 * @file
 * Filters Doxygen PHP input to fix several problems and add features.
 *
 * Rewrites php namespaces to use :: instead of \
 *   Doxygen's PHP parser does not handle the \ separator for
 *   hierarchical namespaces. But it does recognize ::. So replace
 *   all uses of \ with ::. This lets doxygen work properly, and
 *   then we'll need to filter the output HTML to replace :: with
 *   \ to be correct PHP.
 *
 * Replaces \@return with \@retval
 *  Doxygen's \@return does not take a return type, but \@retval
 *  does. However, phpDoc convention uses \@return. So here we
 *  replace all \@return with \@retval.
 *
 * - appends variable names to \@var commands
 * - remove unused annotations
 *   \@codeCoverageIgnore
 *
 * @see http://www.doxygen.org/
 * @see http://www.stack.nl/~dimitri/doxygen/config.html#cfg_input_filter
 */

// Define the namespace separator to use. :: is recognized by doxygen.
define('NSS', '::');

// Read the source file.
$source = file_get_contents($_SERVER['argv'][1]);

// Tokenize the text.
$tokens = token_get_all($source);
$buffer = NULL;

// Sweep through the tokens looking for specific items.
foreach ($tokens as $token)
{
  if ( is_string($token))
  {
    if ((! empty($buffer)) && ($token == ';')) {
      echo $buffer;
      unset($buffer);
    }
    echo $token;
    continue;
  }


  list($id, $text) = $token;
  switch ($id) {

    case T_DOC_COMMENT :
      // Comment found. Look through the comment
      // for items to replace.

      // Replace @return with @retval.
      $text = preg_replace('#@return\s#', '@retval ', $text);

      // Replace starting namespace separator.
      $text = preg_replace('#(\s)\\\\([A-Z]\w+)#ms', '$1$2', $text);

      do {
        // Replace backslash in comment.
        $text = preg_replace('#(\*\s*[^*]*?\b\w+[^\n\r ]+)\\\\([A-Z])#ms', '$1'.NSS.'$2', $text, 1, $count);
      } while ($count);

      // Optimize @var tags.
      if (preg_match('#@var\s+[^\$]*\*/#ms', $text)) {
        $buffer = preg_replace('#(@var\s+[^\n\r]+)(\n\r?.*\*/)#ms',
          '$1 \$\$\$$2', $text);
      } else {
        echo $text;
      }
      break;

    case T_VARIABLE :
      // Variable found.
      if ((! empty($buffer))) {
        echo str_replace('$$$', $text, $buffer);
        unset($buffer);
      }
      echo $text;
      break;

    case T_INLINE_HTML :
      // Replace @namespace tags.
      do {
        $text = preg_replace('#(\*\s*@namespace\s[^\n\r]+)\\\\#', '$1::', $text,1, $count);
      } while ($count);
      do {
        // Replace backslash.
        $text = preg_replace('#(\*\s*[^*]*?\b\w+[^\n\r ]+)\\\\([A-Z])#ms', '$1'.NSS.'$2', $text, 1, $count);
      } while ($count);
      do {
        // Remove starting backslash.
        $text = preg_replace('#(\*\s*[^*]*\s)\\\\([A-Z])#ms', '$1$2', $text, 1, $count);
      } while ($count);
      if ((! empty($buffer))) {
        $buffer .= $text;
      } else {
        echo $text;
      }
      break;

    default:
      if ((! empty($buffer))) {
        $buffer .= $text;
      } else {
        echo $text;
      }
      break;
  }
}
