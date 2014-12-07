<?php

namespace Ydle\SettingsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Request\ParamFetcher;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAware;
use FOS\RestBundle\View\RouteRedirectView,
    FOS\RestBundle\View\View,
    FOS\RestBundle\Controller\Annotations\QueryParam,
    FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\Controller\Annotations\Post;
use Ydle\LogsBundle\Manager\LogsManager;

class LogsController extends Controller
{
    /**
     * @var CategoryManagerInterface
     */
    protected $logsManager;
    protected $container;
    
    public function __construct(\Ydle\LogsBundle\Manager\LogsManager $logsManager, Container $container)
    {
        $this->logsManager = $logsManager;
        $this->container = $container;
    }

    /**
     * Allow to create log for master
     * 
     * @QueryParam(name="message", requirements="\w+", default="test", description="message")
     * @QueryParam(name="level", requirements="\w+", default="info", description="level")
     * @Post("/api/log/add")
     */
    public function postApiLogAction(ParamFetcher $paramFetcher)
    {
        $message = $paramFetcher->get('message');
        $level = $paramFetcher->get('level');

file_put_contents('/var/www/manuel/htdocs/ydletest/debug_ydle.txt', $message);

        if(empty($message)){             
            $error = $this->getTranslator()->trans('log.empty.message');
            throw new HttpException(404, $error);
        } 
    	$this->getLogger()->log($level, $message, 'master'); 
    }
    
    /**
     * Retrieve the list of available node types
     * 
     * @QueryParam(name="page", requirements="\d+", default="1", description="Page for node types list pagination")
     * @QueryParam(name="count", requirements="\d+", default="10", description="Number of nodes by page")
     * @QueryParam(name="type", requirements="\w+", default="", description="type of log")
     * @QueryParam(name="source", requirements="\w+", default="", description="source of log")
     * 
     * @param ParamFetcher $paramFetcher
     */
    public function getLogsListAction(ParamFetcher $paramFetcher)
    {
        $page   = $paramFetcher->get('page');
        $count  = $paramFetcher->get('count');
        $type   = $paramFetcher->get('type');
        $source = $paramFetcher->get('source');
        
        $pager = $this->getLogsManager()->getPager($this->filterCriteria($paramFetcher), $page, $count);
       
        return $pager;
    }
    
    /**
     * 
     * @param \FOS\RestBundle\Request\ParamFetcher $paramFetcher
     */
    public function deleteLogsListAction(ParamFetcher $paramFetcher)
    {    
        $this->getLogsManager()->reset();
        
        $message = $this->getTranslator()->trans('logs.table.empty');
        $this->getLogger()->log('info', $message, 'hub');
        
    }
    
    /**
     * Wrapper for logger
     */
    private function getLogger()
    {
        return $this->container->get('ydle.logger');
    }
    
    /**
     * Wrapper for nodetype manager
     */
    private function getLogsManager()
    {
        return $this->logsManager;
    }
    
    private function getTranslator()
    {
        return $this->container->get('translator');
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
