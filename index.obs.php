<?php
/**
 * @file index.php
 *
 * Copyright (c) 2013-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @brief Wrapper for userComments plugin.
 *
 * @ingroup plugins_generic_userComments
 */

require_once('UserCommentsPlugin.php');
return new UserCommentsPlugin();

