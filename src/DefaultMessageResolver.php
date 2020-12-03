<?php

namespace SMWCentral\Validation;

class DefaultMessageResolver implements IMessageResolver
{
	/**
	 * Create a complete human-readable message.
	 * 
	 * @param string $field
	 * @param string $key
	 * @param array $args
	 * @return string
	 */
	public function resolveMessage(string $field, string $key, array $args): string
	{
		$messages = $this->getMessages();
		
		if(!isset($messages[$key]))
		{
			return $key;
		}
		
		$message = $messages[$key];
		
		foreach($args as $key => $value)
		{
			$message = str_replace('{' . $key . '}', $value, $message);
		}
		
		return str_replace('{field}', $field, $message);
	}
	
	/**
	 * Return the default messages.
	 * 
	 * @return array
	 */
	protected function getMessages(): array
	{
		static $messages;
		
		if(!isset($messages))
		{
			$messages = [
				'required' => 'The {field} is required.',
				'string' => 'The {field} must be a string.',
				'integer' => 'The {field} must be a number.',
				'float' => 'The {field} must be a number.',
				'array' => 'The {field} has invalid data.',
				'token' => 'Invalid token. Please try again.',
				'between.number' => 'The {field} must be between {min} and {max}.',
				'between.array' => 'The {field} must have between {min} and {max} elements.',
				'between.string' => 'The {field} must have between {min} and {max} characters.',
				'max.number' => 'The {field} may not be greater than {max}.',
				'max.string' => 'The {field} may not have more than {max} characters.',
				'max.array' => 'The {field} may not have more than {max} elements.',
				'min.number' => 'The {field} must be at least {min}.',
				'min.string' => 'The {field} must have at least {min} characters.',
				'min.array' => 'The {field} must have at least {min} elements.',
				'size.number' => 'The {field} must be {size}.',
				'size.string' => 'The {field} must have {size} characters.',
				'size.array' => 'The {field} must have {size} elements.',
				'in' => 'The {field} must be one of {values}.',
				'unique' => 'The {field} has already been taken.',
				'exists' => 'The {field} does not exist.'
			];
		}
		
		return $messages;
	}
}
