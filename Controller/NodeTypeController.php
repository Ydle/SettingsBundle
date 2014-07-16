<?php

namespace Ydle\SettingsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Request\ParamFetcher;
use Symfony\Component\DependencyInjection\ContainerAware;
use FOS\RestBundle\View\RouteRedirectView,
    FOS\RestBundle\View\View,
    FOS\RestBundle\Controller\Annotations\QueryParam,
    FOS\RestBundle\Request\ParamFetcherInterface;
use Ydle\NodesBundle\Manager\NodeTypeManager;

class NodeTypeController extends Controller
{    
    /**
     * @var CategoryManagerInterface
     */
    protected $nodeTypeManager;
    
    public function __construct(\Ydle\NodesBundle\Manager\NodeTypeManager $nodeTypeManager)
    {
        $this->nodeTypeManager = $nodeTypeManager;
    }
    
    /**
     * Retrieve the list of available node types
     * 
     * @QueryParam(name="page", requirements="\d+", default="1", description="Page for node types list pagination")
     * @QueryParam(name="count", requirements="\d+", default="10", description="Number of nodes by page")
     * 
     * @param ParamFetcher $paramFetcher
     */
    public function getNodeTypeAction(ParamFetcher $paramFetcher)
    {
        $page = $paramFetcher->get('page');
        $count = $paramFetcher->get('count');
        
        $pager = $this->getNodeTypeManager()->getPager($this->filterCriteria($paramFetcher), $page, $count);
       
        return $pager;
    }
    
    
    /**
     * Retrieve the list of available node types
     * 
     * @QueryParam(name="nodetype_id", requirements="\d+", default="0", description="Id of the node type")
     * 
     * @param ParamFetcher $paramFetcher
     */
    public function getNodeTypeDetailAction(ParamFetcher $paramFetcher)
    {
        $nodetypeId = $paramFetcher->get('nodetype_id');
        
        if(!$result = $this->getNodeTypeManager()->find($nodetypeId)){
            return 'ok';
            throw new HttpException(404, 'This node type does not exist');
        }
        
        return $result;
        
    }
    
    /**
     * 
     * @QueryParam(name="nodetype_id", requirements="\d+", default="0", description="Id of the node type")
     * @QueryParam(name="state", requirements="\d+", default="1", description="New state for this node type")
     * 
     * @param \FOS\RestBundle\Request\ParamFetcher $paramFetcher
     */
    public function putNodeTypeStateAction(ParamFetcher $paramFetcher)
    {
        $nodetypeId = $paramFetcher->get('nodetype_id');
        $state = $paramFetcher->get('state');
        
        if(!$result = $this->getNodeTypeManager()->changeState($nodetypeId, $state)){
            throw new HttpException(404, 'This node type does not exist');
        }
        
        return $result;
    }
    
    /**
     * 
     * @QueryParam(name="nodetype_id", requirements="\d+", default="0", description="Id of the node type")
     * 
     * @param \FOS\RestBundle\Request\ParamFetcher $paramFetcher
     */
    public function deleteNodeTypeAction(ParamFetcher $paramFetcher)
    {
        $nodetypeId = $paramFetcher->get('nodetype_id');        
        
        if(!$object = $this->getNodeTypeManager()->find($nodetypeId)){
            throw new HttpException(404, 'This node type does not exist');
        }
        $result = $this->getNodeTypeManager()->delete($object);
        
        return $result;
    }
    
    /**
     * Wrapper for nodetype manager
     */
    private function getNodeTypeManager()
    {
        return $this->nodeTypeManager;
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
