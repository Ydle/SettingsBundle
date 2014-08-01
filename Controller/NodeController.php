<?php

namespace Ydle\SettingsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\DependencyInjection\Container;
use FOS\RestBundle\Request\ParamFetcher;
use Symfony\Component\DependencyInjection\ContainerAware;
use FOS\RestBundle\View\RouteRedirectView,
    FOS\RestBundle\View\View,
    FOS\RestBundle\Controller\Annotations\QueryParam,
    FOS\RestBundle\Request\ParamFetcherInterface;
use Ydle\NodesBundle\Manager\NodeTypeManager;

class NodeController extends Controller
{    
    /**
     * @var CategoryManagerInterface
     */
    protected $nodeManager;
    
    protected $container;
    
    public function __construct(\Ydle\NodesBundle\Manager\NodeManager $nodeTypeManager, Container $container)
    {
        $this->nodeManager = $nodeTypeManager;
        $this->container = $container;
    }
    
    /**
     * Retrieve the list of available node types
     * 
     * @QueryParam(name="page", requirements="\d+", default="0", description="Number of page")
     * @QueryParam(name="count", requirements="\d+", default="0", description="Number of nodes by page")
     * 
     * @param ParamFetcher $paramFetcher
     */
    public function getNodesListAction(ParamFetcher $paramFetcher)
    {
	$page  = $paramFetcher->get('page');
        $count = $paramFetcher->get('count');
        
        $pager = $this->getNodeManager()->getPager($this->filterCriteria($paramFetcher), $page, $count);
       
        return $pager;
    }
    
    /**
     * Wrapper for node manager
     */
    private function getNodeManager()
    {
        return $this->nodeManager;
    }
    
    /**
    * Filters criteria from $paramFetcher to be compatible with the Pager criteria
    *
    * @param ParamFetcherInterface $paramFetcher
    *
    * @return array The filtered criteria
    */
    protected function filterCriteria(ParamFetcherInterface $paramFetcher)
    {
        $criteria = $paramFetcher->all();

        unset($criteria['page'], $criteria['count']);

        foreach ($criteria as $key => $value) {
            if (null === $value) {
                unset($criteria[$key]);
            }
        }

        return $criteria;
    }
}
