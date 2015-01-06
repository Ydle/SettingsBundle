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
use Ydle\NodesBundle\Manager\RoomManager;

class RoomController extends Controller
{    
    /**
     * @var CategoryManagerInterface
     */
    protected $roomManager;
    
    protected $container;
    
    public function __construct(\Ydle\HubBundle\Manager\RoomManager $roomManager, Container $container)
    {
        $this->roomManager = $roomManager;
        $this->container = $container;
    }
    
    /**
     * Retrieve the list of available rooms
     * 
     * @QueryParam(name="page", requirements="\d+", default="0", description="Number of page")
     * @QueryParam(name="count", requirements="\d+", default="0", description="Number of room by page")
     * 
     * @param ParamFetcher $paramFetcher
     */
    public function getRoomsListAction(ParamFetcher $paramFetcher)
    {
	$page  = $paramFetcher->get('page');
        $count = $paramFetcher->get('count');
        
        $pager = $this->getRoomManager()->getPager($this->filterCriteria($paramFetcher), $page, $count);
       
        return $pager;
    }
    
    /**
     * Wrapper for room manager
     */
    private function getRoomManager()
    {
        return $this->roomManager;
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
     * @QueryParam(name="room_id", requirements="\d+", default="0", description="Id of the room")
     * @QueryParam(name="state", requirements="\d+", default="1", description="New state for this room")
     * 
     * @param \FOS\RestBundle\Request\ParamFetcher $paramFetcher
     */
    public function putRoomStateAction(ParamFetcher $paramFetcher)
    {
        $roomId = $paramFetcher->get('room_id');
        $state = $paramFetcher->get('state');
        
        if(!$result = $this->getRoomManager()->changeState($roomId, $state)){
            throw new HttpException(404, 'This room does not exist');
        }
        if($state == 1){            
            $message = $this->getTranslator()->trans('room.activate.success');
            $this->getLogger()->log('info', $message, 'hub');
        } elseif($state == 0){            
            $message = $this->getTranslator()->trans('room.deactivate.success');
            $this->getLogger()->log('info', $message, 'hub');
        }
        
        return $result;
    }
    
    /**
     * 
     * @QueryParam(name="room_id", requirements="\d+", default="0", description="Id of the room")
     * 
     * @param \FOS\RestBundle\Request\ParamFetcher $paramFetcher
     */
    public function deleteRoomAction(ParamFetcher $paramFetcher)
    {
        $roomId = $paramFetcher->get('room_id');        
        
        if(!$object = $this->getRoomManager()->find($roomId)){
            throw new HttpException(404, 'This room does not exist');
        }
        $result = $this->getRoomManager()->delete($object);
        
        return $result;
    }
}
