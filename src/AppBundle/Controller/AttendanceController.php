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
    // public function indexAction(Request $request,ParamFetcherInterface $paramFetcher)
    // {
    //     $user_id = $paramFetcher->get('user_id');
    //
    //     $sortField = $paramFetcher->get('_sort');
    //     $sortOrder = $paramFetcher->get('_order');
    //     $start = $paramFetcher->get('_start');
    //     $end = $paramFetcher->get('_end');
    //
    //     $query = $this->getDoctrine()
    //         ->getRepository('AppBundle:Attendance')
    //         ->findAllQuery($user_id,$sortField,$sortOrder,$start,$end);
    //
    //     $paginator = new Paginator($query);
    //     $totalCount = $paginator->count();
    //
    //     $restresult = $query->getResult();
    //
    //     if ($restresult === null) {
    //         return new View("there are no Attendances exist", Response::HTTP_NOT_FOUND);
    //     }
    //
    //     $view = $this->view($restresult, 200)
    //         ->setHeader('Access-Control-Expose-Headers', 'X-Total-Count')
    //         ->setHeader('X-Total-Count', $totalCount);
    //
    //     return $this->handleView($view);
    // }

    /**
     * Get specific user attendance (late deduction, absence deduction)
     * @Rest\Get("/api/attendances/{user_id}")
     */
    public function getAction($user_id)
    {
        //create object
        $att = new \stdClass;
        $att->late_deduction = 0;
        $att->late_days = 0;
        $att->absence_days = 0;
        $att->absence_deduction = 0;
        //days role deduction
        $s = $this->getDoctrine()->getManager();
        $role = $s->getRepository('AppBundle:Role')
                          ->findOneByName('days');
        //check user existance
        $user = $this->getDoctrine()
            ->getRepository('AppBundle:User')
            ->find($user_id);
        if(empty($user)){
            return new View("Error in user id", Response::HTTP_NOT_ACCEPTABLE);
        }
        //find Track Old Schedules
        $q = $this->getDoctrine()->getRepository('AppBundle:Schedule')->findTrackOldSchedules($user->getTrackId());
        $schedules = $q->getResult();
        foreach($schedules as $schedule){
            $attendance = $this->getDoctrine()->getRepository('AppBundle:Attendance')->findOneBy(
                array('schedule' => $schedule, 'user' => $user)
            );
            if(empty($attendance) || ($attendance->getApprovedPerm() === 0 && $attendance->getArrive() === null)){

                $att->absence_days += 1;
                $att->absence_deduction += $role->getDeduction();
            }elseif($attendance->getArrive() !== null && $attendance->getDeduction() !== null){
                $att->late_days += 1;
                $att->late_deduction += $attendance->getDeduction();
            }
        }
        // $result = json_encode($att);
        // return new View($result, Response::HTTP_OK);
        return $att;
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

        if(empty($qrcode) || $qrcode != $user->getTrack()->getBranch()->getQrcode()){
        // if($qrcode != $user->getTrack()->getBranch()->getQrcode()){
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
            //check if the user has attendance record today
            if(!empty($attendance->getLeavee())){
                //user arrived and left before
                return new View("You have already left before, Go out of here!", Response::HTTP_NOT_ACCEPTABLE);
            }elseif (empty($attendance->getArrive())) {
                //user has requested permission before but he came
                //delete the permission and set arrive time
                $attendance->setArrive($time);
                $attendance->setApprovedPerm(null);
                $attendance->setRequestPerm(null);
            }else{
                //user has arrive time record today
                //set leavee time
                $attendance->setLeavee($time);
            }
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($attendance);
        $em->flush();

        return new View($attendance, Response::HTTP_OK);
    }

    /**
     * @Rest\Put("/api/attendances")
     */
    public function updateAction(Request $request)
    {
        $s = $this->getDoctrine()->getManager();
        $role = $s->getRepository('AppBundle:Role')
                          ->findOneByName('hours');
        //////////
        $ss = $this->getDoctrine()->getManager();
        $query = $ss->getRepository('AppBundle:Attendance')->findNullDeductions();
        $restResult = $query->getResult();
        foreach($restResult as $single){
            // if($single->getRequestPerm() !== null){
            if($single->getApprovedPerm() == 1){
              $single->setDeduction(0);
            }if($single->getArrive() !== null){
              //arrive_late_hours
              $start_hours = strtotime($single->getSchedule()->getStartTime());
              $arrive_hours = strtotime($single->getArrive());
              $diff_1 = floor(($arrive_hours - $start_hours)/3600);
              $arrive_late_hours = ($diff_1 > 0) ? $diff_1 : 0;
              //leave_early_hours
              if($single->getLeavee() !== null){
                  $end_hours = strtotime($single->getSchedule()->getEndTime());
                  $leave_hours = strtotime($single->getLeavee());
                  $diff_2 = floor(($end_hours - $leave_hours)/3600);
              }else{
                  $diff_2 = 0;
              }
              $leave_early_hours = ($diff_2 > 0) ? $diff_2 : 0;
              //punished_hours
              $punished_hours = $arrive_late_hours + $leave_early_hours;
              //apply hours role
              $single->setDeduction($punished_hours * $role->getDeduction());
            }
        }
        $ss->flush();
        return new View("Deductions Updated Successfully", Response::HTTP_OK);
    }

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
