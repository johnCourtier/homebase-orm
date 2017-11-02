<?php

namespace Homebase\Model\ORM;

interface QueryBuilder
{
	/**
	 * @param Row[] $rows
	 * @return string
	 */
	public function getCreateQuery($rows);

	/**
	 * @param Restriction[] $restrictions
	 * @return string
	 */
	public function getRetrieveQuery($restrictions);

	/**
	 * @param Row[] $rows
	 * @return string
	 */
	public function getUpdateQuery($rows);

	/**
	 * @param Restriction[] $restrictions
	 * @return string
	 */
	public function getDeleteQuery($restrictions);
}
