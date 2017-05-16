<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Schedule;
use AppBundle\Entity\Track;
use Doctrine\ORM\Tools\Pagination\Paginator;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ScheduleController extends FOSRestController
{
    /**
     * @Rest\Get("/api/schedules")
     * @Annotations\QueryParam(name="track_id", nullable=true, description="Track id.")
     * @Annotations\QueryParam(name="_sort", nullable=true, description="Sort field.")
     * @Annotations\QueryParam(name="_order", nullable=true, description="Sort Order.")
     * @Annotations\QueryParam(name="_start", nullable=true, description="Start.")
     * @Annotations\QueryParam(name="_end", nullable=true, description="End.")
     */
    public function indexAction(Request $request,ParamFetcherInterface $paramFetcher)
    {
        $track_id = $paramFetcher->get('track_id');

        $sortField = $paramFetcher->get('_sort');
        $sortOrder = $paramFetcher->get('_order');
        $start = $paramFetcher->get('_start');
        $end = $paramFetcher->get('_end');

        $query = $this->getDoctrine()
            ->getRepository('AppBundle:Schedule')
            ->findAllQuery($track_id,$sortField,$sortOrder,$start,$end);

        $paginator = new Paginator($query);
        $totalCount = $paginator->count();

        $restresult = $query->getResult();

        if ($restresult === null) {
            return new View("there are no Schedules exist", Response::HTTP_NOT_FOUND);
        }

        $view = $this->view($restresult, 200)
            ->setHeader('Access-Control-Expose-Headers', 'X-Total-Count')
            ->setHeader('X-Total-Count', $totalCount);

        return $this->handleView($view);
    }

    /**
     * @Rest\Get("/api/schedules/{id}")
     */
    public function getAction($id)
    {
        $singleresult = $this->getDoctrine()->getRepository('AppBundle:Schedule')->find($id);
        if ($singleresult === null) {
            return new View("track not found", Response::HTTP_NOT_FOUND);
        }
        return $singleresult;
    }

    /**
     * @Rest\Post("/api/schedules")
     */
    public function postAction(Request $request)
    {
        $schedule = new Schedule();

        $_day_date = strtotime($request->get('day_date'));

        $_start_time = explode(':',$request->get('start_time'));
        $_start_time_hours = (int) $_start_time[0];
        $_start_time_minutes = (int) $_start_time[1];

        $start_time = date( 'Y-m-d H:i:s', mktime($_start_time_hours, $_start_time_minutes, 0, date("n",$_day_date), date("j",$_day_date), date("Y",$_day_date)) );

        $_end_time = explode(':',$request->get('end_time'));
        $_end_time_hours = (int) $_end_time[0];
        $_end_time_minutes = (int) $_end_time[1];

        $end_time = date( 'Y-m-d H:i:s', mktime($_end_time_hours, $_end_time_minutes, 0, date("n",$_day_date), date("j",$_day_date), date("Y",$_day_date)) );

        $track = $this->getDoctrine()
            ->getRepository('AppBundle:Track')
            ->find($request->get('track_id'));

        $schedule->setStartTime(new \DateTime($start_time));
        $schedule->setEndTime(new \DateTime($end_time));
        $schedule->setTrack($track);

        $em = $this->getDoctrine()->getManager();
        $em->persist($schedule);
        $em->flush();

        return new View($schedule, Response::HTTP_OK);
    }

    /**
     * @Rest\Put("/api/schedules/{id}")
     */
    public function updateAction($id, Request $request)
    {
        $data = new Track;
        $name = $request->get('name');
        $branch = $this->getDoctrine()->getRepository('AppBundle:Branch')->find($request->get('branch_id'));

        $ss = $this->getDoctrine()->getManager();
        $track = $ss->getRepository('AppBundle:Track')->find($id);

        if (empty($track)) {
            return new View("track not found", Response::HTTP_NOT_FOUND);
        } elseif (!empty($name) && !empty($branch)) {
            $track->setName($name);
            $data->setBranch($branch);
            $ss->flush();
            return new View("Track Updated Successfully", Response::HTTP_OK);
        } else return new View("NULL VALUES ARE NOT ALLOWED", Response::HTTP_NOT_ACCEPTABLE);
    }

    /**
     * @Rest\Delete("/api/schedules/{id}")
     */
    public function deleteAction($id)
    {
        $data = new Track;
        $sn = $this->getDoctrine()->getManager();
        $track = $sn->getRepository('AppBundle:Track')->find($id);
        if (empty($track)) {
            return new View("track not found", Response::HTTP_NOT_FOUND);
        }
        else {
            $sn->remove($track);
            $sn->flush();
        }
        return new View("deleted successfully", Response::HTTP_OK);
    }
}
