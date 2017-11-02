<?php

namespace Homebase\Model\ORM;

use DateTime;

class Expression
{
	const OPERATOR_IS = '=';
	const OPERATOR_IS_NOT = '!=';
	const OPERATOR_IS_IN = 'IN';
	const OPERATOR_IS_NOT_IN = 'NOT IN';
	const OPERATOR_IS_GREATER_THAN = '>';
	const OPERATOR_IS_GREATER_THAN_OR_EQUAL = '>=';
	const OPERATOR_IS_LESS_THAN = '<';
	const OPERATOR_IS_LESS_THAN_OR_EQUAL = '<=';
	const OPERATOR_IS_BETWEEN = 'BETWEEN';
	const OPERATOR_IS_NOT_BETWEEN = 'NOT BETWEEN';

	public static $validOperators = array(
		self::OPERATOR_IS,
		self::OPERATOR_IS_NOT,
		self::OPERATOR_IS_IN,
		self::OPERATOR_IS_NOT_IN,
		self::OPERATOR_IS_GREATER_THAN,
		self::OPERATOR_IS_GREATER_THAN_OR_EQUAL,
		self::OPERATOR_IS_LESS_THAN,
		self::OPERATOR_IS_LESS_THAN_OR_EQUAL,
		self::OPERATOR_IS_BETWEEN,
		self::OPERATOR_IS_NOT_BETWEEN
	);

	/** @var string static::OPERATOR_* */
	private $operator;

	/** @var ExpressionValue|ExpressionValue[] */
	private $value;

	/**
	 * @param string $operator static::OPERATOR_*
	 * @param int|string|array|DateTime|null $value
	 */
	public function __construct($operator, $value)
	{
		try {
			$this->setValue($value);
			$this->setOperator($operator, $this->getValue());
		} catch (InvalidOperatorException $exception) {
			trigger_error($exception->getMessage(), E_USER_ERROR);
			return;
		} catch (InvalidValueException $exception) {
			trigger_error($exception->getMessage(), E_USER_ERROR);
			return;
		}
	}

	/**
	 * @param type $operator
	 * @param type $value
	 * @return \static
	 */
	public static function create($operator, $value)
	{
		return new static($operator, $value);
	}

	/**
	 * @return string $operator static::OPERATOR_*
	 */
	protected function getOperator()
	{
		return $this->operator;
	}

	/**
	 * @return ExpressionValue|ExpressionValue[]
	 */
	protected function getValue()
	{
		return $this->value;
	}

	/**
	 * @param string $operator
	 * @param ExpressionValue|ExpressionValue[] $expressionValue
	 * @throws InvalidOperatorException for not valid operators
	 */
	private function setOperator($operator, $expressionValue)
	{
		if (!in_array($operator, static::$validOperators, TRUE)) {
			throw new InvalidOperatorException('Unable to set operator \''.$operator.'\'. Operator is not valid. Valid operators are \''.var_export(static::$validOperators, TRUE).'\'');
		}

		if ($operator === static::OPERATOR_IS) {
			$this->setOperatorIs($expressionValue);
		} elseif ($operator === static::OPERATOR_IS_BETWEEN) {
			$this->setOperatorIsBetween($expressionValue);
		} elseif ($operator === static::OPERATOR_IS_IN) {
			$this->setOperatorIsIn($expressionValue);
		} elseif ($operator === static::OPERATOR_IS_NOT) {
			$this->setOperatorIsNot($expressionValue);
		} elseif ($operator === static::OPERATOR_IS_NOT_BETWEEN) {
			$this->setOperatorIsNotBetween($expressionValue);
		} elseif ($operator === static::OPERATOR_IS_NOT_IN) {
			$this->setOperatorIsNotIn($expressionValue);
		} else {
			if (is_array($expressionValue)) {
				throw new InvalidOperatorException('Unable to set operator \''.$operator.'\'. Exactly 1 value is required, but '.count($expressionValue).' provided.');
			}
			$this->operator = $operator;
		}
	}

	/**
	 * @param ExpressionValue[]|ExpressionValue $expressionValue
	 */
	private function setOperatorIs($expressionValue)
	{
		$this->setOperatorIsIn($expressionValue);
	}

	/**
	 * @param ExpressionValue[] $expressionValues
	 * @throws InvalidOperatorException if operator is not compatible with provided $expressionValue
	 */
	private function setOperatorIsBetween($expressionValues)
	{
		if (!is_array($expressionValues)) {
			throw new InvalidOperatorException('Unable to set operator \''.static::OPERATOR_IS_BETWEEN.'\'. Second value was not provided.');
		}

		$count = count($expressionValues);
		if ($count !== 2) {
			throw new InvalidOperatorException('Unable to set operator \''.static::OPERATOR_IS_BETWEEN.'\'. Exactly 2 values are required, but '.$count.' provided.');
		}

		$firstExpressionValue = $expressionValues[0];
		$secondExpressionValue = $expressionValues[1];
		if ($firstExpressionValue->getValue() === $secondExpressionValue->getValue()) {
			$this->setOperatorIs($firstExpressionValue);
		} else {
			$this->operator = static::OPERATOR_IS_BETWEEN;
		}
	}

	/**
	 * @param ExpressionValue[]|ExpressionValue $expressionValue
	 */
	private function setOperatorIsIn($expressionValue)
	{
		if (!is_array($expressionValue)) {
			$this->value = array($expressionValue);
		}
		$this->operator = static::OPERATOR_IS_IN;
	}

	/**
	 * @param ExpressionValue[]|ExpressionValue $expressionValue
	 */
	private function setOperatorIsNot($expressionValue)
	{
		$this->setOperatorIsNotIn($expressionValue);
	}

	/**
	 * @param ExpressionValue[] $expressionValues
	 */
	private function setOperatorIsNotBetween($expressionValues)
	{
		if (!is_array($expressionValues)) {
			throw new InvalidOperatorException('Unable to set operator \''.static::OPERATOR_IS_NOT_BETWEEN.'\'. Second value was not provided.');
		}

		$count = count($expressionValues);
		if ($count !== 2) {
			throw new InvalidOperatorException('Unable to set operator \''.static::OPERATOR_IS_NOT_BETWEEN.'\'. Exactly 2 values are required, but '.$count.' provided.');
		}

		$firstExpressionValue = $expressionValues[0];
		$secondExpressionValue = $expressionValues[1];
		if ($firstExpressionValue->getValue() === $secondExpressionValue->getValue()) {
			$this->setOperatorIsNot($firstExpressionValue);
		} else {
			$this->operator = static::OPERATOR_IS_NOT_BETWEEN;
		}
	}

	/**
	 * @param ExpressionValue[]|ExpressionValue $expressionValue
	 */
	private function setOperatorIsNotIn($expressionValue)
	{
		if (!is_array($expressionValue)) {
			$this->value = array($expressionValue);
		}
		$this->operator = static::OPERATOR_IS_NOT_IN;
	}

	/**
	 * @param int|string|array|DateTime|null $value
	 * @throws InvalidValueException if value type is not supported
	 * @throws InvalidOperatorException for not valid operators
	 */
	private function setValue($value)
	{
		if (!is_array($value)) {
			$this->value = new ExpressionValue($value);
		} else {
			$expressionValues = array();
			foreach ($value as $element) {
				$expressionValues[] = new ExpressionValue($element);
			}
			$this->value = $expressionValues;
		}
	}
}
