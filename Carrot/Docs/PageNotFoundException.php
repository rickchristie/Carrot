<?php

/**
 * This file is part of the Carrot framework.
 *
 * Copyright (c) 2011 Ricky Christie <seven.rchristie@gmail.com>.
 *
 * Licensed under the MIT License.
 *
 */

/**
 * Page Not Found Exception
 * 
 * Exception thrown SimpleDocs model when it can't find the page
 * it needs to display. See {@see Model} for more information on
 * when this exception is thrown.
 * 
 * @author      Ricky Christie <seven.rchristie@gmail.com>
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 */

namespace Carrot\Docs;

use RuntimeException;

class PageNotFoundException extends RuntimeException
{
    
}