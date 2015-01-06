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
use Ydle\HubBundle\Manager\NodeTypeManager;

class NodeTypeController extends Controller
{    
    /**
     * @var CategoryManagerInterface
     */
    protected $nodeTypeManager;
    
    protected $container;
    
    public function __construct(\Ydle\HubBundle\Manager\NodeTypeManager $nodeTypeManager, Container $container)
    {
        $this->nodeTypeManager = $nodeTypeManager;
        $this->container = $container;
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
        if($state == 1){            
            $message = $this->getTranslator()->trans('nodetype.activate.success');
            $this->getLogger()->log('info', $message, 'hub');
        } elseif($state == 0){            
            $message = $this->getTranslator()->trans('nodetype.deactivate.success');
            $this->getLogger()->log('info', $message, 'hub');
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
        
        $message = $this->getTranslator()->trans('nodetype.delete.success');
        $this->getLogger()->log('info', $message, 'hub');
        
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
     * Wrapper for translator
     */
    private function getTranslator()
    {
        return $this->container->get('translator');
    }
    
    /**
     * Wrapper for logger
     */
    private function getLogger()
    {
        return $this->container->get('ydle.logger');
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
