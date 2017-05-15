<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Doctrine\ORM\Tools\Pagination\Paginator;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends FOSRestController
{
    /**
     * @Rest\Get("/api/users")
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

        $query = $this->getDoctrine()
            ->getRepository('AppBundle:User')
            ->findAllQuery($sortField,$sortOrder,$start,$end);

        $paginator = new Paginator($query);
        $totalCount = $paginator->count();

        $restresult = $query->getResult();

        if ($restresult === null) {
            return new View("there are no users exist", Response::HTTP_NOT_FOUND);
        }

        $view = $this->view($restresult, 200)
            ->setHeader('Access-Control-Expose-Headers', 'X-Total-Count')
            ->setHeader('X-Total-Count', $totalCount);

        return $this->handleView($view);
    }

    /**
     * @Rest\Get("/api/users/{id}")
     */
    public function getAction($id)
    {
        $singleresult = $this->getDoctrine()->getRepository('AppBundle:User')->find($id);
        if ($singleresult === null) {
            return new View("user not found", Response::HTTP_NOT_FOUND);
        }
        return $singleresult;
    }

    /**
     * @Rest\Post("/api/users")
     */
    public function postAction(Request $request)
    {
        $user = new User;
        $name = $request->get('name');
        $username = $request->get('username');
        $email = $request->get('email');
        $password = $request->get('password');
        $is_admin = $request->get('is_admin',false);
        $roles = $request->get('roles','roles');
        $track = $this->getDoctrine()->getRepository('AppBundle:Track')->find($request->get('track_id'));

        if (empty($name) || empty($username) || empty($email) || empty($password) || empty($track)) {
            return new View("NULL VALUES ARE NOT ALLOWED", Response::HTTP_NOT_ACCEPTABLE);
        }

        $password = $this->get('security.password_encoder')
            ->encodePassword($user, $password);

        $user->setName($name);
        $user->setUserName($username);
        $user->setEmail($email);
        $user->setPassword($password);
        $user->setIsAdmin($is_admin);
        $user->setRoles($roles);
        $user->setTrack($track);

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        return new View($user, Response::HTTP_OK);
    }

    /**
     * @Rest\Put("/api/users/{id}")
     */
    public function updateAction($id, Request $request)
    {
        $user = new User;
        $name = $request->get('name');
        $email = $request->get('email');
        $password = $request->get('password');
        $is_admin = $request->get('is_admin',false);
        $track = $this->getDoctrine()->getRepository('AppBundle:Track')->find($request->get('track_id'));

        $sn = $this->getDoctrine()->getManager();
        $user = $sn->getRepository('AppBundle:User')->find($id);

        if (empty($user)) {
            return new View("user not found", Response::HTTP_NOT_FOUND);
        } elseif (!empty($name) && !empty($email) && !empty($password) && !empty($track)) {
            $user->setName($name);
            $user->setEmail($email);
            $user->setPassword($password);
            $user->setIsAdmin($is_admin);
            $user->setTrack($track);
            $sn->flush();
            return new View("User Updated Successfully", Response::HTTP_OK);
        } else return new View("NULL VALUES ARE NOT ALLOWED", Response::HTTP_NOT_ACCEPTABLE);
    }

    /**
     * @Rest\Delete("/api/users/{id}")
     */
    public function deleteAction($id)
    {
        $data = new User;
        $sn = $this->getDoctrine()->getManager();
        $user = $sn->getRepository('AppBundle:User')->find($id);
        if (empty($user)) {
            return new View("user not found", Response::HTTP_NOT_FOUND);
        }
        else {
            $sn->remove($user);
            $sn->flush();
        }
        return new View("deleted successfully", Response::HTTP_OK);
    }
}
