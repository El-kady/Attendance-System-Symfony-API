<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Branch;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Endroid\QrCode\Factory\QrCodeFactory;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class WebController extends Controller
{
    /**
     * @Route("/web/qrcode/{branch}")
     * @ParamConverter("branch", class="AppBundle:Branch")
     */
    public function qrcodeAction(Branch $branch)
    {
        return $this->render('AppBundle:Web:qrcode.html.twig', array('branch' => $branch));
    }

    /**
     * @Route("/web/qrcode/{branch}/{code}.{extension}", name="endroid_qrcode", requirements={"text"="[\w\W]+", "extension"="jpg|png|gif"})
     */
    public function generateAction(Request $request,$branch, $code, $extension)
    {

        $db = $this->getDoctrine()->getManager();
        $branch = $db->getRepository('AppBundle:Branch')->find((int) $branch);

        if ($branch) {
            $branch->setQrcode($code);
            $db->flush();
        }

        $options = $request->query->all();

        $qrCode = $this->getQrCodeFactory()->createQrCode($options);
        $qrCode->setText($code);

        $mime_type = 'image/'.$extension;
        if ($extension == 'jpg') {
            $mime_type = 'image/jpeg';
        }

        return new Response($qrCode->get($extension), 200, ['Content-Type' => $mime_type]);
    }

    /**
     * Returns the QR code factory.
     *
     * @return QrCodeFactory
     */
    protected function getQrCodeFactory()
    {
        return $this->get('endroid.qrcode.factory');
    }

}
