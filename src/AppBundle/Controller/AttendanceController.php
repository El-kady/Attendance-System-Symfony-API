<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Attendance;
use AppBundle\Entity\User;
use Doctrine\ORM\Tools\Pagination\Paginator;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AttendanceController extends FOSRestController
{
    /**
     * @Rest\Get("/api/attendances")
     * @Annotations\QueryParam(name="user_id", nullable=true, description="User id.")
     * @Annotations\QueryParam(name="_sort", nullable=true, description="Sort field.")
     * @Annotations\QueryParam(name="_order", nullable=true, description="Sort Order.")
     * @Annotations\QueryParam(name="_start", nullable=true, description="Start.")
     * @Annotations\QueryParam(name="_end", nullable=true, description="End.")
     */
    public function indexAction(Request $request,ParamFetcherInterface $paramFetcher)
    {
        $user_id = $paramFetcher->get('user_id');

        $sortField = $paramFetcher->get('_sort');
        $sortOrder = $paramFetcher->get('_order');
        $start = $paramFetcher->get('_start');
        $end = $paramFetcher->get('_end');

        $query = $this->getDoctrine()
            ->getRepository('AppBundle:Attendance')
            ->findAllQuery($user_id,$sortField,$sortOrder,$start,$end);

        $paginator = new Paginator($query);
        $totalCount = $paginator->count();

        $restresult = $query->getResult();

        if ($restresult === null) {
            return new View("there are no Attendances exist", Response::HTTP_NOT_FOUND);
        }

        $view = $this->view($restresult, 200)
            ->setHeader('Access-Control-Expose-Headers', 'X-Total-Count')
            ->setHeader('X-Total-Count', $totalCount);

        return $this->handleView($view);
    }

    /**
     * @Rest\Get("/api/attendances/{id}")
     */
    public function getAction($id)
    {
        $singleresult = $this->getDoctrine()->getRepository('AppBundle:Attendance')->find($id);
        if ($singleresult === null) {
            return new View("Attencance not found", Response::HTTP_NOT_FOUND);
        }
        return $singleresult;
    }

    /**
     * @Rest\Post("/api/attendances")
     */
    public function postAction(Request $request)
    {
        $attendance = new Attendance();

        $arrive = $request->get('arrive');
        $leavee = $request->get('leavee');
        $requestPerm = $request->get('requestPerm');
        $approvedPerm = $request->get('approvedPerm');

        $user = $this->getDoctrine()
            ->getRepository('AppBundle:User')
            ->find($request->get('user_id'));

        $schedule = $this->getDoctrine()
            ->getRepository('AppBundle:Schedule')
            ->findBy(
              array('track' => $user->track)
            );

        $attendance->setArrive($arrive);
        $attendance->setLeavee($leavee);
        $attendance->setRequestPerm($requestPerm);
        $attendance->setApprovedPerm($approvedPerm);
        $attendance->setUser($user);
        $attendance->setSchedule($Schedule);

        $em = $this->getDoctrine()->getManager();
        $em->persist($attendance);
        $em->flush();

        return new View($attendance, Response::HTTP_OK);
    }

    /**
     * @Rest\Put("/api/attendances/{id}")
     */
    // public function updateAction($id, Request $request)
    // {
    //
    //     $day_date = $request->get('day_date');
    //     $start_time = $request->get('start_time');
    //     $end_time = $request->get('end_time');
    //
    //     $user = $this->getDoctrine()
    //         ->getRepository('AppBundle:User')
    //         ->find($request->get('user_id'));
    //
    //     $ss = $this->getDoctrine()->getManager();
    //     $attendance = $ss->getRepository('AppBundle:Attendance')->find($id);
    //
    //     $attendance->setDayDate($day_date);
    //     $attendance->setStartTime($start_time);
    //     $attendance->setEndTime($end_time);
    //     $attendance->setUser($user);
    //
    //     if (!empty($day_date) && !empty($start_time) && !empty($end_time)) {
    //         $ss->flush();
    //         return new View("Updated Successfully", Response::HTTP_OK);
    //     } else return new View("NULL VALUES ARE NOT ALLOWED", Response::HTTP_NOT_ACCEPTABLE);
    // }

    /**
     * @Rest\Delete("/api/attendances/{id}")
     */
    public function deleteAction($id)
    {
        $sn = $this->getDoctrine()->getManager();
        $attendance = $sn->getRepository('AppBundle:Attendance')->find($id);
        if (empty($attendance)) {
            return new View("not found", Response::HTTP_NOT_FOUND);
        }
        else {
            $sn->remove($attendance);
            $sn->flush();
        }
        return new View("deleted successfully", Response::HTTP_OK);
    }
}