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

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Buzz\Client\ClientInterface as HttpClientInterface;
use Buzz\Message\Request as HttpRequest;
use Buzz\Message\RequestInterface as HttpRequestInterface;
use Buzz\Message\Response as HttpResponse;

/**
 * RequestProcessor allow to process an HTTP request.
 *
 * @author Thomas Prelot <tprelot@gmail.com>
 */
class RequestProcessor implements RequestProcessorInterface
{
    /**
     * The HTTP client.
     *
     * @var HttpClientInterface
     */
    protected $httpClient;

    /**
     * The session.
     *
     * @var SessionInterface
     */
    protected $session;

    /**
     * Constructor.
     *
     * @param HttpClientInterface $httpClient The HTTP client.
     * @param SessionInterface $session The session.
     */
    public function __construct(HttpClientInterface $httpClient, SessionInterface $session)
    {
        $this->httpClient = $httpClient;
        $this->httpClient->setMaxRedirects(10);
        $this->httpClient->setTimeout(7);

        $this->session = $session;
    }

    /**
     * {@inheritDoc}
     */
    public function process($url, $content = null, $headers = array(), $method = null)
    {
        if (null === $method) {
            $method = null === $content ? HttpRequestInterface::METHOD_GET : HttpRequestInterface::METHOD_POST;
        }

        $request  = new HttpRequest($method, $url);
        $response = new HttpResponse();

        $headers = array_merge(
            array(
                sprintf('Cookie: PHPSESSID=%s', $this->session->getId()),
                'User-Agent: DaOAuthClientBundle (https://github.com/Gnuckorg/DaOAuthClientBundle)',
            ),
            $headers
        );

        $request->setHeaders($headers);
        $request->setContent($content);

        // Unlock the session for potential redirection.
        $this->session->save();
        $this->httpClient->send($request, $response);
        $this->session->start();

        return $response;
    }
}