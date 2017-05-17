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
        //validate qr code

        $user_id = $request->get('user_id');
        $qrcode = $request->get('qrcode');

        //get current time
        $d = new \DateTime('now', new \DateTimeZone('Africa/Cairo'));
        $time = $d->format('H:i');

        //check user existance
        $user = $this->getDoctrine()
            ->getRepository('AppBundle:User')
            ->find($user_id);
        if(empty($user)){
            return new View("Error in user id", Response::HTTP_NOT_ACCEPTABLE);
        }
        
        if($qrcode != $user->getTrack()->getBranch()->getQrcode()){
            return new View("Wrong QR Code", Response::HTTP_NOT_ACCEPTABLE);
        }

        //check if there is a schedule for this user's track today
        $schedule = $this->getDoctrine()
            ->getRepository('AppBundle:Schedule')
            ->findOneBy(
              array('track' => $user->getTrack(), 'dayDate' => new \DateTime('now', new \DateTimeZone('Africa/Cairo')))
            );

        if(empty($schedule)){
            return new View("There is no schedule for your track today", Response::HTTP_NOT_ACCEPTABLE);
        }

        //check if the user has an attendance record today
        $em = $this->getDoctrine()->getManager();
        $attendance = $em->getRepository('AppBundle:Attendance')
                          ->findOneBy(
                              array('user' => $user, 'schedule' => $schedule)
                          );
        if(empty($attendance)){
            //new arrive attendance record
            $attendance = new Attendance();
            $attendance->setArrive($time);
            $attendance->setUser($user);
            $attendance->setSchedule($schedule);
        }else{
            //check if the user has record of arriving and leaving
            if(!empty($attendance->getLeavee())){
                return new View("You have already left before, Go out of here!", Response::HTTP_NOT_ACCEPTABLE);
            }
            //edit attendance record by add leavee time
            $attendance->setLeavee($time);
        }

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
