<?php

namespace SMWCentral\Validation;

interface ITokenProvider
{
	/**
	 * Return the input key for the CSRF token.
	 * 
	 * @param \SMWCentral\Validation\Validator $validator
	 * @return string
	 */
	public function getTokenKey(Validator $validator): string;
	
	/**
	 * Return the expected value for the CSRF token.
	 * 
	 * @param \SMWCentral\Validation\Validator $validator
	 * @return string
	 */
	public function getTokenValue(Validator $validator): string;
}
