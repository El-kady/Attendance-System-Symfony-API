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
     * @Rest\Get("/api/schedules/new")
     */
    public function getnewAction(Request $request)
    {
        $track_id = $request->get('track_id');

        

        $query = $this->getDoctrine()
            ->getRepository('AppBundle:Schedule')
            ->findNewSchedules($track_id);

        $restresult = $query->getResult();

        if ($restresult === null) {
            return new View("there are no Schedules exist", Response::HTTP_NOT_FOUND);
        }

        $view = $this->view($restresult, 200);

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

        $day_date = $request->get('day_date');
        $start_time = $request->get('start_time');
        $end_time = $request->get('end_time');

        $track = $this->getDoctrine()
            ->getRepository('AppBundle:Track')
            ->find($request->get('track_id'));

        $schedule->setCalenderId(1);
        $schedule->setDayDate(new \DateTime($day_date));
        $schedule->setStartTime($start_time);
        $schedule->setEndTime($end_time);
        $schedule->setTrack($track);
        $schedule->setDescription("123");

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

        $day_date = $request->get('day_date');
        $start_time = $request->get('start_time');
        $end_time = $request->get('end_time');

        $track = $this->getDoctrine()
            ->getRepository('AppBundle:Track')
            ->find($request->get('track_id'));

        $ss = $this->getDoctrine()->getManager();
        $schedule = $ss->getRepository('AppBundle:Schedule')->find($id);

        $schedule->setDayDate(new \DateTime($day_date));
        $schedule->setStartTime($start_time);
        $schedule->setEndTime($end_time);
        $schedule->setTrack($track);

        if (!empty($day_date) && !empty($start_time) && !empty($end_time)) {
            $ss->flush();
            return new View("Updated Successfully", Response::HTTP_OK);
        } else return new View("NULL VALUES ARE NOT ALLOWED", Response::HTTP_NOT_ACCEPTABLE);
    }

    /**
     * @Rest\Delete("/api/schedules/{id}")
     */
    public function deleteAction($id)
    {
        $sn = $this->getDoctrine()->getManager();
        $schedule = $sn->getRepository('AppBundle:Schedule')->find($id);
        if (empty($schedule)) {
            return new View("not found", Response::HTTP_NOT_FOUND);
        }
        else {
            $sn->remove($schedule);
            $sn->flush();
        }
        return new View("deleted successfully", Response::HTTP_OK);
    }
}
