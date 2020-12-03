<?php

namespace SMWCentral\Validation;

use Countable;
use LogicException;

class Variable
{
	/**
	 * Process all rules.
	 * 
	 * @var string
	 */
	private const STATE_PROCESS = 'process';
	
	/**
	 * Ignore rules if an error is added.
	 * 
	 * @var string
	 */
	private const STATE_BAIL = 'bail';
	
	/**
	 * Ignore all rules.
	 * 
	 * @var string
	 */
	private const STATE_IGNORE = 'ignore';
	
	/**
	 * The validator instance.
	 * 
	 * @var \SMWCentral\Validation\Validator
	 */
	protected $validator;
	
	/**
	 * The name of the variable, usually the same as the key.
	 * 
	 * @var string
	 */
	protected $name;
	
	/**
	 * The current value.
	 * 
	 * @var mixed
	 */
	protected $value;
	
	/**
	 * Args to pass into error messages.
	 * 
	 * @var array
	 */
	protected $args;
	
	/**
	 * Is the type of the value still ambiguous?
	 * 
	 * @var bool
	 */
	protected $ambiguous;
	
	/**
	 * Create a new variable instance.
	 * 
	 * @param \SMWCentral\Validation\Validator $validator
	 * @param string $name
	 * @param mixed $value
	 * @param array $args
	 * @return void
	 */
	public function __construct(Validator $validator, string $name, $value, array $args = [])
	{
		$this->validator = $validator;
		$this->name = $name;
		$this->value = $value;
		$this->args = $args;
		$this->ambiguous = true;
		$this->state = self::STATE_PROCESS;
	}
	
	/**
	 * Determine if the given value is "blank".
	 * 
	 * Originally from:
	 * https://github.com/laravel/framework/blob/b89363b/src/Illuminate/Support/helpers.php#L33
	 * 
	 * @param mixed $value
	 * @return bool
	 */
	protected static function isBlank($value): bool
	{
		if($value === null)
		{
			return true;
		}
		
		if(is_string($value))
		{
			return trim($value) === '';
		}
		
		if(is_numeric($value) || is_bool($value))
		{
			return false;
		}
		
		if($value instanceof Countable)
		{
			return count($value) === 0;
		}
		
		return empty($value);
	}
	
	/**
	 * The value is required.
	 * 
	 * @return $this
	 */
	public function required(): Variable
	{
		if(self::isBlank($this->value))
		{
			$this->state = self::STATE_IGNORE;
			$this->addError('required');
		}
		
		return $this;
	}
	
	/**
	 * Use a default value.
	 * 
	 * @param mixed $value
	 * @return $this
	 */
	public function default($value): Variable
	{
		if(self::isBlank($this->value))
		{
			$this->value = $value;
		}
		
		return $this;
	}
	
	/**
	 * The value is a string.
	 * 
	 * @return $this
	 */
	public function string(): Variable
	{
		if($this->value === null)
		{
			$this->ambiguous = false;
		}
		elseif(is_array($this->value))
		{
			$this->value = '';
			$this->state = self::STATE_IGNORE;
			$this->addError('string');
		}
		else
		{
			$this->value = (string)$this->value;
			$this->ambiguous = false;
		}
		
		return $this;
	}
	
	/**
	 * The value is an integer.
	 * 
	 * @return $this
	 */
	public function integer(): Variable
	{
		if($this->value === null)
		{
			$this->ambiguous = false;
		}
		elseif(
			is_int($this->value) || is_float($this->value)
			|| (is_string($this->value) && is_numeric($this->value))
		)
		{
			$this->value = (int)$this->value;
			$this->ambiguous = false;
		}
		else
		{
			$this->value = 0;
			$this->state = self::STATE_IGNORE;
			$this->addError('integer');
		}
		
		return $this;
	}
	
	/**
	 * The value is a number.
	 * 
	 * @return $this
	 */
	public function float(): Variable
	{
		if($this->value === null)
		{
			$this->ambiguous = false;
		}
		elseif(
			is_int($this->value) || is_float($this->value)
			|| (is_string($this->value) && is_numeric($this->value))
		)
		{
			$this->value = (float)$this->value;
			$this->ambiguous = false;
		}
		else
		{
			$this->value = 0.0;
			$this->state = self::STATE_IGNORE;
			$this->addError('float');
		}
		
		return $this;
	}
	
