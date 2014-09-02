<?php

/*
 * This file is part of the Da Project.
 *
 * (c) Thomas Prelot <tprelot@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Da\OAuthClientBundle\Request;

/**
 * IdentitySelectorInterface is the interface that class should
 * implement to be used as an identity selector.
 *
 * @author Thomas Prelot <tprelot@gmail.com>
 */
interface RequestProcessorInterface
{
    /**
     * Process an HTTP request.
     *
     * @param string $url     The url to fetch.
     * @param string $content The content of the request.
     * @param array  $headers The headers of the request.
     * @param string $method  The HTTP method to use.
     *
     * @return \Buzz\Message\MessageInterface The response content.
     */
    function process($url, $content = null, $headers = array(), $method = null);
}