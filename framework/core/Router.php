<?php

/**
 * Router
 *
 * Copyright (c) 2011 Ricky Christie
 *
 * Licensed under the MIT License.
 *
 * 
 *
 * @package		Carrot
 * @author		Ricky Christie <seven.rchristie@gmail.com>
 * @copyright	2011 Ricky Christie <seven.rchristie@gmail.com>
 * @license		http://www.opensource.org/licenses/mit-license.php MIT License
 * @since		0.1
 * @version		0.1
 */

class Router implements IRouter
{
	protected $params = array();
	
	public function __construct()
	{
		
	}
	
	public function set_route()
	{
		
	}
	
	public function set_route_controller_not_found()
	{
		
	}
	
	public function destination_dic_id()
	{
		
	}
	
	public function destination_method()
	{
		
	}
	
	public function params($index = NULL)
	{
		if (!isset($this->params[$index]))
		{
			return NULL;
		}
		
		return $this->params[$index];
	}
}