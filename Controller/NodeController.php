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
use Ydle\HubBundle\Entity\NodeData;
use Symfony\Component\HttpFoundation\JsonResponse;

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
     * Retrieve the list of available node types
     * 
     * @QueryParam(name="room_id", requirements="\d+", default="0", description="Id of the room")
     * @QueryParam(name="page", requirements="\d+", default="0", description="Number of page")
     * @QueryParam(name="count", requirements="\d+", default="0", description="Number of nodes by page")
     * 
     * @param ParamFetcher $paramFetcher
     */
    public function getRoomNodesListAction(ParamFetcher $paramFetcher)
    {
	$page   = $paramFetcher->get('page');
        $count  = $paramFetcher->get('count');
        
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
     * Wrapper for node manager
     */
    private function getNodeTypeManager()
    {
        return $this->container->get('ydle.nodetype.manager');
    }

    private function getNodeDataManager()
    {
        return $this->container->get('ydle.data.manager');
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
    
    /**
     * 
     * @QueryParam(name="node_id", requirements="\d+", default="0", description="Id of the node")
     * @QueryParam(name="state", requirements="\d+", default="1", description="New state for this node")
     * 
     * @param \FOS\RestBundle\Request\ParamFetcher $paramFetcher
     */
    public function putNodeStateAction(ParamFetcher $paramFetcher)
    {
        $nodeId = $paramFetcher->get('node_id');
        $state = $paramFetcher->get('state');
        
        if(!$result = $this->getNodeManager()->changeState($nodeId, $state)){
            $message = $this->getTranslator()->trans('node.not.found');
            throw new HttpException(404, $message);
        }
        if($state == 1){            
            $message = $this->getTranslator()->trans('node.activate.success');
            $this->getLogger()->log('info', $message, 'hub');
        } elseif($state == 0){            
            $message = $this->getTranslator()->trans('node.deactivate.success');
            $this->getLogger()->log('info', $message, 'hub');
        }
        
        return $result;
    }
        
    /**
     * 
     * @QueryParam(name="sender", requirements="\d+", default="0", description="Code of the node sending data")
     * @QueryParam(name="type", requirements="\d+", default="0", description="type of data sent")
     * @QueryParam(name="data", requirements="\d+", default="1", description="New state for this node")
     * 
     * @param \FOS\RestBundle\Request\ParamFetcher $paramFetcher
     */
    public function postNodesDatasAction(ParamFetcher $paramFetcher)
    {
        $sender   = $paramFetcher->get('sender');
        $type     = $paramFetcher->get('type');
        $data     = $paramFetcher->get('data');

        if(!$node = $this->getNodeManager()->findOneBy(array('code' => $sender))){            
            $message = $this->getTranslator()->trans('node.not.found');
            throw new HttpException(404, $message);
        }

        if(!$type = $this->getNodeTypeManager()->find($type)){          
            $message = $this->getTranslator()->trans('nodetype.not.found');
            throw new HttpException(404, $message);
        }

        if(empty($data)){ 
            $message = $this->getTranslator()->trans('data.not.found');
            throw new HttpException(404, $message);
        }
        
        $nodeData = new NodeData();
        $nodeData->setNode($node);
        $nodeData->setData($data);
        $nodeData->setType($type);
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($nodeData);
        $em->flush();
        
        $this->get('ydle.logger')->log('data', 'Data received from node #'.$sender.' : '.$data, 'node');
        
        return new JsonResponse(array('code' => 0, 'result' => 'data sent'));

    }


    /**
     *
     * @QueryParam(name="node", requirements="\d+", default="0", description="Code of the node")
     * @QueryParam(name="filter", requirements="\w+", default="day", description="date filter")
     * 
     * @param \FOS\RestBundle\Request\ParamFetcher $paramFetcher
     */
    public function getRoomNodeStatsAction(ParamFetcher $paramFetcher)
    {
        $code = $paramFetcher->get('node');
        $filter = $paramFetcher->get('filter');

        if(!$node = $this->getNodeManager()->findOneBy(array('code' => $code))){
            $message = $this->getTranslator()->trans('node.not.found');
            throw new HttpException(404, $message);
        }
        
        // Manage starting date
        $startTime = 0;
        switch($filter){
            case 'month':
                $startTime = strtotime("-1 month");
                break;
            case 'week':
                $startTime = strtotime("-1 week");
                break;
            default:
            case 'day':
                $startTime = strtotime("-1 day");
                break;
        }
        $startDate = new \DateTime();
        $startDate->setTimestamp($startTime);
        
        $params = array(
            'node_id' => $node->getId(),
            'room_id' => $node->getRoom()->getId(),
	    'start_date' => $startDate
        );
        $datas = $this->getNodeDataManager()->findByParams($params);

        $result = array();
        foreach($datas as $data)
        {
            $type = $data->getType();
            if(empty($result[$type->getId()])) {
                $result[$type->getId()] = array(
                    'label' => $type->getName().' ('. $type->getUnit().')',
                    'data' => array(),
                );
            }
            $value = $data->getData();
            switch($type->getUnit()){
                case '°C':
                    $value = round($value / 100, 1);
            }
            $result[$type->getId()]['data'][] = array((int)$data->getCreated()->format('U') * 1000, $value);
        }
        //return new JsonResponse(array(array('label'=>'test', 'data' => array(array(1,10), array(2, 12)))));
        return new JsonResponse($result);
    }
    
    public function getTranslator(){
        return $this->container->get('translator');
    }
    
    /**
     * 
     * @QueryParam(name="node", requirements="\d+", default="0", description="Id of the node")
     * 
     * @param \FOS\RestBundle\Request\ParamFetcher $paramFetcher
     */
    public function putNodeResetAction(ParamFetcher $paramFetcher)
    {
        $statusCode = 200;
        $nodeId = $paramFetcher->get('node');
        
        if(!$node = $this->getNodeManager()->find($nodeId)){
            $message = $this->getTranslator()->trans('node.not.found');
            throw new HttpException(404, $message);
        }
        // Check if the required options are set in the parameters.yml file
        $masterAddr = $this->container->getParameter('master_address');
        $masterCode = $this->container->getParameter('master_id');  
        if(empty($masterAddr) || empty($masterAddr)){
            $message = $this->getTranslator()->trans('node.reset.fail.nomaster');
            $this->get('session')->getFlashBag()->add('error', $message);
            $statusCode = 404;            
        }
        
        $address = $masterAddr;
        $address .= ':8888/node/reset?target='.$node->getCode().'&sender=';
        $address .= $masterCode;
        
        $ch = curl_init($address);
        curl_exec($ch);
        curl_close($ch);
        $message = $this->getTranslator()->trans('node.reset.success', array('%nodeCode%' => $node->getCode()));
        $this->get('ydle.logger')->log('info', $message);
        $this->get('session')->getFlashBag()->add('notice', 'Reset envoyé');
        
        return $statusCode;
    }
    
    /**
     * 
     * @QueryParam(name="node_id", requirements="\d+", default="0", description="Id of the node")
     * 
     * @param \FOS\RestBundle\Request\ParamFetcher $paramFetcher
     */
    public function deleteNodeAction(ParamFetcher $paramFetcher)
    {
        $nodeId = $paramFetcher->get('node_id');        
        
        if(!$object = $this->getNodeManager()->find($nodeId)){
            throw new HttpException(404, 'This node does not exist');
        }
        $result = $this->getNodeManager()->delete($object);
        
        return $result;
    }
}
