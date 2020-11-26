<?php

namespace SMWCentral\Validation;

class Validator
{
	/**
	 * The array of input values.
	 * 
	 * @var array
	 */
	protected $input;
	
	/**
	 * The message bag holding the errors.
	 * 
	 * @var \SMWCentral\Validation\MessageBag
	 */
	protected $errors;
	
	/**
	 * Create a new validator instance.
	 * 
	 * @param array $input
	 * @return void
	 */
	public function __construct(array $input)
	{
		$this->input = $input;
		$this->errors = new MessageBag();
	}
	
	/**
	 * Check for validation errors.
	 * 
	 * @param bool $expectToken
	 * @return void
	 */
	public function passes(bool $expectToken = true): bool
	{
		// FIXME
		if($expectToken && $this->getValue(self::TOKEN_NAME) !== token())
		{
			$this->errors->add(self::TOKEN_NAME, new Message('token', 'token'));
		}
		
		return $this->errors->isEmpty();
	}
	
	/**
	 * Get the array of input values.
	 * 
	 * @return array
	 */
	public function getInput(): array
	{
		return $this->input;
	}
	
	/**
	 * Get the value of an input field.
	 * 
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function getValue(string $key, $default = null)
	{
		$array = $this->input;
		
		if(array_key_exists($key, $array))
		{
			return $array[$key];
		}
		
		if(strpos($key, '.') === false)
		{
			return $array[$key] ?? $default;
		}
		
		foreach(explode('.', $key) as $segment)
		{
			if(is_array($array) && array_key_exists($segment, $array))
			{
				$array = $array[$segment];
			}
			else
			{
				return $default;
			}
		}
		
		return $array;
	}
	
	/**
	 * Get the message bag for validation errors.
	 * 
	 * @return \SMWCentral\Validation\MessageBag
	 */
	public function errors(): MessageBag
	{
		return $this->errors;
	}
	
	/**
	 * Create a variable instance for a given input field.
	 * 
	 * If no default value is given, the variable will be implicitly
	 * declared as required.
	 * 
	 * @param string $key
	 * @param mixed $default
	 * @param array $args
	 * @return \SMWCentral\Validation\Variable
	 */
	public function retrieve(string $key, $default, array $args = []): Variable
	{
		$variable = new Variable($this, $key, $this->getValue($key), $args);
		
		if($default === null)
		{
			$variable->required();
		}
		else
		{
			$variable->default($default);
		}
		
		return $variable;
	}
	
	/**
	 * Create a variable instance for a given string field.
	 * 
	 * @param string $key
	 * @param string|null $default
	 * @param array $args
	 * @return \SMWCentral\Validation\Variable
	 */
	public function string(string $key, ?string $default = null, array $args = []): Variable
	{
		return $this->retrieve($key, $default, $args)->string();
	}
	
	/**
	 * Create a variable instance for a given integer field.
	 * 
	 * @param string $key
	 * @param int|null $default
	 * @param array $args
	 * @return \SMWCentral\Validation\Variable
	 */
	public function integer(string $key, ?int $default = null, array $args = []): Variable
	{
		return $this->retrieve($key, $default, $args)->integer();
	}
	
	/**
	 * Create a variable instance for a given number field.
	 * 
	 * @param string $key
	 * @param float|null $default
	 * @param array $args
	 * @return \SMWCentral\Validation\Variable
	 */
	public function float(string $key, ?float $default = null, array $args = []): Variable
	{
		return $this->retrieve($key, $default, $args)->float();
	}
	
	/**
	 * Create a variable instance for a given boolean field.
	 * 
	 * @param string $key
	 * @param bool|null $default
	 * @param array $args
	 * @return \SMWCentral\Validation\Variable
	 */
	public function boolean(string $key, ?bool $default = null, array $args = []): Variable
	{
		return $this->retrieve($key, $default, $args)->boolean();
	}
	
	/**
	 * Create a variable instance for a given array field.
	 * 
	 * @param string $key
	 * @param array|null $default
	 * @param array $args
	 * @return \SMWCentral\Validation\Variable
	 */
	public function array(string $key, $default = null, array $args = []): Variable
	{
		return $this->retrieve($key, $default, $args)->array();
	}
}
