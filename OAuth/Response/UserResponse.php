<?php

namespace Da\OAuthClientBundle\OAuth\Response;

use HWI\Bundle\OAuthBundle\OAuth\Response\PathUserResponse;

/**
 * Class parsing the properties by given path options with raw data.
 *
 * @author Thomas Prelot <thomas.prelot@tessi.fr>
 */
class UserResponse extends PathUserResponse
{
	/**
     * Constructor.
     */
	public function __construct()
	{
		$this->setPaths(array('roles' => 'roles'));
		$this->setPaths(array('raw' => 'raw'));
	}

	/**
     * Get the roles of the user.
     *
     * @return string The json encoded roles of the user.
     */
    public function getRoles()
    {
        return $this->getValueForPath('roles');
    }

    /**
     * Get the raw data of the user.
     *
     * @return string The json encoded raw data of the user.
     */
    public function getRaw()
    {
        return $this->getValueForPath('raw');
    }
}
