<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Role;
use Doctrine\ORM\Tools\Pagination\Paginator;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleController extends FOSRestController
{
    /**
     * @Rest\Get("/api/roles")
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
            ->getRepository('AppBundle:Role')
            ->findAllQuery($sortField,$sortOrder,$start,$end);

        $paginator = new Paginator($query);
        $totalCount = $paginator->count();

        $restresult = $query->getResult();

        if ($restresult === null) {
            return new View("there are no roles exist", Response::HTTP_NOT_FOUND);
        }

        $view = $this->view($restresult, 200)
            ->setHeader('Access-Control-Expose-Headers', 'X-Total-Count')
            ->setHeader('X-Total-Count', $totalCount);

        return $this->handleView($view);
    }

    /**
     * @Rest\Get("/api/roles/{id}")
     */
    public function getAction($id)
    {
        $singleresult = $this->getDoctrine()->getRepository('AppBundle:Role')->find($id);
        if ($singleresult === null) {
            return new View("role not found", Response::HTTP_NOT_FOUND);
        }
        return $singleresult;
    }

    /**
     * @Rest\Post("/api/roles")
     */
    public function postAction(Request $request)
    {
        $data = new Role;
        $name = $request->get('name');
        $deduction = $request->get('deduction');
        $quantity = $request->get('quantity');
        $period = $request->get('period');

        if (empty($name) || empty($deduction) || empty($quantity) || empty($period)) {
            return new View("NULL VALUES ARE NOT ALLOWED", Response::HTTP_NOT_ACCEPTABLE);
        }

        $data->setName($name);
        $data->setDeduction($deduction);
        $data->setQuantity($quantity);
        $data->setPeriod($period);

        $em = $this->getDoctrine()->getManager();
        $em->persist($data);
        $em->flush();

        return new View($data, Response::HTTP_OK);
    }

    /**
     * @Rest\Put("/api/roles/{id}")
     */
    public function updateAction($id, Request $request)
    {
        $data = new Role;
        $name = $request->get('name');
        $deduction = $request->get('deduction');
        $quantity = $request->get('quantity');
        $period = $request->get('address');

        $ss = $this->getDoctrine()->getManager();
        $role = $ss->getRepository('AppBundle:Role')->find($id);

        if (empty($role)) {
            return new View("role not found", Response::HTTP_NOT_FOUND);
        } elseif (!empty($name) && !empty($deduction) && !empty($quantity) && !empty($period)) {
            $role->setName($name);
            $role->setDeduction($deduction);
            $role->setQuantity($quantity);
            $role->setPeriod($period);
            $ss->flush();
            return new View("Role Updated Successfully", Response::HTTP_OK);
        } else return new View("Role name or address cannot be empty", Response::HTTP_NOT_ACCEPTABLE);
    }

    /**
     * @Rest\Delete("/api/roles/{id}")
     */
    public function deleteAction($id)
    {
        $data = new Role;
        $sn = $this->getDoctrine()->getManager();
        $role = $sn->getRepository('AppBundle:Role')->find($id);
        if (empty($role)) {
            return new View("role not found", Response::HTTP_NOT_FOUND);
        }
        else {
            $sn->remove($role);
            $sn->flush();
        }
        return new View("deleted successfully", Response::HTTP_OK);
    }
}