	/**
	 * The value is a boolean.
	 * 
	 * @return $this
	 */
	public function boolean(): Variable
	{
		$this->value = in_array($this->value, ['yes', 'on', '1', 1, true, 'true'], true);
		$this->ambiguous = false;
		
		return $this;
	}
	
	/**
	 * The value is an array.
	 * 
	 * @return $this
	 */
	public function array(): Variable
	{
		if(is_array($this->value) || $this->value === null)
		{
			$this->ambiguous = false;
		}
		else
		{
			$this->value = [];
			$this->state = self::STATE_IGNORE;
			$this->addError('array');
		}
		
		return $this;
	}
	
	/**
	 * Stop validating after the first error.
	 * 
	 * @return $this
	 */
	public function bail(): Variable
	{
		if($this->state !== self::STATE_IGNORE)
		{
			$this->state = self::STATE_BAIL;
		}
		
		return $this;
	}
	
	/**
	 * The value's size must be between [$min; $max].
	 * 
	 * @param int $min
	 * @param int $max
	 * @return $this
	 */
	public function between(int $min, int $max): Variable
	{
		if($this->state !== self::STATE_IGNORE)
		{
			$size = $this->getSize();
			
			if($size < $min || $size > $max)
			{
				$this->addError('between.' . $this->getType(), compact('min', 'max'));
			}
		}
		
		return $this;
	}
	
	/**
	 * The value's size must be at most `$max`.
	 * 
	 * @param int $max
	 * @return $this
	 */
	public function max(int $max): Variable
	{
		if($this->state !== self::STATE_IGNORE && $this->getSize() > $max)
		{
			$this->addError('max.' . $this->getType(), compact('max'));
		}
		
		return $this;
	}
	
	/**
	 * The value's size must be at least `$min`.
	 * 
	 * @param int $min
	 * @return $this
	 */
	public function min(int $min): Variable
	{
		if($this->state !== self::STATE_IGNORE && $this->getSize() < $min)
		{
			$this->addError('min.' . $this->getType(), compact('min'));
		}
		
		return $this;
	}
	
	/**
	 * The value's size must be exactly `$size`.
	 * 
	 * @param int $size
	 * @return $this
	 */
	public function size(int $size): Variable
	{
		if($this->state !== self::STATE_IGNORE && $this->getSize() !== $size)
		{
			$this->addError('size.' . $this->getType(), compact('size'));
		}
		
		return $this;
	}
	
	/**
	 * The value must be one of a given set.
	 * 
	 * @param array $values
	 * @param bool $strict
	 * @return $this
	 */
	public function in(array $values, bool $strict = true): Variable
	{
		if($this->state !== self::STATE_IGNORE)
		{
			$this->checkAmbiguity();
			
			if(!in_array($this->value, $values, $strict))
			{
				$this->addError('in', ['values' => implode(', ', $values)]);
			}
		}
		
		return $this;
	}
	
	/**
	 * Get the current value.
	 * 
	 * @return mixed
	 */
	public function value()
	{
		return $this->value;
	}
	
	/**
	 * Create a validation error.
	 * 
	 * @param string $message
	 * @param array $merge
	 * @return \SMWCentral\Validation\Message
	 */
	protected function addError(string $message, array $merge = []): Message
	{
		if($this->state === self::STATE_BAIL)
		{
			$this->state = self::STATE_IGNORE;
		}
		
		$message = new Message($this->name, $message, array_merge($this->args, $merge));
		return $this->validator->errors()->add($this->name, $message);
	}
	
	/**
	 * Ensure the value's type is non-ambiguous.
	 * 
	 * @return void
	 */
	protected function checkAmbiguity(): void
	{
		if($this->ambiguous)
		{
			throw new LogicException('Cannot validate an ambiguous variable; ensure it is the correct type first');
		}
	}
	
	/**
	 * Get the current value's generic type.
	 * 
	 * @return string
	 */
	protected function getType(): string
	{
		if(is_int($this->value) || is_float($this->value))
		{
			return 'number';
		}
		
		if(is_array($this->value))
		{
			return 'array';
		}
		
		return 'string';
	}
	
	/**
	 * Get the size of the value.
	 * 
	 * @return int
	 */
	protected function getSize(): int
	{
		$this->checkAmbiguity();
		
		$type = $this->getType();
		
		switch($type)
		{
			case 'number':
				return $this->value;
				
			case 'array':
				return count($this->value);
				
			case 'string':
				return mb_strlen($this->value);
		}
		
		throw new LogicException("Cannot determine the size of {$type} variable");
	}
}
