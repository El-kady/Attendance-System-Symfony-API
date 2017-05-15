<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Branch;
use Doctrine\ORM\Tools\Pagination\Paginator;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BranchController extends FOSRestController
{
    /**
     * @Rest\Get("/branches")
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
            ->getRepository('AppBundle:Branch')
            ->findAllQuery($sortField,$sortOrder,$start,$end);

        $paginator = new Paginator($query);
        $totalCount = $paginator->count();

        $restresult = $query->getResult();

        if ($restresult === null) {
            return new View("there are no branches exist", Response::HTTP_NOT_FOUND);
        }

        $view = $this->view($restresult, 200)
            ->setHeader('Access-Control-Expose-Headers', 'X-Total-Count')
            ->setHeader('X-Total-Count', $totalCount);

        return $this->handleView($view);
    }

    /**
     * @Rest\Get("/branches/{id}")
     */
    public function getAction($id)
    {
        $singleresult = $this->getDoctrine()->getRepository('AppBundle:Branch')->find($id);
        if ($singleresult === null) {
            return new View("branch not found", Response::HTTP_NOT_FOUND);
        }
        return $singleresult;
    }

    /**
     * @Rest\Post("/branches")
     */
    public function postAction(Request $request)
    {
        $data = new Branch;
        $name = $request->get('name');
        $address = $request->get('address');

        if (empty($name) || empty($address)) {
            return new View("NULL VALUES ARE NOT ALLOWED", Response::HTTP_NOT_ACCEPTABLE);
        }

        $data->setName($name);
        $data->setAddress($address);

        $em = $this->getDoctrine()->getManager();
        $em->persist($data);
        $em->flush();

        return new View($data, Response::HTTP_OK);
    }

    /**
     * @Rest\Put("/branches/{id}")
     */
    public function updateAction($id, Request $request)
    {
        $data = new Branch;
        $name = $request->get('name');
        $address = $request->get('address');

        $ss = $this->getDoctrine()->getManager();
        $branch = $ss->getRepository('AppBundle:Branch')->find($id);

        if (empty($branch)) {
            return new View("branch not found", Response::HTTP_NOT_FOUND);
        } elseif (!empty($name) && !empty($address)) {
            $branch->setName($name);
            $branch->setAddress($address);
            $ss->flush();
            return new View("Branch Updated Successfully", Response::HTTP_OK);
        } else return new View("Branch name or address cannot be empty", Response::HTTP_NOT_ACCEPTABLE);
    }

    /**
     * @Rest\Delete("/branches/{id}")
     */
    public function deleteAction($id)
    {
        $data = new Branch;
        $sn = $this->getDoctrine()->getManager();
        $branch = $sn->getRepository('AppBundle:Branch')->find($id);
        if (empty($branch)) {
            return new View("branch not found", Response::HTTP_NOT_FOUND);
        }
        else {
            $sn->remove($branch);
            $sn->flush();
        }
        return new View("deleted successfully", Response::HTTP_OK);
    }
}
