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
use Ydle\SettingsBundle\Manager\RoomTypeManager;

class RoomTypeController extends Controller
{    
    /**
     * @var CategoryManagerInterface
     */
    protected $roomTypeManager;
    
    protected $container;
    
    public function __construct(\Ydle\HubBundle\Manager\RoomTypeManager $roomTypeManager, Container $container)
    {
        $this->roomTypeManager = $roomTypeManager;
        $this->container = $container;
    }
    
    /**
     * Retrieve the list of available room types
     * 
     * @QueryParam(name="page", requirements="\d+", default="1", description="Page for room types list pagination")
     * @QueryParam(name="count", requirements="\d+", default="10", description="Number of room by page")
     * 
     * @param ParamFetcher $paramFetcher
     */
    public function getRoomTypeAction(ParamFetcher $paramFetcher)
    {
        $page = $paramFetcher->get('page');
        $count = $paramFetcher->get('count');
        
        $pager = $this->getRoomTypeManager()->getPager($this->filterCriteria($paramFetcher), $page, $count);
       
        return $pager;
    }
    
    
    /**
     * Retrieve the list of available room types
     * 
     * @QueryParam(name="roomtype_id", requirements="\d+", default="0", description="Id of the room type")
     * 
     * @param ParamFetcher $paramFetcher
     */
    public function getRoomTypeDetailAction(ParamFetcher $paramFetcher)
    {
        $roomtypeId = $paramFetcher->get('roomtype_id');
        
        if(!$result = $this->getRoomTypeManager()->find($roomtypeId)){
            return 'ok';
            throw new HttpException(404, 'This room type does not exist');
        }
        
        return $result;
        
    }
    
    /**
     * 
     * @QueryParam(name="roomtype_id", requirements="\d+", default="0", description="Id of the room type")
     * @QueryParam(name="state", requirements="\d+", default="1", description="New state for this room type")
     * 
     * @param \FOS\RestBundle\Request\ParamFetcher $paramFetcher
     */
    public function putRoomTypeStateAction(ParamFetcher $paramFetcher)
    {
        $roomtypeId = $paramFetcher->get('roomtype_id');
        $state = $paramFetcher->get('state');
        
        if(!$result = $this->getRoomTypeManager()->changeState($roomtypeId, $state)){
            throw new HttpException(404, 'This room type does not exist');
        }
        if($state == 1){            
            $message = $this->getTranslator()->trans('roomtype.activate.success');
            $this->getLogger()->log('info', $message, 'hub');
        } elseif($state == 0){            
            $message = $this->getTranslator()->trans('roomtype.deactivate.success');
            $this->getLogger()->log('info', $message, 'hub');
        }
        
        return $result;
    }
    
    /**
     * 
     * @QueryParam(name="roomtype_id", requirements="\d+", default="0", description="Id of the room type")
     * 
     * @param \FOS\RestBundle\Request\ParamFetcher $paramFetcher
     */
    public function deleteRoomTypeAction(ParamFetcher $paramFetcher)
    {
        $roomtypeId = $paramFetcher->get('roomtype_id');        
        
        if(!$object = $this->getRoomTypeManager()->find($roomtypeId)){
            throw new HttpException(404, 'This room type does not exist');
        }
        $result = $this->getRoomTypeManager()->delete($object);
        
        $message = $this->get('translator')->trans('roomtype.delete.success');
        $this->get('ydle.logger')->log('info', $message, 'hub');
        
        return $result;
    }
    
    /**
     * Wrapper for roomtype manager
     */
    private function getRoomTypeManager()
    {
        return $this->roomTypeManager;
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
