<?php

namespace SMWCentral\Validation;

interface IMessageResolver
{
	/**
	 * Create a complete human-readable message.
	 * 
	 * @param string $field
	 * @param string $key
	 * @param array $args
	 * @return string
	 */
	public function resolveMessage(string $field, string $key, array $args): string;
}
