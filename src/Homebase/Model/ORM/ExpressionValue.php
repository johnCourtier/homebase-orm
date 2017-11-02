<?php

namespace Homebase\Model\ORM;

class ExpressionValue
{
	private $escapingRequired = false;

	private $value;

	/**
	 * @param numeric|string|DateTime|null $value
	 * @throws InvalidValueException if value type is not supported
	 */
	public function __construct($value)
	{
		$this->setValue($value);
	}

	/**
	 * @param DateTime|numeric|string|null $value
	 * @throws InvalidValueException if value type is not supported
	 */
	protected function setValue($value)
	{
		if (is_numeric($value)) {
			$this->value = (int) $value;
		} elseif ($value === null) {
			$this->value = $value;
		} elseif (is_string($value)) {
			$this->escapingRequired = true;
			$this->value = $value;
		} elseif ($value instanceof DateTime) {
			$this->value = $value->format('Y-m-d H:i:s');
		} else {
			throw new InvalidValueException('Unable to set value. Value type \''.gettype($value).'\' is not supported.');
		}
	}

	/**
	 * @return int|string|null
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @return bool
	 */
	public function isEscapingRequired()
	{
		return $this->escapingRequired;
	}
}
