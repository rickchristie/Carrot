<?php

/**
 * This file is part of the Carrot framework.
 *
 * Copyright (c) 2011 Ricky Christie <seven.rchristie@gmail.com>
 *
 * Licensed under the MIT License.
 *
 */

/**
 * Dependency Registration File Paths.
 * 
 * The front controller will require this file to load an array named
 * '$registrations'. This array stores dependency registration file paths
 * and will be injected to DependencyInjectionContainer at construction.
 * 
 * You can assign dependency registration files to a particular namespace
 * or the whole class name. Make sure that you use the fully qualified name
 * for it:
 * 
 * <code>
 * $registrations['\Namespace'] = '/absolute/path/to/registration/file.php';
 * $registrations['\Namespace\Subnamespace'] = '/absolute/path/to/registration/file.php';
 * $registrations['\Namespace\Subnamespace\Class'] = '/absolute/path/to/registration/file.php';
 * </code>
 *
 * You can place the registration file anywhere you want and name it anything
 * you want. We recommend you put it at /registrations directory with this
 * format: Namespace.Class.php.
 *
 */

$sep = DIRECTORY_SEPARATOR;
$registrations['\Carrot\Core'] = __DIR__ . "{$sep}registrations{$sep}defaults{$sep}Carrot.Core.php";