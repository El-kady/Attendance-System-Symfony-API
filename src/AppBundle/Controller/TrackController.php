<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Track;
use Doctrine\ORM\Tools\Pagination\Paginator;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackController extends FOSRestController
{
    /**
     * @Rest\Get("/api/tracks")
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
            ->getRepository('AppBundle:Track')
            ->findAllQuery($sortField,$sortOrder,$start,$end);

        $paginator = new Paginator($query);
        $totalCount = $paginator->count();

        $restresult = $query->getResult();

        if ($restresult === null) {
            return new View("there are no tracks exist", Response::HTTP_NOT_FOUND);
        }

        $view = $this->view($restresult, 200)
            ->setHeader('Access-Control-Expose-Headers', 'X-Total-Count')
            ->setHeader('X-Total-Count', $totalCount);

        return $this->handleView($view);
    }

    /**
     * @Rest\Get("/api/tracks/{id}")
     */
    public function getAction($id)
    {
        $singleresult = $this->getDoctrine()->getRepository('AppBundle:Track')->find($id);
        if ($singleresult === null) {
            return new View("track not found", Response::HTTP_NOT_FOUND);
        }
        return $singleresult;
    }

    /**
     * @Rest\Post("/api/tracks")
     */
    public function postAction(Request $request)
    {
        $data = new Track;
        $name = $request->get('name');
        $branch = $this->getDoctrine()->getRepository('AppBundle:Branch')->find($request->get('branch_id'));

        if (empty($name) || empty($branch)) {
            return new View("NULL VALUES ARE NOT ALLOWED", Response::HTTP_NOT_ACCEPTABLE);
        }

        $data->setName($name);
        $data->setBranch($branch);
        $em = $this->getDoctrine()->getManager();
        $em->persist($data);
        $em->flush();

        return new View($data, Response::HTTP_OK);
    }

    /**
     * @Rest\Put("/api/tracks/{id}")
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
     * @Rest\Delete("/api/tracks/{id}")
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
