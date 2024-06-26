<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2017 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\MenuBundle\Voter;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * This voter compares whether a key in the request is identical to the content
 * entry in the menu item extras.
 *
 * This voter is NOT enabled by default, as usually this is already covered
 * by the core menu bundle looking at request URLs.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
class RequestContentIdentityVoter implements VoterInterface
{
    /**
     * @var string The key to look up the content in the request attributes
     */
    private $requestKey;

    private ?\Symfony\Component\HttpFoundation\RequestStack $requestStack = null;

    private ?\Symfony\Component\HttpFoundation\Request $request = null;

    /**
     * @param string $requestKey The key to look up the content in the request
     *                           attributes
     */
    public function __construct($requestKey, RequestStack $requestStack = null)
    {
        $this->requestKey = $requestKey;
        $this->requestStack = $requestStack;
    }

    /**
     * @deprecated since version 2.2. Pass a RequestStack to the constructor instead.
     */
    public function setRequest(Request $request = null)
    {
        @trigger_error(
            sprintf(
                'The %s() method is deprecated since version 2.2.
                Pass a Symfony\Component\HttpFoundation\RequestStack
                in the constructor instead.',
                __METHOD__),
            E_USER_DEPRECATED
        );

        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function matchItem(ItemInterface $item): ?bool
    {
        $request = $this->getRequest();
        if (!$request) {
            return null;
        }

        $content = $item->getExtra('content');

        if (null !== $content
            && $request->attributes->has($this->requestKey)
            && $request->attributes->get($this->requestKey) === $content
        ) {
            return true;
        }

        return null;
    }

    private function getRequest()
    {
        if ($this->requestStack) {
            return $this->requestStack->getMasterRequest();
        }

        return $this->request;
    }
}
