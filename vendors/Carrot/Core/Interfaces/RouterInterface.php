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
 * Router Interface
 * 
 * This interface represents the contract between the Router and the front
 * controller (index.php). 
 * 
 * @author		Ricky Christie <seven.rchristie@gmail.com>
 * @license		http://www.opensource.org/licenses/mit-license.php MIT License
 *
 */

namespace Carrot\Core\Interfaces;

interface RouterInterface
{
	/**
	 * Returns the destination object.
	 * 
	 * @return \Carrot\Core\Classes\Destination
	 * 
	 */
	public function getDestination();
}