<?php

namespace Homebase\Model\ORM;

interface Connection
{
	/**
	 * Returns:
	 *  int - count of changed/deleted rows for update/delete query
	 *  int[] - newly added ids for create query
	 *  array - data for retrieve query
	 * @param string $query
	 * @return array|int|int[]
	 * @throws BadQueryException if bad query is executed
	 * @throws DatabaseManagementSystemException if execution fails on server side
	 */
	public function execute($query);

	/**
	 * @throws ORMException if clearing fails
	 */
	public function clearCachedQueryResults();
}
