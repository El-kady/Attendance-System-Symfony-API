<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Attendance;
use AppBundle\Entity\User;
use AppBundle\Entity\Schedule;
use Doctrine\ORM\Tools\Pagination\Paginator;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionController extends FOSRestController
{
    /**
     * Admin USE
     * @Rest\Get("/api/permissions")
     * @Annotations\QueryParam(name="_sort", nullable=true, description="Sort field.")
     * @Annotations\QueryParam(name="_order", nullable=true, description="Sort Order.")
     * @Annotations\QueryParam(name="_start", nullable=true, description="Start.")
     * @Annotations\QueryParam(name="_end", nullable=true, description="End.")
     */
    public function indexAction(Request $request,ParamFetcherInterface $paramFetcher)
    {
        $sortField = $paramFetcher->get('_sort');
        $sortOrder = $paramFetcher->get('_order');
        $start = $paramFetcher->get('_start');
        $end = $paramFetcher->get('_end');

        if ($sortField == 'user_id') {
            $sortField = 'user';
        }

        $query = $this->getDoctrine()
            ->getRepository('AppBundle:Attendance')
            ->findRequestedPerm($sortField,$sortOrder,$start,$end);

        $paginator = new Paginator($query);
        $totalCount = $paginator->count();

        $restresult = $query->getResult();

        if (empty($restresult)) {
            return new View("there are no Requested Permissions right now", Response::HTTP_NOT_FOUND);
        }

        $view = $this->view($restresult, 200)
            ->setHeader('Access-Control-Expose-Headers', 'X-Total-Count')
            ->setHeader('X-Total-Count', $totalCount);

        return $this->handleView($view);
    }

    /**
     * Student USE
     * @Rest\Get("/api/permissions/{user_id}")
     */
    public function getAction($user_id)
    {
        $query = $this->getDoctrine()->getRepository('AppBundle:Attendance')->findUserPerm($user_id);

        $paginator = new Paginator($query);
        $totalCount = $paginator->count();

        $restresult = $query->getResult();

        if (empty($restresult)) {
          return new View("You have no permissions", Response::HTTP_NOT_FOUND);
        }
        $view = $this->view($restresult, 200)
            ->setHeader('Access-Control-Expose-Headers', 'X-Total-Count')
            ->setHeader('X-Total-Count', $totalCount);

        return $this->handleView($view);
    }

    /**
     * Student USE
     * @Rest\Post("/api/permissions")
     */
    public function postAction(Request $request)
    {
        $user_id = $request->get('user_id');
        $schedule_id = $request->get('schedule_id');
        //check user existance
        $user = $this->getDoctrine()
            ->getRepository('AppBundle:User')
            ->find($user_id);
        if(empty($user)){
            return new View("Error in user id", Response::HTTP_NOT_ACCEPTABLE);
        }

        //check schedule existance
        $schedule = $this->getDoctrine()
            ->getRepository('AppBundle:Schedule')
            ->find($schedule_id);
        if(empty($schedule)){
            return new View("Error in schedule id", Response::HTTP_NOT_ACCEPTABLE);
        }
        //check owner schedule
        if($schedule->getTrack() != $user->getTrack()){
            return new View("That is not your track's schedule", Response::HTTP_NOT_ACCEPTABLE);
        }
        //check asking permission before 1 day at least
        $d1 = $schedule->getDayDate();
        $d2 = new \DateTime('now', new \DateTimeZone('Africa/Cairo'));
        $interval = $d2->diff($d1);
        $t = (int)$interval->format("%r%a");
        if($t <= 0){
            return new View("You are not allowed to request permission in that day ".schedule_id." ".$t, Response::HTTP_NOT_ACCEPTABLE);
        }
        //check if requested permission before
        $e = $this->getDoctrine()->getManager();
        $attendance = $e->getRepository('AppBundle:Attendance')
                          ->findOneBy(
                              array('user' => $user, 'schedule' => $schedule)
                          );
        if(!empty($attendance)){
            return new View("You have already asked permission for that schedule before", Response::HTTP_NOT_ACCEPTABLE);
        }
        $attendance = new Attendance();
        $attendance->setUser($user);
        $attendance->setSchedule($schedule);
        $attendance->setRequestPerm(1);

        $em = $this->getDoctrine()->getManager();
        $em->persist($attendance);
        $em->flush();

        return new View($attendance, Response::HTTP_OK);
    }

    /**
     * Admin USE
     * @Rest\Put("/api/permissions/{id}")
     */
    public function updateAction($id, Request $request)
    {
        $data = new Attendance();
        $action = $request->get('action');

        $ss = $this->getDoctrine()->getManager();
        $attendance = $ss->getRepository('AppBundle:Attendance')->find($id);

        if (empty($attendance)) {
            return new View("attendance row not found", Response::HTTP_NOT_FOUND);
        } elseif ($action == 0 || $action == 1) {
            $attendance->setApprovedPerm($action);
            $ss->flush();
            return new View("Permission Updated Successfully", Response::HTTP_OK);
        } else return new View("Permission action value error", Response::HTTP_NOT_ACCEPTABLE);
    }

    /**
     * @Rest\Delete("/api/permissions/{id}")
     */
    // public function deleteAction($id)
    // {
    //     $sn = $this->getDoctrine()->getManager();
    //     $attendance = $sn->getRepository('AppBundle:Attendance')->find($id);
    //     if (empty($attendance)) {
    //         return new View("not found", Response::HTTP_NOT_FOUND);
    //     }
    //     else {
    //         $sn->remove($attendance);
    //         $sn->flush();
    //     }
    //     return new View("deleted successfully", Response::HTTP_OK);
    // }
}
